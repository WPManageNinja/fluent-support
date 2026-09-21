// Ticket/AI content can contain literal `class="el-overlay"` style markup (e.g.
// copied dev-tools output, or an AI draft echoing such content back). The admin
// panel ships Element Plus's real .el-overlay/.el-dialog CSS globally, so such
// classes would otherwise become live full-viewport overlays that break real
// dialogs (like delete confirmations) rendered elsewhere on the page.
//
// This strips any `el-`-prefixed class token before it reaches the DOM, so
// pasted/generated content can never match Element Plus's own selectors.
export function stripReservedElementPlusClasses(purifyInstance) {
    if (!purifyInstance || purifyInstance.__fsStripReservedElClassHook) {
        return;
    }

    purifyInstance.addHook('uponSanitizeAttribute', (node, data) => {
        if (data.attrName === 'class' && data.attrValue) {
            data.attrValue = data.attrValue
                .split(/\s+/)
                .filter((cls) => !/^el-/.test(cls))
                .join(' ');
        }
    });

    purifyInstance.__fsStripReservedElClassHook = true;
}
