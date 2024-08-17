import React from "react";
import {CoinData} from "../../../types";
import {HTMLProps, useState} from "react";

const Card001 = ({coinData, currency_symbol = "$", ...props} : {coinData: CoinData, currency_symbol?: string} & HTMLProps<HTMLDivElement>) => {
	const [coin, setCoin] = useState(coinData);
	props.className = `ucwp-coin-marquee-coin-card-bounding-box ucwp-marquee-content ${props.className}`;
	return (
		<div  {...props}>
			<div className="ucwp-coin-marquee-coin-logo">
				<img src={coin.image} alt={coin.name}/>
			</div>
			<div className="ucwp-coin-marquee-main-coin-basic-info">
				<div className="ucwp-coin-marquee-coin-name-and-symbol">
					<span className="ucwp-coin-marquee-coin-name">{coin.name}</span>
					<span className="ucwp-coin-marquee-coin-symbol">({coin.symbol.toUpperCase()})</span>
				</div>
				<div className="ucwp-coin-marquee-coin-price">
					<span className="ucwp-coin-marquee-coin-price-text">{currency_symbol}{coin.current_price}</span>
				</div>
				<div className="wcp-coin-marquee-coin-growth">
					<i className={`fa ${coin.price_change_percentage_24h > 0 ? 'fa-arrow-up' : 'fa-arrow-down'}`}></i>
					<span
						className="ucwp-coin-marquee-coin-growth-text">{coin.price_change_percentage_24h.toFixed(2)}%</span>
				</div>
			</div>
		</div>
	)
}

export default Card001;