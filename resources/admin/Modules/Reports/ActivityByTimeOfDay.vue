<template>
    <div v-if="has_pro" class="fs_activity_page">
        <div class="fs_activity_header">
            <div class="fs_activity_header_row">
                <h3 class="fs_page_title">{{ $t("Activity") }}</h3>
                <LiveSnapshotSelector
                    v-model="trendLiveMode"
                    v-model:selected-snapshot="trendSelectedSnapshot"
                    report-type="response_activity"
                    @change="handleTrendLiveSnapshotChange"
                />
            </div>
            <div class="fs_activity_header_row fs_activity_toolbar">
                <div class="fs_toolbar_left">
                    <div class="fs_date_button_group">
                        <div class="fs_date_button_group_item fs_date_picker_wrapper">
                            <div class="fs_date_display">
                                <IconPack icon-key="calendar" :width="20" :height="20" class="fs_calendar_icon" />
                                <span v-if="formattedDateRange" class="fs_date_text">{{ formattedDateRange }}</span>
                                <span v-else class="fs_date_placeholder">{{ $t('Select date range') }}</span>
                            </div>
                            <el-date-picker
                                v-model="dateRange"
                                type="daterange"
                                :editable="false"
                                :disabled="trendIsSnapshotMode"
                                range-separator="To"
                                :disabled-date="disabledDate"
                                value-format="YYYY-MM-DD"
                                :start-placeholder="$t('Start date')"
                                :end-placeholder="$t('End date')"
                                :shortcuts="shortcuts"
                                @change="handleDateChange"
                                class="fs_date_range_picker"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fs_activity_content">
            <!-- Activity Trends by Time of Day -->
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ $t("Activity Trends by Time of Day") }}</h4>
                    <div class="fs_section_header_actions">
                        <el-select
                            v-if="reportType === 'agent_response'"
                            clearable
                            filterable
                            :disabled="trendIsSnapshotMode"
                            :placeholder="$t('All Agent')"
                            @change="fetchStats"
                            v-model="agentId"
                            class="fs_activity_agent_filter fs_staff_filter fs_select_field"
                        >
                            <el-option
                                v-for="agent in appVars.support_agents"
                                :key="agent.id"
                                :value="agent.id"
                                :label="agent.full_name"
                            ></el-option>
                        </el-select>
                        <el-dropdown trigger="click" @command="handleReportTypeChange">
                            <button class="fs_pill_dropdown_btn">
                                {{ reportTypeLabel }}
                                <el-icon class="fs_dropdown_arrow"><ArrowDown /></el-icon>
                            </button>
                            <template #dropdown>
                                <el-dropdown-menu class="fs_global_dropdown">
                                    <el-dropdown-item command="ticket">{{ $t('All Tickets') }}</el-dropdown-item>
                                    <el-dropdown-item command="agent_response">{{ $t('Agent Response') }}</el-dropdown-item>
                                    <el-dropdown-item command="customer_response">{{ $t('Customer Response') }}</el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </div>
                </div>
                <div class="fs_section_body fs_wid_day_by_day">
                    <div v-if="appReady && !heatmapFetchError" class="fs_time_widget">
                        <div class="fs_time_widget_header">
                            <div class="fs_time_day"></div>
                            <div v-for="day in days" :key="day" class="fs_time_day">{{ dayLabels[day] }}</div>
                        </div>
                        <div class="fs_time_widget_body">
                            <div class="fs_wid_sub_headers">
                                <div v-for="tipIndex in tipIndexes" :key="tipIndex" class="fs_wid_sub_header">{{ tipIndex }}</div>
                            </div>
                            <div v-for="day in days" :key="day" class="fs_time_day">
                                <div v-for="keyItem in filledSlots" :key="keyItem" :class="'fs_wid_' + getLevel(dataItems[day][keyItem])" class="fs_time_hour">
                                    <el-tooltip v-if="dataItems[day][keyItem]" :content="getTooltipContent(day, keyItem)" placement="top">
                                        <div class="fs_time_hour_value">
                                            <span>{{ dataItems[day][keyItem] }}</span>
                                        </div>
                                    </el-tooltip>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fs_chart_error fs_heatmap_error" v-else-if="heatmapFetchError">
                        <p>{{ $t("Couldn't load this data.") }}</p>
                    </div>
                    <el-skeleton v-else :rows="5"></el-skeleton>
                    <div class="fs_wid_label_info">
                        <span class="fs_wid_dir">{{ $t('Less') }}</span>
                        <span class="fs_wid_level fs_wid_level_0"></span>
                        <span class="fs_wid_level fs_wid_level_1"></span>
                        <span class="fs_wid_level fs_wid_level_2"></span>
                        <span class="fs_wid_level fs_wid_level_3"></span>
                        <span class="fs_wid_level fs_wid_level_4"></span>
                        <span class="fs_wid_level fs_wid_level_5"></span>
                        <span class="fs_wid_dir">{{ $t('More') }}</span>
                    </div>
                </div>
            </div>

            <!-- Ticket Creation vs. Agent Replies -->
            <div class="fs_overview_section" style="margin-top: 24px;">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ trendChartTitle }}</h4>
                </div>
                <div class="fs_chart_legend fs_chart_legend--centered">
                    <span
                        class="fs_legend_item"
                        v-for="series in trendLegendItems"
                        :key="series.key"
                    >
                        <span class="fs_legend_dot" :style="{ background: series.color }"></span>
                        <span class="fs_legend_text">{{ series.label }}</span>
                    </span>
                </div>
                <div class="fs_section_body">
                    <div class="fs_chart_container" style="height: 320px;" v-loading="trendLoading">
                        <line-chart-base
                            v-if="responseActivityChartData"
                            :chartData="responseActivityChartData"
                            :chartOptions="responseActivityChartOptions"
                            :plugins="chartPlugins"
                        ></line-chart-base>
                        <div class="fs_chart_error" v-else-if="trendFetchError">
                            <p>{{ $t("Couldn't load this data.") }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <NarrowPromo
        v-else
        :heading="$t('get_overall_reports')"
        :description="$t('pro_promo')"
        :button-text="$t('Upgrade To Pro')"
        utm-content="feature_lock_activity_by_time_reports"
    />
</template>

<script>
import NarrowPromo from "@/admin/Components/NarrowPromo";
import LineChartBase from "./Charts/LineChartBase";
import LiveSnapshotSelector from "./AdvanceReport/Parts/LiveSnapshotSelector.vue";
import IconPack from "@/admin/Components/IconPack";
import { ArrowDown } from "@element-plus/icons-vue";
import { shortcuts } from "./Utils/dateShortCuts";
import { formatDateRangeForDisplay, isFutureDate, isSameDateRange } from "./Utils/reportHelpers";

const REPORT_TYPE_LABELS = {
    ticket: "All Tickets",
    agent_response: "Agent Response",
    customer_response: "Customer Response"
};

const dashedYGridPlugin = {
    id: "dashedYGrid",
    beforeDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        const yScale = scales.y;
        if (!yScale || !chartArea) {
            return;
        }
        const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--fs-stroke-soft').trim() || '#E1E4EA';
        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;
        yScale.ticks.forEach((tick) => {
            const y = yScale.getPixelForValue(tick.value);
            ctx.beginPath();
            ctx.moveTo(chartArea.left, y);
            ctx.lineTo(chartArea.right, y);
            ctx.stroke();
        });
        ctx.restore();
    }
};

