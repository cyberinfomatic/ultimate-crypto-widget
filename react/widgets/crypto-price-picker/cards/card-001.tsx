import {CoinData} from "../../../types";
import {HTMLProps, useState} from "react";

const Card001 = ({coinData, currency_symbol = "$", ...props} : {coinData: CoinData, currency_symbol?: string} & HTMLProps<HTMLDivElement>) => {
	const [coin, setCoin] = useState(coinData);
	props.className = `crypto-picker-card-coin-bounding-box ${props.className}`;
	return (
		<div  {...props}>
			<div className="crypto-picker-card-content-cnt">
				<div className="crypto-picker-card-bounding-box-coin-logo">
					<img src={coin?.image} alt={coin?.name}/>
				</div>
				<div className="crypto-picker-card-bounding-box-coin-info">
					<div className="crypto-picker-card-coin-name">
						<span>{coin?.name}({coin?.symbol})</span>
						<div className="crypto-picker-card-coin-growth">
							<i className={`fa-solid ${coin.price_change_percentage_24h > 0 ? 'fa-arrow-up' : 'fa-arrow-down'}`}></i>
							<span>{coin?.price_change_percentage_24h}%</span>
						</div>
					</div>
					<div>
						<span
							className="crypto-picker-card-coin-price">{currency_symbol}{coin?.current_price}</span>
					</div>
				</div>
			</div>
		</div>
	)
}

export default Card001;