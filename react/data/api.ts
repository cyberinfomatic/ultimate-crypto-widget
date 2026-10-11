/**
 * The plugin's REST API as the widgets use it: every answer is checked and comes
 * back as a typed result, so a widget always knows whether it has data.
 */

export type Result<T> = { ok: true; data: T } | { ok: false; kind: "network" | "http" | "shape"; status?: number };

export type PricePoint = { time: number; value: number };

export type CoinInfo = {
	/** Plain text; any markup in the source is removed. */
	description: string;
	/** Price by lowercase currency code. */
	prices: Record<string, number>;
};

/** Where the API is when the page does not say (pages cached before 1.0). */
export const DEFAULT_API = "/wp-json/ultimate-crypto-widget/v1/";

/**
 * The address of one route. The API root can be a pretty URL or, on sites
 * without pretty permalinks, "…/index.php?rest_route=/…", so parameters are
 * joined with whichever of ? and & fits.
 */
export function endpoint(api: string, route: string, params: Record<string, string | number | undefined> = {}): string {
	const query = Object.entries(params)
		.filter((entry): entry is [string, string | number] => entry[1] !== undefined && entry[1] !== "")
		.map(([name, value]) => `${encodeURIComponent(name)}=${encodeURIComponent(String(value))}`)
		.join("&");
	const url = api.replace(/\/+$/, "") + "/" + route;

	return query === "" ? url : url + (url.includes("?") ? "&" : "?") + query;
}

export async function getJson(url: string): Promise<Result<unknown>> {
	let response: Response;
	try {
		response = await fetch(url, { headers: { Accept: "application/json" } });
	} catch {
		return { ok: false, kind: "network" };
	}
	if (!response.ok) {
		return { ok: false, kind: "http", status: response.status };
	}
	try {
		return { ok: true, data: await response.json() };
	} catch {
		return { ok: false, kind: "shape" };
	}
}

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === "object" && value !== null && !Array.isArray(value);

/** A price history: { prices: [[time in ms, price], …] }, oldest first. */
export function parseChart(body: unknown): Result<PricePoint[]> {
	if (!isRecord(body) || !Array.isArray(body.prices)) {
		return { ok: false, kind: "shape" };
	}
	const points: PricePoint[] = [];
	for (const row of body.prices) {
		if (Array.isArray(row) && Number.isFinite(row[0]) && Number.isFinite(row[1])) {
			points.push({ time: row[0], value: row[1] });
		}
	}
	return { ok: true, data: points };
}

export function parseCoinInfo(body: unknown): Result<CoinInfo> {
	if (!isRecord(body)) {
		return { ok: false, kind: "shape" };
	}
	const description = isRecord(body.description) && typeof body.description.en === "string" ? body.description.en : "";
	const prices: Record<string, number> = {};
	const current = isRecord(body.market_data) ? body.market_data.current_price : null;
	if (isRecord(current)) {
		for (const [code, price] of Object.entries(current)) {
			if (typeof price === "number" && Number.isFinite(price)) {
				prices[code.toLowerCase()] = price;
			}
		}
	}
	// The description is shown as text, so its links and line breaks are dropped.
	return { ok: true, data: { description: description.replace(/<[^>]*>/g, "").replace(/\s+/g, " ").trim(), prices } };
}

/**
 * At most `max` points, spread evenly and always keeping the first and last,
 * so a year of hourly prices does not become a thousand dots.
 */
export function thin<T>(points: T[], max: number): T[] {
	if (max < 2 || points.length <= max) {
		return points;
	}
	const step = (points.length - 1) / (max - 1);
	return Array.from({ length: max }, (_, index) => points[Math.round(index * step)]);
}
