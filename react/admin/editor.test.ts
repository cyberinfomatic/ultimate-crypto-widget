import { describe, expect, it } from "vitest";
import { fieldApplies, joinChosen, meaningful, previewUrl, shortcodeFor, withinLimit } from "./editor";

describe("fieldApplies", () => {
	it("shows a setting only for the widget types that use it", () => {
		expect(fieldApplies("coin-marquee-1 coin-marquee-2", "coin-marquee-1")).toBe(true);
		expect(fieldApplies("coin-marquee-1 coin-marquee-2", "coin-marquee")).toBe(false);
		expect(fieldApplies("coin-marquee-1", "crypto-price-table-1")).toBe(false);
	});

	it("hides every setting until a type is chosen", () => {
		expect(fieldApplies("coin-marquee-1", "")).toBe(false);
		expect(fieldApplies("", "coin-marquee-1")).toBe(false);
	});

	it("always shows a field that is not tied to a type", () => {
		expect(fieldApplies(null, "")).toBe(true);
		expect(fieldApplies(undefined, "anything")).toBe(true);
	});
});

describe("meaningful", () => {
	it("puts the type first and leaves out empty settings", () => {
		expect(meaningful({ count: "10", coins: "", type: "coin-marquee-1" })).toEqual([
			["type", "coin-marquee-1"],
			["count", "10"],
		]);
	});

	it("is empty without a type", () => {
		expect(meaningful({ count: "10" })).toEqual([]);
	});
});

describe("shortcodeFor", () => {
	it("writes the settings as attributes", () => {
		expect(shortcodeFor({ type: "crypto-price-table-1", count: "5", parent_width: "100%" })).toBe('[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%"]');
	});

	it("removes characters that would break out of an attribute or the shortcode", () => {
		expect(shortcodeFor({ type: "x", search_placeholder: 'Find "a" coin] <b>' })).toBe('[ucwp_widget type="x" search_placeholder="Find a coin b"]');
	});

	it("is empty until a type is chosen", () => {
		expect(shortcodeFor({ count: "5" })).toBe("");
	});
});

describe("previewUrl", () => {
	const base = "https://example.org/wp-admin/admin-post.php?action=ucwp_preview&_wpnonce=abc";

	it("adds each setting under s[…]", () => {
		expect(previewUrl(base, { type: "coin-marquee-1", coins: "bitcoin,ethereum" })).toBe(`${base}&s%5Btype%5D=coin-marquee-1&s%5Bcoins%5D=bitcoin%2Cethereum`);
	});

	it("is the bare address until a type is chosen", () => {
		expect(previewUrl(base, {})).toBe(base);
	});
});

describe("withinLimit", () => {
	it("keeps the first few when there is a limit", () => {
		expect(withinLimit(["a", "b", "c", "d"], 3)).toEqual(["a", "b", "c"]);
		expect(withinLimit(["a", "b"], 0)).toEqual(["a", "b"]);
	});
});

describe("joinChosen", () => {
	it("writes ticked values the way the shortcode does", () => {
		expect(joinChosen(["bitcoin", "ethereum"])).toBe("bitcoin,ethereum");
	});

	it("drops the form's empty entry, spaces and repeats", () => {
		expect(joinChosen(["", " usd ", "eur", "usd"])).toBe("usd,eur");
		expect(joinChosen([])).toBe("");
	});
});
