#!/usr/bin/env node

/**
 * Translation String Extractor for Fluent Support
 *
 * This script extracts all translatable strings wrapped in $t() function
 * from Vue files and generates a PHP class with the translations.
 *
 * Usage: node extract-translations.js
 */

const fs = require('fs');
const path = require('path');

// Configuration
const RESOURCES_DIR = path.join(__dirname, 'resources');
const CUSTOMER_PORTAL_DIR = path.join(RESOURCES_DIR, 'customer_portal');
const LONG_TRANSLATIONS_FILE = path.join(RESOURCES_DIR, 'long_translations.json');
const OUTPUT_FILE = path.join(__dirname, 'app', 'Services', 'TranslationStrings.php');
const TEXT_DOMAIN = 'fluent-support';
const NAMESPACE = 'FluentSupport\\App\\Services';
const CLASS_NAME = 'TranslationStrings';

// Load long translations mapping (key => full translation value)
let longTranslations = {};
if (fs.existsSync(LONG_TRANSLATIONS_FILE)) {
    try {
        longTranslations = JSON.parse(fs.readFileSync(LONG_TRANSLATIONS_FILE, 'utf8'));
        console.log(`Loaded ${Object.keys(longTranslations).length} long translation mappings\n`);
    } catch (e) {
        console.warn(`Warning: Could not parse ${LONG_TRANSLATIONS_FILE}: ${e.message}`);
    }
}

// Translation call names to scan for. `$t` is the global mixin method (and the
// module scope helper in resources/admin/Bits/i18n.js); `transWith` is the
// sprintf style wrapper used for strings with %s / %d placeholders.
const TRANSLATION_FUNCTIONS = ['\\$t', 'transWith'];

// Each pattern captures the first string argument. The quoted-string sub-patterns
// allow the opposite quote character and backslash escapes inside the literal, so
// strings like $t("Don't have a license key?") are extracted correctly.
const TRANSLATION_PATTERNS = TRANSLATION_FUNCTIONS.flatMap(fn => [
    // fn('string')  /  fn('string', ...)
    new RegExp(`(?:^|[^\\w$])${fn}\\(\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*[,)]`, 'g'),
    // fn("string")  /  fn("string", ...)
    new RegExp(`(?:^|[^\\w$])${fn}\\(\\s*"((?:[^"\\\\]|\\\\.)*)"\\s*[,)]`, 'g'),
]);

/**
 * Turn a JS string literal body into its real value (\' -> ', \" -> ", \\ -> \).
 */
