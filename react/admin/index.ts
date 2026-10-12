/**
 * The widget editor and the quick widget screen. The form works without this
 * script; it adds three things: only the settings of the chosen widget type are
 * shown, the preview follows the form, and shortcodes can be copied.
 */
import { __ } from "@wordpress/i18n";
import { FIELD_PREFIX, fieldApplies, joinChosen, previewUrl, shortcodeFor, WidgetValues } from "./editor";
import "./admin.scss";

type Control = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/** One setting of the form: its inputs (several for a list of choices) and where it sits. */
type Setting = {
	name: string;
	inputs: Control[];
	/** The widget types that use it, from the field's data-ucw-for; null for the type itself. */
	usedBy: string | null;
	/** The most choices it may hold; 0 for no limit. */
	max: number;
	row: HTMLElement;
};

const PREVIEW_DELAY_MS = 500;

/** Whether an input is one of a group of tick boxes (the toolkit's multiple-choice field). */
const isBox = (control: Control): control is HTMLInputElement => control instanceof HTMLInputElement && control.type === "checkbox";

/**
 * The toolkit prints a list of choices as a fieldset after the field's label, inside a paragraph.
 * A browser closes a paragraph when a fieldset starts, which leaves the label, the fieldset and the
 * description as loose siblings. They are put back in one row, so a setting can be hidden or laid
 * out as a whole.
 */
const gather = (fieldset: HTMLElement): HTMLElement => {
	const existing = fieldset.closest<HTMLElement>(".wptoolkit-field");
	if (existing) {
		return existing;
	}
	const label = fieldset.id ? document.querySelector<HTMLElement>(`label[for="${CSS.escape(fieldset.id)}"]`) : null;
	// The label stayed in the paragraph the browser closed: that paragraph is the row.
	let row = label?.closest<HTMLElement>(".wptoolkit-field") ?? null;
	if (!row) {
		row = document.createElement("div");
		row.className = "wptoolkit-field";
		(label ?? fieldset).before(row);
		if (label) {
			row.append(label);
		}
	}
	row.append(fieldset);
	// What follows the fieldset until the next setting: a break, the description, an empty paragraph.
	let next = row.nextElementSibling;
	while (next && (next.tagName === "BR" || next.classList.contains("description") || (next.tagName === "P" && !next.classList.contains("wptoolkit-field") && !next.textContent?.trim()))) {
		const following: Element | null = next.nextElementSibling;
		if (next.tagName === "P") {
			next.remove();
		} else {
			row.append(next);
		}
		next = following;
	}
	return row;
};

/** The form's settings, by name. Hidden helper inputs are skipped. */
const settingsOf = (form: HTMLElement): Map<string, Setting> => {
	const settings = new Map<string, Setting>();
	form.querySelectorAll<Control>(`[name^="${FIELD_PREFIX}"]`).forEach((control) => {
		if (control instanceof HTMLInputElement && control.type === "hidden") {
			return;
		}
		const name = control.name.slice(FIELD_PREFIX.length).replace(/\[\]$/, "");
		const known = settings.get(name);
		if (known) {
			known.inputs.push(control);
			return;
		}
		// A field's attributes are on its control, or on the fieldset around a group of tick boxes.
		const holder = control.closest<HTMLElement>("[data-ucw-for]") ?? control;
		const fieldset = isBox(control) ? control.closest<HTMLElement>("fieldset") : null;
		settings.set(name, {
			name,
			inputs: [control],
			usedBy: holder.getAttribute("data-ucw-for"),
			max: Number(holder.getAttribute("data-ucw-max") ?? 0),
			row: fieldset ? gather(fieldset) : (control.closest<HTMLElement>(".wptoolkit-field") ?? control),
		});
	});
	return settings;
};

const valueOf = (setting: Setting): string => {
	const first = setting.inputs[0];
	if (isBox(first)) {
		return joinChosen(setting.inputs.filter((input) => (input as HTMLInputElement).checked).map((input) => input.value));
	}
	if (first instanceof HTMLSelectElement && first.multiple) {
		return joinChosen(Array.from(first.selectedOptions, (option) => option.value));
	}
	return first.value.trim();
};

function setUp(form: HTMLElement): void {
	const settings = settingsOf(form);
	const typeSetting = settings.get("type");
	if (!typeSetting) {
		return;
	}
	const frame = document.querySelector<HTMLIFrameElement>("iframe[data-ucw-preview]");
	const shortcode = document.querySelector<HTMLElement>("[data-ucw-shortcode]");
	let timer: ReturnType<typeof setTimeout> | undefined;

	const read = (): WidgetValues => {
		const type = valueOf(typeSetting);
		const values: WidgetValues = {};
		settings.forEach((setting, name) => {
			// A setting the chosen type does not have is not part of the widget.
			if (fieldApplies(setting.usedBy, type)) {
				values[name] = valueOf(setting);
			}
		});
		return values;
	};

	const showRows = () => {
		const type = valueOf(typeSetting);
		settings.forEach((setting) => {
			setting.row.hidden = !fieldApplies(setting.usedBy, type);
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

	/** Stops a tick beyond a list's limit. The server refuses too long a list anyway; this saves the trip. */
	const keepWithinLimit = (box: HTMLInputElement) => {
		const setting = Array.from(settings.values()).find((candidate) => candidate.inputs.includes(box));
		if (!setting || setting.max < 1 || !box.checked) {
			return;
		}
		if (setting.inputs.filter((input) => (input as HTMLInputElement).checked).length > setting.max) {
			box.checked = false;
			// Lets the chip picker, which follows the boxes, show it.
			box.dispatchEvent(new Event("change", { bubbles: true }));
		}
	};

	const changed = (event: Event) => {
		const control = event.target;
		if (control instanceof HTMLInputElement && isBox(control)) {
			keepWithinLimit(control);
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
