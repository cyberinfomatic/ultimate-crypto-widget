import { createContext, useContext } from "react";
import { DEFAULT_API } from "./api";

/** What a widget knows about the page it is on. */
export type WidgetEnv = {
	/** The root of the plugin's REST API, as the server gave it. */
	api: string;
};

export const EnvContext = createContext<WidgetEnv>({ api: DEFAULT_API });

export const useEnv = (): WidgetEnv => useContext(EnvContext);
