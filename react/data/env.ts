import { createContext, useContext } from "react";
import { DEFAULT_API } from "./api";

/** What a widget knows about the page it is on. */
export type WidgetEnv = {
	/** The root of the plugin's REST API, as the server gave it. */
	api: string;
};

/** Set for each widget by the code that mounts it, from the data the server put on its element. */
export const EnvContext = createContext<WidgetEnv>({ api: DEFAULT_API });

/** The page a widget is on, as EnvContext gives it. */
export const useEnv = (): WidgetEnv => useContext(EnvContext);
