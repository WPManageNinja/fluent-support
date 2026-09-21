<template>
    <div class="fs_ticket_overview_page">
        <!-- Header -->
        <div class="fs_ticket_overview_header">
            <div class="fs_ticket_overview_header_row">
                <h3 class="fs_page_title">{{ $t("Ticket Overview") }}</h3>
                <LiveSnapshotSelector
                    v-model="liveMode"
                    v-model:selected-snapshot="selectedSnapshot"
                    report-type="ticket_overview"
                    @change="handleLiveSnapshotChange"
                />
            </div>
            <div class="fs_ticket_overview_header_row fs_ticket_overview_toolbar">
                <div class="fs_toolbar_left">
                    <el-dropdown trigger="click" :disabled="isSnapshotMode" @command="handleProductChange">
                        <button class="fs_pill_dropdown_btn" :disabled="isSnapshotMode">
                            <IconPack icon-key="shopping_bag_line" :width="20" :height="20" />
                            {{ selectedProductLabel }}
                            <el-icon class="fs_dropdown_arrow"><ArrowDown /></el-icon>
                        </button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_global_dropdown">
                                <el-dropdown-item
                                    v-for="product in productOptions"
                                    :key="product.id"
                                    :command="product.id"
                                >
                                    {{ product.name }}
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>

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

            <div class="fs_snapshot_banner" v-if="liveMode === 'snapshot' && selectedSnapshot">
                {{ $t("Viewing snapshot from") }} {{ formattedSnapshotDate }} {{ $t("at") }} {{ selectedSnapshot.time }}
            </div>
        </div>

        <!-- Main Content -->
        <div class="fs_ticket_overview_content" v-loading="loading">
            <div class="fs_page_error" v-if="fetchError">
                <p>{{ $t("Couldn't load this data.") }}</p>
                <el-button size="small" @click="retryFetch">{{ $t("Retry") }}</el-button>
            </div>
            <div class="fs_ticket_overview_columns" v-else>
                <!-- Main Column -->
                <div class="fs_ticket_overview_col_main">
                    <!-- Current Support Workload -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Current Support Workload") }}</h4>
                            <span class="fs_attention_badge" v-if="stats.needsAttention">
                                <IconPack icon-key="alert_fill" :width="16" :height="16" />
                                {{ $t("Needs Attention") }}
                            </span>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_workload_cards_row">
                                <div class="fs_workload_card">
                                    <span class="fs_workload_card_icon fs_workload_card_icon--error">
                                        <IconPack icon-key="clock" :width="20" :height="20" />
                                    </span>
                                    <div class="fs_workload_card_text">
                                        <div class="fs_workload_card_label_row">
                                            <p class="fs_stat_label">{{ $t("Tickets Awaiting Reply") }}</p>
                                            <IconPack icon-key="info_custom_fill" :width="20" :height="20" />
                                        </div>
                                        <span class="fs_stat_value">{{ formatStatValue(stats.awaitingReply) }}</span>
                                    </div>
                                </div>
                                <div class="fs_workload_divider"></div>
                                <div class="fs_workload_card">
                                    <span class="fs_workload_card_icon fs_workload_card_icon--warning">
                                        <IconPack icon-key="user_unfollow_line" :width="20" :height="20" />
                                    </span>
                                    <div class="fs_workload_card_text">
                                        <p class="fs_stat_label">{{ $t("Unassigned Tickets") }}</p>
                                        <span class="fs_stat_value">{{ formatStatValue(stats.unassignedTickets) }}</span>
                                    </div>
                                </div>
                                <div class="fs_workload_divider"></div>
                                <div class="fs_workload_card">
                                    <span class="fs_workload_card_icon fs_workload_card_icon--info">
                                        <IconPack icon-key="history_line" :width="20" :height="20" />
                                    </span>
                                    <div class="fs_workload_card_text">
                                        <p class="fs_stat_label">{{ $t("Avg. Waiting Time") }}</p>
                                        <span class="fs_stat_value">{{ stats.avgWaitingTime }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Ticket Stats -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Overall Ticket Stats") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_stat_cards_row">
                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("New Tickets") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.newTickets) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Not Responded Yet") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.notResponded) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Closed Ticket") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.closedTicket) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_chart_divider"></div>

                            <div class="fs_chart_section">
                                <div class="fs_chart_header">
                                    <h5 class="fs_chart_title">{{ ticketChartTitle }}</h5>
                                    <div class="fs_chart_legend">
                                        <span class="fs_legend_dot" :style="{ background: 'var(--fs-chart-primary, #0CBE7E)' }"></span>
                                        <span class="fs_legend_text">{{ $t("Number of tickets") }}</span>
                                    </div>
                                </div>
                                <div class="fs_chart_container" style="height: 220px;">
                                    <bar-chart-base
                                        v-if="hourlyTicketChartData"
                                        :chartData="hourlyTicketChartData"
                                        :chartOptions="hourlyTicketChartOptions"
                                    ></bar-chart-base>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agent Response Stats -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Agent Response Stats") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_stat_cards_row">
                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Total Responses") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.totalResponses) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Tickets Handled") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.ticketsHandled) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Active Agents") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.activeAgents) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Avg. Response") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.avgResponse) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_chart_divider"></div>

                            <div class="fs_chart_section">
                                <div class="fs_chart_header">
                                    <h5 class="fs_chart_title">{{ responseChartTitle }}</h5>
                                    <div class="fs_chart_legend">
                                        <span class="fs_legend_dot" :style="{ background: 'var(--fs-chart-primary, #0CBE7E)' }"></span>
                                        <span class="fs_legend_text">{{ $t("Number of responses") }}</span>
                                    </div>
                                </div>
                                <div class="fs_chart_container" style="height: 220px;">
                                    <bar-chart-base
                                        v-if="hourlyAgentResponseChartData"
                                        :chartData="hourlyAgentResponseChartData"
                                        :chartOptions="hourlyTicketChartOptions"
                                    ></bar-chart-base>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Column -->
                <div class="fs_ticket_overview_col_side">
                    <!-- Product Distribution by Tickets -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Product Distribution by Tickets") }}</h4>
                        </div>
                        <div class="fs_section_body fs_product_dist_body">
                            <div class="fs_product_total">
                                <span class="fs_total_label">{{ $t("Total Tickets") }}</span>
                                <span class="fs_total_value">{{ formatStatValue(ticketDistribution.total) }}</span>
                            </div>
                            <div class="fs_product_bar_list">
                                <div
                                    class="fs_product_bar_item"
                                    v-for="(product, index) in ticketDistribution.products"
                                    :key="index"
                                >
                                    <div class="fs_product_bar_row">
                                        <span class="fs_product_name">{{ product.name }}</span>
                                        <span class="fs_product_count">{{ formatStatValue(product.tickets) }}</span>
                                    </div>
                                    <div class="fs_horizontal_bar_track">
                                        <div
                                            class="fs_horizontal_bar_fill"
                                            :style="{ width: product.percent + '%', background: getProductColor(index) }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <el-button
                                type="default"
                                size="small"
                                class="fs_outline_btn fs_view_details_btn"
                                @click="handleViewDetails"
                            >
                                {{ $t("View Details") }}
                            </el-button>
                        </div>
                    </div>

                    <!-- Same-Day Response Rate -->
                    <div class="fs_overview_section" style="margin-top: 24px;">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Same-Day Response Rate") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_chart_container fs_gauge_chart_container" style="height: 220px;">
                                <vue-apex-charts
                                    v-if="responseChartData"
                                    type="donut"
                                    height="220"
                                    :options="responseChartOptions"
                                    :series="responseChartData.series"
                                ></vue-apex-charts>
                            </div>
                            <div class="fs_product_list">
                                <div
                                    class="fs_product_item"
                                    v-for="(category, index) in sameDayResponse.categories"
                                    :key="index"
                                >
                                    <div class="fs_product_info">
                                        <span class="fs_product_swatch">
                                            <span class="fs_product_dot" :style="{ background: getResponseColor(index) }"></span>
                                        </span>
                                        <span class="fs_product_name">{{ category.name }}</span>
                                    </div>
                                    <span class="fs_product_count">{{ formatStatValue(category.responses) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import BarChartBase from "../Charts/BarChartBase";
import IconPack from "@/admin/Components/IconPack.vue";
import LiveSnapshotSelector from "./Parts/LiveSnapshotSelector.vue";
import VueApexCharts from "vue3-apexcharts";
import { ArrowDown } from "@element-plus/icons-vue";
import { shortcuts } from "../Utils/dateShortCuts";
import { formatDateRangeForDisplay, formatStatValue, isFutureDate, isSameDateRange } from "../Utils/reportHelpers";
import dayjs from "dayjs";

export default {
    name: "TicketOverview",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        BarChartBase,
        IconPack,
        LiveSnapshotSelector,
        VueApexCharts,
        ArrowDown
    },
    data() {
        return {
            loading: false,
            fetchError: false,
            fetchRequestId: 0,
            liveMode: "live",
            selectedSnapshot: null,
            preSnapshotDateRange: null,
            selectedProductId: "all",
            products: [],
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            activityGranularity: "hour",
            stats: {
                needsAttention: false,
                awaitingReply: 0,
                unassignedTickets: 0,
                avgWaitingTime: "--",
                newTickets: 0,
                notResponded: 0,
                closedTicket: 0,
                totalResponses: 0,
                ticketsHandled: 0,
                activeAgents: 0,
                avgResponse: 0,
            },
            ticketDistribution: {
                total: 0,
                products: []
            },
            sameDayResponse: {
                total: 0,
                categories: []
            },
            hourlyTicketChartData: null,
            hourlyAgentResponseChartData: null,
            hourlyTicketChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: true,
                            color: "#E1E4EA"
                        },
                        border: {
                            display: false,
                            dash: [4, 4]
                        },
                        ticks: {
                            color: "#99A0AE",
                            precision: 0,
                            font: {
                                family: "Inter",
                                size: 12,
                                weight: 500
                            }
                        }
                    },
                    x: {
                        grid: {
                            drawOnChartArea: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: "#99A0AE",
                            font: {
                                family: "Inter",
                                size: 12,
                                weight: 500
                            },
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 8
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
                        left: 16,
                        right: 24,
                        top: 12,
                        bottom: 24
                    }
                },
                datasets: {
                    bar: {
                        barPercentage: 0.7,
                        categoryPercentage: 0.8,
                        borderRadius: {
                            topLeft: 2,
                            topRight: 2,
                            bottomLeft: 0,
                            bottomRight: 0
                        },
                        borderSkipped: false
                    }
                }
            },
            responseChartData: null,
            responseChartOptions: {
                chart: {
                    type: "donut"
                },
                labels: [],
                colors: [],
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "78%",
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: "14px",
                                    fontWeight: 500,
                                    offsetY: -4
                                },
                                value: {
                                    show: true,
                                    fontSize: "24px",
                                    fontWeight: 500,
                                    offsetY: 4,
                                    formatter: (val) => this.formatStatValue(val)
                                },
                                total: {
                                    show: true,
                                    label: this.$t("Total Responses"),
                                    formatter: () => this.formatStatValue(this.sameDayResponse.total)
                                }
                            }
                        }
                    }
                }
            }
        };
    },
    computed: {
        productOptions() {
            return [
                { id: "all", name: this.$t("All Products") },
                ...this.products.map(product => ({ id: product.id, name: product.name }))
            ];
        },
        selectedProductLabel() {
            const product = this.productOptions.find(item => item.id === this.selectedProductId);
            return product ? product.name : this.$t("All Products");
        },
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        formattedSnapshotDate() {
            if (!this.selectedSnapshot) {
                return "";
            }
            return dayjs(this.selectedSnapshot.date).format("MMM DD, YYYY");
        },
        ticketChartTitle() {
            return this.activityGranularity === "day" ? this.$t("Daily Ticket Creation") : this.$t("Hourly Ticket Creation");
        },
        responseChartTitle() {
            return this.activityGranularity === "day" ? this.$t("Daily Response Distribution") : this.$t("Hourly Response Distribution");
        },
        isSnapshotMode() {
            return this.liveMode === "snapshot" && !!this.selectedSnapshot;
        }
    },
    watch: {
        date_range: {
            handler(newVal) {
                if (this.isSameDateRange(newVal, this.localDateRange)) {
                    return;
                }
                if (this.isSnapshotMode) {
                    this.preSnapshotDateRange = newVal;
                    return;
                }
                this.localDateRange = newVal;
                this.fetchTicketOverview();
            },
            deep: true
        }
    },
    methods: {
        formatStatValue,
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
                this.fetchTicketOverview();
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
                    this.applyTicketOverviewResponse(response.data);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.resetStats();
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
                this.fetchTicketOverview();
            }
        },
        handleProductChange(productId) {
            this.selectedProductId = productId;
            this.fetchTicketOverview();
        },
        disabledDate: isFutureDate,
        isSameDateRange,
        handleDateChange() {
            this.$emit('date-change', this.localDateRange);
            this.fetchTicketOverview();
        },
        handleViewDetails() {
            this.$emit('navigate-tab', 'product-insight');
        },
        getProductColor(index) {
            const computedStyle = getComputedStyle(document.documentElement);
            const colors = [
                computedStyle.getPropertyValue('--fs-chart-secondary').trim() || '#47C2FF',
                computedStyle.getPropertyValue('--fs-chart-quaternary').trim() || '#7D52F4',
                computedStyle.getPropertyValue('--fs-chart-warning').trim() || '#FA7319',
                computedStyle.getPropertyValue('--fs-chart-info').trim() || '#335CFF',
                computedStyle.getPropertyValue('--fs-chart-success').trim() || '#1FC16B',
            ];
            return colors[index % colors.length];
        },
        getResponseColor(index) {
            const computedStyle = getComputedStyle(document.documentElement);
            const colors = [
                computedStyle.getPropertyValue('--fs-chart-quinary').trim() || '#FF8447',
                computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0CBE7E',
            ];
            return colors[index % colors.length];
        },
        setupHourlyTicketChart(labels, data) {
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0CBE7E';
            this.hourlyTicketChartData = {
                labels,
                datasets: [{
                    label: this.$t("Number of tickets"),
                    backgroundColor: chartColor,
                    data,
                    barThickness: 12,
                }]
            };
        },
        setupHourlyAgentResponseChart(labels, data) {
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0CBE7E';
            this.hourlyAgentResponseChartData = {
                labels,
                datasets: [{
                    label: this.$t("Number of responses"),
                    backgroundColor: chartColor,
                    data,
                    barThickness: 12,
                }]
            };
        },
        setupResponseChart() {
            const labels = this.sameDayResponse.categories.map(c => c.name);
            const series = this.sameDayResponse.categories.map(c => c.responses);
            const colors = this.sameDayResponse.categories.map((c, i) => this.getResponseColor(i));
            this.responseChartData = { series };
            this.responseChartOptions = {
                ...this.responseChartOptions,
                labels,
                colors
            };
        },
        resetStats() {
            this.stats = {
                needsAttention: false,
                awaitingReply: 0,
                unassignedTickets: 0,
                avgWaitingTime: "--",
                newTickets: 0,
                notResponded: 0,
                closedTicket: 0,
                totalResponses: 0,
                ticketsHandled: 0,
                activeAgents: 0,
                avgResponse: 0,
            };
            this.ticketDistribution = { total: 0, products: [] };
            this.sameDayResponse = { total: 0, categories: [] };
            this.hourlyTicketChartData = null;
            this.hourlyAgentResponseChartData = null;
            this.responseChartData = null;
        },
        applyTicketOverviewResponse(response) {
            if (response.snapshot_range) {
                this.localDateRange = [response.snapshot_range.from, response.snapshot_range.to];
            }
            const workload = response.workload;
            const tickets = response.tickets;
            const agentResponses = response.agent_responses;
            const sameDay = response.same_day_response;

            this.stats = {
                needsAttention: workload.awaiting_reply > 0,
                awaitingReply: workload.awaiting_reply,
                unassignedTickets: workload.unassigned,
                avgWaitingTime: workload.avg_wait || "--",
                newTickets: tickets.new,
                notResponded: tickets.not_responded,
                closedTicket: tickets.closed,
                totalResponses: agentResponses.total_responses,
                ticketsHandled: agentResponses.tickets_handled,
                activeAgents: agentResponses.active_agents,
                avgResponse: agentResponses.avg_per_agent,
            };

            this.ticketDistribution = {
                total: response.product_distribution.total,
                products: response.product_distribution.products.map((product) => ({
                    name: product.name,
                    tickets: product.tickets,
                    percent: product.percent
                }))
            };

            this.products = response.products;

            this.sameDayResponse = {
                total: sameDay.same_day + sameDay.not_same_day,
                categories: [
                    { name: this.$t("Answered same day"), responses: sameDay.same_day },
                    { name: this.$t("Not answered same day"), responses: sameDay.not_same_day },
                ]
            };

            this.activityGranularity = response.activity.granularity;
            const labels = response.activity.granularity === "day"
                ? response.activity.labels.map((date) => dayjs(date).format("MMM D"))
                : response.activity.labels;

            this.setupHourlyTicketChart(labels, response.activity.ticket_creation);
            this.setupHourlyAgentResponseChart(labels, response.activity.agent_replies);
            this.setupResponseChart();
        },
        fetchTicketOverview() {
            if (!this.localDateRange || this.localDateRange.length !== 2) {
                // Invalidate any in-flight request so its callbacks can't
                // repopulate the page after the range has been cleared.
                this.fetchRequestId++;
                this.loading = false;
                this.fetchError = false;
                this.resetStats();
                this.selectedProductId = "all";
                this.products = [];
                return;
            }
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            const [from, to] = this.localDateRange;
            const params = { from, to };
            if (this.selectedProductId !== "all") {
                params.product_id = this.selectedProductId;
            }
            this.$get("advanced-reports/ticket-overview", params)
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyTicketOverviewResponse(response);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.resetStats();
                    this.fetchError = true;
                    this.$handleError(error);
                })
                .always(() => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetchTicketOverview();
    }
};
</script>

