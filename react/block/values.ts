/**
 * What the block works out from its attributes and the settings schema. Kept
 * free of the editor so it can be tested.
 */
import { fieldApplies, previewUrl, WidgetValues, withinLimit } from "../admin/editor";

export type FieldOption = { value: string; label: string; locked: boolean };

/** One widget setting, as WidgetSchema::toArray() describes it. */
export type SchemaField = {
	name: string;
	label: string;
	help: string;
	control: "type" | "select" | "number" | "text" | "list";
	default: string;
	usedBy: string[];
	options: FieldOption[];
	min: number | null;
	max: number | null;
	step: number | null;
	limit: number;
};

export type BlockAttributes = {
	/** A saved widget to show; 0 when the block carries its own settings. */
	widgetId: number;
	settings: WidgetValues;
};

/** The settings a widget type has, in the schema's order, without the type itself. */
export function fieldsFor(fields: SchemaField[], type: string): SchemaField[] {
	return fields.filter((field) => field.name !== "type" && fieldApplies(field.usedBy.join(" "), type));
}

/**
 * The settings a block starts with when a type is chosen: that type's settings
 * at their defaults, keeping whatever was already set for a setting both types share.
 */
export function settingsForType(fields: SchemaField[], type: string, current: WidgetValues = {}): WidgetValues {
	const next: WidgetValues = { type };
	for (const field of fieldsFor(fields, type)) {
		next[field.name] = current[field.name] ?? field.default;
	}
	return next;
}

/** A list setting's chosen values, within its limit, as it is stored: "a,b,c". */
export function listValue(chosen: string[], limit: number): string {
	return withinLimit(
		chosen.filter((value, index) => value !== "" && chosen.indexOf(value) === index),
		limit
	).join(",");
}

/** Where the block's preview comes from, or null when there is nothing to show yet. */
export function blockPreviewUrl(base: string, attributes: BlockAttributes): string | null {
	if (attributes.widgetId > 0) {
		return `${base}${base.includes("?") ? "&" : "?"}id=${attributes.widgetId}`;
	}
	return attributes.settings?.type ? previewUrl(base, attributes.settings) : null;
}
