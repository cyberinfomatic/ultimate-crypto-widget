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

/** A list setting's stored value ("bitcoin,ethereum") as the names its picker shows. */
export function tokensFor(field: SchemaField, value: string): string[] {
	return value
		.split(",")
		.map((chosen) => field.options.find((option) => option.value === chosen)?.label)
		.filter((label): label is string => label !== undefined);
}

/**
 * The names picked in a list setting's picker, back to the value it stores. A name
 * that is not one of the setting's choices is dropped.
 */
export function listFromTokens(field: SchemaField, tokens: Array<string | { value: string }>): string {
	const values = tokens.map((token) => {
		const name = typeof token === "string" ? token : token.value;
		return field.options.find((option) => option.label === name && !option.locked)?.value ?? "";
	});
	return listValue(values, field.limit);
}

/** One entry in the block inserter: the block with a widget type already chosen. */
export type BlockVariation = {
	name: string;
	title: string;
	icon: string;
	keywords: string[];
	attributes: BlockAttributes;
	scope: Array<"inserter">;
};

/** A dashicon for a widget type, by a word in its name. The first match wins. */
const ICONS: Array<[string, string]> = [
	["heatmap", "grid-view"],
	["converter", "randomize"],
	["calculator", "calculator"],
	["compare", "columns"],
	["movers", "sort"],
	["trending", "star-filled"],
	["overview", "chart-pie"],
	["chart", "chart-line"],
	["table", "editor-table"],
	["marquee", "leftright"],
	["slider", "slides"],
	["tab", "index-card"],
	["accordion", "list-view"],
	["info", "id"],
	["picker", "tag"],
	["block", "money-alt"],
];

/**
 * One inserter entry per widget type that can be used, like the Elementor panel.
 * Each starts at its type's defaults. A locked type gets none.
 */
export function variationsFor(fields: SchemaField[]): BlockVariation[] {
	const types = fields.find((field) => field.name === "type")?.options ?? [];
	return types
		.filter((option) => !option.locked)
		.map((option) => ({
			name: option.value,
			title: option.label,
			icon: ICONS.find(([word]) => option.value.includes(word))?.[1] ?? "chart-line",
			keywords: ["crypto", "coin", "price"],
			attributes: { widgetId: 0, settings: settingsForType(fields, option.value) },
			scope: ["inserter"],
		}));
}

/** Whether a block is a variation: it carries its own settings, of that variation's type. */
export function isVariationActive(block: Partial<BlockAttributes>, variation: Partial<BlockAttributes>): boolean {
	return !block.widgetId && block.settings?.type !== undefined && block.settings.type === variation.settings?.type;
}

/** Where the block's preview comes from, or null when there is nothing to show yet. */
export function blockPreviewUrl(base: string, attributes: BlockAttributes): string | null {
	if (attributes.widgetId > 0) {
		return `${base}${base.includes("?") ? "&" : "?"}id=${attributes.widgetId}`;
	}
	return attributes.settings?.type ? previewUrl(base, attributes.settings) : null;
}
