import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { CoinIdentity } from "./CoinIdentity";
import { Cell, Row, Table } from "./DataTable";
import { Delta } from "./Delta";
import { Notice } from "./Notice";
import { Price } from "./Price";
import { Skeleton } from "./Skeleton";
import { Sparkline, sparklinePoints } from "./Sparkline";
import { Surface } from "./Surface";
import { tabAfterKey, Tabs } from "./Tabs";
import { MAX_SPEED, MIN_SPEED, Ticker, tickerDuration } from "./Ticker";
import { directionOf } from "./TrendIcon";

const html = (node: React.ReactElement) => renderToStaticMarkup(node);

describe("Surface", () => {
	it("takes its width from a saved setting", () => {
		expect(html(<Surface width="400" />)).toContain("inline-size:400px");
		expect(html(<Surface width="100%" />)).toContain("inline-size:100%");
	});

	it("ignores a width that is not a plain length", () => {
		expect(html(<Surface width="1px;background:url(x)" />)).not.toContain("style");
	});
});

describe("CoinIdentity", () => {
	it("shows the name and symbol, with a logo that adds nothing for screen readers", () => {
		const out = html(<CoinIdentity name="Bitcoin" symbol="btc" image="https://example.org/btc.png" />);

		expect(out).toContain("Bitcoin");
		expect(out).toContain("btc");
		expect(out).toContain('alt=""');
	});

	it("keeps its shape when a coin has no logo", () => {
		expect(html(<CoinIdentity name="Bitcoin" symbol="btc" />)).not.toContain("<img");
	});

	it("shows only the symbol when compact, with the name as a tooltip", () => {
		const out = html(<CoinIdentity name="Bitcoin" symbol="btc" compact />);

		expect(out).not.toContain("ucw-coin__name");
		expect(out).toContain('title="Bitcoin"');
	});
});

describe("Price", () => {
	it("formats the value for the visitor", () => {
		expect(html(<Price value={67123.456} currency="usd" locale="en-US" />)).toContain("$67,123.46");
	});

	it("shows a dash, not NaN, when the price is missing", () => {
		const out = html(<Price value={undefined} currency="usd" locale="en-US" />);

		expect(out).toContain("—");
		expect(out).not.toContain("NaN");
	});
});

describe("Delta", () => {
	it("marks a rise with a sign and an arrow, not only a colour", () => {
		const out = html(<Delta value={2.41} locale="en-US" />);

		expect(out).toContain('data-direction="up"');
		expect(out).toContain("+2.41%");
		expect(out).toContain("<svg");
	});

	it("marks a fall", () => {
		const out = html(<Delta value={-0.8} locale="en-US" />);

		expect(out).toContain('data-direction="down"');
		expect(out).toContain("-0.80%");
	});

	it("calls a change too small to show flat", () => {
		const out = html(<Delta value={-0.001} locale="en-US" />);

		expect(out).toContain('data-direction="flat"');
		expect(out).toContain("0.00%");
	});

	it("does not crash on a coin with no change figure", () => {
		expect(html(<Delta value={null} />)).toContain('data-direction="flat"');
	});
});

describe("directionOf", () => {
	it("reads the sign", () => {
		expect([directionOf(1), directionOf(-1), directionOf(0), directionOf(NaN), directionOf("5")]).toEqual(["up", "down", "flat", "flat", "flat"]);
	});
});

describe("Table", () => {
	it("is announced as a table with rows, headings and cells", () => {
		const out = html(
			<Table label="Prices" columns="1fr auto">
				<Row header>
					<Cell header>Name</Cell>
					<Cell header numeric>
						Price
					</Cell>
				</Row>
				<Row>
					<Cell>Bitcoin</Cell>
					<Cell numeric>$1</Cell>
				</Row>
			</Table>
		);

		expect(out).toContain('role="table"');
		expect(out).toContain('aria-label="Prices"');
		expect(out.match(/role="row"/g)).toHaveLength(2);
		expect(out.match(/role="columnheader"/g)).toHaveLength(2);
		expect(out.match(/role="cell"/g)).toHaveLength(2);
		expect(out).toContain("grid-template-columns:1fr auto");
	});
});

