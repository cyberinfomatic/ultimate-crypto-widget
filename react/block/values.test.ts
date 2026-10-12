import { describe, expect, it } from "vitest";
import { blockPreviewUrl, fieldsFor, listValue, SchemaField, settingsForType } from "./values";

const field = (name: string, usedBy: string[], fallback = ""): SchemaField => ({
	name,
	label: name,
	help: "",
	control: "text",
	default: fallback,
	usedBy,
	options: [],
	min: null,
	max: null,
	step: null,
	limit: 0,
});

const fields = [field("type", []), field("count", ["table", "marquee"], "10"), field("speed", ["marquee"], "3000"), field("parent_width", ["table", "marquee"], "100%")];

describe("fieldsFor", () => {
	it("gives a type its own settings, in order, without the type itself", () => {
		expect(fieldsFor(fields, "table").map((f) => f.name)).toEqual(["count", "parent_width"]);
		expect(fieldsFor(fields, "marquee").map((f) => f.name)).toEqual(["count", "speed", "parent_width"]);
	});

	it("gives nothing until a type is chosen", () => {
		expect(fieldsFor(fields, "")).toEqual([]);
	});
});

describe("settingsForType", () => {
	it("starts a type at its defaults", () => {
		expect(settingsForType(fields, "marquee")).toEqual({ type: "marquee", count: "10", speed: "3000", parent_width: "100%" });
	});

	it("keeps what was set for a setting both types have, and drops the rest", () => {
		expect(settingsForType(fields, "table", { type: "marquee", count: "5", speed: "100" })).toEqual({ type: "table", count: "5", parent_width: "100%" });
	});
});

describe("listValue", () => {
	it("joins the choices as they are stored", () => {
		expect(listValue(["bitcoin", "ethereum"], 0)).toBe("bitcoin,ethereum");
	});

	it("drops repeats and blanks and keeps to the limit", () => {
		expect(listValue(["usd", "", "eur", "usd", "gbp", "ngn"], 3)).toBe("usd,eur,gbp");
	});
});

describe("blockPreviewUrl", () => {
	const base = "https://example.org/wp-admin/admin-post.php?action=ucwp_preview&_wpnonce=abc";

	it("asks for a saved widget by id", () => {
		expect(blockPreviewUrl(base, { widgetId: 12, settings: { type: "ignored" } })).toBe(`${base}&id=12`);
	});

	it("sends the block's own settings otherwise", () => {
		expect(blockPreviewUrl(base, { widgetId: 0, settings: { type: "table", count: "5" } })).toBe(`${base}&s%5Btype%5D=table&s%5Bcount%5D=5`);
	});

	it("has nothing to show before a widget or a type is chosen", () => {
		expect(blockPreviewUrl(base, { widgetId: 0, settings: {} })).toBeNull();
	});
});
