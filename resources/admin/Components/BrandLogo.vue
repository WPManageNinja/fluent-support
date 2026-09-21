<template>
    <div class="fs_logo_section">
        <span class="fs_brand_logo">
            <img :src="logoSrc" />
        </span>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'BrandLogo',
    props: {
        assetUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            isDark: document.body.classList.contains('fs-dark-mode')
        }
    },
    computed: {
        logoSrc() {
            const file = this.isDark
                ? 'dashboard_onboarding_logo_dark.png'
                : 'dashboard_onboarding_logo.png';
            return this.assetUrl + 'images/' + file;
        }
    },
    mounted() {
        this.observer = new MutationObserver(() => {
            this.isDark = document.body.classList.contains('fs-dark-mode');
        });
        this.observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });
    },
    beforeUnmount() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}
</script>
