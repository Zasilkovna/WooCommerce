const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const {resolve} = require("path");

module.exports = {
    ...defaultConfig,
    output: {
        ...defaultConfig.output,
        path: resolve( process.cwd(), 'public/js' ),
    },
    plugins: [
        ...defaultConfig.plugins,
        new WooCommerceDependencyExtractionWebpackPlugin(),
    ],
};
