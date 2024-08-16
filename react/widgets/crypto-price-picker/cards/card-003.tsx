import {CoinData} from "../../../types";
import {HTMLProps, useState} from "react";
import {abbreviateNumber} from "../../../helper/helper";
const Card003 = ({coinData, currency_symbol = "$", ...props} : {coinData: CoinData, currency_symbol?: string} & HTMLProps<HTMLDivElement>) => {
	const [coin, setCoin] = useState(coinData);
	props.className = `crypto-picker-card-coin-bounding-box-card-003 ${props.className}`;
	return (
		<div {...props}>
				<div className="crypto-picker-card-003-section crypto-picker-card-003-basic-details">
					<div className="crypto-picker-card-bounding-box-coin-logo">
						<img src={coin.image} alt={coin.name}/>
					</div>
					<div className="crypto-picker-card-003-basic-info">
						<div className="crypto-picker-card-003-coin-name">
							<div className="crypto-picker-card-coin-name-and-symbol">
								<span>{coin.name}</span>
								<span className="crypto-picker-card-coin-symbol">({coin.symbol})</span>
							</div>
							<div className="crypto-picker-card-coin-growth">
								<i className={`fa-solid ${coin.price_change_percentage_24h > 0 ? 'fa-arrow-up' : 'fa-arrow-down'}`}></i>
								<span>{coin.price_change_percentage_24h}%</span>
							</div>
						</div>
						<div className="crypto-picker-card-coin-price">
							<span>{currency_symbol}{coin.current_price}</span>
						</div>
					</div>
				</div>
				<div className="crypto-picker-card-003-section crypto-picker-card-003-market-details">
					<div className="crypto-picker-card-003-market-cap">
						<span>{currency_symbol}{abbreviateNumber(coin?.market_cap)}</span>
					</div>
					<div className="crypto-picker-card-003-volume">
						<span>{currency_symbol}{abbreviateNumber(coin?.total_volume)}</span>
					</div>
				</div>
		</div>
	)
}

export default Card003;