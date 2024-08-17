export type UCWPWidgetSetting = {
	type : string,
	count ?: number,
	parent_width ?: number | `${number}%` | 'auto' | `${number}px`,
	card_width ?: number //| `${number}%` | 'auto' | `${number}px`,
	no_of_days ?: number,
	data_interval ?: 'daily' | 'weekly' | 'monthly',
	default_currency ?: string,
	speed ?: number,
	card ?: `card-${number}`,
	currency_symbol ?: string,
	orientation ?: 'horizontal' | 'vertical',
	coins :  string | string[],
	dark_mode ?: 'true' | 'false' | boolean,
}

export interface CoinData {
	id: string;
	symbol: string;
	name: string;
	image: string;
	current_price: number;
	market_cap: number;
	market_cap_rank: number;
	fully_diluted_valuation: number | null;
	total_volume: number;
	high_24h: number;
	low_24h: number;
	price_change_24h: number;
	price_change_percentage_24h: number;
	market_cap_change_24h: number;
	market_cap_change_percentage_24h: number;
	circulating_supply: number;
	total_supply: number;
	max_supply: number | null;
	ath: number;
	ath_change_percentage: number;
	ath_date: string;
	atl: number;
	atl_change_percentage: number;
	atl_date: string;
	roi: number | {
		times: number;
		currency: string;
		percentage: number;
	} | null;
	last_updated: string;
	price_change_percentage_7d_in_currency?: number;
	price_change_percentage_30d_in_currency?: number;
}

export type GraphDataSetSettings = {
	[key: string]: any
}

export type GraphData = {
	id: number | string,
	label?: string,
	data: any[],
} & GraphDataSetSettings


export type WP_API_PATHS = {
	method : 'GET' | 'POST' ,
	endpoint : string,
	required_params ?: string[],
	required_body_params ?: string[],
	headers ?: Headers,
}
// export t