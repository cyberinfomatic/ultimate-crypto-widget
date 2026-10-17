import React, { ReactNode } from "react";

type NumberFieldProps = {
	/** What the number is, for screen readers. */
	label: string;
	/** What was typed, as text, so "1." and "" are not lost while typing. */
	value: string;
	onChange: (value: string) => void;
	placeholder?: string;
};

/** A one-line box for an amount. Looks like the search box. */
export const NumberField = ({ label, value, onChange, placeholder }: NumberFieldProps) => (
	<input className="ucw-input" type="number" inputMode="decimal" min="0" step="any" aria-label={label} placeholder={placeholder} value={value} onChange={(event) => onChange(event.target.value)} />
);

type SelectFieldProps = {
	/** What is being chosen, for screen readers. */
	label: string;
	value: string;
	onChange: (value: string) => void;
	/** The <option> and <optgroup> elements; the caller writes their wording. */
	children: ReactNode;
};

/** A drop-down list. Looks like the search box. */
export const SelectField = ({ label, value, onChange, children }: SelectFieldProps) => (
	<select className="ucw-input ucw-select" aria-label={label} value={value} onChange={(event) => onChange(event.target.value)}>
		{children}
	</select>
);

/**
 * An amount typed by a visitor as a number: a comma is read as a decimal point,
 * and anything that is not a finite number zero or more is 0.
 */
export function toAmount(text: string): number {
	const parsed = Number(String(text).trim().replace(",", "."));
	return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}
