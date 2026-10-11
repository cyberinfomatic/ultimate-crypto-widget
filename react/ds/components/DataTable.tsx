import React, { HTMLAttributes, ReactNode } from "react";
import { cx } from "../cx";

/*
 * A table built from a CSS grid with table roles, not <table>. Themes style
 * <table>, <th> and <td> heavily and unpredictably; this keeps the meaning for
 * screen readers and leaves the look to us.
 */

type TableProps = {
	/** What the table lists, for screen readers. */
	label: string;
	/** The grid's column sizes, e.g. "auto 1fr auto auto". */
	columns: string;
	id?: string;
	children: ReactNode;
};

export const Table = ({ label, columns, id, children }: TableProps) => (
	<div className="ucw-table" role="table" id={id} aria-label={label} style={{ gridTemplateColumns: columns }}>
		{children}
	</div>
);

export const Row = ({ header = false, className, ...rest }: HTMLAttributes<HTMLDivElement> & { header?: boolean }) => (
	<div {...rest} role="row" className={cx("ucw-table__row", header && "ucw-table__row--header", className)} />
);

type CellProps = HTMLAttributes<HTMLDivElement> & {
	/** A column heading. */
	header?: boolean;
	/** Numbers line up on the trailing edge. */
	numeric?: boolean;
	/** Less important text, such as a rank. */
	muted?: boolean;
};

export const Cell = ({ header = false, numeric = false, muted = false, className, ...rest }: CellProps) => (
	<div {...rest} role={header ? "columnheader" : "cell"} className={cx("ucw-table__cell", numeric && "ucw-table__cell--numeric", muted && "ucw-table__cell--muted", className)} />
);