function unescapeLiteral(raw) {
    return raw.replace(/\\(['"\\])/g, '$1');
}

/**
 * Recursively get all files with specific extensions from a directory
 */
function getFilesRecursively(dir, extensions = ['.vue', '.js']) {
    const files = [];

    if (!fs.existsSync(dir)) {
        return files;
    }

    const items = fs.readdirSync(dir);

    for (const item of items) {
        const fullPath = path.join(dir, item);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            files.push(...getFilesRecursively(fullPath, extensions));
        } else if (extensions.some(ext => item.endsWith(ext))) {
            files.push(fullPath);
        }
    }

    return files;
}

/**
 * Extract translation strings from a file
 */
function extractStringsFromFile(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const strings = new Set();

    for (const pattern of TRANSLATION_PATTERNS) {
        // Reset the regex lastIndex
        pattern.lastIndex = 0;

        let match;
        while ((match = pattern.exec(content)) !== null) {
            const str = unescapeLiteral(match[1]).trim();
            if (str && str.length > 0) {
                strings.add(str);
            }
        }
    }

    return Array.from(strings);
}

/**
 * Extract all translation strings from a directory (excluding specified paths)
 */
function extractStringsFromDirectory(dir, excludePaths = []) {
    const allStrings = new Set();
    const files = getFilesRecursively(dir);

    for (const file of files) {
        // Skip excluded paths
        const shouldExclude = excludePaths.some(excludePath =>
            file.startsWith(excludePath)
        );

        if (shouldExclude) {
            continue;
        }

        const strings = extractStringsFromFile(file);
        strings.forEach(str => allStrings.add(str));
    }

    return Array.from(allStrings).sort();
}

/**
 * Generate PHP array entries for translation strings
 */
function generateArrayEntries(strings, indent = '            ') {
    let entries = '';

    for (const str of strings) {
        // Escape single quotes in the key
        const escapedKey = str.replace(/'/g, "\\'");

        // Check if this key has a long translation mapping
        const translationValue = longTranslations[str] || str;
        const escapedValue = translationValue.replace(/'/g, "\\'");

        entries += `${indent}'${escapedKey}' => __('${escapedValue}', '${TEXT_DOMAIN}'),\n`;
    }

    return entries;
}

/**
 * Generate the complete PHP class file
 */
function generatePHPClass(adminStrings, portalStrings) {
    const timestamp = new Date().toISOString();

    return `<?php

namespace ${NAMESPACE};

/**
 * Translation Strings for Fluent Support
 *
 * Auto-generated translation strings extracted from Vue components.
 * Generated at: ${timestamp}
 *
 * DO NOT EDIT THIS FILE DIRECTLY
 * Run 'node extract-translations.js' to regenerate
 *
 * @package FluentSupport
 */

if (!defined('ABSPATH')) {
    exit;
}

class ${CLASS_NAME}
{
    /**
     * Get all admin panel translation strings
     *
     * @return array
     */
    public static function getAdminStrings()
    {
        return [
${generateArrayEntries(adminStrings)}        ];
    }

    /**
     * Get all customer portal translation strings
     *
     * @return array
     */
    public static function getPortalStrings()
    {
        return [
${generateArrayEntries(portalStrings)}        ];
    }

    /**
     * Get all translation strings (admin + portal combined)
     *
     * @return array
     */
    public static function getAllStrings()
    {
        return array_merge(self::getAdminStrings(), self::getPortalStrings());
    }
}
`;
}

/**
 * Ensure directory exists
 */
function ensureDirectoryExists(filePath) {
    const dir = path.dirname(filePath);
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
}

/**
 * Main function
 */
function main() {
    console.log('Extracting translation strings from Fluent Support...\n');

    // Extract strings from admin area (excluding customer_portal)
    console.log('Processing admin resources...');
    const adminStrings = extractStringsFromDirectory(RESOURCES_DIR, [CUSTOMER_PORTAL_DIR]);
    console.log(`  Found ${adminStrings.length} unique strings in admin area`);

    // Extract strings from customer portal
    console.log('Processing customer portal resources...');
    const portalStrings = extractStringsFromDirectory(CUSTOMER_PORTAL_DIR);
    console.log(`  Found ${portalStrings.length} unique strings in customer portal`);

    // Ensure output directory exists
    ensureDirectoryExists(OUTPUT_FILE);

    // Generate PHP class
    const phpContent = generatePHPClass(adminStrings, portalStrings);
    fs.writeFileSync(OUTPUT_FILE, phpContent);
    console.log(`\nGenerated: ${OUTPUT_FILE}`);

    // Print summary
    console.log('\n--- Summary ---');
    console.log(`Total admin strings: ${adminStrings.length}`);
    console.log(`Total portal strings: ${portalStrings.length}`);
    console.log(`Total unique strings: ${new Set([...adminStrings, ...portalStrings]).size}`);

    // Print some sample strings
    console.log('\n--- Sample Admin Strings (first 10) ---');
    adminStrings.slice(0, 10).forEach(str => console.log(`  - ${str}`));

    if (portalStrings.length > 0) {
        console.log('\n--- Sample Portal Strings (first 10) ---');
        portalStrings.slice(0, 10).forEach(str => console.log(`  - ${str}`));
    }

    console.log('\n--- Usage ---');
    console.log('use FluentSupport\\App\\Services\\TranslationStrings;');
    console.log('');
    console.log('$adminStrings = TranslationStrings::getAdminStrings();');
    console.log('$portalStrings = TranslationStrings::getPortalStrings();');
    console.log('$allStrings = TranslationStrings::getAllStrings();');

    console.log('\nDone!');
}

// Run the script
main();
