import { useState } from "react";
import "@/styles/sass/crypto-price-picker.scss";
import { UCWPWidgetSetting, CoinData } from "../../types";
import ReactRender from "../../helper-components/react-wrapper";
import Card from "./cards/card-001";

import Chart from "chart.js/auto";
import { CategoryScale } from "chart.js";
import React from "react";

ReactRender(
  ({ coins, settings }: { coins: CoinData[]; settings: UCWPWidgetSetting }) => {
    const [coinList, setCoinList] = useState<CoinData[]>(coins ?? []); // Initialize with props

    return (
      <div
        className="ucwp-crypto-price-picker-main"
        style={{
          flexDirection:
            settings?.orientation == "horizontal" ? "row" : "column",
        }}
      >
        {coinList.slice(0, settings.count).map((coin, index) => (
          <Card
            key={coin.id}
            coinData={coin}
            style={{}}
            currency_symbol={settings.currency_symbol}
          />
        ))}
      </div>
    );
  }
);
