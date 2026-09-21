<template>
    <div class="fs_agents_report">
        <div v-if="has_pro">
            <!-- Header Section -->
            <div class="fs_inside_menu_component_header">
                <div class="fs_component_head">
                    <h3 class="fs_page_title">{{ $t("Agents Reports") }}</h3>
                </div>
                <div class="fs_box_actions">
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
                                @change="handleDateChange"
                                range-separator="To"
                                :disabled-date="disabledDate"
                                value-format="YYYY-MM-DD"
                                :start-placeholder="$t('Start date')"
                                :end-placeholder="$t('End date')"
                                :shortcuts="shortcuts"
                                class="fs_date_range_picker"
                            />
                        </div>
                    </div>

                    <el-button size="small" class="fs_settings_button" @click="open_setting=true">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 7.2285C0 6.57975 0.0825 5.95125 0.237 5.3505C0.651349 5.37228 1.06365 5.27906 1.42833 5.08115C1.79301 4.88324 2.09586 4.58834 2.3034 4.22906C2.51095 3.86977 2.6151 3.4601 2.60436 3.04532C2.59361 2.63053 2.46837 2.2268 2.2425 1.87875C3.14921 0.986677 4.26809 0.340122 5.49375 0C5.68199 0.370104 5.96898 0.6809 6.32294 0.897983C6.6769 1.11506 7.08402 1.22997 7.49925 1.22997C7.91447 1.22997 8.3216 1.11506 8.67556 0.897983C9.02952 0.6809 9.31651 0.370104 9.50475 0C10.7304 0.340122 11.8493 0.986677 12.756 1.87875C12.5299 2.22687 12.4045 2.63075 12.3936 3.04572C12.3828 3.4607 12.487 3.87057 12.6946 4.23001C12.9023 4.58944 13.2054 4.88442 13.5703 5.08231C13.9352 5.2802 14.3477 5.37328 14.7622 5.35125C14.9167 5.95125 14.9992 6.57975 14.9992 7.2285C14.9992 7.87725 14.9167 8.50575 14.7622 9.1065C14.3478 9.08457 13.9354 9.17768 13.5706 9.37553C13.2059 9.57339 12.9029 9.86827 12.6953 10.2276C12.4876 10.5869 12.3834 10.9966 12.3941 11.4115C12.4048 11.8263 12.5301 12.2301 12.756 12.5782C11.8493 13.4703 10.7304 14.1169 9.50475 14.457C9.31651 14.0869 9.02952 13.7761 8.67556 13.559C8.3216 13.3419 7.91447 13.227 7.49925 13.227C7.08402 13.227 6.6769 13.3419 6.32294 13.559C5.96898 13.7761 5.68199 14.0869 5.49375 14.457C4.26809 14.1169 3.14921 13.4703 2.2425 12.5782C2.46863 12.2301 2.59405 11.8262 2.60488 11.4113C2.61571 10.9963 2.51152 10.5864 2.30386 10.227C2.09619 9.86755 1.79314 9.57257 1.42823 9.37469C1.06332 9.1768 0.650778 9.08372 0.23625 9.10575C0.0825 8.5065 0 7.878 0 7.2285ZM3.603 9.4785C4.0755 10.2967 4.2105 11.238 4.026 12.1215C4.332 12.339 4.6575 12.5272 4.99875 12.684C5.68625 12.0681 6.57699 11.7279 7.5 11.7285C8.445 11.7285 9.3285 12.0817 10.0012 12.684C10.3425 12.5272 10.668 12.339 10.974 12.1215C10.7846 11.2185 10.9352 10.2773 11.397 9.4785C11.858 8.67928 12.5978 8.07837 13.4745 7.791C13.5092 7.4168 13.5092 7.0402 13.4745 6.666C12.5975 6.37879 11.8574 5.77786 11.3962 4.9785C10.9345 4.1797 10.7838 3.23853 10.9732 2.3355C10.6673 2.11794 10.3417 1.92961 10.0005 1.773C9.31319 2.3887 8.42276 2.72896 7.5 2.7285C6.57699 2.72914 5.68625 2.38887 4.99875 1.773C4.6576 1.92962 4.33192 2.11795 4.026 2.3355C4.21542 3.23853 4.06479 4.1797 3.603 4.9785C3.14203 5.77772 2.40224 6.37863 1.5255 6.666C1.49081 7.0402 1.49081 7.4168 1.5255 7.791C2.40252 8.0782 3.1426 8.67914 3.60375 9.4785H3.603ZM7.5 9.4785C6.90326 9.4785 6.33097 9.24145 5.90901 8.81949C5.48705 8.39753 5.25 7.82524 5.25 7.2285C5.25 6.63176 5.48705 6.05947 5.90901 5.63751C6.33097 5.21555 6.90326 4.9785 7.5 4.9785C8.09674 4.9785 8.66903 5.21555 9.09099 5.63751C9.51295 6.05947 9.75 6.63176 9.75 7.2285C9.75 7.82524 9.51295 8.39753 9.09099 8.81949C8.66903 9.24145 8.09674 9.4785 7.5 9.4785ZM7.5 7.9785C7.69891 7.9785 7.88968 7.89948 8.03033 7.75883C8.17098 7.61818 8.25 7.42741 8.25 7.2285C8.25 7.02959 8.17098 6.83882 8.03033 6.69817C7.88968 6.55752 7.69891 6.4785 7.5 6.4785C7.30109 6.4785 7.11032 6.55752 6.96967 6.69817C6.82902 6.83882 6.75 7.02959 6.75 7.2285C6.75 7.42741 6.82902 7.61818 6.96967 7.75883C7.11032 7.89948 7.30109 7.9785 7.5 7.9785V7.9785Z" fill="#525866"/>
                        </svg>
                    </el-button>

                    <!-- <el-button size="small" class="fs_filter_button" @click="filterReport">
                        <el-icon class="fs_button_icon">
                            <Filter />
                        </el-icon>
                        <span>{{ translate("Filter") }}</span>
                    </el-button> -->

                    <el-button size="small" class="fs_export_button" @click="handleExportClick">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.25 1.773V3.273H1.5V13.773H13.5V7.023H15V14.523C15 14.7219 14.921 14.9127 14.7803 15.0533C14.6397 15.194 14.4489 15.273 14.25 15.273H0.75C0.551088 15.273 0.360322 15.194 0.21967 15.0533C0.0790176 14.9127 0 14.7219 0 14.523V2.523C0 2.32409 0.0790176 2.13332 0.21967 1.99267C0.360322 1.85202 0.551088 1.773 0.75 1.773H5.25ZM12.7125 3.273L10.5 1.0605L11.5605 0L15.5655 4.005C15.6284 4.06793 15.6711 4.14808 15.6885 4.23532C15.7058 4.32256 15.6969 4.41298 15.6629 4.49515C15.6288 4.57733 15.5712 4.64758 15.4973 4.69704C15.4234 4.74649 15.3364 4.77292 15.2475 4.773H9C8.60218 4.773 8.22064 4.93104 7.93934 5.21234C7.65804 5.49364 7.5 5.87518 7.5 6.273V10.773H6V6.273C6 5.47735 6.31607 4.71429 6.87868 4.15168C7.44129 3.58907 8.20435 3.273 9 3.273H12.7125Z" fill="#525866"/>
                        </svg>

                        <span class="fs-ml-5">{{ $t('Export') }}</span>
                    </el-button>
                    <!-- <el-icon v-if="show_export_btn && has_pro" @click="open_export_options=true" class="fs_summary_export_icon" title="Export Report"><Download /></el-icon> -->
                </div>
            </div>

            <!-- Stat Boxes Row -->
            <div v-loading="stat_loading">
                <SideBar :date_range="localDateRange" />
            </div>

            <!-- Main Content -->
            <div class="fs_reports_main_content" v-loading="loading">
                <div class="fs_box">
                    <div class="fs_box_header fs_agent_report_header">
                        <h3 class="fs_section_title">{{ $t("Agents Statistics") }}</h3>
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
                        <!-- Chart Tabs -->


                        <!-- Chart Component -->
                        <div class="fs_chart_container" style="height: 400px;">
                            <component
                                v-if="showing_charts"
                                :is="currently_showing"
                                :key="`${currently_showing}-${chartRefreshKey}`"
                                :date_range="localDateRange"
                                :url="'reports'"
                                :agent_id="agent"
                                type="agent"
                            ></component>
                        </div>
                    </div>
                </div>

                <!-- Agent Response Statistics Table -->
                <div style="margin-top: 24px;">
                    <agent-reports
                        :url="'reports/agents-summary'"
                        :show_settings="true"
                        :show_export_btn="true"
                        :date_range="date_range"
                        :open_setting="open_setting"
                        :open_export="open_export"
                        @update:open_setting="open_setting = $event"
                        @update:open_export="open_export = $event"
                    />
                </div>
            </div>
        </div>

        <NarrowPromo
            v-else
            :heading="$t('get_overall_reports')"
            :description="$t('pro_promo')"
            :button-text="$t('Upgrade To Pro')"
            utm-content="feature_lock_reports_overview"
        />
    </div>