describe("Tabs", () => {
	const tabs = [
		{ id: "usd", label: "USD" },
		{ id: "eur", label: "EUR" },
		{ id: "ngn", label: "NGN" },
	];

	it("marks the chosen tab and makes it the only one in the tab order", () => {
		const out = html(<Tabs label="Currency" tabs={tabs} value="eur" onChange={() => {}} />);

		expect(out).toContain('role="tablist"');
		expect(out.match(/aria-selected="true"/g)).toHaveLength(1);
		expect(out.match(/tabindex="0"/g)).toHaveLength(1);
		expect(out).toMatch(/aria-selected="true"[^>]*tabindex="0"[^>]*>EUR/);
	});

	it("moves with the arrow keys and wraps round", () => {
		expect(tabAfterKey("ArrowRight", 0, 3)).toBe(1);
		expect(tabAfterKey("ArrowRight", 2, 3)).toBe(0);
		expect(tabAfterKey("ArrowLeft", 0, 3)).toBe(2);
		expect(tabAfterKey("Home", 2, 3)).toBe(0);
		expect(tabAfterKey("End", 0, 3)).toBe(2);
	});

	it("swaps the arrow keys in a right-to-left page", () => {
		expect(tabAfterKey("ArrowLeft", 0, 3, true)).toBe(1);
		expect(tabAfterKey("ArrowRight", 0, 3, true)).toBe(2);
	});

	it("leaves other keys alone", () => {
		expect(tabAfterKey("Enter", 0, 3)).toBeNull();
		expect(tabAfterKey("ArrowRight", 0, 0)).toBeNull();
	});
});

describe("Ticker", () => {
	it("renders its content twice and hides the copy from screen readers", () => {
		const out = html(
			<Ticker label="Prices">
				<span>BTC</span>
			</Ticker>
		);

		expect(out.match(/BTC/g)).toHaveLength(2);
		expect(out.match(/aria-hidden="true"/g)).toHaveLength(1);
		expect(out).toContain('aria-label="Prices"');
	});

	it("takes longer for wider content and less time at a higher speed", () => {
		expect(tickerDuration(1000, 50)).toBe(20);
		expect(tickerDuration(1000, 100)).toBe(10);
	});

	it("keeps the speed within a readable range", () => {
		expect(tickerDuration(1000, 100000)).toBe(1000 / MAX_SPEED);
		expect(tickerDuration(1000, 0)).toBe(1000 / MIN_SPEED);
		expect(tickerDuration(1000, NaN)).toBe(20);
		expect(tickerDuration(0, 50)).toBe(0);
	});
});

describe("Sparkline", () => {
	it("fits the points to its box, highest at the top", () => {
		expect(sparklinePoints([0, 10], 12, 12)).toBe("1.0,11.0 11.0,1.0");
	});

	it("draws a flat line for a price that did not move", () => {
		expect(sparklinePoints([5, 5, 5], 12, 12)).toBe("1.0,11.0 6.0,11.0 11.0,11.0");
	});

	it("says what it shows and which way it went", () => {
		const out = html(<Sparkline points={[1, 3, 2]} label="Bitcoin, last 7 days" />);

		expect(out).toContain('aria-label="Bitcoin, last 7 days"');
		expect(out).toContain('data-direction="up"');
	});

	it("renders nothing without at least two points", () => {
		expect(html(<Sparkline points={[1]} label="x" />)).toBe("");
		expect(html(<Sparkline points={[NaN, NaN]} label="x" />)).toBe("");
	});
});

describe("Skeleton and Notice", () => {
	it("hides the skeleton from screen readers", () => {
		expect(html(<Skeleton width="80" height="12" />)).toContain('aria-hidden="true"');
	});

	it("announces a notice", () => {
		expect(html(<Notice>Prices are unavailable.</Notice>)).toContain('role="status"');
	});
});
