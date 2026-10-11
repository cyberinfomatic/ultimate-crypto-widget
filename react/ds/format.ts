/**
 * Every number a widget shows goes through here, so prices, percentages and
 * large amounts are written the way the visitor's language writes them.
 */

export type PriceOptions = {
	/** ISO code such as "usd". Decides the symbol and where it goes. */
	currency?: string;
	/** Used only when there is no usable currency code. */
	symbol?: string;
	/** 1,250,000 becomes 1.25M. */
	compact?: boolean;
	locale?: string;
};

/** What is shown in place of a number that is missing. */
export const MISSING = "—";

const formatters = new Map<string, Intl.NumberFormat>();

const formatter = (locale: string | undefined, options: Intl.NumberFormatOptions): Intl.NumberFormat => {
	const key = `${locale ?? ""}|${JSON.stringify(options)}`;
	let found = formatters.get(key);
	if (!found) {
		found = new Intl.NumberFormat(locale, options);
		formatters.set(key, found);
	}
	return found;
};

/** The page's language, which WordPress sets on <html>. */
export const pageLocale = (): string | undefined => {
	if (typeof document === "undefined") {
		return undefined;
	}
	return document.documentElement.lang || undefined;
};

const isNumber = (value: unknown): value is number => typeof value === "number" && Number.isFinite(value);

/**
 * How many digits a price needs: two decimals for ordinary prices, and enough
 * significant digits that a coin worth a fraction of a cent is not shown as 0.00.
 */
const priceDigits = (value: number, compact: boolean): Intl.NumberFormatOptions => {
	if (compact) {
		return { notation: "compact", maximumFractionDigits: 2 };
	}
	if (value !== 0 && Math.abs(value) < 1) {
		return { minimumSignificantDigits: 2, maximumSignificantDigits: 4 };
	}
	return { minimumFractionDigits: 2, maximumFractionDigits: 2 };
};

export function formatPrice(value: unknown, { currency, symbol, compact = false, locale = pageLocale() }: PriceOptions = {}): string {
	if (!isNumber(value)) {
		return MISSING;
	}
	const digits = priceDigits(value, compact);

	if (currency && /^[a-z]{3}$/i.test(currency)) {
		try {
			return formatter(locale, { style: "currency", currency: currency.toUpperCase(), ...digits }).format(value);
		} catch {
			// Not a code this browser knows; fall through to the plain number.
		}
	}
	return `${symbol ?? ""}${formatter(locale, digits).format(value)}`;
}

/** A change in percent, always with its sign: +2.41%, -0.80%. Takes 2.41, not 0.0241. */
export function formatPercent(value: unknown, locale: string | undefined = pageLocale()): string {
	if (!isNumber(value)) {
		return MISSING;
	}
	return formatter(locale, {
		style: "percent",
		signDisplay: "exceptZero",
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	}).format(value / 100);
}

/** A plain amount: 1,234.5 or, when compact, 1.2K. */
export function formatNumber(value: unknown, { compact = false, locale = pageLocale() }: { compact?: boolean; locale?: string } = {}): string {
	if (!isNumber(value)) {
		return MISSING;
	}
	return formatter(locale, compact ? { notation: "compact", maximumFractionDigits: 1 } : { maximumFractionDigits: 2 }).format(value);
}

/**
 * A width or height from a saved setting as a CSS length. "400" and 400 mean
 * pixels; "100%" or "20rem" are kept; anything else is refused, so a setting can
 * never put arbitrary CSS on the page.
 */
export function cssSize(value: unknown): string | undefined {
	if (typeof value === "number" && Number.isFinite(value) && value > 0) {
		return `${value}px`;
	}
	if (typeof value !== "string") {
		return undefined;
	}
	const match = value.trim().match(/^(\d+(?:\.\d+)?)(px|%|rem|em|vw)?$/);
	if (!match || Number(match[1]) <= 0) {
		return undefined;
	}
	return `${match[1]}${match[2] ?? "px"}`;
}

/** A whole number from a saved setting, which arrives as text. */
export function toCount(value: unknown, fallback: number): number {
	const parsed = typeof value === "number" ? value : parseInt(String(value ?? ""), 10);
	return Number.isFinite(parsed) && parsed > 0 ? Math.floor(parsed) : fallback;
}
