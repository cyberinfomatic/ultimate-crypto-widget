import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { Disclosure } from "./Disclosure";
import { IconButton } from "./IconButton";
import { LineChart, nearestIndex, rangeOf } from "./LineChart";
import { clampPage, pageCount, pageOf, Pager } from "./Pager";
import { filterByText, SearchField } from "./SearchField";
import { Stat } from "./Stat";
import { Ticker } from "./Ticker";

const html = (node: React.ReactElement) => renderToStaticMarkup(node);

describe("LineChart", () => {
	const points = [
		{ time: Date.UTC(2026, 0, 1), value: 10 },
		{ time: Date.UTC(2026, 0, 2), value: 30 },
		{ time: Date.UTC(2026, 0, 3), value: 20 },
	];

	it("says what it shows and reads out the low and the high", () => {
		const out = html(<LineChart points={points} label="Bitcoin price, last 3 days" formatValue={(value) => `$${value}`} formatTime={(time) => new Date(time).toISOString().slice(0, 10)} />);

		expect(out).toContain('aria-label="Bitcoin price, last 3 days"');
		expect(out).toContain("$10 — $30");
		expect(out).toContain("2026-01-01 — 2026-01-03");
		expect(out).toContain('data-direction="up"');
	});

	it("can be reached with the keyboard", () => {
		expect(html(<LineChart points={points} label="x" formatValue={String} />)).toContain('tabindex="0"');
	});

	it("renders nothing without at least two points", () => {
		expect(html(<LineChart points={points.slice(0, 1)} label="x" formatValue={String} />)).toBe("");
		expect(html(<LineChart points={[]} label="x" formatValue={String} />)).toBe("");
	});

	it("finds the point under the pointer", () => {
		expect(nearestIndex(0, 5)).toBe(0);
		expect(nearestIndex(0.5, 5)).toBe(2);
		expect(nearestIndex(1, 5)).toBe(4);
		expect(nearestIndex(2, 5)).toBe(4);
		expect(nearestIndex(-1, 5)).toBe(0);
		expect(nearestIndex(NaN, 5)).toBe(0);
	});

	it("finds the range", () => {
		expect(rangeOf(points)).toEqual({ low: 10, high: 30 });
		expect(rangeOf([])).toBeNull();
	});
});

describe("Disclosure", () => {
	it("is the browser's own details element, closed to begin with", () => {
		const out = html(<Disclosure summary="Bitcoin">More</Disclosure>);

		expect(out).toMatch(/^<details class="ucw-disclosure"><summary/);
		expect(out).not.toContain(" open");
		expect(out).toContain("More");
	});
});

describe("SearchField", () => {
	it("is labelled for screen readers", () => {
		const out = html(<SearchField label="Search coins" placeholder="Search Coin" value="" onChange={() => {}} />);

		expect(out).toContain('type="search"');
		expect(out).toContain('aria-label="Search coins"');
		expect(out).toContain('placeholder="Search Coin"');
	});

	it("matches any of an item's texts, ignoring case and outer spaces", () => {
		const coins = [
			{ name: "Bitcoin", symbol: "btc" },
			{ name: "Ethereum", symbol: "eth" },
		];
		const textOf = (coin: { name: string; symbol: string }) => [coin.name, coin.symbol];

		expect(filterByText(coins, " BIT ", textOf)).toEqual([coins[0]]);
		expect(filterByText(coins, "eth", textOf)).toEqual([coins[1]]);
		expect(filterByText(coins, "", textOf)).toEqual(coins);
		expect(filterByText(coins, "doge", textOf)).toEqual([]);
	});
});

describe("Pager", () => {
	it("counts pages, with at least one", () => {
		expect(pageCount(25, 10)).toBe(3);
		expect(pageCount(20, 10)).toBe(2);
		expect(pageCount(0, 10)).toBe(1);
		expect(pageCount(5, 0)).toBe(5);
	});

	it("gives one page of a list", () => {
		const items = [1, 2, 3, 4, 5];

		expect(pageOf(items, 0, 2)).toEqual([1, 2]);
		expect(pageOf(items, 2, 2)).toEqual([5]);
	});

	it("pulls a page that no longer exists back into range", () => {
		expect(clampPage(9, 5, 2)).toBe(2);
		expect(clampPage(-1, 5, 2)).toBe(0);
		expect(pageOf([1, 2, 3], 9, 2)).toEqual([3]);
	});

	it("disables the button that has nowhere to go", () => {
		const first = html(<Pager page={0} pageSize={10} total={25} onChange={() => {}} />);

		expect(first).toContain("Page 1 of 3");
		expect(first).toMatch(/aria-label="Previous page"[^>]*disabled/);
		expect(first).not.toMatch(/aria-label="Next page"[^>]*disabled/);
		expect(html(<Pager page={2} pageSize={10} total={25} onChange={() => {}} />)).toMatch(/aria-label="Next page"[^>]*disabled/);
	});

	it("draws nothing when everything fits on one page", () => {
		expect(html(<Pager page={0} pageSize={10} total={10} onChange={() => {}} />)).toBe("");
	});
});

describe("IconButton, Stat and a reversed Ticker", () => {
	it("names a button that has no text", () => {
		expect(html(<IconButton label="Next page" icon="next" onClick={() => {}} />)).toContain('aria-label="Next page"');
	});

	it("pairs a figure with its name", () => {
		const out = html(<Stat label="Market cap">$1.7T</Stat>);

		expect(out).toContain("Market cap");
		expect(out).toContain("$1.7T");
	});

	it("marks a ticker that runs the other way", () => {
		expect(html(<Ticker label="x" reverse>a</Ticker>)).toContain("data-reverse");
		expect(html(<Ticker label="x">a</Ticker>)).not.toContain("data-reverse");
	});
});
