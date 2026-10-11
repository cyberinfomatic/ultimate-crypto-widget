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

// Each widget is its own bundle; so are the admin screen and the shared stylesheet.
const entries = {
	...getEntries(),
	admin: path.resolve(__dirname, 'react/admin/index.tsx'),
	theme: path.resolve(__dirname, 'react/theme/index.ts'),
};
const isProduction = process.env.NODE_ENV === 'production';
module.exports = {
	...defaults,
	mode: isProduction ? 'production' : 'development',
	// Source maps help while developing; they are not shipped.
	devtool: isProduction ? false : 'source-map',
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
			cacheGroups: {
				vendors: {
					// Libraries and the plugin's own shared code go in one bundle the widgets share.
					// Scripts only: the design system's stylesheet is its own file, theme.css.
					test: /[\\/]node_modules[\\/]|[\\/]react[\\/](helper|live|ds|data)[\\/].*\.(js|jsx|ts|tsx)$/,
					name: 'vendors',
					chunks: 'all',
					enforce: true,
				},
			},
		},
	},
};
