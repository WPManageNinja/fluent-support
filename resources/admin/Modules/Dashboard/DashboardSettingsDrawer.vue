<template>
    <el-drawer
        class="fs_dashboard_settings"
        v-model="isOpen"
        :with-header="false"
        size="400px"
    >
        <!-- Drawer Header -->
        <div class="fs_drawer_header">
            <div class="fs_drawer_header_content">
                <div class="fs_drawer_title">
                    <h2>{{ $t("All Widgets") }}</h2>
                </div>
                <el-button
                    class="fs_drawer_close_btn"
                    @click="handleClose"
                    text
                    circle
                >   
                <IconPack :icon-key="'close'" />
                </el-button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="fs_drawer_search">
            <el-input
                v-model="searchQuery"
                class="fs_text_input"
                :placeholder="$t('Search Widget...')"
                clearable
            >
                <template #prefix>
                    <IconPack :icon-key="'search'" class="fs_search_icon" />
                </template>
            </el-input>
        </div>

        <!-- Widget List -->
        <div class="fs_drawer_content">
            <!-- Dashboard Component Widgets -->
            <template v-for="column_data in dashboardParam" :key="column_data">
                <div
                    v-for="component_list_data in column_data"
                    :key="component_list_data.id"
                    class="fs_widget_card"
                    v-show="isWidgetVisible(component_list_data)"
                >
                    <div class="fs_widget_header">
                        <div class="fs_widget_info">
                            <IconPack
                                :icon-key="component_list_data.icon"
                                class="fs_widget_icon"
                            />
                            <span class="fs_widget_title">
                                {{ $t(getWidgetDisplayName(component_list_data.component)) }}
                            </span>
                        </div>
                        <el-button
                            class="fs_widget_action_btn"
                            :class="{ 'added': component_list_data.show }"
                            @click="toggleWidget(component_list_data, 'component')"
                            size="small"
                        >
                            <el-icon>
                                <Check v-if="component_list_data.show" />
                                <Plus v-else />
                            </el-icon>
                            <span>
                                {{ component_list_data.show ? $t("Added") : $t("Add to home") }}
                            </span>
                        </el-button>
                    </div>
                    <div class="fs_widget_preview">
                        <div class="fs_widget_skeleton">
                            <!-- Dynamic Widget Preview based on component type -->
                            <img 
                                :src="appVars.asset_url + component_list_data.image"
                                class="fs_widget_preview_image"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </el-drawer>
</template>

<script>
import { 
    Close, 
    Search, 
    Plus, 
    Check, 
    Message,
    Star,
    DataAnalysis,
    Box,
    User
} from '@element-plus/icons-vue';
import IconPack from "../../Components/IconPack.vue";

export default {
    name: "DashboardSettingsDrawer",
    
    components: {
        Close,
        Search,
        Plus,
        Check,
        Message,
        Star,
        DataAnalysis,
        Box,
        User,
        IconPack
    },
    
    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        dashboardParam: {
            type: Object,
            required: true
        }
    },

    emits: ['update:modelValue', 'close', 'widget-toggled'],

    data() {
        return {
            searchQuery: '',
            greetingImage: "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjQiIGN5PSIyNCIgcj0iMjQiIGZpbGw9IiNFMUU0RUEiLz4KPHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBzdHlsZT0idHJhbnNmb3JtOiB0cmFuc2xhdGUoMTJweCwgMTJweCk7Ij4KPHBhdGggZD0iTTEyIDJDNi40OCAyIDIgNi40OCAyIDEyQzIgMTcuNTIgNi40OCAyMiAxMiAyMkMxNy41MiAyMiAyMiAxNy41MiAyMiAxMkMyMiA2LjQ4IDE3LjUyIDIgMTIgMloiIGZpbGw9IiM5OUEwQUUiLz4KPC9zdmc+Cjwvc3ZnPgo=",
        };
    },

    computed: {
        isOpen: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit('update:modelValue', value);
            }
        },
        
        isGreetingVisible() {
            if (!this.searchQuery) return true;
            return this.$t("Greeting Message").toLowerCase().includes(this.searchQuery.toLowerCase());
        }
    },

    methods: {
        handleClose() {
            this.$emit('close');
            this.isOpen = false;
        },

        toggleWidget(widget, type = 'greeting') {
            if (type === 'greeting') {
                this.dashboardParam.greetingMessage = !this.dashboardParam.greetingMessage;
            } else {
                widget.show = !widget.show;
            }
            this.$emit('widget-toggled', { widget, type });
        },

        getWidgetIcon(component) {
            const iconMap = {
                'MentionedTicket': 'Star',
                'SuggestedTicket': 'Lightbulb', 
                'TicketsByProduct': 'Box',
                'AgentPerformance': 'User',
                'TicketStatistics': 'DataAnalysis'
            };
            return iconMap[component] || 'Star';
        },

        getWidgetDisplayName(component) {
            const nameMap = {
                'MentionedTicket': 'Bookmarked Tickets',
                'SuggestedTicket': 'Suggested Tickets',
                'TicketsByProduct': 'Tickets by Products', 
                'AgentPerformance': 'Agent Performance',
                'TicketStatistics': 'Tickets Statistic'
            };
            return nameMap[component] || component;
        },

        isWidgetVisible(widget) {
            if (!this.searchQuery) return true;
            const displayName = this.getWidgetDisplayName(widget.component);
            return this.$t(displayName).toLowerCase().includes(this.searchQuery.toLowerCase());
        }
    }
};
</script>