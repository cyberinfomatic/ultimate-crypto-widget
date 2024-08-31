import { useState, useEffect, useCallback } from "react";
import { TickerData } from "../../types";

interface BinanceWebSocketResponse {
  id: string;
  status: number;
  result: {
    symbol: string;
    lastPrice: string;
    priceChange: string;
    priceChangePercent: string;
    volume: string;
    highPrice: string;
    lowPrice: string;
    [key: string]: any;
  };
}

function useBinanceTickerWebSocket(
  symbols: string[],
  defaultCurrencyDollarRate = 1
) {
  const [connected, setConnected] = useState(false);
  const [tickerData, setTickerData] = useState<Record<string, TickerData>>({});
  const [error, setError] = useState<string | null>(null);

  const connectWebSocket = useCallback(() => {
    const socket = new WebSocket("wss://ws-api.binance.com:443/ws-api/v3");

    socket.onopen = () => {
      console.log("Connected to Binance WebSocket");
      setConnected(true);

      symbols.forEach((symbol) => {
        const message = {
          id: Date.now().toString(),
          method: "ticker.24hr",
          params: {
            symbol: `${symbol.toUpperCase()}USDT`,
          },
        };
        socket.send(JSON.stringify(message));
      });
    };

    socket.onmessage = (event) => {
      const response: BinanceWebSocketResponse = JSON.parse(event.data);
      if (response.result) {
        const data = response.result;
        const originalSymbol = data.symbol.replace("USDT", "");
        setTickerData((prevData) => ({
          ...prevData,
          [originalSymbol]: {
            symbol: originalSymbol.toLowerCase(),
            current_price:
              parseFloat(data.lastPrice) * defaultCurrencyDollarRate,
            total_volume: parseFloat(data.volume),
            high_24h: parseFloat(data.highPrice) * defaultCurrencyDollarRate,
            low_24h: parseFloat(data.lowPrice) * defaultCurrencyDollarRate,
            price_change_24h:
              parseFloat(data.priceChange) * defaultCurrencyDollarRate,
            price_change_percentage_24h: parseFloat(data.priceChangePercent),
            last_updated: new Date().toLocaleString(),
          },
        }));
      }
    };

    socket.onclose = () => {
      console.log("Disconnected from Binance WebSocket");
      setConnected(false);
    };

    socket.onerror = (err) => {
      console.error("WebSocket error:", err);
      setError("Error connecting to Binance WebSocket");
    };

    return () => {
      socket.close();
    };
  }, []);

  useEffect(() => {
    const cleanup = connectWebSocket();
    return cleanup;
  }, [connectWebSocket]);

  return { connected, data: tickerData, error };
}

export default useBinanceTickerWebSocket;
