import { useState, useEffect, useCallback } from "react";
import { BasicCoinData } from "../../types"; 

interface CoinbasePrimeWebSocketResponse {
  channel: string;
  timestamp: string;
  sequence_num: number;
  events: Array<{
    type: string;
    product_id: string;
    price: string;
    volume_24h: string;
    price_change_24h: string;
    price_change_percent_24h: string;
    time: string;
    [key: string]: any;
  }>;
}

function useCoinbasePrimeTickerWebSocket(
  symbols: string[],
  defaultCurrencyDollarRate = 1,
  accessKey: string,
  apiKeyId: string,
  passphrase: string,
  portfolioId: string
) {
  const [connected, setConnected] = useState(false);
  const [tickerData, setTickerData] = useState<Record<string, BasicCoinData>>(
    {}
  );
  const [error, setError] = useState<string | null>(null);

  const connectWebSocket = useCallback(() => {
    const socket = new WebSocket("wss://ws-feed.prime.coinbase.com");

    socket.onopen = () => {
      console.log("Connected to Coinbase Prime WebSocket");
      setConnected(true);

      const subscribeMessage = {
        type: "subscribe",
        channel: "ticker",
        access_key: accessKey,
        api_key_id: apiKeyId,
        timestamp: new Date().toISOString(),
        passphrase: passphrase,
        signature: "SIGNATURE", // Note: Implement proper signature generation
        portfolio_id: portfolioId,
        product_ids: symbols.map((symbol) => `${symbol.toUpperCase()}-USD`),
      };
      socket.send(JSON.stringify(subscribeMessage));
    };

    socket.onmessage = (event) => {
      try {
        const response: CoinbasePrimeWebSocketResponse = JSON.parse(event.data);
        if (
          response.channel === "ticker" &&
          response.events &&
          response.events.length > 0
        ) {
          response.events.forEach((tickerEvent) => {
            if (tickerEvent.type === "ticker") {
              const originalSymbol = tickerEvent.product_id.replace("-USD", "");
              setTickerData((prevData) => ({
                ...prevData,
                [originalSymbol]: {
                  id: originalSymbol.toLowerCase(),
                  symbol: originalSymbol,
                  current_price:
                    parseFloat(tickerEvent.price) * defaultCurrencyDollarRate,
                  total_volume: parseFloat(tickerEvent.volume_24h),
                  high_24h: 0, // Placeholder, adjust as needed
                  low_24h: 0, // Placeholder, adjust as needed
                  price_change_24h:
                    parseFloat(tickerEvent.price_change_24h) *
                    defaultCurrencyDollarRate,
                  price_change_percentage_24h: parseFloat(
                    tickerEvent.price_change_percent_24h
                  ),
                  last_updated: new Date(tickerEvent.time).toLocaleString(),
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
      console.log("Disconnected from Coinbase Prime WebSocket");
      setConnected(false);
    };

    socket.onerror = (err) => {
      console.error("WebSocket error:", err);
      setError("Error connecting to Coinbase Prime WebSocket");
    };

    return () => {
      socket.close();
    };
  }, [
    symbols,
    defaultCurrencyDollarRate,
    accessKey,
    apiKeyId,
    passphrase,
    portfolioId,
  ]);

  useEffect(() => {
    const cleanup = connectWebSocket();
    return cleanup;
  }, [connectWebSocket]);

  return { connected, data: tickerData, error };
}

export default useCoinbasePrimeTickerWebSocket;
