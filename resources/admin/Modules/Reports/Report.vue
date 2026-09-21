<template>
    <div class="fs_reports_wrapper">
        <!-- Mobile Drawer Overlay -->
        <div
            class="fs_reports_menu_overlay"
            :class="{ 'is-open': isMenuOpen }"
            @click.stop="closeMenu"
            v-show="isMenuOpen"
        ></div>

        <!-- Mobile Toggle Button -->
        <div class="fs_reports_menu_toggle" :class="{ 'is-hidden': isMenuOpen }">
            <el-button class="fs_outline_btn fs_reports_menu_toggle_btn" v-if="!isMenuOpen" @click="toggleMenu">
                <el-icon><Menu /></el-icon>
                {{ $t('Reports') }}
            </el-button>
        </div>

        <div class="fs_reports_layout">
            <!-- Desktop Sidebar (always visible on desktop) -->
            <ReportSidebar
                variant="desktop"
                :tabs="visibleTabs"
                :active-name="activeName"
                :collapsed="isSidebarCollapsed"
                @select="setActiveTab"
                @toggle-collapse="toggleSidebarCollapse"
            />

            <!-- Mobile Drawer Sidebar (only visible on mobile when toggled) -->
            <ReportSidebar
                variant="mobile"
                :tabs="visibleTabs"
                :active-name="activeName"
                :is-open="isMenuOpen"
                @select="setActiveTab"
                @toggle-collapse="closeMenu"
            />

            <div class="fs_box_wrapper">
                <div class="fs_reports_content">
                    <div class="fs_component_dashboard">
                        <keep-alive>
                            <component
                                :is="activeComponent"
                                :url="activeComponentUrl"
                                :key="activeName"
                                :date_range="date_range"
                                v-bind="activeComponentProps"
                                @date-change="handleDateChange"
                                @navigate-tab="setActiveTab"
                            />
                        </keep-alive>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import PersonalReports from "./PersonalReports";
import ActivityByTimeOfDay from "./ActivityByTimeOfDay";
import TimeSheet from "./TimeSheet/TimeSheet.vue";
import AdvanceReportPlaceholder from "./AdvanceReport/AdvanceReportPlaceholder.vue";
import TicketOverview from "./AdvanceReport/TicketOverview.vue";
import AgentPerformance from "./AdvanceReport/PerformanceOverview.vue";
import WorkloadHealth from "./AdvanceReport/WorkloadHealth.vue";
import ProductInsights from "./AdvanceReport/ProductInsights.vue";
import TicketAudit from "./AdvanceReport/TicketAudit.vue";
import ReportSidebar from "./Parts/ReportSidebar.vue";
import { getDefaultDateRange } from "./Utils/reportHelpers";
import { Menu } from '@element-plus/icons-vue';
import { $t } from '@/admin/Bits/i18n';

const reportTabs = [
    {
        name: 'ticket-overview',
        label: $t('Ticket Overview'),
        icon: 'ticket_overview',
        component: 'TicketOverview',
        sensitive: true
    },
    {
        name: 'personal-reports',
        label: $t('Personal Reports'),
        icon: 'personal_reports',
        component: 'PersonalReports',
        url: 'reports/stats',
        sensitive: false,
        free: true
    },
    {
        name: 'agent-performance',
        label: $t('Agent Performance'),
        icon: 'team_performance',
        component: 'AgentPerformance',
        sensitive: true
    },
    {
        name: 'product-insight',
        label: $t('Product Insight'),
        icon: 'product_support',
        component: 'ProductInsights',
        sensitive: true
    },
    {
        name: 'business-boxes',
        label: $t('Business Boxes'),
        icon: 'business_boxes_report',
        component: 'WorkloadHealth',
        sensitive: true
    },
    {
        name: 'activity-reports',
        label: $t('Activity'),
        icon: 'activity_reports',
        component: 'ActivityByTimeOfDay',
        url: 'activity-by-time-of-day',
        sensitive: true
    },
    {
        name: 'time-sheet',
        label: $t('Time Sheet'),
        icon: 'time_sheet',
        component: 'TimeSheet',
        url: 'time-sheet',
        sensitive: true,
        requiresTimeTracking: true
    },
    {
        name: 'ai-ticket-audit',
        label: $t('AI Ticket Audit'),
        icon: 'ai_ticket_audit',
        component: 'TicketAudit',
        sensitive: true
    }
];

const defaultTab = 'personal-reports';

