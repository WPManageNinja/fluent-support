<template>
    <div class="fs_personal_reports_header">
        <div class="fs_personal_reports_header_row">
            <h3 class="fs_page_title">{{ $t("Personal Reports") }}</h3>
        </div>
        <div class="fs_personal_reports_header_row fs_personal_reports_toolbar">
            <div class="fs_toolbar_left">
                <div class="fs_date_button_group">
                    <div class="fs_date_button_group_item fs_date_picker_wrapper">
                        <div class="fs_date_display">
                            <IconPack icon-key="calendar" :width="20" :height="20" class="fs_calendar_icon" />
                            <span v-if="formattedDateRange" class="fs_date_text">{{ formattedDateRange }}</span>
                            <span v-else class="fs_date_placeholder">{{ $t('Select date range') }}</span>
                        </div>
                        <el-date-picker
                            v-model="localDateRange"
                            type="daterange"
                            :editable="false"
                            :range-separator="$t('To')"
                            :start-placeholder="$t('Start')"
                            :end-placeholder="$t('End')"
                            :unlink-panels="true"
                            :shortcuts="shortcuts"
                            @change="handleDateChange"
                            value-format="YYYY-MM-DD"
                            class="fs_date_range_picker"
                        >
                            <template #prefix-icon>
                                <el-icon><Calendar /></el-icon>
                            </template>
                        </el-date-picker>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="fs_stat_boxes_row" v-loading="loading">
        <StatBox
            v-for="(stat, index) in mergedReports"
            :key="index"
            :label="stat.label"
            :value="stat.value"
            :icon="stat.icon"
            :icon-bg="stat.iconBg"
        />
    </div>
    <div class="fs_personal_report">
        <div class="fs_box_wrapper" v-loading="loading">
            <el-row :gutter="30">
                <el-col :sm="24" :md="14" :lg="16">
                    <div class="fs_box">
                        <div class="fs_box_header fs_personal_report_header">
                            <h3 class="fs_personal_report_title">{{ $t("My Statistics") }}</h3>
                            <div class="fs_status_tabs">
                                <div class="fs_segmented_control">
                                    <button
                                        v-for="(mapName, mapKey) in chartMaps"
                                        :key="mapKey"
                                        @click="handleComponentChange(mapKey)"
                                        :class="['fs_segment_button', { 'fs_segment_active': currently_showing === mapKey }]"
                                    >
                                        {{ mapName }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="fs_box_body">
                            <component
                                v-if="showing_charts"
                                :is="currently_showing"
                                :date_range="localDateRange"
                                :url="'my-reports'"
                                :compact="true"
                            ></component>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="24" :md="10" :lg="8">
                    <agent-reports :url="'my-reports/my-summary'" :is_personal="true" :date_range="localDateRange" />
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
import ResponseChart from "./Charts/ResponseGrowth";
import ResolveChart from "./Charts/ResolveGrowth";
import AgentReports from "./AgentReports";
import StatBox from "./Parts/StatBox";
import { ArrowDown, Calendar, Filter, Download } from "@element-plus/icons-vue";
import {shortcuts} from "./Utils/dateShortCuts";
import { formatDateRangeForDisplay, formatStatValue } from "./Utils/reportHelpers";
import IconPack from "@/admin/Components/IconPack";

export default {
    name: "PersonalReports",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        ResponseChart,
        ResolveChart,
        AgentReports,
        StatBox,
        ArrowDown,
        Calendar,
        Filter,
        Download,
        IconPack
    },

    data() {

        return {
            loading: false,
            stat_loading: false,
            overall_reports: {},
            currently_showing: "resolve-chart",
            showing_charts: true,
            localDateRange: this.date_range,
            chartMaps: {
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            me: this.appVars.me,
            today_reports: {},
            shortcuts: shortcuts,
        };
    },

    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        mergedReports() {
            const stats = [];
            const iconMap = {
                'tickets': 'new_tickets',
                'new_tickets': 'new_tickets',
                'closed': 'closed_tickets',
                'closed_tickets': 'closed_tickets',
                'responses': 'responses',
                'active': 'active_agents',
                'active_tickets': 'active_tickets',
                'open': 'open_tickets',
                'open_tickets': 'open_tickets',
                'pending': 'new_tickets',
                'replies': 'reply',
                'total_replies': 'total_replies',
                'resolved': 'closed_tickets',
            };
            const defaultIcon = 'new_tickets';

            // Merge overall_reports
            Object.keys(this.overall_reports || {}).forEach((key) => {
                const stat = this.overall_reports[key];
                const statKey = key.toLowerCase();
                stats.push({
                    label: stat.title || key,
                    value: this.formatStatValue(stat.count || 0),
                    icon: iconMap[statKey] || iconMap[statKey.replace('_', '')] || iconMap[statKey.split('_')[0]] || defaultIcon,
                    iconBg: '',
                });
            });


            return stats;
        },
    },

    watch: {
        date_range: {
            handler(newVal) {
                this.localDateRange = newVal;
                this.fetchReports();
            },
            deep: true
        }
    },

    methods: {
        formatStatValue,
        handleDateChange() {
            this.$emit('date-change', this.localDateRange);
            this.fetchReports();
        },
        async fetchReports() {
            this.loading = true;
            const agent_id = this.me.id;
            const params = {
                agent_id: agent_id
            };

            if (this.localDateRange && Array.isArray(this.localDateRange) && this.localDateRange.length === 2) {
                if (this.localDateRange[0] && this.localDateRange[1]) {
                    params.date_range = this.localDateRange;
                }
            }

            await this.$get(this.url, params)
                .then((response) => {
                    this.overall_reports = response.overall_reports;
                    this.today_reports = response.today_reports;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        handleComponentChange(item) {
            this.currently_showing = item;
        },

        filterReport() {
            const current = this.currently_showing;
            this.currently_showing = {
                render: () => {},
            };
            this.$nextTick(() => {
                this.currently_showing = current;
            });
        },
    },

    mounted() {
        this.fetchReports();
        this.$setTitle(this.$t("Personal Reports"));
    },
};
</script>
