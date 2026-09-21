/**
 * Module scope translation helper.
 *
 * Components should keep using the global `this.$t()` mixin method. Use this
 * helper only for strings defined outside of a component instance (module level
 * constants, shared option lists, etc.) where `this` is not available.
 *
 * Keep the string literal inside the call. `extract-translations.js` scans for
 * `$t('...')` to build app/Services/TranslationStrings.php.
 */
export function $t(string) {
    const i18n = window.fluentSupportAdmin && window.fluentSupportAdmin.i18n;
    return (i18n && i18n[string]) || string;
}

export default $t;
