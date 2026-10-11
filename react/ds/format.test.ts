import { describe, expect, it } from "vitest";
import { cssSize, formatNumber, formatPercent, formatPrice, MISSING, toCount } from "./format";

const en = { locale: "en-US" };

describe("formatPrice", () => {
	it("writes an ordinary price with two decimals and the currency's own symbol", () => {
		expect(formatPrice(67123.456, { ...en, currency: "usd" })).toBe("$67,123.46");
		expect(formatPrice(3, { ...en, currency: "EUR" })).toBe("€3.00");
	});

	it("keeps enough digits that a tiny price is not shown as zero", () => {
		expect(formatPrice(0.00001234, { ...en, currency: "usd" })).toBe("$0.00001234");
		expect(formatPrice(0.5, { ...en, currency: "usd" })).toBe("$0.50");
	});

	it("puts the symbol where the visitor's language puts it", () => {
		expect(formatPrice(1234.5, { locale: "de-DE", currency: "eur" })).toBe("1.234,50 €");
	});

	it("falls back to the given symbol when there is no usable currency code", () => {
		expect(formatPrice(12.5, { ...en, symbol: "$" })).toBe("$12.50");
		expect(formatPrice(12.5, { ...en, currency: "dollars", symbol: "$" })).toBe("$12.50");
	});

	it("shortens large amounts when asked", () => {
		expect(formatPrice(1_250_000, { ...en, currency: "usd", compact: true })).toBe("$1.25M");
	});

	it("shows a dash for a price that is missing", () => {
		for (const missing of [null, undefined, NaN, "12", Infinity]) {
			expect(formatPrice(missing, en)).toBe(MISSING);
		}
	});
});

describe("formatPercent", () => {
	it("always writes the sign, to two decimals", () => {
		expect(formatPercent(2.4123, "en-US")).toBe("+2.41%");
		expect(formatPercent(-0.8, "en-US")).toBe("-0.80%");
		expect(formatPercent(0, "en-US")).toBe("0.00%");
	});

	it("shows a dash when the change is unknown", () => {
		expect(formatPercent(null, "en-US")).toBe(MISSING);
	});
});

describe("formatNumber", () => {
	it("groups digits, and shortens when asked", () => {
		expect(formatNumber(1234.567, en)).toBe("1,234.57");
		expect(formatNumber(19_700_000, { ...en, compact: true })).toBe("19.7M");
	});
});

describe("cssSize", () => {
	it("reads a bare number as pixels", () => {
		expect(cssSize("400")).toBe("400px");
		expect(cssSize(157)).toBe("157px");
	});

	it("keeps a length that already has a unit", () => {
		expect(cssSize("100%")).toBe("100%");
		expect(cssSize(" 20rem ")).toBe("20rem");
	});

	it("refuses anything that is not a plain length", () => {
		for (const bad of ["auto", "", "0", "100%;color:red", "calc(1px)", "url(x)", null, undefined, -5]) {
			expect(cssSize(bad)).toBeUndefined();
		}
	});
});

describe("toCount", () => {
	it("reads a saved setting, which is text", () => {
		expect(toCount("10", 5)).toBe(10);
		expect(toCount(7.9, 5)).toBe(7);
	});

	it("uses the fallback for anything unusable", () => {
		for (const bad of ["", "abc", null, undefined, "0", -3]) {
			expect(toCount(bad, 5)).toBe(5);
		}
	});
});
