let mix = require('laravel-mix');
const AutoImport = require("unplugin-auto-import/webpack").default;
const {ElementPlusResolver} = require("unplugin-vue-components/resolvers");
const Components = require("unplugin-vue-components/webpack").default;
var path = require('path');

mix.webpackConfig({
    module: {
        rules: [{
            test: /\.mjs$/,
            resolve: {fullySpecified: false},
            include: /node_modules/,
            type: "javascript/auto"
        }]

    },
    plugins: [
        AutoImport({
            resolvers: [ElementPlusResolver()],
        }),
        Components({
            resolvers: [ElementPlusResolver()],
            directives: false
        }),
    ],
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: {
            '@': path.resolve(__dirname, 'resources/')
        }
    }
});

if (!mix.inProduction()) {
    mix.webpackConfig({
        devtool: 'source-map'
    }).sourceMaps(true, 'source-map');
} else {
    // During production build we'll remove the existing assets
    // directory so that the source-maps are deleted as well.
    let fs = require('fs-extra');
    fs.remove('assets');
}

mix
    .js('resources/admin/start.js', 'assets/admin/js/start.js').vue({ version: 3 })
    .js('resources/customer_portal/portal.js', 'assets/portal/js/app.js').vue({ version: 3 })
    .js('resources/admin/global_admin.js', 'assets/admin/js/global_admin.js')
    .js('resources/admin/global_summary.js', 'assets/admin/js/global_summary.js')
    .js('resources/customer_portal/login_helper.js', 'assets/portal/js/login_helper.js')
    .sass('resources/customer_portal/app.scss', 'assets/portal/css/app.css')
    .sass('resources/scss/alpha-admin.scss', 'assets/admin/css/alpha-admin.css')
    .sass('resources/scss/all_public.scss', 'assets/admin/css/all_public.css')
    .copy('resources/images', 'assets/images')
    .copy('resources/libs', 'assets/libs');
mix.js('resources/block-editor/blocks/customer-portal/index.js', 'assets/block-editor/js/fs_block.js').react();
