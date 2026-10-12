/**
 * The widget editor and the quick widget screen. The form works without this
 * script; it adds three things: only the settings of the chosen widget type are
 * shown, the preview follows the form, and shortcodes can be copied.
 */
import { __ } from "@wordpress/i18n";
import { FIELD_PREFIX, fieldApplies, previewUrl, shortcodeFor, WidgetValues, withinLimit } from "./editor";
import "./admin.scss";

type Control = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

const PREVIEW_DELAY_MS = 500;

/** The form's settings controls, by setting name. Hidden helper inputs are skipped. */
const controlsOf = (form: HTMLElement): Map<string, Control> => {
	const controls = new Map<string, Control>();
	form.querySelectorAll<Control>(`[name^="${FIELD_PREFIX}"]`).forEach((control) => {
		if (control instanceof HTMLInputElement && control.type === "hidden") {
			return;
		}
		controls.set(control.name.slice(FIELD_PREFIX.length).replace(/\[\]$/, ""), control);
	});
	return controls;
};

const valueOf = (control: Control): string => {
	if (control instanceof HTMLSelectElement && control.multiple) {
		return Array.from(control.selectedOptions, (option) => option.value).join(",");
	}
	return control.value.trim();
};

/** The row a control sits in: label, input and description together. */
const rowOf = (control: Control): HTMLElement => control.closest<HTMLElement>(".wptoolkit-field") ?? control;

function setUp(form: HTMLElement): void {
	const controls = controlsOf(form);
	const typeControl = controls.get("type");
	if (!typeControl) {
		return;
	}
	const frame = document.querySelector<HTMLIFrameElement>("iframe[data-ucw-preview]");
	const shortcode = document.querySelector<HTMLElement>("[data-ucw-shortcode]");
	let timer: ReturnType<typeof setTimeout> | undefined;

	const read = (): WidgetValues => {
		const type = valueOf(typeControl);
		const values: WidgetValues = {};
		controls.forEach((control, name) => {
			// A setting the chosen type does not have is not part of the widget.
			if (fieldApplies(control.getAttribute("data-ucw-for"), type)) {
				values[name] = valueOf(control);
			}
		});
		return values;
	};

	const showRows = () => {
		const type = valueOf(typeControl);
		controls.forEach((control) => {
			rowOf(control).hidden = !fieldApplies(control.getAttribute("data-ucw-for"), type);
		});
	};

	const refresh = () => {
		const values = read();
		if (shortcode) {
			shortcode.textContent = shortcodeFor(values) || __("Choose a widget type first.", "ultimate-crypto-widget");
		}
		if (frame?.dataset.ucwPreview) {
			const next = previewUrl(frame.dataset.ucwPreview, values);
			if (frame.src !== next && values.type) {
				frame.src = next;
			}
		}
	};

	const changed = (event: Event) => {
		const control = event.target;
		if (control instanceof HTMLSelectElement && control.multiple) {
			// Keep a list within its limit by undoing the choices beyond it.
			const max = Number(control.dataset.ucwMax ?? 0);
			const chosen = Array.from(control.selectedOptions);
			const kept = withinLimit(chosen, max);
			chosen.filter((option) => !kept.includes(option)).forEach((option) => (option.selected = false));
		}
		showRows();
		clearTimeout(timer);
		timer = setTimeout(refresh, PREVIEW_DELAY_MS);
	};

	form.addEventListener("input", changed);
	form.addEventListener("change", changed);
	showRows();
	if (shortcode) {
		refresh();
	}
}

function setUpCopying(): void {
	document.addEventListener("click", (event) => {
		const button = event.target instanceof Element ? event.target.closest<HTMLElement>("[data-ucw-copy-from]") : null;
		const source = button ? document.querySelector<HTMLElement>(button.dataset.ucwCopyFrom ?? "") : null;
		const text = source?.textContent?.trim() ?? "";
		if (!button || text === "" || !text.startsWith("[")) {
			return;
		}
		navigator.clipboard?.writeText(text).then(() => {
			const label = button.textContent;
			button.textContent = __("Copied", "ultimate-crypto-widget");
			setTimeout(() => (button.textContent = label), 1500);
		});
	});
}

/** Makes each preview frame as tall as the widget inside it says it is. */
function fitPreviews(): void {
	window.addEventListener("message", (event) => {
		const height = Number(event.data?.ucwPreviewHeight);
		if (event.origin !== window.location.origin || !(height > 0)) {
			return;
		}
		document.querySelectorAll<HTMLIFrameElement>("iframe[data-ucw-preview]").forEach((frame) => {
			if (frame.contentWindow === event.source) {
				frame.style.blockSize = `${Math.min(height, 1600)}px`;
			}
		});
	});
}

const start = () => {
	// The post editor's own form, or the quick widget's.
	document.querySelectorAll<HTMLElement>("form#post, form[data-ucw-editor]").forEach(setUp);
	setUpCopying();
	fitPreviews();
};

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", start);
} else {
	start();
}
