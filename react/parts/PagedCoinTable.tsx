import React, { ReactNode, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { Cell, CoinIdentity, filterByText, Notice, pageOf, Pager, Row, SearchField, Surface, Table, toCount } from "../ds";
import { useLiveCoins } from "../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../types";
import "./paged-coin-table.scss";

export type CoinColumn = {
	header: string;
	render: (coin: CoinData) => ReactNode;
};

type PagedCoinTableProps = {
	coins: CoinData[];
	settings: UCWPWidgetSetting & { search_placeholder?: string };
	/** The columns after rank and name. All hold figures. */
	columns: CoinColumn[];
};

/**
 * A searchable, paged list of coins. The "count" setting is the page size; the
 * widgets that use this differ only in the columns they pass.
 */
export const PagedCoinTable = ({ coins, settings, columns }: PagedCoinTableProps) => {
	const [typed, setTyped] = useState("");
	const [page, setPage] = useState(0);

	const all = Array.isArray(coins) ? coins : [];
	const pageSize = toCount(settings.count, 10);
	const found = filterByText(all, typed, (coin) => [coin.name, coin.symbol]);
	// Live prices are followed for the coins on screen only.
	const shown = useLiveCoins(pageOf(found, page, pageSize), { count: pageSize, usd_conversion_rate: settings.usd_conversion_rate, live_prices: settings.live_prices });

	const search = (value: string) => {
		setTyped(value);
		setPage(0);
	};

	return (
		<Surface width={settings.parent_width}>
			<div className="ucw-paged__bar">
				<SearchField label={__("Search coins", "ultimate-crypto-widget")} placeholder={settings.search_placeholder || __("Search coins", "ultimate-crypto-widget")} value={typed} onChange={search} />
			</div>
			{shown.length === 0 ? (
				<Notice>{all.length === 0 ? __("Prices are not available right now.", "ultimate-crypto-widget") : __("No coin matches your search.", "ultimate-crypto-widget")}</Notice>
			) : (
				<Table label={__("Cryptocurrency prices", "ultimate-crypto-widget")} columns={`auto minmax(9rem, 1fr) repeat(${columns.length}, auto)`}>
					<Row header>
						<Cell header>#</Cell>
						<Cell header>{__("Name", "ultimate-crypto-widget")}</Cell>
						{columns.map((column) => (
							<Cell header numeric key={column.header}>
								{column.header}
							</Cell>
						))}
					</Row>
					{shown.map((coin) => (
						<Row key={coin.id}>
							<Cell muted>{all.findIndex((other) => other.id === coin.id) + 1}</Cell>
							<Cell>
								<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							</Cell>
							{columns.map((column) => (
								<Cell numeric key={column.header}>
									{column.render(coin)}
								</Cell>
							))}
						</Row>
					))}
				</Table>
			)}
			<Pager
				labels={{
					/* translators: 1: current page number, 2: number of pages. */
					status: (current, pages) => sprintf(__("Page %1$d of %2$d", "ultimate-crypto-widget"), current, pages),
					previous: __("Previous page", "ultimate-crypto-widget"),
					next: __("Next page", "ultimate-crypto-widget"),
				}}
				page={page}
				pageSize={pageSize}
				total={found.length}
				onChange={setPage}
			/>
		</Surface>
	);
};
