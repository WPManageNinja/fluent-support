class Theme {
    static MODE_LIGHT = 'light';
    static MODE_DARK = 'dark';
    static MODE_SYSTEM = 'system';
    static STORAGE_KEY = 'fs-theme';

    static getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return this.MODE_DARK;
        }
        return this.MODE_LIGHT;
    }

    static getSavedTheme() {
        return localStorage.getItem(this.STORAGE_KEY);
    }

    static getCurrentTheme() {
        const saved = this.getSavedTheme();
        if (saved === this.MODE_DARK || saved === this.MODE_LIGHT) {
            return saved;
        }
        return this.MODE_SYSTEM;
    }

    static isDark() {
        if (this.isSystem()) {
            return this.getSystemTheme() === this.MODE_DARK;
        }
        return this.getCurrentTheme() === this.MODE_DARK;
    }

    static isSystem() {
        const saved = this.getSavedTheme();
        return !saved || saved.startsWith(this.MODE_SYSTEM);
    }

    static apply(mode) {
        let resolvedMode = mode;

        if (mode === this.MODE_SYSTEM) {
            resolvedMode = this.getSystemTheme();
        }

        var targets = [document.documentElement, document.body];
        targets.forEach(function (el) {
            if (!el) return;
            if (resolvedMode === 'dark') {
                el.classList.add('fs-dark-mode');
            } else {
                el.classList.remove('fs-dark-mode');
            }
        });

        if (mode === this.MODE_SYSTEM) {
            localStorage.setItem(this.STORAGE_KEY, `${mode}:${resolvedMode}`);
        } else {
            localStorage.setItem(this.STORAGE_KEY, mode);
        }

        window.dispatchEvent(new CustomEvent('fs-theme-changed', { detail: { mode: resolvedMode } }));
    }

    listenForSystemChange() {
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (Theme.isSystem()) {
                    Theme.apply(Theme.MODE_SYSTEM);
                }
            });
        }
    }

    init() {
        this.listenForSystemChange();
        Theme.apply(Theme.getCurrentTheme());
    }
}

(new Theme).init();

export default Theme;
