import {HTMLProps, useState, useEffect} from "react";
import '@/styles/sass/historical-price-chart.scss'
import {UCWPWidgetSetting, CoinData, GraphData} from "../../types";
import ReactRender from "../../helper-components/react-wrapper";
import Chart from "chart.js/auto";
import { CategoryScale } from "chart.js";
import {abbreviateNumber, roundToSignificantFigures} from "../../helper/helper";
import Graph from "../../helper-components/Graph";
import {ucwpAPIV1} from "../../helper/api-helper";
import React from "react";


Chart.register(CategoryScale);



const Card = ({coinData, currency_symbol = "$", no_of_days = 7,  max_point_graph = 15,...props} : {coinData: CoinData, currency_symbol?: string, no_of_days ?: number|string,  max_point_graph ?: number|string} & HTMLProps<HTMLDivElement>)  => {
	const [graphData, setGraphData] = useState<GraphData[]>([]);
	const [graphLabels, setGraphLabels] = useState<string[]>([]); 
	const [graphFetchCount, setGraphFetchCount] = useState(0);
	const graphColor = coinData.price_change_percentage_24h > 0 ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)'
	// parse no of days and max point graph to integer if string is passed
	no_of_days = typeof no_of_days === 'string' ? parseInt(no_of_days) : no_of_days;
	max_point_graph = typeof max_point_graph === 'string' ? parseInt(max_point_graph) : max_point_graph;
	const defaultDataSetSettings = {
		backgroundColor: 'rgba(255, 99, 132, 0.2)',
		borderColor: graphColor,
		borderWidth: 1,
		pointRadius: 6,
		pointHitRadius: 8,
		pointHoverRadius: 8,
		fill : {
			target: 'origin',
			below: graphColor
		}
	}

	const GraphOptions = {
		maintainAspectRatio: false,
		scales: {
			y: {
				display: true, // Hide the y-axis
				ticks: {
					callback: function(value: number, index: number, values: number[]) {
						let numStr = `${currency_symbol}`;
						// if no lesser than 1 round of to significant figures
						if(value < 1){
							numStr += roundToSignificantFigures(value, 3);
						}
						// if greater than >= 1m abbreviate the number
						else if(value >= 1e6){
							numStr += abbreviateNumber(value);
						}
						else {
							// number with separator
							numStr += value.toLocaleString();
						}
						return numStr;
					}
				}
			},
			x : {
				display: false
			}
		},
	}

	// use effect to update the graph data from api /wp-json/ultimate-crypto-widget/v1/coin-chart-data?coin_id={coin.id} with axios
	useEffect(() => {
		console.log("useEffect called");
		ucwpAPIV1
			.fetchData<{ prices: [number, number][] }>("coin-chart-data", {
				coin_id: coinData.id,
				days: no_of_days,
			})
			.then((data) => {
				let newData: ([number, number] | null)[] = data?.prices?.map(
					(_price) => {
						const time = new Date(_price?.[0]);
						const date = new Date();
						date.setDate(date.getDate() - no_of_days);
						_price[1] = roundToSignificantFigures(_price[1], 3);
						if (time >= date) {
							return _price;
						}
						return null;
					},
				);
				// remove all duplicate price point and remove null values
				newData = newData
					?.filter((price, index) => {
						if (!price) {
							return false;
						}
						return (
							newData.findIndex(
								(_price) => _price?.[0] === price?.[0],
							) === index
						);
					})
					.slice(0, max_point_graph);

				if (!newData) {
					throw new Error("No data found");
				}
				const prices = newData?.map((price) => price?.[1] || 0);
				const days_time = newData?.map((price) => {
					const time = new Date(price![0]);
					return time.toLocaleDateString();
				});
				// co0nsole.log prices
				setGraphData(() => {
					return [
						{
							id: coinData.id,
							label: coinData.name,
							data: prices,
						},
					];
				});
				setGraphLabels(() => {
					return days_time;
				});
			})
			.catch((error) => {
				console.error(error, "error", graphFetchCount);
				if (graphFetchCount < 3) {
					// sleep for 4 seconds and try again
					setTimeout(
						() => {
							setGraphFetchCount((prev) => prev + 1);
						},
						4000 * (graphFetchCount + 1),
					);
				}
			});

	}, [coinData, graphFetchCount]);

	props.className = `ucwp-historical-price-chart-card ${props?.className}`;
	return (
		<div {...props}>
			<div className={'ucwp-historical-price-chart-backdrop'} >
				<div className={`ucwp-hpc-bd-image-holder`}>
					<img src={coinData.image} alt={coinData.name} />
				</div>
			</div>
			<div className={'ucwp-historical-price-chart-content'}>
				<div className={`ucwp-hpc-content-header`}>
				{/*	coin title and symbol*/}
					<div className={`ucwp-hpc-ch-title`}>
						<span>{coinData.name} ({coinData.symbol.toUpperCase()}) </span>
					</div>
				{/*	section for market details */}
					<div className={`ucwp-hpc-ch-market-details`}>
						<div className={`ucwp-hpc-ch-md-item-cnt`}>
							<div className={`ucwp-hpc-ch-md-item`}>
								<span>Market Cap:</span>
								<span>{currency_symbol}{abbreviateNumber(coinData.market_cap)}</span>
							</div>
							<div className={`ucwp-hpc-ch-md-item`}>
								<span>Price:</span>
								<span>{currency_symbol}{coinData.current_price}</span>
							</div>
						</div>
					</div>
				</div>
				<div className={`ucwp-hpc-content-small-details`}>
					<div className={`ucwp-hpc-csd-item`}>
						<span>Price:</span>
						<span>{currency_symbol}{coinData.current_price}</span>
					</div>
					<div className={`ucwp-hpc-csd-item`}>
						<span>Volume:</span>
						<span>{currency_symbol}{abbreviateNumber(coinData.total_volume)}</span>
					</div>
				</div>
				<div className={`ucwp-hpc-content-chart`}>
					<Graph  chartData={graphData} className="ucwp-hpc-cc-chart" labels={graphLabels} defaultDataSetSettings={defaultDataSetSettings} options={GraphOptions} />
				</div>
			</div>
		</div>
	)
}
ReactRender(({ coins, settings }: { coins: CoinData[], settings: UCWPWidgetSetting }) => {
	const [coinList, _] = useState<CoinData[]>(coins ?? []);
	const parentWidth = typeof settings.parent_width === 'number' ? `${settings.parent_width}px` : settings.parent_width;
	const darkMode = settings?.dark_mode == 'true';
	let coin = settings.coins;
	console.log(coin)
	return (
		<div className="ucwp-historical-price-chart-widget" style={{ width: parentWidth }}>
			{
				coinList.map((coin, index) => (
					<Card key={index} coinData={coin} className={darkMode ? 'ucwp-his-dark' : ''} no_of_days={settings.no_of_days} currency_symbol={settings.currency_symbol} />
				))
			}
		</div>
	);
})


