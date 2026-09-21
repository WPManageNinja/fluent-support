import {defineConfig} from "vite";
import {viteStaticCopy} from "vite-plugin-static-copy";
import vue from "@vitejs/plugin-vue";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import {ElementPlusResolver} from "unplugin-vue-components/resolvers";
import path from "path";
import fs from "fs";

const serverConfig = require('./config/vite.json');

function getPluginVersion() {
    const pluginFile = fs.readFileSync(path.resolve(__dirname, 'fluent-support.php'), 'utf8');
    const match = pluginFile.match(/define\s*\(\s*['"]FLUENT_SUPPORT_VERSION['"]\s*,\s*['"]([^'"]+)['"]\s*\)/);
    return match ? match[1] : '1.0.0';
}

const PLUGIN_VERSION = getPluginVersion();

const chunkCacheBustPlugin = {
    name: 'chunk-cache-bust',
    renderDynamicImport() {
        return {
            left: 'import(',
            right: ' + "?ver=' + PLUGIN_VERSION + '")'
        };
    },
    renderChunk(code) {
        const ver = '?ver=' + PLUGIN_VERSION;
        return code.replace(/(from\s*["'])(\.[^"']+\.js)(["'])/g, `$1$2${ver}$3`)
                   .replace(/(import\s*["'])(\.[^"']+\.js)(["'])/g, `$1$2${ver}$3`)
                   .replace(/([\[,]\s*)(["'])(\.[^"']+\.js)(["'])/g, `$1$2$3${ver}$4`);
    }
};

// Entry points (admin app + standalone scripts)
// Customer portal is built separately via vite.config.portal.mjs as a standalone bundle
const inputs = [
    "resources/admin/start.js",
    "resources/admin/global_admin.js",
    "resources/admin/global_summary.js",
    "resources/customer_portal/login_helper.js",
    "resources/scss/alpha-admin.scss",
    "resources/scss/all_public.scss",
];

// Map Vite source-relative paths to desired output paths.
// Admin JS goes into admin/js/ and portal JS into portal/js/ to match release structure.
function mapOutputPath(srcRelativePath) {
    const specificMap = {
        'customer_portal/login_helper.js': 'portal/js/login_helper.js',
    };
    if (specificMap[srcRelativePath]) return specificMap[srcRelativePath];
    if (srcRelativePath.startsWith('admin/')) {
        return 'admin/js/' + srcRelativePath.slice('admin/'.length);
    }
    return srcRelativePath;
}

// Keep legacy CSS output locations so existing PHP enqueue paths and relative font URLs continue to work.
const legacyCssOutputMap = {
    "resources/scss/alpha-admin.scss": "admin/css/alpha-admin.css",
    "resources/scss/all_public.scss": "admin/css/all_public.css",
};

// Merge all chunk CSS (from Vue components / JS imports) into alpha-admin.css.
// Standalone SCSS entries (legacyCssOutputMap) keep their own output files.
const legacyCssPaths = new Set(Object.values(legacyCssOutputMap));
const mergeCssChunksPlugin = {
    name: 'merge-css-chunks',
    enforce: 'post',
    generateBundle(_, bundle) {
        let mergedCss = '';
        const toRemove = [];

        for (const [fileName, asset] of Object.entries(bundle)) {
            if (asset.type !== 'asset' || !fileName.endsWith('.css')) continue;
            if (legacyCssPaths.has(fileName)) continue;

            mergedCss += asset.source + '\n';
            toRemove.push(fileName);
        }

        for (const f of toRemove) delete bundle[f];

        if (mergedCss) {
            // Append component/module CSS into alpha-admin.css to avoid a separate style.css file.
            const targetKey = 'admin/css/alpha-admin.css';
            if (bundle[targetKey] && bundle[targetKey].type === 'asset') {
                bundle[targetKey].source += '\n' + mergedCss;
            } else {
                this.emitFile({ type: 'asset', fileName: targetKey, source: mergedCss });
            }
        }
    }
};

// vue3-apexcharts ships a pre-built dist that contains a dynamic import() for its SSR
// companion file (apexcharts.ssr.esm-*.js). We never need SSR hydration in the WordPress
// admin, so patch that import to a no-op and drop the chunk from the output.
const suppressApexchartsSSRPlugin = {
    name: 'suppress-apexcharts-ssr',
    generateBundle(_, bundle) {
        const ssrFileName = Object.keys(bundle).find(f => f.includes('apexcharts.ssr.esm'));
        if (!ssrFileName) return;

        // Replace every dynamic import() of that chunk (including cache-busted form
        // "file.js" + "?ver=x.y.z") with a resolved empty object so the call succeeds.
        for (const chunk of Object.values(bundle)) {
            if (chunk.type !== 'chunk' || !chunk.code) continue;
            if (!chunk.code.includes('apexcharts.ssr.esm')) continue;
            chunk.code = chunk.code.replace(
                /import\(["'][^"']*apexcharts\.ssr\.esm[^"']*["'](?:\s*\+\s*["'][^"']*["'])?\)/g,
                'Promise.resolve({default:{}})'
            );
        }
        delete bundle[ssrFileName];
    }
};

// Extract license comments from minified chunks and write them to .LICENSE.txt files,
// replicating the webpack TerserPlugin `extractComments: true` behaviour.
// Matches: /*! ... */ and /** ... @license/@preserve/@cc_on ... */
function createExtractLicensesPlugin() {
    const licenseMap = new Map();
    return {
        name: 'extract-licenses',
        enforce: 'post', // Run after Vite's internal terser plugin
        renderChunk(code, chunk) {
            const blockCommentRegex = /\/\*[\s\S]*?\*\//g;
            const seen = new Set();
            const licenseComments = [];
            let match;
            while ((match = blockCommentRegex.exec(code)) !== null) {
                const comment = match[0];
                if (/^\/\*!/.test(comment) || /@license|@preserve|@cc_on/i.test(comment)) {
                    if (!seen.has(comment)) {
                        seen.add(comment);
                        licenseComments.push(comment);
                    }
                }
            }
            if (!licenseComments.length) return null;
            licenseMap.set(chunk.fileName, licenseComments.join('\n\n'));
            // Strip all matched license comments from the output JS
            const licenseRegex = /\/\*![\s\S]*?\*\//g;
            return { code: code.replace(licenseRegex, '').trimStart(), map: null };
        },
        generateBundle(_, bundle) {
            for (const [fileName, content] of licenseMap.entries()) {
                if (bundle[fileName]) {
                    this.emitFile({ type: 'asset', fileName: fileName + '.LICENSE.txt', source: content });
                }
            }
        },
    };
}

let viteConfig;

const moveManifestPlugin = {
    name: "move-manifest",
    configResolved(resolvedConfig) {
        viteConfig = resolvedConfig;
    },
    writeBundle() {
        const outDir = viteConfig.build.outDir;
        const manifestSrc = path.join(outDir, ".vite", "manifest.json");
        const manifestDest = path.resolve(__dirname, serverConfig.manifest_path);
        const viteDir = path.join(outDir, ".vite");

        if (fs.existsSync(manifestSrc)) {
            fs.renameSync(manifestSrc, manifestDest);

            if (fs.existsSync(viteDir) && fs.readdirSync(viteDir).length === 0) {
                fs.rmSync(viteDir, {recursive: true});
            }

            const manifestContent = JSON.parse(fs.readFileSync(manifestDest, "utf8"));
            const phpArray = jsonToPhpArray(manifestContent);

            const configPath = path.resolve(__dirname, "config/vite_config.php");
            if (!fs.existsSync(configPath)) {
                fs.writeFileSync(configPath, '<?php return [];', "utf8");
            }

            fs.writeFileSync(configPath, '<?php return ' + phpArray + ';', "utf8");

            // Save JSON copy for portal build to merge with
            const jsonCopyPath = path.resolve(__dirname, "config/vite_manifest_admin.json");
            fs.writeFileSync(jsonCopyPath, JSON.stringify(manifestContent, null, 2), "utf8");

            console.log("Manifest array injected into config/vite_config.php");
        }
    },
};

function jsonToPhpArray(obj, indentLevel = 1) {
    const indent = "    ".repeat(indentLevel);
    if (Array.isArray(obj)) {
        return "[\n" +
            obj.map(v => `${indent}${valueToPhp(v, indentLevel + 1)}`).join(",\n") +
            "\n" + "    ".repeat(indentLevel - 1) + "]";
    } else if (typeof obj === "object" && obj !== null) {
        return "[\n" +
            Object.entries(obj)
                .map(([key, value]) => `${indent}'${key}' => ${valueToPhp(value, indentLevel + 1)}`)
                .join(",\n") +
            "\n" + "    ".repeat(indentLevel - 1) + "]";
    }
    return valueToPhp(obj, indentLevel);
}

function valueToPhp(value, indentLevel) {
    if (typeof value === "string") {
        return `'${value.replace(/'/g, "\\'")}'`;
    } else if (typeof value === "number") {
        return String(value);
    } else if (typeof value === "boolean") {
        return value ? "true" : "false";
    } else if (value === null) {
        return "null";
    } else if (Array.isArray(value) || typeof value === "object") {
        return jsonToPhpArray(value, indentLevel);
    }
    return "null";
}

export default defineConfig({
    base: '',
    publicDir: false,
    css: {
        preprocessorOptions: {
            scss: {
                api: "modern",
            }
        }
    },
    plugins: [
        AutoImport({
            resolvers: [ElementPlusResolver()],
        }),
        Components({
            resolvers: [ElementPlusResolver()],
        }),
        vue({
            template: {
                compilerOptions: {
                    compatConfig: {
                        MODE: 3
                    }
                }
            }
        }),
        viteStaticCopy({
            targets: [
                {src: "resources/images", dest: ""},
                {src: "resources/libs", dest: ""},
                // Block editor is built separately via `npm run build:block` (React/JSX, uses WP globals)
            ],
        }),
        moveManifestPlugin,
        mergeCssChunksPlugin,
        suppressApexchartsSSRPlugin,
        createExtractLicensesPlugin(),
    ],

    build: {
        sourcemap: false,
        manifest: true,
        outDir: "assets",
        emptyOutDir: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug', 'console.trace'],
            },
            mangle: {
                safari10: true,
            }
        },
        chunkSizeWarningLimit: 1000,
        reportCompressedSize: true,
        cssCodeSplit: true,
        assetsInlineLimit: 4096,

        rollupOptions: {
            input: inputs,
            output: {
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId || '';
                    if (facadeModuleId.includes('resources/')) {
                        const srcPath = facadeModuleId.split('resources/').pop();
                        const dirPath = path.dirname(srcPath);
                        const mappedDir = mapOutputPath(dirPath + '/x').replace('/x', '');
                        return `${mappedDir}/[name].js`;
                    }
                    // Vendor and other shared chunks go into admin/js/
                    return "admin/js/[name].js";
                },
                entryFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId || '';
                    if (facadeModuleId.includes('resources/')) {
                        const srcPath = facadeModuleId.split('resources/').pop();
                        const baseName = path.basename(srcPath, path.extname(srcPath));
                        const dirPath = path.dirname(srcPath);
                        const defaultPath = `${dirPath}/${baseName}.js`;
                        return mapOutputPath(defaultPath);
                    }
                    return "[name].js";
                },
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';

                    if (name.endsWith('.css')) {
                        const originalFileName = (assetInfo.originalFileName || '').replace(/\\/g, '/');

                        for (const [sourcePath, outputPath] of Object.entries(legacyCssOutputMap)) {
                            if (originalFileName.endsWith(sourcePath)) {
                                return outputPath;
                            }
                        }

                        if (originalFileName.includes('resources/')) {
                            const srcPath = originalFileName.split('resources/').pop();
                            const dirPath = path.dirname(srcPath);
                            const baseName = path.basename(srcPath, path.extname(srcPath));
                            return `${dirPath}/${baseName}.css`;
                        }
                        return "admin/css/[name][extname]";
                    }

                    return "[name][extname]";
                },
            },
            plugins: [chunkCacheBustPlugin],
        },
    },

    resolve: {
        alias: {
            'vue': "vue/dist/vue.esm-bundler.js",
            '@': path.resolve(__dirname, "resources/"),
            '~element-plus': path.resolve(__dirname, 'node_modules/element-plus')
        },
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue', '.scss']
    },

    server: {
        port: serverConfig.port,
        strictPort: serverConfig.strict_port,
        origin: `http://localhost:${serverConfig.port}`,
        hmr: {
            host: serverConfig.host,
            port: serverConfig.port,
            protocol: serverConfig.vite_protocol,
        },
        cors: {
            origin: true,
            credentials: true,
        },
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization'
        }
    },

    optimizeDeps: {
        include: ["vue", "vue-router"]
    },

    esbuild: {
        drop: ['console', 'debugger'],
    },
});
