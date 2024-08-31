import { useState, useEffect, useCallback } from "react";
import { BasicCoinData } from "../../types"; 

interface BitMEXWebSocketResponse {
  table: string;
  action: "partial" | "update" | "insert" | "delete";
  data: Array<{
    symbol: string;
    lastPriceProtected?: number;
    openValue?: number;
    fairPrice?: number;
    markPrice?: number;
    timestamp?: string;
  }>;
  keys?: string[];
  types?: { [key: string]: string };
  filter?: { account?: number; symbol?: string };
}

function useBitMEXTickerWebSocket(
  symbols: string[],
  defaultCurrencyDollarRate = 1
) {
  const [connected, setConnected] = useState(false);
  const [tickerData, setTickerData] = useState<Record<string, BasicCoinData>>(
    {}
  );
  const [error, setError] = useState<string | null>(null);

  const connectWebSocket = useCallback(() => {
    const socket = new WebSocket("wss://ws.bitmex.com/realtime");

    socket.onopen = () => {
      console.log("Connected to BitMEX WebSocket");
      setConnected(true);

      const subscribeMessage = {
        op: "subscribe",
        args: symbols.map((symbol) => `instrument:${symbol.toUpperCase()}USD`),
      };
      socket.send(JSON.stringify(subscribeMessage));
    };

    socket.onmessage = (event) => {
      try {
        const response: BitMEXWebSocketResponse = JSON.parse(event.data);
        if (response.table === "instrument" && response.action === "update") {
          response.data.forEach((instrumentData) => {
            if (instrumentData.symbol) {
              const originalSymbol = instrumentData.symbol.replace("USD", "");
              setTickerData((prevData) => ({
                ...prevData,
                [originalSymbol]: {
                  symbol: originalSymbol.toLowerCase(),
                  current_price:
                    (instrumentData.lastPriceProtected ?? 0) *
                    defaultCurrencyDollarRate,
                  total_volume: instrumentData.openValue ?? 0,
                  high_24h:
                    (instrumentData.lastPriceProtected ?? 0) *
                    defaultCurrencyDollarRate,
                  low_24h:
                    (instrumentData.fairPrice ?? 0) * defaultCurrencyDollarRate,
                  price_change_24h: 0, // Not provided in the response
                  price_change_percentage_24h: 0, // Not provided in the response
                  last_updated:
                    instrumentData.timestamp ?? new Date().toLocaleString(),
                },
              }));
            }
          });
        }
      } catch (err) {
        console.error("Error parsing WebSocket message:", err);
        setError("Error parsing WebSocket message");
      }
    };

    socket.onclose = () => {
      console.log("Disconnected from BitMEX WebSocket");
      setConnected(false);
    };

    socket.onerror = (err) => {
      console.error("WebSocket error:", err);
      setError("Error connecting to BitMEX WebSocket");
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

export default useBitMEXTickerWebSocket;