</template>

<script type="text/babel">
import TicketsChart from "./Charts/TicketsGrowth";
import ResponseChart from "./Charts/ResponseGrowth";
import ResolveChart from "./Charts/ResolveGrowth";
import AgentReports from "./AgentReports";
import SideBar from "./Parts/_SideBar"
import {shortcuts} from "./Utils/dateShortCuts";
import NarrowPromo from "@/admin/Components/NarrowPromo";
import IconPack from "@/admin/Components/IconPack";
import { formatDateRangeForDisplay, getDefaultDateRange, isFutureDate } from "./Utils/reportHelpers";

export default {
    name: "Reports",
    props: ["date_range"],
    components: {
        TicketsChart,
        ResponseChart,
        ResolveChart,
        AgentReports,
        SideBar,
        Document,
        NarrowPromo,
        IconPack
    },
    data() {
        return {
            loading: false,
            stat_loading: false,
            currently_showing: "tickets-chart",
            localDateRange: this.date_range,
            open_setting: false,
            open_export: false,
            shortcuts: shortcuts,
            showing_charts: true,
            chartRefreshKey: 0,
            chartMaps: {
                "tickets-chart": this.$t("Ticket Stats"),
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            agent: ""
        };
    },
    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        }
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
        disabledDate: isFutureDate,
        handleComponentChange(item) {
            this.currently_showing = item;
        },
        handleExportClick() {
            this.open_export = true;
        },
        filterReport() {
            // Force re-render by incrementing the refresh key
            // This ensures proper cleanup and re-initialization of the chart
            this.chartRefreshKey += 1;
        },
    },
    mounted() {
        this.$setTitle("Reports");
    },
};
</script>
