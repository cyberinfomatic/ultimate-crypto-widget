/// <reference types="node" />
/**
 * The colour roles must stay readable. The themes are written in OKLCH, so this reads the token files,
 * works out each role's colour, and checks the pairs the components put on top of each other against WCAG 2.2:
 * 4.5:1 for text, 3:1 for the accent used as a mark or a link on a surface.
 */
import { readFileSync } from "node:fs";
import { join } from "node:path";
import { describe, expect, it } from "vitest";

const read = (file: string): string => readFileSync(join(__dirname, file), "utf8");

type Rgb = [number, number, number];

/** The declarations of one file: custom property name -> value. */
const declarations = (source: string): Map<string, string> => {
	const found = new Map<string, string>();
	for (const [, name, value] of source.matchAll(/(--ucw-[a-z0-9-]+):\s*([^;]+);/g)) {
		found.set(name, value.trim());
	}
	return found;
};

const brand = declarations(read("_brand.scss"));
const primitives = new Map([...declarations(read("_primitives.scss")), ...brand]);

const number = (text: string, scope: Map<string, string>): number => {
	const reference = text.match(/^var\((--[a-z0-9-]+)\)$/);
	if (reference) {
		return number(scope.get(reference[1]) ?? "", scope);
	}
	return Number(text);
};

/** "calc(var(--ucw-brand-chroma) * 0.75)", "0.19", "var(--x)": the number it stands for. */
const evaluate = (text: string, scope: Map<string, string>): number => {
	const product = text.match(/^calc\((.+?)\s*\*\s*([\d.]+)\)$/);
	return product ? number(product[1].trim(), scope) * Number(product[2]) : number(text, scope);
};

/** OKLCH to sRGB (0 to 1 per channel, out-of-gamut colours clipped), after Björn Ottosson. */
const toRgb = (lightness: number, chroma: number, hue: number): Rgb => {
	const a = chroma * Math.cos((hue * Math.PI) / 180);
	const b = chroma * Math.sin((hue * Math.PI) / 180);
	const l = (lightness + 0.3963377774 * a + 0.2158037573 * b) ** 3;
	const m = (lightness - 0.1055613458 * a - 0.0638541728 * b) ** 3;
	const s = (lightness - 0.0894841775 * a - 1.291485548 * b) ** 3;
	const linear: Rgb = [4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s, -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s, -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s];
	return linear.map((channel) => Math.min(1, Math.max(0, channel))) as Rgb;
};

const colourOf = (value: string, scope: Map<string, string>): Rgb => {
	const inside = value.match(/^oklch\((.*)\)$/)?.[1];
	if (inside === undefined) {
		throw new Error(`Not a colour: ${value}`);
	}
	// Split on spaces that are not inside a calc( ) or var( ).
	const parts: string[] = [];
	let depth = 0;
	let current = "";
	for (const character of inside.trim()) {
		depth += character === "(" ? 1 : character === ")" ? -1 : 0;
		if (character === " " && depth === 0) {
			parts.push(current);
			current = "";
		} else {
			current += character;
		}
	}
	parts.push(current);
	return toRgb(Number(parts[0].replace("%", "")) / 100, evaluate(parts[1], scope), evaluate(parts[2], scope));
};

/** A role's colour: follow its var() down to a primitive. */
const roles = (file: string): Map<string, Rgb> => {
	const result = new Map<string, Rgb>();
	for (const [name, value] of declarations(read(file))) {
		const target = value.match(/^var\((--ucw-p-[a-z0-9-]+)\)$/);
		if (target) {
			result.set(name, colourOf(primitives.get(target[1]) ?? "", primitives));
		}
	}
	return result;
};

const luminance = ([r, g, b]: Rgb): number => 0.2126 * r + 0.7152 * g + 0.0722 * b;

const contrast = (first: Rgb, second: Rgb): number => {
	const [light, dark] = [luminance(first), luminance(second)].sort((x, y) => y - x);
	return (light + 0.05) / (dark + 0.05);
};

const TEXT = 4.5;
const MARK = 3;

/** [text or mark, background, least ratio] */
const pairs: Array<[string, string, number]> = [
	["--ucw-fg", "--ucw-surface", TEXT],
	["--ucw-fg", "--ucw-surface-sunken", TEXT],
	["--ucw-fg", "--ucw-surface-hover", TEXT],
	["--ucw-fg-muted", "--ucw-surface", TEXT],
	["--ucw-fg-muted", "--ucw-surface-sunken", TEXT],
	["--ucw-fg-muted", "--ucw-surface-hover", TEXT],
	["--ucw-on-accent", "--ucw-accent", TEXT],
	["--ucw-accent", "--ucw-surface", MARK],
	["--ucw-accent", "--ucw-accent-soft", MARK],
	["--ucw-up", "--ucw-surface", TEXT],
	["--ucw-up", "--ucw-up-soft", TEXT],
	["--ucw-down", "--ucw-surface", TEXT],
	["--ucw-down", "--ucw-down-soft", TEXT],
];

describe.each([
	["light", "themes/_light.scss"],
	["dark", "themes/_dark.scss"],
])("%s theme contrast", (_name, file) => {
	const colours = roles(file);

	it.each(pairs)("%s on %s is at least %s:1", (foreground, background, least) => {
		const front = colours.get(foreground);
		const back = colours.get(background);
		expect(front, foreground).toBeDefined();
		expect(back, background).toBeDefined();
		expect(contrast(front as Rgb, back as Rgb)).toBeGreaterThanOrEqual(least);
	});
});
