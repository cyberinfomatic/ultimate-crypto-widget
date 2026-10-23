import React from "react";
import { cx } from "../cx";
import { formatPrice, PriceOptions } from "../format";
import { useMovement } from "./useMovement";

type PriceProps = PriceOptions & {
	value: unknown;
	size?: "md" | "lg";
};

/** A price in the visitor's number format, tinted briefly when a live update changes it. */
export const Price = ({ value, size = "md", ...options }: PriceProps) => {
	const moved = useMovement(value);

	return (
		<span className={cx("ucw-price", size === "lg" && "ucw-price--lg")} data-moved={moved === "flat" ? undefined : moved}>
			{formatPrice(value, options)}
		</span>
	);
};
