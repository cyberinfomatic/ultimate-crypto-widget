import React, { ReactNode } from "react";

/** A short message shown in place of a widget's content: nothing to show, or it failed. */
export const Notice = ({ children }: { children: ReactNode }) => (
	<p className="ucw-notice" role="status">
		{children}
	</p>
);
