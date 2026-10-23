import React, { HTMLAttributes } from "react";
import { cx } from "../cx";
import { cssSize } from "../format";

type SurfaceProps = HTMLAttributes<HTMLDivElement> & {
	/** A saved width setting: 400, "400", "100%". */
	width?: unknown;
	/** No border or shadow, for a surface that sits inside another. */
	flat?: boolean;
};

/** The panel a widget's content sits on. It owns the background and text colour. */
export const Surface = ({ width, flat = false, className, style, ...rest }: SurfaceProps) => (
	<div {...rest} className={cx("ucw-surface", flat && "ucw-surface--flat", className)} style={{ ...style, inlineSize: cssSize(width) }} />
);
