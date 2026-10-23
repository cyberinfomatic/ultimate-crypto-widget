import React from "react";
import { cx } from "../cx";
import { cssSize } from "../format";

type SkeletonProps = {
	width?: unknown;
	height?: unknown;
	round?: boolean;
};

/** A grey block standing in for content that is still loading. */
export const Skeleton = ({ width, height, round = false }: SkeletonProps) => (
	<span className={cx("ucw-skeleton", round && "ucw-skeleton--round")} style={{ inlineSize: cssSize(width), blockSize: cssSize(height) }} aria-hidden="true" />
);
