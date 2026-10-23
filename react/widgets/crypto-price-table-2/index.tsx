import React from "react";
import { __ } from "@wordpress/i18n";
import { Delta, formatNumber, formatPrice, Price } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { PagedCoinTable } from "../../parts/PagedCoinTable";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** The price table with search, pages, market cap, volume and supply. */
export const CryptoPriceTable2 = ({ coins, settings }: Props) => {
	const money = { currency: settings.default_currency, symbol: settings.currency_symbol };

	return (
		<PagedCoinTable
			coins={coins}
			settings={settings}
			columns={[
				{ header: __("Price", "ultimate-crypto-widget"), render: (coin) => <Price value={coin.current_price} {...money} /> },
				{ header: __("24h", "ultimate-crypto-widget"), render: (coin) => <Delta value={coin.price_change_percentage_24h} /> },
				{ header: __("Market cap", "ultimate-crypto-widget"), render: (coin) => formatPrice(coin.market_cap, { ...money, compact: true }) },
				{ header: __("Volume (24h)", "ultimate-crypto-widget"), render: (coin) => formatPrice(coin.total_volume, { ...money, compact: true }) },
				{ header: __("Supply", "ultimate-crypto-widget"), render: (coin) => formatNumber(coin.circulating_supply, { compact: true }) },
			]}
		/>
	);
};

ReactRender<Props>(CryptoPriceTable2);
