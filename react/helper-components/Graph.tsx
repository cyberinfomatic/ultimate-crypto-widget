import {HTMLProps} from "react";
import { Chart  } from "react-chartjs-2";
import {generateLabelArrayFromChartData} from "../helper/helper";
import {GraphData, GraphDataSetSettings} from "../types";
import React from "react";





export default function Graph({chartData = [], labels = [], options ,  defaultDataSetSettings = {}, ...props} : {chartData : GraphData[], labels?: string[], defaultDataSetSettings?: GraphDataSetSettings, options ?: any} & HTMLProps<HTMLDivElement>){
	if (labels.length === 0 && chartData.length > 0) {
		labels = generateLabelArrayFromChartData(chartData);
	}
	const chartJsData = {
		labels: labels,
		datasets: chartData.map((data) => {
			return {
				...defaultDataSetSettings,
				...data
			}
		})
	}

	const GraphOptions = {
		responsive: true,
		maintainAspectRatio: true,
		plugins: {
			legend: {
				display: false, // Hide the legend
			},
		},
		scales: {
			x: {
				display: false, // Hide the x-axis
			},
			y: {
				display: false, // Hide the y-axis
			},
		},
		...options
	}
	return <div {...props}>
		<Chart  type={"line"} data={chartJsData} width={'100%'} height={'100%'} options={GraphOptions} />
	</div>
}