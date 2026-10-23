import React, { KeyboardEvent, useRef } from "react";

export type TabItem<Id extends string> = { id: Id; label: string };

type TabsProps<Id extends string> = {
	/** What the tabs choose between, for screen readers. */
	label: string;
	tabs: Array<TabItem<Id>>;
	value: Id;
	onChange: (id: Id) => void;
	/** The id of the element the chosen tab changes. */
	controls?: string;
};

/**
 * Which tab a key press moves to, or null when the key is not for the tabs.
 * Arrow keys wrap round; in a right-to-left page they are swapped.
 */
export function tabAfterKey(key: string, current: number, count: number, rtl = false): number | null {
	if (count === 0) {
		return null;
	}
	const forward = rtl ? "ArrowLeft" : "ArrowRight";
	const back = rtl ? "ArrowRight" : "ArrowLeft";

	switch (key) {
		case forward:
			return (current + 1) % count;
		case back:
			return (current - 1 + count) % count;
		case "Home":
			return 0;
		case "End":
			return count - 1;
		default:
			return null;
	}
}

/** A row of choices where one is active; arrow keys move between them. */
export function Tabs<Id extends string>({ label, tabs, value, onChange, controls }: TabsProps<Id>) {
	const list = useRef<HTMLDivElement>(null);

	const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
		const current = tabs.findIndex((tab) => tab.id === value);
		const rtl = list.current ? getComputedStyle(list.current).direction === "rtl" : false;
		const next = tabAfterKey(event.key, Math.max(current, 0), tabs.length, rtl);
		if (next === null) {
			return;
		}
		event.preventDefault();
		onChange(tabs[next].id);
		list.current?.querySelectorAll<HTMLButtonElement>('[role="tab"]')[next]?.focus();
	};

	return (
		<div className="ucw-tabs" role="tablist" aria-label={label} ref={list} onKeyDown={onKeyDown}>
			{tabs.map((tab) => (
				<button key={tab.id} type="button" role="tab" className="ucw-tabs__tab" aria-selected={tab.id === value} aria-controls={controls} tabIndex={tab.id === value ? 0 : -1} onClick={() => onChange(tab.id)}>
					{tab.label}
				</button>
			))}
		</div>
	);
}
