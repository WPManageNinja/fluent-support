/**
 * Block editor webpack config.
 *
 * Extends @wordpress/scripts default config. CSS is extracted to a separate
 * file (assets/block-editor/css/fs_block.css) via MiniCSSExtractPlugin so
 * WordPress can register it as editor_style and inject it into the Block
 * Editor iframe (required for Block API v3 / iframe-mode compatibility).
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const webpack = require( 'webpack' );

// Replace the default MiniCSSExtractPlugin instance with one that writes CSS
// to ../css/ (relative to the JS output path) so JS and CSS land in separate
// sibling directories under assets/block-editor/.
const pluginsWithCssRedirect = defaultConfig.plugins.map( ( plugin ) => {
	if ( plugin instanceof MiniCSSExtractPlugin ) {
		return new MiniCSSExtractPlugin( { filename: '../css/[name].css' } );
	}
	return plugin;
} );

module.exports = {
	...defaultConfig,
	plugins: [
		...pluginsWithCssRedirect,
		new webpack.BannerPlugin( {
			banner: `@license React\nreact.production.min.js\n\nCopyright (c) Facebook, Inc. and its affiliates.\n\nThis source code is licensed under the MIT license found in the\nLICENSE file in the root directory of this source tree.`,
			raw: false,
		} ),
	],
	optimization: {
		...defaultConfig.optimization,
		minimizer: [
			new TerserPlugin( {
				extractComments: true,
				parallel: true,
				terserOptions: {
					output: { comments: false },
					compress: { passes: 2 },
					mangle: { reserved: [ '__', '_n', '_nx', '_x' ] },
				},
			} ),
		],
	},
};