export default {
    name: 'FeedbackByTimeOfDay',
    props: ["date_range"],
    emits: ["date-change"],
    components: {
        NarrowPromo,
        LineChartBase,
        LiveSnapshotSelector,
        IconPack,
        ArrowDown
    },

    data() {
        return {
            appReady: false,
            trendFetchRequestId: 0,
            trendLoading: false,
            trendFetchError: false,
            heatmapFetchRequestId: 0,
            heatmapFetchError: false,
            reportType: 'ticket',
            dateRange: this.date_range,
            shortcuts: shortcuts,
            agentId: '',
            customerId: '',
            dataItems: {},
            // English keys: they index the API grid from Reporting::getTicketStats().
            // Only `dayLabels` is translated, for display.
            days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            dayLabels: {
                Mon: this.$t('Mon'),
                Tue: this.$t('Tue'),
                Wed: this.$t('Wed'),
                Thu: this.$t('Thu'),
                Fri: this.$t('Fri'),
                Sat: this.$t('Sat'),
                Sun: this.$t('Sun')
            },
            filledSlots: ['0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
            tipIndexes: ['1am', '4am', '7am', '10am', '1pm', '4pm', '7pm', '10pm'],
            trendLiveMode: "live",
            trendSelectedSnapshot: null,
            trendPreSnapshotDateRange: null,
            trendDateRange: this.date_range,
            activitySnapshotGrids: null,
            granularity: "hour",
            chartLabels: [],
            ticketCreation: [],
            agentReplies: [],
            responseActivityChartData: null,
            responseActivityChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        left: 8,
                        right: 16,
                        top: 12,
                        bottom: 8
                    }
                }
            }
        };
    },

    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.dateRange);
        },
        maxValue() {
            let max = 0;
            for (let day in this.dataItems) {
                for (let key in this.dataItems[day]) {
                    if (this.dataItems[day][key] > max) {
                        max = this.dataItems[day][key];
                    }
                }
            }
            return max < 5 ? 5 : max;
        },
        reportTypeLabel() {
            return this.$t(REPORT_TYPE_LABELS[this.reportType]);
        },
        trendChartTitle() {
            return this.granularity === "day"
                ? this.$t("Ticket Creation vs. Agent Replies, by Day")
                : this.$t("Ticket Creation vs. Agent Replies, by Hour");
        },
        trendLegendItems() {
            const computedStyle = getComputedStyle(document.documentElement);
            return [
                {
                    key: "ticket_creation",
                    label: this.$t("Ticket Creation"),
                    color: computedStyle.getPropertyValue('--fs-chart-secondary').trim() || '#47c2ff'
                },
                {
                    key: "agent_replies",
                    label: this.$t("Agent Replies"),
                    color: computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0cbe7e'
                }
            ];
        },
        chartPlugins() {
            return [dashedYGridPlugin];
        },
        trendIsSnapshotMode() {
            return this.trendLiveMode === "snapshot" && !!this.trendSelectedSnapshot;
        }
    },

    watch: {
        date_range: {
            handler(newVal) {
                if (this.isSameDateRange(newVal, this.dateRange)) {
                    return;
                }
                this.dateRange = newVal;
                if (this.trendIsSnapshotMode) {
                    this.trendPreSnapshotDateRange = newVal;
                } else {
                    this.fetchStats();
                    this.trendDateRange = newVal;
                    this.fetchResponseActivity();
                }
            },
            deep: true
        }
    },

    methods: {
        getLevel(value) {
            value = parseInt(value);
            if (!value) {
                return 'level_0';
            }

            const itemValue = Math.round((value / this.maxValue) * 100);

            if (itemValue > 80) {
                return 'level_5';
            } else if (itemValue > 60) {
                return 'level_4';
            } else if (itemValue > 40) {
                return 'level_3';
            } else if (itemValue > 20) {
                return 'level_2';
            } else {
                return 'level_1';
            }
        },

        getTooltipContent(day, keyItem) {
            if (this.reportType === 'ticket') {
                return this.dataItems[day][keyItem] + ' tickets created';
            } else {
                return this.dataItems[day][keyItem] + ' response created';
            }
        },

        handleReportTypeChange(reportType) {
            this.reportType = reportType;
            if (this.trendIsSnapshotMode) {
                this.applyActivitySnapshotGrid();
            } else {
                this.fetchStats();
            }
        },

        handleDateChange() {
            this.$emit('date-change', this.dateRange);
            if (this.trendIsSnapshotMode) {
                this.trendPreSnapshotDateRange = this.dateRange;
            } else {
                this.fetchStats();
                this.trendDateRange = this.dateRange;
                this.fetchResponseActivity();
            }
        },

        isSameDateRange,

        handleTrendLiveSnapshotChange({ mode, snapshot }) {
            this.trendLiveMode = mode;
            this.trendSelectedSnapshot = snapshot;
            if (mode === "snapshot" && snapshot) {
                if (!this.trendPreSnapshotDateRange) {
                    this.trendPreSnapshotDateRange = this.dateRange;
                }
                // Invalidate any in-flight live heatmap fetch so it can't land
                // after this and overwrite the snapshot's grid with live data.
                this.heatmapFetchRequestId++;
                this.fetchTrendSnapshotData(snapshot.id);
            } else {
                if (this.trendPreSnapshotDateRange) {
                    this.trendDateRange = this.trendPreSnapshotDateRange;
                    this.dateRange = this.trendPreSnapshotDateRange;
                    this.trendPreSnapshotDateRange = null;
                }
                this.activitySnapshotGrids = null;
                this.fetchResponseActivity();
                this.fetchStats();
            }
        },

        fetchTrendSnapshotData(id) {
            this.trendLoading = true;
            this.trendFetchError = false;
            this.appReady = false;
            this.heatmapFetchError = false;
            const requestId = ++this.trendFetchRequestId;
            this.$get("advanced-reports/snapshots/" + id)
                .then((response) => {
                    if (requestId !== this.trendFetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyTrendSnapshotResponse(response.data);
                })
                .fail(error => {
                    if (requestId !== this.trendFetchRequestId) {
                        return;
                    }

                    this.responseActivityChartData = null;
                    this.trendFetchError = true;
                    this.heatmapFetchError = true;
                    this.appReady = true;
                })
                .always(() => {
                    if (requestId !== this.trendFetchRequestId) {
                        return;
                    }
                    this.trendLoading = false;
                });
        },

        applyTrendSnapshotResponse(response) {
            const snapshotRange = response.chart_range || response.snapshot_range;
            if (snapshotRange) {
                const range = [snapshotRange.from, snapshotRange.to];
                this.trendDateRange = range;
                this.dateRange = range;
            }
            this.granularity = response.granularity;
            this.chartLabels = response.granularity === "day"
                ? response.labels.map((date) => this.$dayjs(date).format("MMM D"))
                : response.labels;
            this.ticketCreation = response.ticket_creation;
            this.agentReplies = response.agent_replies;
            this.setupResponseActivityChart();

            if (response.activity_grid) {
                this.activitySnapshotGrids = response.activity_grid;
                this.applyActivitySnapshotGrid();
            } else {
                this.activitySnapshotGrids = null;
                this.dataItems = this.emptyDayTimeGrid();
                this.appReady = true;
            }
        },

        emptyDayTimeGrid() {
            const emptyHours = {};
            this.filledSlots.forEach((slot) => {
                emptyHours[slot] = 0;
            });
            const grid = {};
            this.days.forEach((day) => {
                grid[day] = { ...emptyHours };
            });
            return grid;
        },

        applyActivitySnapshotGrid() {
            if (!this.activitySnapshotGrids) {
                return;
            }
            this.dataItems = this.activitySnapshotGrids[this.reportType] || this.activitySnapshotGrids.ticket || {};
            this.appReady = true;
        },

        disabledDate: isFutureDate,

        fetchStats() {
            this.appReady = false;
            this.heatmapFetchError = false;
            const requestId = ++this.heatmapFetchRequestId;
            this.$get('reports/day-time-stats', {
                report_type: this.reportType,
                agent_id: this.agentId,
                date_range: this.dateRange
            })
                .then(res => {
                    if (requestId !== this.heatmapFetchRequestId) {
                        return; // superseded — either a newer fetch, or a snapshot was selected meanwhile
                    }
                    this.dataItems = res.stats;
                })
                .fail(error => {
                    if (requestId !== this.heatmapFetchRequestId) {
                        return;
                    }
                })
                .always(() => {
                    if (requestId !== this.heatmapFetchRequestId) {
                        return;
                    }
                    this.appReady = true;
                });
        },

        withOpacity(hex, opacity) {
            const normalized = hex.replace('#', '');
            const bigint = parseInt(normalized.length === 3
                ? normalized.split('').map(c => c + c).join('')
                : normalized, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        },

        setupResponseActivityChart() {
            const [ticketColor, agentColor] = this.trendLegendItems.map(item => item.color);
            this.responseActivityChartData = {
                labels: this.chartLabels,
                datasets: [
                    {
                        label: this.$t("Ticket Creation"),
                        data: this.ticketCreation,
                        borderColor: ticketColor,
                        backgroundColor: this.withOpacity(ticketColor, 0.12),
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: ticketColor
                    },
                    {
                        label: this.$t("Agent Replies"),
                        data: this.agentReplies,
                        borderColor: agentColor,
                        backgroundColor: this.withOpacity(agentColor, 0.12),
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: agentColor
                    }
                ]
            };
        },

        fetchResponseActivity() {
            if (!this.trendDateRange || this.trendDateRange.length !== 2) {
                this.trendFetchRequestId++;
                this.responseActivityChartData = null;
                this.trendLoading = false;
                this.trendFetchError = false;
                return;
            }
            this.trendLoading = true;
            this.trendFetchError = false;
            const requestId = ++this.trendFetchRequestId;
            const [from, to] = this.trendDateRange;
            this.$get("advanced-reports/response-activity", { from, to })
                .then((response) => {
                    if (requestId !== this.trendFetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.granularity = response.granularity;
                    this.chartLabels = response.granularity === "day"
                        ? response.labels.map((date) => this.$dayjs(date).format("MMM D"))
                        : response.labels;
                    this.ticketCreation = response.ticket_creation;
                    this.agentReplies = response.agent_replies;
                    this.setupResponseActivityChart();
                })
                .fail(error => {
                    if (requestId !== this.trendFetchRequestId) {
                        return;
                    }

                    this.responseActivityChartData = null;
                    this.trendFetchError = true;
                })
                .always(() => {
                    if (requestId !== this.trendFetchRequestId) {
                        return;
                    }
                    this.trendLoading = false;
                });
        }
    },

    mounted() {
        this.fetchStats();
        this.fetchResponseActivity();
    },
};
</script>
