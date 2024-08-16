import {CoinData} from "../../../types";
import {HTMLProps, useState} from "react";

const Card001 = ({coinData, currency_symbol = "$", ...props} : {coinData: CoinData, currency_symbol?: string} & HTMLProps<HTMLDivElement>) => {
	const [coin, _] = useState(coinData);
	props.className = `ucwp-price-slider-card ${props?.className}`;
	return (
		<div {...props}>
			<div className={`ucwp-price-slider-card-image`}>
				<img src={coin?.image} alt={`${coin?.name} (${coin?.symbol})`} />
			</div>
			<div className={`ucwp-price-slider-card-content`}>
				<div className={`ucwp-price-slider-card-content-child ucwp-price-slider-card-content-title`}>
					{`${coin?.name}`} {`(${coin?.symbol?.toUpperCase()})`}
				</div>
				<div className={`ucwp-price-slider-card-content-child ucwp-price-slider-card-content-price`}>
					{`${currency_symbol}${coin?.current_price}`}
				</div>
			</div>
		</div>
	)
}

export default Card001;