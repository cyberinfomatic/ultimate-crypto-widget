const defaults = require('@wordpress/scripts/config/webpack.config.js');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const fs = require('fs');
const TsconfigPathsPlugin = require('tsconfig-paths-webpack-plugin');


function getEntries(dir = 'react/widgets') {
	const componentsPath = path.resolve(__dirname, dir);
	const components = fs.readdirSync(componentsPath);
	const dirName = dir.split('/').slice(1).join('/');
	const entries = {};

	components.forEach(component => {
		const componentPath = path.resolve(componentsPath, component);
		const stat = fs.statSync(componentPath);
		if (stat.isDirectory()) {
			const indexExt = ['tsx', 'ts', 'jsx', 'js'].find(ext => fs.existsSync(path.resolve(componentPath, `index.${ext}`)));
			if (indexExt) {
				entries[`${dirName}/${component}`] = path.resolve(componentPath, `index.${indexExt}`);
			}
		}
	});

	return entries;
}

const entries = { ...getEntries(), ...getEntries('react') };
console.log("entries gotten, script about to start")
module.exports = {
	...defaults,
	mode : 'development',
	externals: {
		'@wordpress/element': ['wp', 'element'],
		react: ['React'],
		'react-dom': ['ReactDOM']
	},
	entry: entries,
	output: {
		path: path.resolve(__dirname, 'assets/react-build'),
		filename: '[name]/index.js',
		chunkFilename: 'vendor/[name].js',  // Specify the folder for vendor chunks
	},
	devServer: {
		contentBase: path.resolve(__dirname, 'assets/react-build'),
		filename: '[name]/index.js',
		chunkFilename: 'vendor/[name].js',
		hot: true,
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx|ts|tsx)$/,
				exclude: /node_modules/,
				use: ['babel-loader'],
			},
			{
				test: /\.s[ac]ss$/i,
				use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		extensions: ['.*', '.js', '.jsx', '.ts', '.tsx'],
		alias: {
			'@/styles': path.resolve(__dirname, 'assets/styles'),
			'@/components': path.resolve(__dirname, 'react/components'),
		},
		plugins: [
			new TsconfigPathsPlugin(),
			// new MiniCssExtractPlugin({
			//  filename: '[name].css',
			// }),
		],
	},
	optimization: {
		splitChunks: {
			chunks: 'all',
			cacheGroups: {
				vendors: {
					test: /[\\/]node_modules[\\/]|[\\/]react[\\/]helper[\\/]/, // target node
					name: 'vendors',
					chunks: 'all',
					enforce: true,
				},
			},
		},
	},
};