export default {
    name: 'Report',
    components: {
        PersonalReports,
        ActivityByTimeOfDay,
        TimeSheet,
        AdvanceReportPlaceholder,
        TicketOverview,
        AgentPerformance,
        WorkloadHealth,
        ProductInsights,
        TicketAudit,
        ReportSidebar,
        Menu
    },

    data() {
        const tabFromUrl = this.getTabFromUrl();
        const dateRange = this.getDateRangeFromUrl() || getDefaultDateRange();
        return {
            activeName: tabFromUrl,
            me: this.appVars.me,
            date_range: dateRange,
            isMenuOpen: false,
            isSidebarCollapsed: this.$getData('reportsMenuCollapsed') === 'yes'
        }
    },

    computed: {
        hasSensitiveAccess() {
            return this.me.permissions.indexOf('fst_sensitive_data') !== -1;
        },
        visibleTabs() {
            return reportTabs.filter(tab => {
                if (tab.sensitive && !this.hasSensitiveAccess) {
                    return false;
                }
                if (tab.requiresTimeTracking && (this.appVars.agent_time_tracking !== 'yes' || !this.has_pro)) {
                    return false;
                }
                return true;
            });
        },
        activeTab() {
            return reportTabs.find(tab => tab.name === this.activeName);
        },
        isProLockedTab() {
            return !!this.activeTab && !this.activeTab.free && !this.has_pro;
        },
        activeComponent() {
            if (this.isProLockedTab) {
                return 'AdvanceReportPlaceholder';
            }
            return (this.activeTab && this.activeTab.component) || 'AdvanceReportPlaceholder';
        },
        activeComponentUrl() {
            return (this.activeTab && this.activeTab.url) || '';
        },
        activeReportTabTitle() {
            return this.activeTab ? this.activeTab.label : '';
        },
        activeComponentProps() {
            if (this.activeComponent !== 'AdvanceReportPlaceholder') {
                return {};
            }

            if (this.isProLockedTab) {
                return {
                    title: this.activeReportTabTitle,
                    description: this.$t('Upgrade to Fluent Support Pro to unlock this report.'),
                    buttonText: this.$t('Upgrade To Pro'),
                    utmContent: 'feature_lock_report_' + this.activeName
                };
            }

            return { title: this.activeReportTabTitle };
        }
    },

    methods: {
        getHashParams() {
            const hash = window.location.hash;
            const queryIndex = hash.indexOf('?');
            if (queryIndex !== -1) {
                return new URLSearchParams(hash.substring(queryIndex + 1));
            }
            return new URLSearchParams();
        },

        getTabFromUrl() {
            const TAB_ALIASES = {
                'response-activity': 'activity-reports'
            };
            const rawTab = this.getHashParams().get('tab');
            const tab = TAB_ALIASES[rawTab] || rawTab;

            return reportTabs.some(item => item.name === tab) ? tab : defaultTab;
        },

        getDateRangeFromUrl() {
            const urlParams = this.getHashParams();
            const from = urlParams.get('from');
            const to = urlParams.get('to');
            if (from && to) {
                return [from, to];
            }
            return null;
        },

        setActiveTab(tabName) {
            this.activeName = tabName;
            this.updateUrl();
            this.closeMenu();
        },

        toggleMenu() {
            this.isMenuOpen = !this.isMenuOpen;
            if (this.isMenuOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },

        toggleSidebarCollapse() {
            this.isSidebarCollapsed = !this.isSidebarCollapsed;
            this.$saveData('reportsMenuCollapsed', this.isSidebarCollapsed ? 'yes' : 'no');
        },

        closeMenu() {
            this.isMenuOpen = false;
            document.body.style.overflow = '';
        },

        updateUrl() {
            const hash = window.location.hash;
            const hashPath = hash.split('?')[0] || '#/reports';
            const params = new URLSearchParams();

            params.set('tab', this.activeName);
            // Avoid pushing date params when in Time Sheet tab
            if (this.activeName !== 'time-sheet' && this.date_range && this.date_range.length === 2) {
                params.set('from', this.date_range[0]);
                params.set('to', this.date_range[1]);
            }

            const newHash = hashPath + '?' + params.toString();
            window.history.pushState({}, '', window.location.pathname + window.location.search + newHash);
        },

        handleDateChange(newDateRange) {
            this.date_range = newDateRange;
            this.updateUrl();
        }
    },

    mounted() {
        // Handle browser back/forward navigation
        window.addEventListener('popstate', () => {
            this.activeName = this.getTabFromUrl();

            const dateRange = this.getDateRangeFromUrl();
            if (dateRange) {
                this.date_range = dateRange;
            }
        });
    },

    beforeUnmount() {
        document.body.style.overflow = '';
    }
}
</script>
