const path = require( 'path' );
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );

const isProduction = process.env.NODE_ENV === 'production';

const webpackConfig = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'admin': './src/admin-screen/index.js',
		'tracking': './src/tracking/index.js'
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			'~': path.join( __dirname, 'src' ),
		},
	},
	plugins: [
		...defaultConfig.plugins.filter( (plugin) => {
			const filteredPlugins = [
				// Filter WP/DEWP, as we will replace it with WC one.
				'DependencyExtractionWebpackPlugin',
				'MiniCssExtractPlugin',
			];
			return ! filteredPlugins.includes( plugin.constructor.name );
		} ),
		new WooCommerceDependencyExtractionWebpackPlugin(),
		new MiniCSSExtractPlugin( {
			filename: '[name].css',
			chunkFilename: '[name].css?ver=[chunkhash]',
		} ),
	],
};

const sassTest = /\.(sc|sa)ss$/;
const updatedSassOptions = {
	sourceMap: ! isProduction,
	sassOptions: {
		includePaths: [ 'src/css/abstracts' ],
	},
	additionalData:
		'@use "sass:color";' +
		'@import "_colors"; ' +
		'@import "_variables"; ' +
		'@import "_mixins"; ' +
		'@import "_breakpoints"; ',
};

webpackConfig.module.rules.forEach( ( { test, use }, ruleIndex ) => {
	if ( test.toString() === sassTest.toString() ) {
		use.forEach( ( { loader }, loaderIndex ) => {
			if ( loader === require.resolve( 'sass-loader' ) ) {
				webpackConfig.module.rules[ ruleIndex ].use[
					loaderIndex
				].options = updatedSassOptions;
			}
		} );
	}
} );

module.exports = webpackConfig;
