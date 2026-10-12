/**
 * What the widget editor's script works out from the form. Kept free of the
 * page itself so it can be tested.
 */

/** A widget's settings by name: { type: "coin-marquee-1", count: "10", coins: "bitcoin,ethereum" }. */
export type WidgetValues = Record<string, string>;

/** The form names every setting "ucwp_widget_{name}". */
export const FIELD_PREFIX = "ucwp_widget_";

/**
 * Whether a setting belongs to a widget type. `usedBy` is the field's
 * data-ucw-for attribute: the types that use it, separated by spaces.
 */
export function fieldApplies(usedBy: string | null | undefined, type: string): boolean {
	if (usedBy === null || usedBy === undefined) {
		return true; // The type field itself.
	}
	return type !== "" && usedBy.split(/\s+/).includes(type);
}

/** The settings worth sending: the type first, then every other one that has a value. */
export function meaningful(values: WidgetValues): Array<[string, string]> {
	const entries = Object.entries(values).filter(([name, value]) => name !== "type" && value !== "");
	return values.type ? [["type", values.type], ...entries] : [];
}

/**
 * The shortcode that draws a widget with these settings. Characters that would
 * end an attribute or the shortcode are removed from the values.
 */
export function shortcodeFor(values: WidgetValues): string {
	const attributes = meaningful(values).map(([name, value]) => `${name}="${value.replace(/["\[\]<>]/g, "").trim()}"`);
	return attributes.length === 0 ? "" : `[ucwp_widget ${attributes.join(" ")}]`;
}

/** The preview's address for these settings. `base` already has its own query string. */
export function previewUrl(base: string, values: WidgetValues): string {
	const query = meaningful(values)
		.map(([name, value]) => `${encodeURIComponent(`s[${name}]`)}=${encodeURIComponent(value)}`)
		.join("&");
	return query === "" ? base : `${base}${base.includes("?") ? "&" : "?"}${query}`;
}

/** The first `max` items, or all of them when there is no limit (0). */
export function withinLimit<T>(items: T[], max: number): T[] {
	return max > 0 ? items.slice(0, max) : items;
}
