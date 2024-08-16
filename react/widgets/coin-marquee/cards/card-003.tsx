import {CoinData, GraphData} from "../../../types";
import {HTMLProps, useEffect, useState} from "react";
import {abbreviateNumber} from "../../../helper/helper";
import Graph from "../../../helper-components/Graph";
import {UCWPAPIV1} from "../../../helper/api-helper";


const Card003 = ({coinData, currency_symbol = "$", graph_days_count = 7, max_point_graph = 15, ...props} : {coinData: CoinData, currency_symbol?: string, graph_days_count ?: number, max_point_graph ?: number} & HTMLProps<HTMLDivElement>) => {
	const [coin, setCoin] = useState(coinData);
	const [graphData, setGraphData] = useState<GraphData[]>([]);
	const [graphLabels, setGraphLabels] = useState([]);
	const [graphFetchCount, setGraphFetchCount] = useState(0);

	// use effect to update the graph data from api /wp-json/ultimate-crypto-widget/v1/coin-chart-data?coin_id={coin.id} with axios
	useEffect(() => {
		console.log("useEffect called");
		UCWPAPIV1.fetchData<{prices : [number, number][]}>('coin-chart-data', {coin_id :coinData.id, days:graph_days_count}).then((data) => {
			let newData : ([number, number] | null)[] = data?.prices?.map((_price: [number , number]) => {
				const time = new Date(_price?.[0]);
				const date = new Date();
				date.setDate(date.getDate() - graph_days_count);
				if(time >= date){
					return _price;
				}
				return null;
			})
			newData = newData?.filter((price) => price).slice(0, max_point_graph);
			if(!newData){
				throw new Error("No data found");
			}
			const prices = newData?.map((price: [number, number]) => price?.[1] || 0);
			const days_time = newData?.map((price: [number, number]) => {
				const time = new Date(price?.[0]);
				return time.toLocaleDateString();
			});
			setGraphData(() => {
				return [
					{
						id: coin.id,
						label: coin.name,
						data: prices,
					}
				]
			});
			setGraphLabels(() => {
				return days_time;
			});
		}).catch((error) => {
			console.error(error, "error", graphFetchCount);
			if (graphFetchCount < 3) {
				// sleep for 4 seconds and try again
				setTimeout(() => {
					setGraphFetchCount((prev) => prev + 1);
				}, 4000 * (graphFetchCount + 1));
			}
		});

	}, [coinData, graphFetchCount]);

	const defaultDataSetSettings = {
		backgroundColor: 'rgba(255, 99, 132, 0.2)',
		borderColor: coin.price_change_percentage_24h > 0 ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)',
		borderWidth: 1,
		// 	remove the point on the graph
		pointRadius: 0,
		pointHitRadius: 0,
		pointHoverRadius: 0,
		pointHoverBorderWidth: 0,
		pointBorderWidth: 0
	}
	props.className = `ucwp-coin-marquee-coin-card-bounding-box crypto-price-card ucwp-marquee-content ${props.className || ''}`;
	return (
		<div  {...props}>
			<div className="ucwp-coin-marquee-coin-logo">
				<img src={coin.image} alt={coin.name}/>
			</div>
			<Graph  chartData={graphData} className="ucwp-coin-marquee-coin-graph" labels={graphLabels} defaultDataSetSettings={defaultDataSetSettings} />
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

export default Card003;