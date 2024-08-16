import {CoinData} from "../../../types";
import {HTMLProps, useState} from "react";
import {abbreviateNumber} from "../../../helper/helper";

const Card002 = ({coinData, currency_symbol = "$", ...props} : {coinData: CoinData, currency_symbol?: string} & HTMLProps<HTMLDivElement>) => {
	const [coin, setCoin] = useState(coinData);
	props.className = `ucwp-coin-marquee-coin-card-bounding-box crypto-price-card ucwp-marquee-content ${props.className}`;
	return (
		<div {...props}>
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
			<div className="ucwp-coin-marquee-coin-tooltip">
				<div className="ucwp-coin-marquee-coin-tooltip-content">
					<div className="ucwp-coin-marquee-coin-tooltip-content-text">
						<span className="ucwp-coin-marquee-coin-tooltip-content-text-title">24H: </span>
						<span className="ucwp-coin-marquee-coin-tooltip-content-text-value wcp-coin-marquee-coin-growth">
					<i className={`fa ${coin.price_change_percentage_24h > 0 ? 'fa-arrow-up' : 'fa-arrow-down'}`}></i>
					{coin.price_change_percentage_24h.toFixed(2)}%
				</span>
					</div>
					<div className="ucwp-coin-marquee-coin-tooltip-content-text">
						<span className="ucwp-coin-marquee-coin-tooltip-content-text-title">Volume: </span>
						<span
							className="ucwp-coin-marquee-coin-tooltip-content-text-value">{currency_symbol}{abbreviateNumber(coin.total_volume)}</span>
					</div>
					<div className="ucwp-coin-marquee-coin-tooltip-content-text">
						<span className="ucwp-coin-marquee-coin-tooltip-content-text-title">Market Cap: </span>
						<span
							className="ucwp-coin-marquee-coin-tooltip-content-text-value">{currency_symbol}{abbreviateNumber(coin.market_cap)}</span>
					</div>
				</div>
			</div>
		</div>
	)
}

export default Card002;