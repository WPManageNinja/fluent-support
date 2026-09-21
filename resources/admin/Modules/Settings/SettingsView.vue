<template>
    <div class="fs_tickets_view fs_settings_view">
        <!-- Mobile Drawer Overlay -->
        <div
            class="fs_settings_menu_overlay"
            :class="{ 'is-open': isMenuOpen }"
            @click.stop="closeMenu"
            v-show="isMenuOpen"
        ></div>

        <!-- Mobile Toggle Button -->
        <div class="fs_settings_menu_toggle" :class="{ 'is-hidden': isMenuOpen }">
            <el-button class="fs_outline_btn fs_settings_menu_toggle_btn" v-if="!isMenuOpen" @click="toggleMenu">
                <img :src="appVars.asset_url + 'images/settings.svg'" alt="Settings" />
                {{ $t('Settings') }}
            </el-button>
        </div>

        <div class="fs_settings_wrapper">
            <!-- Desktop Sidebar (always visible on desktop) -->
            <div class="fs_inner_sidebar fs_desktop_sidebar">
                <ul>
                    <li v-for="(item, index) in settings_items" :key="index">
                        <!-- Group item with children -->
                        <template v-if="item.children && item.children.length">
                            <button
                                class="fs_sidebar_group_toggle"
                                :class="{ 'is-open': openGroups[index] }"
                                @click="toggleGroup(index)"
                            >
                                <img :src="appVars.asset_url + 'images/' + item.icon + '.svg'" class="fs-sidebar-icons" />
                                <span>{{ item.title }}</span>
                                <svg class="fs_sidebar_chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <ul v-show="openGroups[index]" class="fs_sidebar_children">
                                <li v-for="(child, ci) in item.children" :key="ci">
                                    <router-link :to="{ name: child.route_name }">
                                        {{ child.title }}
                                    </router-link>
                                </li>
                            </ul>
                        </template>

                        <!-- Regular flat item -->
                        <router-link
                            v-else
                            :to="{
                                name: item.route_name,
                                query: item.route_query,
                            }"
                        >
                            <img :src="appVars.asset_url + 'images/' + item.icon + '.svg'" class="fs-sidebar-icons" />
                            {{ item.title }}
                        </router-link>
                    </li>
                </ul>
            </div>

            <!-- Mobile Drawer Sidebar (only visible on mobile when toggled) -->
            <div class="fs_inner_sidebar fs_mobile_drawer" :class="{ 'is-open': isMenuOpen }">
                <ul>
                    <li v-for="(item, index) in settings_items" :key="index">
                        <!-- Group item with children -->
                        <template v-if="item.children && item.children.length">
                            <button
                                class="fs_sidebar_group_toggle"
                                :class="{ 'is-open': openGroups[index] }"
                                @click="toggleGroup(index)"
                            >
                                <img :src="appVars.asset_url + 'images/' + item.icon + '.svg'" class="fs-sidebar-icons" />
                                <span>{{ item.title }}</span>
                                <svg class="fs_sidebar_chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <ul v-show="openGroups[index]" class="fs_sidebar_children">
                                <li v-for="(child, ci) in item.children" :key="ci">
                                    <router-link :to="{ name: child.route_name }" @click="closeMenu">
                                        {{ child.title }}
                                    </router-link>
                                </li>
                            </ul>
                        </template>

                        <!-- Regular flat item -->
                        <router-link
                            v-else
                            :to="{
                                name: item.route_name,
                                query: item.route_query,
                            }"
                            @click="closeMenu"
                        >
                            <img :src="appVars.asset_url + 'images/' + item.icon + '.svg'" class="fs-sidebar-icons" />
                            {{ item.title }}
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="fs_inner_body">
                <router-view key="products_view"></router-view>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: "SettingsView",
    data() {
        return {
            settings_items: [],
            isMenuOpen: false,
            openGroups: {},
        }
    },
    watch: {
        $route() {
            this.autoOpenActiveGroup();
        },
        settings_items() {
            this.autoOpenActiveGroup();
        },
    },
    methods: {
        fetchSettingsMenu() {
            this.$get("settings/settings-menu")
                .then((response) => {
                    this.settings_items = response;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
        },
        toggleGroup(index) {
            this.openGroups = {
                ...this.openGroups,
                [index]: !this.openGroups[index],
            };
        },
        autoOpenActiveGroup() {
            const currentRoute = this.$route.name;
            this.settings_items.forEach((item, index) => {
                if (item.children && item.children.length) {
                    const hasActive = item.children.some(c => c.route_name === currentRoute);
                    if (hasActive) {
                        this.openGroups = { ...this.openGroups, [index]: true };
                    }
                }
            });
        },
        toggleMenu() {
            this.isMenuOpen = !this.isMenuOpen;
            if (this.isMenuOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
        closeMenu() {
            this.isMenuOpen = false;
            document.body.style.overflow = '';
        }
    },
    mounted() {
        this.fetchSettingsMenu();
    },
    beforeUnmount() {
        document.body.style.overflow = '';
    }
};
</script>
