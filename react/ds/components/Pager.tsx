import React from "react";
import { IconButton } from "./IconButton";

/** The pager's wording, from whoever uses it, so it is translated with that plugin's own strings. */
export type PagerLabels = {
	/** "Page 2 of 5". Both numbers start at 1. */
	status: (page: number, pages: number) => string;
	previous: string;
	next: string;
};

type PagerProps = {
	labels: PagerLabels;
	/** The first page is 0. */
	page: number;
	pageSize: number;
	total: number;
	onChange: (page: number) => void;
};

/** How many pages a list needs; at least one, so an empty list still has a page. */
export const pageCount = (total: number, pageSize: number): number => Math.max(1, Math.ceil(total / Math.max(1, pageSize)));

/** The page to show: the one asked for, pulled back into range if the list got shorter. */
export const clampPage = (page: number, total: number, pageSize: number): number => Math.min(Math.max(0, page), pageCount(total, pageSize) - 1);

/** One page of a list. */
export function pageOf<T>(items: T[], page: number, pageSize: number): T[] {
	const size = Math.max(1, pageSize);
	const start = clampPage(page, items.length, size) * size;
	return items.slice(start, start + size);
}

/** Previous and next, with where the reader is. Draws nothing for a single page. */
export const Pager = ({ labels, page, pageSize, total, onChange }: PagerProps) => {
	const pages = pageCount(total, pageSize);
	const current = clampPage(page, total, pageSize);

	if (pages < 2) {
		return null;
	}

	return (
		<div className="ucw-pager">
			<span className="ucw-pager__status" aria-live="polite">
				{labels.status(current + 1, pages)}
			</span>
			<IconButton label={labels.previous} icon="previous" disabled={current === 0} onClick={() => onChange(current - 1)} />
			<IconButton label={labels.next} icon="next" disabled={current >= pages - 1} onClick={() => onChange(current + 1)} />
		</div>
	);
};
