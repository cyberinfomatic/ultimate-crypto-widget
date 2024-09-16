import React, { useEffect, useState } from "react";
import ReactRender from "../../helper-components/react-wrapper";
import '@/styles/sass/crypto-price-table.scss'
import { roundToDecimalPlaces, searchCoin } from "../../helper/helper";
import { CoinData } from "../../types";
import PricePercentage from "../../helper-components/PricePercentage";
import useBinanceStreamTickerWebSocket from "../../helper-components/WebHooks/BinanceStreamTicker";

ReactRender(({ coins, settings }) => {
  settings.count = parseInt(settings.count ?? "10");
  const [coinList, setCoinList] = useState<CoinData[]>(coins ?? []); // Initialize with props
  const [startCount, setStartCount] = useState<number>(0);
  const { data } = useBinanceStreamTickerWebSocket(
    coinList?.map((coin) => coin.symbol).slice(0, settings.count),
    settings?.usd_conversion_rate ?? 1
  );


  useEffect(() => {
    setCoinList(coins.slice(startCount, startCount + (settings.count ?? 10)));
  }, [startCount]);
  


  let width = settings.parent_width;
  // if width does not end with % or px then add px
  if (!width.endsWith('%') &&!width.endsWith('px')) {
    width += 'px';
  }
  return (
    <div className="ucwp-crypto-price-table" style={{ width: width }}>
      <div className="ucwp-crypto-price-table-main">
        <table className={`ucwp-crypto-price-table-main-table`}>
          <thead>
            <tr>
              <th></th>
              <th>#Name</th>
              <th>Price </th>
              <th>24H Change</th>
            </tr>
          </thead>
          <tbody>
            {coinList
              .slice(0, (settings.count ?? 10))
              .map((_coin, index) => {
                const coin = { ..._coin, ...data[_coin.symbol.toUpperCase()] };
                return (
                  <tr
                    key={index}
                    data-table-row-coin={`${coin.name}---${coin.symbol}`}
                  >
                    <td>{index + 1 + startCount}</td>
                    <td>
                      <div className="crypto-price-table-name-info">
                        <div className="crypto-price-table-name-info-image">
                          <img src={coin.image} alt={coin.name} />
                        </div>
                        <div className="crypto-price-table-name-info-name">
                          <span>{coin.name}</span>
                          <span>{coin.symbol}</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      {settings.currency_symbol}
                      {roundToDecimalPlaces(coin.current_price, 2)}
                    </td>
                    <td>
                      <PricePercentage
                        percentage={coin.price_change_percentage_24h}
                        arrowSize={12}
                      />
                    </td>
                  </tr>
                );})}
          </tbody>
        </table>
      </div>
    </div>
  );
})
