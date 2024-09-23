import {WP_API_PATHS} from "../types";

// accept generic with default any  and return the generic type
export const fetchDataJson = async <T = any> (url: string | Request | URL, options: RequestInit) => {
	const response = await fetch(url, options);
	return await response.json() as T;

}


export const fetchDataText = async <T = any> (url: string | Request | URL, options: RequestInit) => {
	const response = await fetch(url, options);
	return await response.text() as T;

}

// class tp handle my wp-json api class
export class ucwpWPAPI {
	private readonly namespace: string;
	private readonly origin =` ${window.location.origin}/`;
	private paths : {
		[key: string] : WP_API_PATHS
	}

	constructor(namespace: string, version: string){
		this.namespace = namespace + "/" + version;
		this.paths = {};
	}

	addPath(key: string, method: WP_API_PATHS['method'], endpoint: string, required_params ?: string[], required_body_params ?: string[], headers ?: Headers, ){
		this.paths[key]  = {
			method,
			endpoint,
			required_params,
			required_body_params,
			headers
		}
		return this;
	}

	addBulkPaths(paths: {[key: string]: WP_API_PATHS}){
		this.paths = {
			...this.paths,
			...paths
		}
		return this;
	}

	fetchData<T = any>(key: string,  params ?: {[key: string]: string | number | boolean}, body ?: {[key: string]: any} , expect : 'json' | 'text' = 'json'){
		const path = this.paths[key] ?? null;
		if(!path){
			throw new Error("Path not found");
		}
		let url = new URL(`/wp-json/${this.namespace}${path.endpoint}`, this.origin);
		if (path.required_params) {
			path.required_params.forEach((param) => {
				if(!params){
					throw new Error("Missing required params");
				}
				if(!params[param]){
					throw new Error("Missing required param: " + param);
				}
				url.searchParams.append(param, params[param] as string);
			})
		}
		if(path.required_body_params){
			path.required_body_params.forEach((param) => {
				if(!body){
					throw new Error("Missing required body params");
				}
				if(!body[param]){
					throw new Error("Missing required body param: " + param);
				}
			})
		}
		const options = {
			method: path.method,
			headers: path.headers,
			body: JSON.stringify(body)
		}
		if(expect === 'json'){
			return fetchDataJson<T>(url, options);
		}
		return fetchDataText<T>(url, options);

	}

	getPaths(){
		return this.paths;
	}

	getNamespace(){
		return this.namespace;
	}

	getPath(key: string){
		return this.paths[key];
	}

	getPathEndpoint(key: string){
		return this.paths[key]?.endpoint;
	}

	getPathMethod(key: string){
		return this.paths[key]?.method;
	}

	getPathRequiredParams(key: string){
		return this.paths[key]?.required_params;
	}

	getPathRequiredBodyParams(key: string){
		return this.paths[key]?.required_body_params;
	}

	getPathHeaders(key: string){
		return this.paths[key]?.headers;
	}

	deletePath(key: string){
		delete this.paths[key];
		return this;
	}
}