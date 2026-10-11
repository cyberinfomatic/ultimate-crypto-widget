import React from "react";
import { ComponentType } from "react";
import { createRoot, Root } from "react-dom/client";
import { UCWPWidgetSetting } from "../types";

declare global {
	interface Window {
		/** How pages rendered by plugin versions before 1.0 handed data to a widget. */
		ucwpReactData?: Record<string, any>;
		/** Re-scan the page for one widget type, e.g. after an editor inserts one. */
		ucwMount?: Record<string, () => void>;
	}
}

type WidgetProps<P> = P & { settings: UCWPWidgetSetting };

const roots = new WeakMap<Element, Root>();

/**
 * Which widget this script draws, read from its own address
 * (…/widgets/<view>/index.js). Must run while the script is first evaluated.
 */
const viewOfThisScript = (): string | null => {
	const script = document.currentScript as HTMLScriptElement | null;
	const match = script?.src.match(/\/widgets\/([^/?#]+)\/index\.js/);
	return match ? match[1] : null;
};

/** The data a widget's element carries, or null if it is missing or broken. */
const propsOf = (element: HTMLElement): Record<string, any> | null => {
	try {
		const props = JSON.parse(element.dataset.ucwProps ?? "null");
		return props && typeof props === "object" ? props : null;
	} catch {
		return null;
	}
};

/**
 * Draws a widget component into every element on the page that asks for it.
 *
 * The server prints one element per widget with its data in `data-ucw-props`, so
 * any number of widgets, of the same type or different ones, can share a page.
 */
export default function ReactRender<PropType = any>(Component: ComponentType<WidgetProps<PropType>>) {
	const view = viewOfThisScript();

	const mountAll = () => {
		if (view === null) {
			return 0;
		}
		let mounted = 0;
		document.querySelectorAll<HTMLElement>(`[data-ucw-view="${view}"]`).forEach((element) => {
			const props = propsOf(element);
			if (roots.has(element) || props === null) {
				return;
			}
			const root = createRoot(element);
			roots.set(element, root);
			root.render(<Component {...(props as WidgetProps<PropType> & React.JSX.IntrinsicAttributes)} />);
			mounted += 1;
		});
		return mounted;
	};

	if (view !== null) {
		window.ucwMount = { ...(window.ucwMount ?? {}), [view]: mountAll };
	}

	if (mountAll() > 0) {
		return;
	}

	// A page cached before 1.0: one widget, its data in a global, its element found by id.
	const legacy = window.ucwpReactData;
	const element = legacy?.react_id ? document.getElementById(legacy.react_id) : null;
	if (legacy && element && !roots.has(element)) {
		const root = createRoot(element);
		roots.set(element, root);
		root.render(<Component {...(legacy as WidgetProps<PropType> & React.JSX.IntrinsicAttributes)} />);
	}
}
