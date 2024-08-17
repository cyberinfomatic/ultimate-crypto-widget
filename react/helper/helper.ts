import {CoinData, GraphData} from "../types";

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
	let labels : string[] = [];
	chartData.forEach(data => {
		if(data?.data?.length > labels.length){
			labels = data.data.map((_, i) => `${data.label || ''} ${i}`);
		}
	});
	return labels;

}


export function levenshteinDistance(str1, str2) {
		const len1 = str1.length;
		const len2 = str2.length;
		const dp = Array.from(Array(len1 + 1), () => Array(len2 + 1).fill(0));

		for (let i = 0; i <= len1; i++) {
			for (let j = 0; j <= len2; j++) {
				if (i === 0) {
					dp[i][j] = j;
				} else if (j === 0) {
					dp[i][j] = i;
				} else if (str1[i - 1] === str2[j - 1]) {
					dp[i][j] = dp[i - 1][j - 1];
				} else {
					dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
				}
			}
		}

		return dp[len1][len2];
}
	
export function searchCoin(needle: string, coins: CoinData[], minLevenshteinDistance: number = 3) {
    const value = needle.toLowerCase();
    // if empty then show all coins
	if (value.length === 0) {
		return coins;
    }
    const filtered = coins.filter((coin) => {
      const nameDistance = levenshteinDistance(
        coin.name.toLowerCase(),
        value.toLowerCase()
      );
      const symbolDistance = levenshteinDistance(
        coin.symbol.toLowerCase(),
        value.toLowerCase()
      );
      return nameDistance <= minLevenshteinDistance || symbolDistance <= minLevenshteinDistance;
    });
    // sort my which has the highest match
    // Combined sort function by both name and symbol distance
    filtered.sort((a, b) => {
      const nameDistanceA = levenshteinDistance(a.name.toLowerCase(), value);
      const nameDistanceB = levenshteinDistance(b.name.toLowerCase(), value);
      const symbolDistanceA = levenshteinDistance(
        a.symbol.toLowerCase(),
        value
      );
      const symbolDistanceB = levenshteinDistance(
        b.symbol.toLowerCase(),
        value
      );

      return (
        nameDistanceA + symbolDistanceA - (nameDistanceB + symbolDistanceB)
      );
    });
    return filtered;
};
  


// function to handle copying to clipboard
export function copyToClipboard(text: string) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    textArea.remove();
}