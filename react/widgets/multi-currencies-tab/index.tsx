import React, { useEffect, useState } from "react";
import ReactRender from "../../helper-components/react-wrapper";
import '@/styles/sass/multi-currencies-tab.scss'
import { CoinData, currenciesSymbol, DefaultCurrencyRateByCurrency, UCWPWidgetSetting } from "react/types";
import PricePercentage from "react/helper-components/PricePercentage";
import { useBinanceStreamTickerWebSocket } from "react/helper-components/WebHooks";
import { abbreviateNumber } from "react/helper/helper";

ReactRender(
  ({
    coins,
    settings,
    default_currencies_rate,
    default_currencies_symbol,
  }: {
    coins: CoinData[];
    settings: UCWPWidgetSetting;
    default_currencies_rate: DefaultCurrencyRateByCurrency;
    default_currencies_symbol: currenciesSymbol;
  }) => {
    const [coinList, setCoinList] = useState<CoinData[]>(coins ?? []);
    // State for current selected currency and its rate
    const [currentCurrency, setCurrentCurrency] = useState<string>(
      settings?.default_currency || "USD"
    );
    const [currentRate, setCurrentRate] = useState<number>(
      default_currencies_rate?.[settings?.default_currency ?? "usd"] ?? 1
    );

    // symbol 
    const [symbol, setSymbol] = useState<string>(
      settings?.currency_symbol ??
        "$" ??
        default_currencies_symbol[currentCurrency.toLowerCase()]
    );

    const { connected, data, error } = useBinanceStreamTickerWebSocket(
      coinList?.map((coin) => coin.symbol).slice(0, settings.count),
      settings?.usd_conversion_rate ?? 1
    );

    // Update currentRate when currentCurrency changes
    useEffect(() => {
      const newRate =
        default_currencies_rate[currentCurrency.toLowerCase()] ??
        default_currencies_rate[currentCurrency.toUpperCase()] ?? 1;
      setCurrentRate(newRate);
      setSymbol(default_currencies_symbol[currentCurrency.toLowerCase()] ?? default_currencies_symbol[currentCurrency.toUpperCase()] ?? "$");
    }, [currentCurrency, default_currencies_rate]);
    // currencies array and remove duplicates, null, and undefined values
    const currencies: string[] = [
      settings.default_currency,
      ...Object.keys(default_currencies_rate),
    ].filter(
      (value, index, self): value is string =>
        value !== undefined && self.indexOf(value) === index
    );

    return (
      <div className="ucwp-multi-currencies-tab">
        <div className="ucwp-multi-currencies-flat-holder">
          {currencies.map((currency) => {
            return (
              <button
                className={`ucwp-mc-fh-button ${currency === currentCurrency ? "ucwp-mc-fh-button-active" : ""}`}
                key={currency}
                onClick={() => setCurrentCurrency(currency)}
              >
                {currency?.toUpperCase()} {/* Add safe access operator */}
              </button>
            );
          })}
        </div>
        <div className="ucwp-mct-table-main">
          <table className="ucwp-mct-main-table">
            <tbody>
              {coinList.slice(0, settings.count ?? 10).map((_coin, index) => {
                const coin = { ..._coin, ...data[_coin.symbol.toUpperCase()] };
                return (
                  <tr
                    key={index}
                    data-table-row-coin={`${coin.name}---${coin.symbol}`}
                  >
                    <td className="ucwp-mct-rank">{index + 1}</td>
                    <td className="ucwp-mct-name">
                      <div className="ucwp-mct-name-info">
                        <div className="ucwp-mct-name-info-image">
                          <img src={coin.image} alt={coin.name} />
                        </div>
                        <div className="ucwp-mct-name-info-name">
                          <span>{coin.name}</span>
                          <div className="ucwp-mct-name-sybmol">
                            <span>{coin.symbol}</span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="ucwp-mct-price">
                      {symbol}
                      {abbreviateNumber(
                        coin.current_price * currentRate,
                        10e5,
                        true
                      )}
                    </td>
                    <td className="ucwp-mct-change">
                      <PricePercentage
                        percentage={coin.price_change_percentage_24h}
                      />
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    );
  }
);

