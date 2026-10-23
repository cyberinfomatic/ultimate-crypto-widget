import React from "react";
import { cx } from "../cx";

type CoinIdentityProps = {
	name: string;
	symbol: string;
	image?: string;
	/** Logo and ticker symbol only, on one line. */
	compact?: boolean;
};

/** A coin's logo, name and ticker symbol. */
export const CoinIdentity = ({ name, symbol, image, compact = false }: CoinIdentityProps) => (
	<span className={cx("ucw-coin", compact && "ucw-coin--compact")}>
		{/* The name is written next to the logo, so the image itself says nothing more. */}
		{image ? <img className="ucw-coin__logo" src={image} alt="" loading="lazy" decoding="async" /> : <span className="ucw-coin__logo" aria-hidden="true" />}
		<span className="ucw-coin__text">
			{!compact && <span className="ucw-coin__name">{name}</span>}
			<span className="ucw-coin__symbol" title={compact ? name : undefined}>
				{symbol}
			</span>
		</span>
	</span>
);
