<template>
    <div class="fs_client_portal">
        <router-view></router-view>
    </div>
</template>

<script>
    export default {
        data() {
            return {
            }
        },
        methods: {
            handleApplicationWidth() {
                const appWidth = document.querySelector('.fs_client_portal');
                if (appWidth) {
                    const contentWidth = appWidth.offsetWidth;
                    
                    if (contentWidth <= 755) {
                        appWidth.classList.add('fs_customer_portal_mobile');
                    } else {
                        appWidth.classList.remove('fs_customer_portal_mobile');
                    }
                }
            }
        },
        mounted() {
            this.handleApplicationWidth();
            window.addEventListener('resize', this.handleApplicationWidth);
        }
    }
</script>
