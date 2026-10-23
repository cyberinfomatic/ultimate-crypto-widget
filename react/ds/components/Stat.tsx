import React, { ReactNode } from "react";

/** A figure with its name: "Market cap  $1.7T". */
export const Stat = ({ label, children }: { label: string; children: ReactNode }) => (
	<span className="ucw-stat">
		<span className="ucw-stat__label">{label}</span>
		<span className="ucw-stat__value">{children}</span>
	</span>
);
