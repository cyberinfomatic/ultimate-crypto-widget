import React from "react";
import { cx } from "../cx";
import { formatPercent } from "../format";
import { directionOf, TrendIcon } from "./TrendIcon";

type DeltaProps = {
	/** The change in percent: 2.41 means +2.41%. */
	value: unknown;
	/** Draw it on a tinted background. */
	pill?: boolean;
	locale?: string;
};

/** A percentage change with its sign, an arrow and the up or down colour. */
export const Delta = ({ value, pill = false, locale }: DeltaProps) => {
	// From the number as it is shown: -0.001 reads "0.00%" and must not get a down arrow.
	const direction = directionOf(typeof value === "number" ? Math.round(value * 100) / 100 : value);

	return (
		<span className={cx("ucw-delta", pill && "ucw-delta--pill")} data-direction={direction}>
			<TrendIcon direction={direction} />
			{locale ? formatPercent(value, locale) : formatPercent(value)}
		</span>
	);
};
