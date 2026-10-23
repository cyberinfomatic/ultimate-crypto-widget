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

/** How much one unit of a widget's own currency is in each other currency, by code. */
export type DefaultCurrencyRateByCurrency = {
  [key : string]: number;
};
