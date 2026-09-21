<template>
    <div class="fs_agents_report">
        <div v-if="has_pro" class="fs_inside_menu_component_header">
            <div class="fs_component_head">
                <h3 class="fs_page_title">{{ $t("Business Box Reports") }}</h3>
            </div>
            <div class="fs_box_actions">
                <el-select
                    clearable
                    filterable
                    :placeholder="$t('All Business Box')"
                    @change="filterReport"
                    v-model="mailbox"
                    class="fs_report_by_product fs_select_field fs_staff_filter fs_select_field_min_width"
                >
                    <el-option
                        v-for="mailbox in appVars.mailboxes"
                        :key="mailbox.id"
                        :value="mailbox.id"
                        :label="mailbox.name"
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
            :mailbox_id="mailbox"
            :date_range="localDateRange"
        />
        <div v-if="has_pro" class="fs_box_wrapper" v-loading="loading">
            <el-row :gutter="30">
                <el-col :sm="24" :md="24" :lg="24">
                    <div class="fs_box">
                        <div class="fs_box_header fs_agent_report_header">
                            <h3 class="fs_section_title">{{ $t("Business Box Statistics") }}</h3>
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
                                :url="'mailbox-reports'"
                                :mailbox_id="mailbox"
                                type="mailbox"
                            ></component>
                        </div>
                    </div>
                    <business-box-report-summary
                        :url="'mailbox-reports/mailbox-reports-summary'"
                        :show_settings="true"
                        :show_export_btn="true"
                        :date_range="localDateRange"
                        :mailbox_id="mailbox"
                    />
                </el-col>
            </el-row>
        </div>
        <NarrowPromo
            v-else
            :heading="$t('get_overall_reports')"
            :description="$t('pro_promo')"
            :button-text="$t('Upgrade To Pro')"
            utm-content="feature_lock_mailbox_reports"
        />
    </div>
</template>

<script type="text/babel">
import TicketsChart from "./Charts/TicketsGrowth";
import ResponseChart from "./Charts/ResponseGrowth";
import ResolveChart from "./Charts/ResolveGrowth";
import SideBar from "./Parts/_SideBar"
import {shortcuts} from "./Utils/dateShortCuts";
import BusinessBoxReportSummary from "./BusinessBoxReportSummary";
import NarrowPromo from "@/admin/Components/NarrowPromo";
import IconPack from "@/admin/Components/IconPack";
import { formatDateRangeForDisplay, getDefaultDateRange } from "./Utils/reportHelpers";

export default {
    name: "BusinessBoxReports",
    props: ["date_range"],
    components: {
        TicketsChart,
        ResponseChart,
        ResolveChart,
        BusinessBoxReportSummary,
        SideBar,
        NarrowPromo,
        IconPack
    },
    data() {
        return {
            loading: false,
            stat_loading: false,
            currently_showing: "tickets-chart",
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            showing_charts: true,
            chartMaps: {
                "tickets-chart": this.$t("Ticket Stats"),
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            mailbox: "",
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

<style lang="scss">
.fs_product_report_rh_filter {
    display: flex;
    align-items: center;
    .fs_report_by_product {
        width: auto;
        margin-right: 0.3em;
    }
    @media (max-width: 767px) {
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        justify-content: center;
        padding: 0.7em;
        .fs_report_by_product {
            width: auto;
            margin-right: 0;
            margin-bottom: 0.4em;
        }
    }
}
</style>

