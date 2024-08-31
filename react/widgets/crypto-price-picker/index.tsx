import { useState } from "react";
import "@/styles/sass/crypto-price-picker.scss";
import { UCWPWidgetSetting, CoinData } from "../../types";
import ReactRender from "../../helper-components/react-wrapper";
import Card from "./cards/card-001";

import { CategoryScale } from "chart.js";
import React from "react";
import useKrakenTickerWebSocket from "../../helper-components/WebHooks/KrakenTicker";

ReactRender(
  ({ coins, settings }: { coins: CoinData[]; settings: UCWPWidgetSetting }) => {
    const [coinList, setCoinList] = useState<CoinData[]>(coins ?? []); // Initialize with props
    const { connected, data, error } = useKrakenTickerWebSocket(
      coinList?.map((coin) => coin.symbol).slice(0, settings.count),
      settings?.usd_conversion_rate ?? 1
    );

    return (
      <div
        className="ucwp-crypto-price-picker-main"
        style={{
          flexDirection:
            settings?.orientation == "horizontal" ? "row" : "column",
        }}
      >
        {coinList.slice(0, settings.count).map((_coin, index) => {
          const coin = { ..._coin, ...data[_coin.symbol.toUpperCase()] };
          return (
          <Card
            key={coin.id}
            coinData={coin}
            style={{}}
            currency_symbol={settings.currency_symbol}
          />
        )})}
      </div>
    );
  }
);
