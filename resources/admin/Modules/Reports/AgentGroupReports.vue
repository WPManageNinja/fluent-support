<template>
    <div class="fs_agents_report">
        <div v-if="has_pro" class="fs_inside_menu_component_header">
            <div class="fs_component_head">
                <h3 class="fs_page_title">{{ $t("Agent Groups Reports") }}</h3>
            </div>
            <div class="fs_box_actions">
                <el-select
                    clearable
                    filterable
                    :placeholder="$t('All Agent Groups')"
                    @change="filterReport"
                    v-model="agentGroup"
                    class="fs_report_by_product fs_select_field fs_staff_filter fs_select_field_min_width"
                >
                    <el-option
                        v-for="group in appVars.agent_groups"
                        :key="group.id"
                        :value="group.id"
                        :label="group.title"
                    ></el-option>
                </el-select>
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
                            :clearable="true"
                        >
                        </el-date-picker>
                    </div>
                </div>
            </div>
        </div>
        <SideBar
            v-if="has_pro"
            :agent_group_id="agentGroup"
            :date_range="localDateRange"
        />
        <div v-if="has_pro" class="fs_box_wrapper" v-loading="loading">
            <el-row :gutter="30">
                <el-col :sm="24" :md="24" :lg="24">
                    <div class="fs_box">
                        <div class="fs_box_header">
                            <div class="fs_product_statistics_header">
                                <h4 class="fs_product_statistics_title">{{ $t("Agent Group Statistics") }}</h4>
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
                        </div>
                        <div class="fs_box_body">
                            <component
                                v-if="showing_charts"
                                :is="currently_showing"
                                :date_range="localDateRange"
                                :url="'agent-group-reports'"
                                :agent_group_id="agentGroup"
                            ></component>
                        </div>
                    </div>
                    <agent-group-report-summary
                        :url="'agent-group-reports/agent-groups-summary'"
                        :date_range="localDateRange"
                    />
                </el-col>
            </el-row>
        </div>
        <NarrowPromo
            v-else
            :heading="$t('get_overall_reports')"
            :description="$t('pro_promo')"
            :button-text="$t('Upgrade To Pro')"
            utm-content="feature_lock_agent_group_reports"
        />
    </div>
</template>

<script type="text/babel">
import TicketsChart from "./Charts/TicketsGrowth";
import ResponseChart from "./Charts/ResponseGrowth";
import ResolveChart from "./Charts/ResolveGrowth";
import SideBar from "./Parts/_SideBar";
import {shortcuts} from "./Utils/dateShortCuts";
import AgentGroupReportSummary from "./AgentGroupReportSummary";
import NarrowPromo from "@/admin/Components/NarrowPromo";
import IconPack from "@/admin/Components/IconPack";
import { formatDateRangeForDisplay, getDefaultDateRange } from "./Utils/reportHelpers";

export default {
    name: "AgentGroupReports",
    props: ["date_range"],
    components: {
        TicketsChart,
        ResponseChart,
        ResolveChart,
        AgentGroupReportSummary,
        SideBar,
        NarrowPromo,
        IconPack
    },
    data() {
        return {
            loading: false,
            currently_showing: "tickets-chart",
            localDateRange: this.date_range,
            showing_charts: true,
            shortcuts: shortcuts,
            chartMaps: {
                "tickets-chart": this.$t("Ticket Stats"),
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            agentGroup: "",
        };
    },
    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
    },
    watch: {
        date_range: {
            handler(newVal) {
                this.localDateRange = newVal;
            },
            deep: true
        }
    },
    methods: {
        handleDateChange() {
            this.$emit('date-change', this.localDateRange);
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
        handleComponentChange(item) {
            this.currently_showing = item;
        },
    },
    mounted() {
        this.$setTitle("Reports");
    },
};
</script>
