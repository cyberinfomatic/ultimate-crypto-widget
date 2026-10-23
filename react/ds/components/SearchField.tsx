import React from "react";

type SearchFieldProps = {
	/** What is being searched, for screen readers. */
	label: string;
	placeholder?: string;
	value: string;
	onChange: (value: string) => void;
};

/** A one-line search box. */
export const SearchField = ({ label, placeholder, value, onChange }: SearchFieldProps) => (
	<input className="ucw-search" type="search" aria-label={label} placeholder={placeholder} value={value} onChange={(event) => onChange(event.target.value)} />
);

/**
 * The items whose text contains what was typed, ignoring case and outer spaces.
 * Everything, when nothing was typed.
 */
export function filterByText<T>(items: T[], typed: string, textOf: (item: T) => string[]): T[] {
	const needle = typed.trim().toLowerCase();
	if (needle === "") {
		return items;
	}
	return items.filter((item) => textOf(item).some((text) => text.toLowerCase().includes(needle)));
}
