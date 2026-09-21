#!/usr/bin/env node

/**
 * Fluent Support Vite Mode Switcher
 *
 * Switches between development and production modes by updating config/app.php
 *
 * Usage:
 *   node vite-mode.js dev        # Switch to development mode
 *   node vite-mode.js prod       # Switch to production mode
 *   node vite-mode.js production # Switch to production mode
 */

const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const mode = args[0];

const configPath = path.resolve(__dirname, 'config/app.php');

if (!mode || !['dev', 'prod', 'production'].includes(mode)) {
    console.error('Invalid mode. Use: dev, prod, or production');
    console.log('\nUsage:');
    console.log('  node vite-mode.js dev        # Switch to development mode');
    console.log('  node vite-mode.js prod       # Switch to production mode');
    process.exit(1);
}

const targetMode = mode === 'dev' ? 'dev' : 'production';

try {
    if (!fs.existsSync(configPath)) {
        console.error(`Config file not found: ${configPath}`);
        process.exit(1);
    }

    let content = fs.readFileSync(configPath, 'utf8');

    // Replace the env value
    const envPattern = /(['"])env['"]\s*=>\s*['"][^'"]+['"]/g;
    const newEnvLine = `'env' => '${targetMode}'`;

    content = content.replace(envPattern, newEnvLine);

    fs.writeFileSync(configPath, content, 'utf8');

    console.log(`Switched to ${targetMode.toUpperCase()} mode`);
    console.log(`Updated: ${configPath}`);

    if (targetMode === 'dev') {
        console.log('\nNow run: npm run dev');
        console.log('Assets will be served from Vite dev server with HMR');
    } else {
        console.log('\nNow run: npm run build');
        console.log('Assets will be built for production');
    }

} catch (error) {
    console.error('Error updating config:', error.message);
    process.exit(1);
}
