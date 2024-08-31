import { useState, useEffect, useCallback } from "react";
import { TickerData } from "../../types";


interface KrakenWebSocketResponse {
  channel: string;
  type: "snapshot" | "update";
  data: Array<{
    ask: number;
    bid: number;
    last: number;
    volume: number;
    low: number;
    high: number;
    change: number;
    change_pct: number;
    symbol: string;
    [key: string]: any;
  }>;
}

function useKrakenTickerWebSocket(
  symbols: string[],
  defaultCurrencyDollarRate = 1
) {
  console.log("KrakenTicker", symbols);
  const [connected, setConnected] = useState(false);
  const [tickerData, setTickerData] = useState<Record<string, TickerData>>({});
  const [error, setError] = useState<string | null>(null);

  const connectWebSocket = useCallback(() => {
    const socket = new WebSocket("wss://ws.kraken.com/v2");

    socket.onopen = () => {
      console.log("Connected to Kraken WebSocket");
      setConnected(true);

      const subscribeMessage = {
        method: "subscribe",
        params: {
          channel: "ticker",
          snapshot: true,
          symbol: symbols.map((symbol) => `${symbol.toUpperCase()}/USD`),
        },
        req_id: Date.now(),
      };
      socket.send(JSON.stringify(subscribeMessage));
    };

    socket.onmessage = (event) => {
      try {
        const response: KrakenWebSocketResponse = JSON.parse(event.data);
        console.log("Kraken WebSocket message:", response);
        if (
          response.channel === "ticker" &&
          response.data &&
          response.data.length > 0
        ) {
          response.data.forEach((tickerUpdate) => {
            const originalSymbol = tickerUpdate.symbol.split("/")[0];
            setTickerData((prevData) => ({
              ...prevData,
              [originalSymbol]: {
                symbol: originalSymbol.toLowerCase(),
                current_price: tickerUpdate.last * defaultCurrencyDollarRate,
                total_volume: tickerUpdate.volume,
                high_24h: tickerUpdate.high * defaultCurrencyDollarRate,
                low_24h: tickerUpdate.low * defaultCurrencyDollarRate,
                price_change_24h:
                  tickerUpdate.change * defaultCurrencyDollarRate,
                price_change_percentage_24h: tickerUpdate.change_pct,
                last_updated: new Date().toLocaleString(),
              },
            }));
          });
        }
      } catch (err) {
        console.error("Error parsing WebSocket message:", err);
      }
    };

    socket.onclose = () => {
      console.log("Disconnected from Kraken WebSocket");
      setConnected(false);
    };

    socket.onerror = (err) => {
      console.error("WebSocket error:", err);
      setError("Error connecting to Kraken WebSocket");
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

export default useKrakenTickerWebSocket;
