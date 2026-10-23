import React from "react";

const ICONS = {
	// Drawn pointing left and right; the stylesheet mirrors them on right-to-left pages.
	previous: "M7.5 2.5 4 6l3.5 3.5",
	next: "M4.5 2.5 8 6 4.5 9.5",
};

type IconButtonProps = {
	/** What the button does; it has no visible text. */
	label: string;
	icon: keyof typeof ICONS;
	disabled?: boolean;
	pressed?: boolean;
	onClick: () => void;
};

/** A small square button with only an icon. */
export const IconButton = ({ label, icon, disabled = false, pressed, onClick }: IconButtonProps) => (
	<button type="button" className="ucw-icon-button" aria-label={label} title={label} aria-pressed={pressed} disabled={disabled} onClick={onClick}>
		<svg viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
			<path d={ICONS[icon]} />
		</svg>
	</button>
);
