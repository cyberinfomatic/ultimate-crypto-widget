import {useState} from "react";
import '@/styles/sass/crypto-price-picker.scss'
import {UCWPWidgetSetting, CoinData} from "../../types";
import ReactRender from "../../helper-components/react-wrapper";
import Card001 from "./cards/card-001";
import Card002 from "./cards/card-002";
import Card003 from "./cards/card-003";

import Chart from "chart.js/auto";
import { CategoryScale } from "chart.js";

Chart.register(CategoryScale);
const getCard = (card: string) => {
	switch (card) {
		case 'card-001':
			return Card001;
		case 'card-002':
			return Card002;
		case 'card-003':
			return Card003
		default:
			return Card001;
	}
}
ReactRender(({ coins, settings }: { coins: CoinData[], settings: UCWPWidgetSetting }) => {
	const [coinList, setCoinList] = useState<CoinData[]>(coins ?? []); // Initialize with props
	const Card = getCard(settings.card ?? 'card-001');

	return (
		<div className="crypto-price-picker-main" style={{ flexDirection : settings?.orientation == 'horizontal' ? 'row' : 'column' }}>
			{coinList.slice(0, settings.count).map((coin, index) => (
				<Card key={coin.id} coinData={coin} style={{  }} currency_symbol={settings.currency_symbol} />
			))}
		</div>
	);
})

