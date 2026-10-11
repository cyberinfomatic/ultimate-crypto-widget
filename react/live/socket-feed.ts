import { LiveFeed, Tick } from "./types";

/**
 * What differs between one exchange's public WebSocket ticker and another's.
 */
export interface SocketFeedSpec {
	name: string;
	url: string;
	/** The message that subscribes to these symbols. */
	subscribeMessage(symbols: string[]): unknown;
	/** The ticks in one incoming message; an empty list for anything else. */
	parse(message: unknown): Tick[];
}

/**
 * Builds a LiveFeed over a WebSocket from a spec, so adding an exchange is a
 * matter of describing its messages.
 */
export const socketFeed = (spec: SocketFeedSpec): LiveFeed => ({
	name: spec.name,

	subscribe(symbols, onTick, onDown) {
		let stopped = false;
		let socket: WebSocket;

		const down = () => {
			if (!stopped) {
				stopped = true;
				onDown();
			}
		};

		try {
			socket = new WebSocket(spec.url);
		} catch {
			// Blocked by the browser or the network before it even started.
			setTimeout(down, 0);
			return () => {
				stopped = true;
			};
		}

		socket.onopen = () => socket.send(JSON.stringify(spec.subscribeMessage(symbols)));
		socket.onmessage = (event) => {
			let message: unknown;
			try {
				message = JSON.parse(String(event.data));
			} catch {
				return;
			}
			spec.parse(message).forEach(onTick);
		};
		socket.onerror = down;
		socket.onclose = down;

		return () => {
			stopped = true;
			socket.onclose = null;
			socket.onerror = null;
			if (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING) {
				socket.close();
			}
		};
	},
});

/** A number from a field that may be a number, a numeric string or missing. */
export const num = (value: unknown): number | undefined => {
	const n = typeof value === "number" ? value : parseFloat(String(value));
	return Number.isFinite(n) ? n : undefined;
};
