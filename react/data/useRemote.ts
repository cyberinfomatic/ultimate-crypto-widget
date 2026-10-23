import { useEffect, useState } from "react";
import { CoinInfo, endpoint, getJson, parseChart, parseCoinInfo, PricePoint, Result } from "./api";
import { useEnv } from "./env";

export type Remote<T> = { status: "idle" } | { status: "loading" } | { status: "ready"; data: T } | { status: "error" };

// One request per address for the life of the page, however many widgets ask.
const requests = new Map<string, Promise<Result<unknown>>>();

const request = (url: string): Promise<Result<unknown>> => {
	let pending = requests.get(url);
	if (!pending) {
		pending = getJson(url);
		requests.set(url, pending);
		// A failure is not remembered, so a later widget can try again.
		pending.then((result) => {
			if (!result.ok) {
				requests.delete(url);
			}
		});
	}
	return pending;
};

/**
 * Loads one address and checks the answer. Pass null to wait (for example until
 * a panel is opened); the state is then "idle".
 */
export function useRemote<T>(url: string | null, parse: (body: unknown) => Result<T>): Remote<T> {
	const [state, setState] = useState<Remote<T>>(url === null ? { status: "idle" } : { status: "loading" });

	useEffect(() => {
		if (url === null) {
			setState({ status: "idle" });
			return undefined;
		}
		let current = true;
		setState({ status: "loading" });
		request(url).then((result) => {
			if (!current) {
				return;
			}
			const parsed = result.ok ? parse(result.data) : result;
			setState(parsed.ok ? { status: "ready", data: parsed.data } : { status: "error" });
		});
		return () => {
			current = false;
		};
		// The parser is a fixed function per call site; only the address matters.
	}, [url]);

	return state;
}

/** A coin's price history. `enabled: false` waits. */
export function useChart(coinId: string, days: number, currency: string | undefined, enabled = true): Remote<PricePoint[]> {
	const { api } = useEnv();
	return useRemote(enabled ? endpoint(api, "coin-chart-data", { coin_id: coinId, days, currency: currency?.toLowerCase() }) : null, parseChart);
}

/** A coin's description and its price in other currencies. */
export function useCoinInfo(coinId: string, enabled = true): Remote<CoinInfo> {
	const { api } = useEnv();
	return useRemote(enabled ? endpoint(api, "coin-info", { coin_id: coinId }) : null, parseCoinInfo);
}
