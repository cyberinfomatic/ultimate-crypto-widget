// Base interface with common properties
export interface BasicCoinData {
  symbol: string;
  current_price: number;
  high_24h: number;
  low_24h: number;
  price_change_24h: number;
  price_change_percentage_24h: number;
  total_volume: number;
  last_updated: string;
}

// TickerData type, which is equivalent to BasicCoinData
export type TickerData = BasicCoinData;

// CoinData interface, which extends BasicCoinData with additional properties
export interface CoinData extends BasicCoinData {
  id: string;
  name: string;
  image: string;
  market_cap: number;
  market_cap_rank: number;
  fully_diluted_valuation: number | null;
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
  price_change_percentage_7d_in_currency?: number;
  price_change_percentage_30d_in_currency?: number;
}

// UCWPWidgetSetting type
export type UCWPWidgetSetting = {
  type: string;
  count?: number;
  parent_width?: number | `${number}%` | 'auto' | `${number}px`;
  card_width?: number;
  no_of_days?: number;
  data_interval?: 'daily' | 'weekly' | 'monthly';
  default_currency?: string;
  speed?: number;
  card?: `card-${number}`;
  currency_symbol?: string;
  orientation?: 'horizontal' | 'vertical';
  coins: string | string[];
  dark_mode?: 'true' | 'false' | boolean;
  usd_conversion_rate?: number;
};

// GraphDataSetSettings type for additional graph settings
export type GraphDataSetSettings = {
  [key: string]: any;
};

// GraphData type, which extends GraphDataSetSettings
export type GraphData = {
  id: number | string;
  label?: string;
  data: any[];
} & GraphDataSetSettings;

// WP_API_PATHS type for defining API paths
export type WP_API_PATHS = {
  method: 'GET' | 'POST';
  endpoint: string;
  required_params?: string[];
  required_body_params?: string[];
  headers?: Headers;
};

export type DefaultCurrencyRateByCurrency = {
  [key : string]: number;
};
export type currenciesSymbol = {
  [key : string]: string;
};
