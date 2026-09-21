<template>
    <div class="fluent_framework-app">
        <div class="fluent_framework-main-menu-items"></div>
        <div class="fluent_framework-body">
            <el-config-provider :locale="locale">
                <router-view />
            </el-config-provider>
        </div>

    </div>
</template>

<script type="text/babel">
    import { ElConfigProvider } from 'element-plus'
    import en from 'element-plus/lib/locale/lang/en'
    export default {
        name: 'FluentFrameworkDashboard',
        components: {
            ElConfigProvider,
        },
        data() {
            return {
                locale: en,
            }
        },
        mounted() {
            jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();
        }
    };
</script>
