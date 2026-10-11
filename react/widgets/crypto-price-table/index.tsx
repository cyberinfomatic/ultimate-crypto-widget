import React from "react";
import { __ } from "@wordpress/i18n";
import { Cell, CoinIdentity, Delta, Notice, Price, Row, Surface, Table } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** A ranked list of coins: name, price and the last day's change. */
export const CryptoPriceTable = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	return (
		<Surface width={settings.parent_width}>
			{list.length === 0 ? (
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			) : (
				<Table label={__("Cryptocurrency prices", "ultimate-crypto-widget")} columns="auto minmax(0, 1fr) auto auto">
					<Row header>
						<Cell header>#</Cell>
						<Cell header>{__("Name", "ultimate-crypto-widget")}</Cell>
						<Cell header numeric>
							{__("Price", "ultimate-crypto-widget")}
						</Cell>
						<Cell header numeric>
							{__("24h", "ultimate-crypto-widget")}
						</Cell>
					</Row>
					{list.map((coin, index) => (
						<Row key={coin.id}>
							<Cell muted>{index + 1}</Cell>
							<Cell>
								<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							</Cell>
							<Cell numeric>
								<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
							</Cell>
							<Cell numeric>
								<Delta value={coin.price_change_percentage_24h} />
							</Cell>
						</Row>
					))}
				</Table>
			)}
		</Surface>
	);
};

ReactRender<Props>(CryptoPriceTable);
