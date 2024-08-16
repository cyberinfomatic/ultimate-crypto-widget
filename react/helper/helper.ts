import {GraphData} from "../types";

export function abbreviateNumber(num: number) {
	if (num >= 1e12) {
		return (num / 1e12).toFixed(1) + 'T';
	}
	if (num >= 1e9) {
		return (num / 1e9).toFixed(1) + 'B';
	}
	if (num >= 1e6) {
		return (num / 1e6).toFixed(1) + 'M';
	}
	if (num >= 1e3) {
		return (num / 1e3).toFixed(1) + 'K';
	}
	return `${num}`;
}

// round of to significant figures
	export function roundToSignificantFigures(num: number, significantFigures: number){
	return parseFloat(num.toPrecision(significantFigures));
}

// generate labels from chartData with the max length of the data array
export function generateLabelArrayFromChartData(chartData: GraphData[]){
	let labels = [];
	chartData.forEach(data => {
		if(data?.data?.length > labels.length){
			labels = data.data.map((_, i) => `${data.label || ''} ${i}`);
		}
	});
	return labels;

}