<style lang="scss" scoped>
.fs_ticket_overview_page {
    padding: 0;
}

.fs_ticket_overview_header {
    margin: 0 calc(-1 * var(--fs-report-gutter, 32px)) 24px;
    background: var(--fs-bg-primary, #FFF);
}

.fs_ticket_overview_header_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 57px;
    padding: 10px var(--fs-report-gutter, 32px);
    border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
    flex-wrap: nowrap;
}

.fs_ticket_overview_toolbar {
    justify-content: flex-start;
    overflow-x: auto;
    overflow-y: hidden;
}

.fs_toolbar_left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;
    flex-shrink: 0;
}

.fs_pill_dropdown_btn {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 6px;
    min-height: 40px;
    flex-shrink: 0;
    border-radius: var(--fs-radius-md, 8px);
    border: 1px solid var(--fs-stroke-soft, #E1E4EA);
    background: var(--fs-bg-primary, #FFF);
    box-shadow: 0px 1px 2px 0px rgba(10, 13, 20, 0.03);
    color: var(--fs-text-secondary, #525866);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    letter-spacing: -0.084px;
    cursor: pointer;
    white-space: nowrap;

    &:hover {
        border-color: var(--fs-stroke-strong, #0E121B);
    }
}

.fs_dropdown_arrow {
    font-size: 14px;
}

.fs_snapshot_banner {
    padding: 10px var(--fs-report-gutter, 32px);
    background: var(--fs-state-away-lighter, #FFFAEB);
    color: var(--fs-state-away-dark, #624C18);
    font-size: 13px;
    font-weight: 500;
}

.fs_ticket_overview_content {
    padding-bottom: 32px;
}

.fs_page_error {
    width: 100%;
    padding: 60px 0;
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

.fs_ticket_overview_columns {
    display: flex;
    gap: 24px;
    align-items: flex-start;

    @media (max-width: 1024px) {
        flex-direction: column;
    }
}

.fs_ticket_overview_col_main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.fs_ticket_overview_col_side {
    width: 304px;
    flex-shrink: 0;

    @media (max-width: 1024px) {
        width: 100%;
    }
}

.fs_overview_section {
    background: var(--fs-bg-primary, #FFF);
    border-radius: var(--fs-radius-md, 8px);
    overflow: hidden;
}

.fs_section_header {
    border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
    padding: 12px 20px;
    height: 56px;
    display: flex;
    align-items: center;
    gap: 8px;

    .fs_section_title {
        flex: 1;
    }
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

.fs_attention_badge {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 2px 6px 2px 4px;
    border-radius: var(--fs-radius-6, 6px);
    background: var(--fs-badge-warning-bg, #FFF3EB);
    color: var(--fs-badge-warning-color, #71330A);
    font-size: 12px;
    font-weight: 500;
    line-height: 16px;
    white-space: nowrap;
}

.fs_workload_cards_row {
    display: flex;
    align-items: center;
    gap: 24px;

    @media (max-width: 768px) {
        flex-wrap: wrap;
        gap: 16px;
    }
}

.fs_workload_card {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    justify-content: center;

    @media (max-width: 768px) {
        flex: 1 1 40%;
    }

    @media (max-width: 480px) {
        flex-basis: 100%;
    }
}

.fs_workload_divider {
    width: 1px;
    height: 100px;
    background: var(--fs-stroke-soft, #E1E4EA);
    flex-shrink: 0;

    @media (max-width: 768px) {
        display: none;
    }
}

.fs_workload_card_icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 6px;
    border-radius: var(--fs-radius-6, 6px);

    &--error {
        background: var(--fs-stat-icon-error-bg, #FFEBEC);
        color: var(--fs-stat-icon-error-color, #FB3748);
    }

    &--warning {
        background: var(--fs-stat-icon-warning-bg, #FFF1EB);
        color: var(--fs-stat-icon-warning-color, #FF8447);
    }

    &--info {
        background: var(--fs-stat-icon-info-bg, #EBF1FF);
        color: var(--fs-stat-icon-info-color, #335CFF);
    }
}

.fs_workload_card_text {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
}

.fs_workload_card_label_row {
    display: flex;
    align-items: center;
    gap: 4px;
    width: 100%;

    .fs_stat_label {
        margin: 0;
    }
}

.fs_stat_label {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    letter-spacing: -0.084px;
    color: var(--fs-text-secondary, #525866);
    margin: 0;
}

.fs_stat_value {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 24px;
    line-height: 32px;
    color: var(--fs-text-primary, #0E121B);
}

.fs_stat_cards_row {
    display: flex;
    gap: 24px;
    align-items: center;

    @media (max-width: 768px) {
        flex-wrap: wrap;
        gap: 16px;
    }
}

.fs_stat_card {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;

    @media (max-width: 768px) {
        flex: 1 1 40%;
    }

    @media (max-width: 480px) {
        flex-basis: 100%;
    }
}

.fs_stat_content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.fs_stat_value_row {
    display: flex;
    gap: 12px;
    align-items: center;
}

.fs_stat_divider {
    width: 1px;
    height: 56px;
    background: var(--fs-stroke-soft, #E1E4EA);
    flex-shrink: 0;

    @media (max-width: 768px) {
        display: none;
    }
}

.fs_chart_divider {
    height: 1px;
    background: var(--fs-stroke-soft, #E1E4EA);
    margin: 20px 0;
}

.fs_chart_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fs_chart_title {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
    text-transform: uppercase;
    letter-spacing: 0.48px;
    margin: 0;
}

.fs_chart_legend {
    display: flex;
    align-items: center;
    gap: 4px;
}

.fs_legend_dot {
    width: 12px;
    height: 12px;
    border-radius: var(--fs-radius-full, 999px);
    border: 1px solid var(--fs-stroke-white, #FFF);
    box-shadow: 0px 1px 2px 0px rgba(10, 13, 20, 0.03);
}

.fs_legend_text {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
}

.fs_chart_container {
    width: 100%;
    position: relative;
}

.fs_gauge_chart_container {
    display: flex;
    justify-content: center;
}

.fs_product_dist_body {
    padding-top: 12px;
}

.fs_product_total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.fs_total_label {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
}

.fs_total_value {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 24px;
    line-height: 32px;
    letter-spacing: -0.36px;
    color: var(--fs-text-primary, #0E121B);
}

.fs_product_bar_list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.fs_product_bar_item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.fs_product_bar_row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
}

.fs_product_name {
    flex: 1;
    min-width: 0;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
    word-break: break-word;
}

.fs_product_count {
    flex-shrink: 0;
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
}

.fs_horizontal_bar_track {
    width: 100%;
    height: 6px;
    background: var(--fs-state-faded-lighter, #F2F5F8);
    border-radius: var(--fs-radius-full, 999px);
    overflow: hidden;
}

.fs_horizontal_bar_fill {
    height: 100%;
    border-radius: var(--fs-radius-full, 999px);
}

.fs_product_list {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;

    .fs_product_name {
        color: var(--fs-text-primary, #0E121B);
        letter-spacing: -0.084px;
    }

    .fs_product_count {
        color: var(--fs-text-secondary, #525866);
        letter-spacing: -0.084px;
    }
}

.fs_product_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.fs_product_info {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

// 20px box keeps the 12px dot optically aligned with the 20px text line
.fs_product_swatch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: var(--fs-radius-6, 6px);
    flex-shrink: 0;
}

.fs_product_dot {
    width: 12px;
    height: 12px;
    border-radius: var(--fs-radius-sm, 4px);
    flex-shrink: 0;
}

.fs_view_details_btn {
    width: 100%;
    height: 28px;
    padding: 4px 6px;
    margin-top: 16px;
    box-shadow: 0px 1px 2px 0px rgba(10, 13, 20, 0.03);
}
</style>
