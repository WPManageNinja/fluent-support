<template>
    <div class="fs_color_mode">
        <el-dropdown
            placement="bottom-end"
            trigger="click"
            popper-class="fs_theme_dropdown"
            @command="handleThemeChange"
        >
            <div class="fs_color_mode_core fs_menu_button">
                <span class="fs_color_mode_action">
                    <IconPack :icon-key="'theme_' + currentTheme" width="20" height="20" />
                </span>
            </div>

            <template #dropdown>
                <el-dropdown-menu>
                    <el-dropdown-item
                        v-for="option in themeOptions"
                        :key="option.value"
                        :command="option.value"
                        :class="{ 'is-active': currentTheme === option.value }"
                    >
                        <IconPack :icon-key="'theme_' + option.value" width="16" height="16" />
                        {{ option.label }}
                    </el-dropdown-item>
                </el-dropdown-menu>
            </template>
        </el-dropdown>
    </div>
</template>

<script>
import IconPack from '../Components/IconPack.vue';
import Theme from '../Bits/Theme';

export default {
    name: 'ColorMode',
    components: {
        IconPack,
    },
    data() {
        return {
            currentTheme: Theme.getCurrentTheme(),
            themeOptions: [
                { value: Theme.MODE_LIGHT, label: this.$t('Light') },
                { value: Theme.MODE_DARK, label: this.$t('Dark') },
                { value: Theme.MODE_SYSTEM, label: this.$t('System') },
            ],
        };
    },
    methods: {
        handleThemeChange(command) {
            if (this.currentTheme === command) {
                return;
            }
            this.currentTheme = command;
            Theme.apply(command);
        },
    },
};
</script>
