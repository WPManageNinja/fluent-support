<template>
    <div class="fs_response_activity_page">
        <!-- Header -->
        <div class="fs_response_activity_header">
            <div class="fs_response_activity_header_row">
                <h3 class="fs_page_title">{{ $t("Response Activity") }}</h3>
                <LiveSnapshotSelector
                    v-model="liveMode"
                    v-model:selected-snapshot="selectedSnapshot"
                    report-type="response_activity"
                    @change="handleLiveSnapshotChange"
                />
            </div>
            <div class="fs_response_activity_header_row fs_response_activity_toolbar">
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
                                :disabled="isSnapshotMode"
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
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="fs_response_activity_content" v-loading="loading">
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ chartTitle }}</h4>
                    <div class="fs_chart_legend">
                        <span
                            class="fs_legend_item"
                            v-for="series in legendItems"
                            :key="series.key"
                        >
                            <span class="fs_legend_dot" :style="{ background: series.color }"></span>
                            <span class="fs_legend_text">{{ series.label }}</span>
                        </span>
                    </div>
                </div>
                <div class="fs_section_body">
                    <div class="fs_chart_container" style="height: 320px;">
                        <line-chart-base
                            v-if="responseActivityChartData"
                            :chartData="responseActivityChartData"
                            :chartOptions="responseActivityChartOptions"
                        ></line-chart-base>
                        <div class="fs_chart_error" v-else-if="fetchError">
                            <p>{{ $t("Couldn't load this data.") }}</p>
                            <el-button size="small" @click="retryFetch">{{ $t("Retry") }}</el-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import LineChartBase from "../Charts/LineChartBase";
import LiveSnapshotSelector from "./Parts/LiveSnapshotSelector.vue";
import IconPack from "@/admin/Components/IconPack.vue";
import { shortcuts } from "../Utils/dateShortCuts";
import { formatDateRangeForDisplay, isFutureDate, isSameDateRange } from "../Utils/reportHelpers";
import dayjs from "dayjs";

