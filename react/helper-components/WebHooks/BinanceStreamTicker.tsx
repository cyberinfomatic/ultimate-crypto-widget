import { useState, useEffect, useCallback } from "react";
import { TickerData } from "../../types";

interface BinanceStreamSocketResponse {
  data: {
    e: string; // Event type
    E: number; // Event time
    s: string; // Symbol
    p: string; // Price change
    P: string; // Price change percent
    w: string; // Weighted average price
    x: string; // First trade(F)-1 price (first trade before the 24hr rolling window)
    c: string; // Last price
    Q: string; // Last quantity
    b: string; // Best bid price
    B: string; // Best bid quantity
    a: string; // Best ask price
    A: string; // Best ask quantity
    o: string; // Open price
    h: string; // High price
    l: string; // Low price
    v: string; // Total traded base asset volume
    q: string; // Total traded quote asset volume
    O: number; // Statistics open time
    C: number; // Statistics close time
    F: number; // First trade ID
    L: number; // Last trade Id
    n: number; // Total number of trades
    [key: string]: string | number; // Allow for additional properties
  };
  stream: string; // Stream name e.g. "btcusdt@ticker"
}

function useBinanceStreamTickerWebSocket(
  symbols: string[],
  defaultCurrencyDollarRate = 1
) {
  const [connected, setConnected] = useState(false);
  const [tickerData, setTickerData] = useState<Record<string, TickerData>>({});
  const [error, setError] = useState<string | null>(null);

  const connectWebSocket = useCallback(() => {
    const socket = new WebSocket("wss://stream.binance.com:9443/stream?streams=");

    socket.onopen = () => {
      console.log("Connected to Binance Stream WebSocket");
      setConnected(true);

        const message = {
          id: Date.now().toString(),
          method: "SUBSCRIBE",
          params: [
            ...symbols.map((symbol) => `${symbol.toLowerCase()}usdt@ticker`),
          ],
        };
        socket.send(JSON.stringify(message));
      
    };

    socket.onmessage = (event) => {
      const response: BinanceStreamSocketResponse = JSON.parse(event.data);
      if (response && response.data) {
        let data = response.data;
        // Symbol is in the format "BTCUSDT", remove "USDT" to get the original symbol
        let originalSymbol = data.s.replace("USDT", "");
        setTickerData((prevData) => ({
          ...prevData,
          [originalSymbol]: {
            symbol: originalSymbol.toLowerCase(),
            current_price: parseFloat(data.c) * defaultCurrencyDollarRate,
            total_volume: parseFloat(data.v),
            high_24h: parseFloat(data.h) * defaultCurrencyDollarRate,
            low_24h: parseFloat(data.l) * defaultCurrencyDollarRate,
            price_change_24h:
              parseFloat(data.p) * defaultCurrencyDollarRate,
            price_change_percentage_24h: parseFloat(data.P),
            last_updated: new Date(data.E).toLocaleString(),
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

export default useBinanceStreamTickerWebSocket;
