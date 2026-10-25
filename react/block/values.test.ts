import { describe, expect, it } from "vitest";
import { blockPreviewUrl, fieldsFor, isVariationActive, listFromTokens, listValue, SchemaField, settingsForType, tokensFor, variationsFor } from "./values";

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

const coins: SchemaField = {
	...field("coins", ["table"]),
	control: "list",
	limit: 2,
	options: [
		{ value: "bitcoin", label: "Bitcoin (BTC)", locked: false },
		{ value: "ethereum", label: "Ethereum (ETH)", locked: false },
		{ value: "solana", label: "Solana (SOL)", locked: false },
	],
};

describe("tokensFor", () => {
	it("shows the stored coins by name, in order", () => {
		expect(tokensFor(coins, "ethereum,bitcoin")).toEqual(["Ethereum (ETH)", "Bitcoin (BTC)"]);
	});

	it("shows nothing for nothing chosen, and skips a coin no longer offered", () => {
		expect(tokensFor(coins, "")).toEqual([]);
		expect(tokensFor(coins, "bitcoin,gone")).toEqual(["Bitcoin (BTC)"]);
	});
});

describe("listFromTokens", () => {
	it("stores the picked names as their values", () => {
		expect(listFromTokens(coins, ["Bitcoin (BTC)", { value: "Solana (SOL)" }])).toBe("bitcoin,solana");
	});

	it("drops a typed name that is not a choice, and keeps to the limit", () => {
		expect(listFromTokens(coins, ["Dogecoin", "Bitcoin (BTC)", "Ethereum (ETH)", "Solana (SOL)"])).toBe("bitcoin,ethereum");
	});
});

describe("variationsFor", () => {
	const typeField: SchemaField = {
		...field("type", []),
		control: "type",
		options: [
			{ value: "table", label: "Price Table", locked: false },
			{ value: "crypto-heatmap", label: "Crypto Heatmap", locked: false },
			{ value: "marquee", label: "Locked Marquee", locked: true },
		],
	};
	const variations = variationsFor([typeField, ...fields.slice(1)]);

	it("gives every usable type its own inserter entry, starting at its defaults", () => {
		expect(variations.map((v) => [v.name, v.title])).toEqual([
			["table", "Price Table"],
			["crypto-heatmap", "Crypto Heatmap"],
		]);
		expect(variations[0].attributes).toEqual({ widgetId: 0, settings: { type: "table", count: "10", parent_width: "100%" } });
		expect(variations[0].scope).toEqual(["inserter"]);
	});

	it("picks an icon by the type's name", () => {
		expect(variations.map((v) => v.icon)).toEqual(["editor-table", "grid-view"]);
	});
});

describe("isVariationActive", () => {
	const heatmap = { settings: { type: "crypto-heatmap" } };

	it("matches a block set up in place with that type", () => {
		expect(isVariationActive({ widgetId: 0, settings: { type: "crypto-heatmap", count: "50" } }, heatmap)).toBe(true);
	});

	it("does not match another type, a saved widget, or a block with no type yet", () => {
		expect(isVariationActive({ widgetId: 0, settings: { type: "table" } }, heatmap)).toBe(false);
		expect(isVariationActive({ widgetId: 7, settings: { type: "crypto-heatmap" } }, heatmap)).toBe(false);
		expect(isVariationActive({ widgetId: 0, settings: {} }, heatmap)).toBe(false);
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
