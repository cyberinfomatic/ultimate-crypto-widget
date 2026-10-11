import React from "react";

/**
 * An up or down arrow drawn as inline SVG, so no icon font has to be loaded.
 * It takes the text colour of its parent and is hidden from screen readers:
 * the number beside it already carries its sign.
 */
const TrendArrow = ({ up }: { up: boolean }) => (
	<svg
		className={`ucwp-trend-arrow ${up ? "ucwp-trend-arrow-up" : "ucwp-trend-arrow-down"}`}
		width="0.75em"
		height="0.75em"
		viewBox="0 0 12 12"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.75"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{up ? <path d="M6 10V2M2.5 5.5 6 2l3.5 3.5" /> : <path d="M6 2v8M2.5 6.5 6 10l3.5-3.5" />}
	</svg>
);

export default TrendArrow;
