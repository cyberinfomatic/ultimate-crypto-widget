import React, { ReactNode } from "react";

type DisclosureProps = {
	/** Always visible; pressing it opens and closes the rest. */
	summary: ReactNode;
	/** Called with true when it opens, so content can be loaded only then. */
	onToggle?: (open: boolean) => void;
	children: ReactNode;
};

/**
 * A row that opens to show more. It is the browser's own <details>, so the
 * keyboard, screen readers and find-in-page work without any script.
 */
export const Disclosure = ({ summary, onToggle, children }: DisclosureProps) => (
	<details className="ucw-disclosure" onToggle={(event) => onToggle?.(event.currentTarget.open)}>
		<summary className="ucw-disclosure__summary">
			{summary}
			<svg className="ucw-disclosure__chevron" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
				<path d="M2.5 4.5 6 8l3.5-3.5" />
			</svg>
		</summary>
		<div className="ucw-disclosure__panel">{children}</div>
	</details>
);