export default {
    name: "ResponseActivity",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        LineChartBase,
        LiveSnapshotSelector,
        IconPack
    },
    data() {
        return {
            loading: false,
            fetchError: false,
            fetchRequestId: 0,
            liveMode: "live",
            selectedSnapshot: null,
            preSnapshotDateRange: null,
            localDateRange: this.date_range,
            shortcuts: shortcuts,
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
                            drawOnChartArea: true
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
            return formatDateRangeForDisplay(this.localDateRange);
        },
        chartTitle() {
            return this.granularity === "day"
                ? this.$t("Ticket Creation vs. Agent Replies, by Day")
                : this.$t("Ticket Creation vs. Agent Replies, by Hour");
        },
        legendItems() {
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
        isSnapshotMode() {
            return this.liveMode === "snapshot" && !!this.selectedSnapshot;
        }
    },
    watch: {
        date_range: {
            handler(newVal) {
                // Guard against re-fetching for this component's own
                // date-change emission round-tripping back through the
                // parent prop — only externally supplied ranges (e.g.
                // browser back/forward restoring date_range via Report.vue's
                // popstate handler) should trigger a refetch here.
                if (this.isSameDateRange(newVal, this.localDateRange)) {
                    return;
                }
                if (this.isSnapshotMode) {
                    // Keep the frozen snapshot on screen; remember this range
                    // so switching back to Live restores it instead of the
                    // snapshot's own range.
                    this.preSnapshotDateRange = newVal;
                    return;
                }
                this.localDateRange = newVal;
                this.fetchResponseActivity();
            },
            deep: true
        }
    },
    methods: {
        handleLiveSnapshotChange({ mode, snapshot }) {
            this.liveMode = mode;
            this.selectedSnapshot = snapshot;
            if (mode === "snapshot" && snapshot) {
                if (!this.preSnapshotDateRange) {
                    this.preSnapshotDateRange = this.localDateRange;
                }
                this.fetchSnapshotData(snapshot.id);
            } else {
                if (this.preSnapshotDateRange) {
                    this.localDateRange = this.preSnapshotDateRange;
                    this.preSnapshotDateRange = null;
                }
                this.fetchResponseActivity();
            }
        },
        fetchSnapshotData(id) {
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            this.$get("advanced-reports/snapshots/" + id)
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.applyResponseActivityResponse(response.data);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.responseActivityChartData = null;
                    this.fetchError = true;
                    this.$handleError(error);
                })
                .always(() => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.loading = false;
                });
        },
        retryFetch() {
            if (this.isSnapshotMode) {
                this.fetchSnapshotData(this.selectedSnapshot.id);
            } else {
                this.fetchResponseActivity();
            }
        },
        disabledDate: isFutureDate,
        isSameDateRange,
        handleDateChange() {
            this.$emit("date-change", this.localDateRange);
            this.fetchResponseActivity();
        },
        applyResponseActivityResponse(response) {
            if (response.snapshot_range) {
                this.localDateRange = [response.snapshot_range.from, response.snapshot_range.to];
            }
            this.granularity = response.granularity;
            this.chartLabels = response.granularity === "day"
                ? response.labels.map((date) => dayjs(date).format("MMM D"))
                : response.labels;
            this.ticketCreation = response.ticket_creation;
            this.agentReplies = response.agent_replies;
            this.setupResponseActivityChart();
        },
        fetchResponseActivity() {
            if (!this.localDateRange || this.localDateRange.length !== 2) {
                // Invalidate any in-flight request so its callbacks can't
                // repopulate the chart after the range has been cleared.
                this.fetchRequestId++;
                this.loading = false;
                this.fetchError = false;
                this.responseActivityChartData = null;
                return;
            }
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            const [from, to] = this.localDateRange;
            this.$get("advanced-reports/response-activity", { from, to })
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyResponseActivityResponse(response);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    // Clear the stale chart rather than leaving the previous
                    // range's data rendered under the newly selected range.
                    this.responseActivityChartData = null;
                    this.fetchError = true;
                    this.$handleError(error);
                })
                .always(() => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.loading = false;
                });
        },
        setupResponseActivityChart() {
            const [ticketColor, agentColor] = this.legendItems.map(item => item.color);
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
        withOpacity(hex, opacity) {
            const normalized = hex.replace('#', '');
            const bigint = parseInt(normalized.length === 3
                ? normalized.split('').map(c => c + c).join('')
                : normalized, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        }
    },
    mounted() {
        this.fetchResponseActivity();
    }
};
</script>

<style lang="scss" scoped>
.fs_response_activity_page {
    padding: 0;
}

.fs_response_activity_header {
    margin: 0 -32px 24px -32px;
    background: var(--fs-bg-primary, #FFF);
}

.fs_response_activity_header_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    height: 57px;
    padding: 10px 32px;
    border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
    flex-wrap: wrap;
}

.fs_response_activity_toolbar {
    justify-content: flex-start;
}

.fs_toolbar_left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    flex-wrap: wrap;
}

.fs_response_activity_content {
    padding-bottom: 32px;
}

.fs_overview_section {
    background: var(--fs-bg-primary, #FFF);
    border-radius: var(--fs-radius-md, 8px);
    overflow: hidden;
}

.fs_section_header {
    border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
    padding: 12px 20px;
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.fs_section_title {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 16px;
    line-height: 24px;
    letter-spacing: -0.176px;
    color: var(--fs-text-primary, #0E121B);
    margin: 0;
}

.fs_section_body {
    padding: 20px;
}

.fs_chart_legend {
    display: flex;
    align-items: center;
    gap: 16px;
}

.fs_legend_item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.fs_legend_dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.fs_legend_text {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12.5px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
}

.fs_chart_container {
    width: 100%;
    position: relative;
}

.fs_chart_error {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;

    p {
        margin: 0;
        font-size: 14px;
        color: var(--fs-text-secondary, #525866);
    }
}
</style>
