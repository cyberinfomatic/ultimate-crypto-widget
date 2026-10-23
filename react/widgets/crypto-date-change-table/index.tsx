import React from "react";
import { __ } from "@wordpress/i18n";
import { Delta, Price } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { PagedCoinTable } from "../../parts/PagedCoinTable";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** The price table with each coin's change over a day, a week and a month. */
export const CryptoDateChangeTable = ({ coins, settings }: Props) => (
	<PagedCoinTable
		coins={coins}
		settings={settings}
		columns={[
			{ header: __("Price", "ultimate-crypto-widget"), render: (coin) => <Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} /> },
			{ header: __("24h", "ultimate-crypto-widget"), render: (coin) => <Delta value={coin.price_change_percentage_24h} /> },
			{ header: __("7d", "ultimate-crypto-widget"), render: (coin) => <Delta value={coin.price_change_percentage_7d_in_currency} /> },
			{ header: __("30d", "ultimate-crypto-widget"), render: (coin) => <Delta value={coin.price_change_percentage_30d_in_currency} /> },
		]}
	/>
);

ReactRender<Props>(CryptoDateChangeTable);
