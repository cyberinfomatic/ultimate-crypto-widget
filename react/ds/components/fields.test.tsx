import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { NumberField, SelectField, toAmount } from "./Fields";

describe("NumberField", () => {
	it("is labelled for screen readers and keeps the typed text", () => {
		const html = renderToStaticMarkup(<NumberField label="Amount of Bitcoin" value="1." onChange={() => undefined} />);

		expect(html).toContain('aria-label="Amount of Bitcoin"');
		expect(html).toContain('value="1."');
		expect(html).toContain('type="number"');
		expect(html).toContain('min="0"');
	});
});

describe("SelectField", () => {
	it("shows the options its caller gives, with the chosen one selected", () => {
		const html = renderToStaticMarkup(
			<SelectField label="From" value="b" onChange={() => undefined}>
				<option value="a">A</option>
				<option value="b">B</option>
			</SelectField>
		);

		expect(html).toContain('aria-label="From"');
		expect(html).toMatch(/<option value="b" selected="">B<\/option>/);
	});
});

describe("toAmount", () => {
	it("reads what a visitor typed", () => {
		expect(toAmount("2.5")).toBe(2.5);
		expect(toAmount(" 2,5 ")).toBe(2.5);
		expect(toAmount("0.00001")).toBe(0.00001);
	});

	it("is zero for anything that is not a positive number", () => {
		expect(toAmount("")).toBe(0);
		expect(toAmount("abc")).toBe(0);
		expect(toAmount("-3")).toBe(0);
		expect(toAmount("1e999")).toBe(0);
	});
});
