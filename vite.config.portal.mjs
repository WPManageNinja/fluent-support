import {defineConfig} from "vite";
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
    renderChunk(code) {
        const ver = '?ver=' + PLUGIN_VERSION;
        return code.replace(/(from\s*["'])(\.[^"']+\.js)(["'])/g, `$1$2${ver}$3`)
                   .replace(/(import\s*["'])(\.[^"']+\.js)(["'])/g, `$1$2${ver}$3`)
                   .replace(/([\[,]\s*)(["'])(\.[^"']+\.js)(["'])/g, `$1$2$3${ver}$4`);
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

// Merge manifest from portal build into the existing vite_config.php
let viteConfig;
const mergeManifestPlugin = {
    name: "merge-manifest",
    configResolved(resolvedConfig) {
        viteConfig = resolvedConfig;
    },
    writeBundle() {
        const outDir = viteConfig.build.outDir;
        const manifestSrc = path.join(outDir, ".vite", "manifest.json");
        const configPath = path.resolve(__dirname, "config/vite_config.php");
        const viteDir = path.join(outDir, ".vite");

        if (fs.existsSync(manifestSrc)) {
            const portalManifest = JSON.parse(fs.readFileSync(manifestSrc, "utf8"));

            // Load existing manifest from admin build
            let existingManifest = {};
            if (fs.existsSync(configPath)) {
                // Re-read the PHP file and extract the array
                // Simpler: just load the existing manifest.json if available, otherwise start fresh
                const existingConfigPath = path.resolve(__dirname, serverConfig.manifest_path);
                if (fs.existsSync(existingConfigPath)) {
                    try {
                        // Read the JSON version if it exists alongside
                        const jsonPath = path.resolve(__dirname, "config/vite_manifest_admin.json");
                        if (fs.existsSync(jsonPath)) {
                            existingManifest = JSON.parse(fs.readFileSync(jsonPath, "utf8"));
                        }
                    } catch (e) {
                        // Fall through
                    }
                }
            }

            // Merge portal entries into admin manifest
            const merged = {...existingManifest, ...portalManifest};

            // Convert to PHP array
            const phpArray = jsonToPhpArray(merged);
            fs.writeFileSync(configPath, '<?php return ' + phpArray + ';', "utf8");

            // Clean up .vite dir
            fs.unlinkSync(manifestSrc);
            if (fs.existsSync(viteDir) && fs.readdirSync(viteDir).length === 0) {
                fs.rmSync(viteDir, {recursive: true});
            }

            console.log("Portal manifest merged into config/vite_config.php");
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
        mergeManifestPlugin,
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
        chunkSizeWarningLimit: 5000,
        reportCompressedSize: true,
        cssCodeSplit: false, // Bundle all CSS into portal's app.css
        assetsInlineLimit: 4096,

        rollupOptions: {
            input: [
                "resources/customer_portal/portal.js",
                "resources/customer_portal/app.scss",
            ],
            output: {
                // No code splitting — everything inlined into portal.js
                manualChunks: undefined,
                entryFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId || '';
                    // portal.js → portal/js/app.js to match release structure
                    if (facadeModuleId.includes('resources/customer_portal/portal')) {
                        return 'portal/js/app.js';
                    }
                    if (facadeModuleId.includes('resources/')) {
                        const srcPath = facadeModuleId.split('resources/').pop();
                        const dirPath = path.dirname(srcPath);
                        const baseName = path.basename(srcPath, path.extname(srcPath));
                        return `${dirPath}/${baseName}.js`;
                    }
                    return "[name].js";
                },
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';
                    if (name.endsWith('.css')) {
                        return "portal/css/app.css";
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

    esbuild: {
        drop: ['console', 'debugger'],
    },
});
