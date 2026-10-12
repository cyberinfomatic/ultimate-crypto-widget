/**
 * The "Crypto Widget" block in the editor. The server registers the block and
 * draws it on the site; this adds the controls and the preview.
 */
import React, { useEffect, useRef, useState } from "react";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { registerBlockType } from "@wordpress/blocks";
import { PanelBody, Placeholder, SelectControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { WidgetValues } from "../admin/editor";
import { BlockAttributes, blockPreviewUrl, fieldsFor, listValue, SchemaField, settingsForType } from "./values";
import "./block.scss";

type BlockData = {
	name: string;
	fields: SchemaField[];
	saved: Array<{ id: number; title: string }>;
	previewUrl: string;
	newUrl: string;
};

type EditProps = {
	attributes: BlockAttributes;
	setAttributes: (next: Partial<BlockAttributes>) => void;
};

declare global {
	interface Window {
		wptoolkit?: Record<string, { block?: BlockData } | undefined>;
	}
}

const data = window.wptoolkit?.["ultimate-crypto-widget"]?.block;

/** How long the preview waits after the last change before reloading. */
const PREVIEW_DELAY_MS = 600;
const DEFAULT_HEIGHT = 240;

/** A value that follows another one only once it has stopped changing. */
function useSettled<T>(value: T, delay: number): T {
	const [settled, setSettled] = useState(value);
	useEffect(() => {
		const timer = setTimeout(() => setSettled(value), delay);
		return () => clearTimeout(timer);
	}, [value, delay]);
	return settled;
}

/**
 * The widget in a frame, as tall as the widget is. A transparent cover sits over
 * it so a click selects the block instead of landing inside the frame.
 */
const PreviewFrame = ({ src }: { src: string }) => {
	const frame = useRef<HTMLIFrameElement>(null);
	const [height, setHeight] = useState(DEFAULT_HEIGHT);

	useEffect(() => {
		// The editor's canvas can be a frame of its own; listen where this component lives.
		const view = frame.current?.ownerDocument.defaultView;
		const onMessage = (event: MessageEvent) => {
			const reported = Number(event.data?.ucwPreviewHeight);
			if (event.source === frame.current?.contentWindow && reported > 0) {
				setHeight(Math.min(reported, 1600));
			}
		};
		view?.addEventListener("message", onMessage);
		return () => view?.removeEventListener("message", onMessage);
	}, []);

	return (
		// Styled inline: the editor's canvas is a frame that the block's stylesheet does not reach.
		<div style={{ position: "relative", blockSize: height }}>
			<iframe ref={frame} src={src} title={__("Widget preview", "ultimate-crypto-widget")} tabIndex={-1} style={{ display: "block", inlineSize: "100%", blockSize: "100%", border: 0 }} />
			<div style={{ position: "absolute", inset: 0 }} />
		</div>
	);
};

const Control = ({ field, value, onChange }: { field: SchemaField; value: string; onChange: (value: string) => void }) => {
	const options = field.options.map((option) => ({ value: option.value, label: option.label, disabled: option.locked }));

	switch (field.control) {
		case "select":
			return <SelectControl __nextHasNoMarginBottom label={field.label} help={field.help} value={value} options={options} onChange={onChange} />;
		case "list":
			return (
				<SelectControl
					__nextHasNoMarginBottom
					multiple
					label={field.label}
					help={field.help}
					value={value === "" ? [] : value.split(",")}
					options={options}
					onChange={(chosen: string[]) => onChange(listValue(chosen, field.limit))}
				/>
			);
		case "number":
			return <TextControl __nextHasNoMarginBottom type="number" label={field.label} help={field.help} value={value} min={field.min ?? undefined} max={field.max ?? undefined} step={field.step ?? undefined} onChange={onChange} />;
		default:
			return <TextControl __nextHasNoMarginBottom label={field.label} help={field.help} value={value} onChange={onChange} />;
	}
};

const Edit = ({ attributes, setAttributes }: EditProps) => {
	const blockProps = useBlockProps();
	const settings: WidgetValues = attributes.settings ?? {};
	const type = settings.type ?? "";
	const src = useSettled(data ? blockPreviewUrl(data.previewUrl, attributes) : null, PREVIEW_DELAY_MS);

	if (!data) {
		return <div {...blockProps}>{__("The Crypto Widget block could not load its settings.", "ultimate-crypto-widget")}</div>;
	}

	const typeField = data.fields.find((field) => field.name === "type");
	const typeOptions = [{ value: "", label: __("Choose a widget type…", "ultimate-crypto-widget"), disabled: false }, ...(typeField?.options ?? []).map((option) => ({ value: option.value, label: option.locked ? `${option.label} (Pro)` : option.label, disabled: option.locked }))];
	const savedOptions = [{ value: "0", label: __("Set up in this block", "ultimate-crypto-widget") }, ...data.saved.map((widget) => ({ value: String(widget.id), label: widget.title }))];

	const chooseSaved = (id: string) => setAttributes({ widgetId: Number(id) || 0 });
	const chooseType = (next: string) => setAttributes({ widgetId: 0, settings: next === "" ? {} : settingsForType(data.fields, next, settings) });
	const set = (name: string, value: string) => setAttributes({ settings: { ...settings, [name]: value } });

	const source = <SelectControl __nextHasNoMarginBottom label={__("Widget", "ultimate-crypto-widget")} help={__("Use a widget you have saved, or set one up here.", "ultimate-crypto-widget")} value={String(attributes.widgetId || 0)} options={savedOptions} onChange={chooseSaved} />;
	const typeControl = <SelectControl __nextHasNoMarginBottom label={typeField?.label ?? __("Widget type", "ultimate-crypto-widget")} value={type} options={typeOptions} onChange={chooseType} />;

	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Widget", "ultimate-crypto-widget")}>
					<div className="ucw-block-controls">
						{data.saved.length > 0 && source}
						{attributes.widgetId === 0 && typeControl}
						{attributes.widgetId === 0 && fieldsFor(data.fields, type).map((field) => <Control key={field.name} field={field} value={settings[field.name] ?? field.default} onChange={(value) => set(field.name, value)} />)}
					</div>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{src ? (
					<PreviewFrame src={src} />
				) : (
					<Placeholder icon="chart-line" label={__("Crypto Widget", "ultimate-crypto-widget")} instructions={__("Choose what to show. You can change its settings in the block sidebar.", "ultimate-crypto-widget")}>
						<div style={{ display: "flex", flexDirection: "column", gap: 16, inlineSize: "100%" }}>
							{data.saved.length > 0 && source}
							{attributes.widgetId === 0 && typeControl}
						</div>
					</Placeholder>
				)}
			</div>
		</>
	);
};

if (data) {
	// Title, attributes and the rest come from the server's registration.
	registerBlockType(data.name, { edit: Edit, save: () => null });
}
