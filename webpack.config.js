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
        // slice off the last plugin to avoid duplicate WP DependencyExtractionWebpackPlugin
        ...defaultConfig.plugins.slice(0, -1),
        new WooCommerceDependencyExtractionWebpackPlugin(),
    ],
};
