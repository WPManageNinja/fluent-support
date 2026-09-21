<template>
    <div class="fs_overview_page">
        <!-- Header Section -->
        <div class="fs_inside_menu_component_header">
            <div class="fs_component_head">
                <h3 class="fs_page_title">{{ $t("Overview") }}</h3>
            </div>
            <div class="fs_box_actions">
                <el-dropdown
                    @command="handleDateRangeShortcutChange"
                    trigger="click"
                    placement="bottom-end"
                >
                    <el-button size="small" class="fs_date_range_dropdown">
                        <span>{{ selectedDateRangeLabel }}</span>
                        <el-icon class="fs_dropdown_arrow">
                            <ArrowDown />
                        </el-icon>
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu class="fs_global_dropdown">
                            <el-dropdown-item
                                v-for="(shortcut, index) in dateShortcuts"
                                :key="index"
                                :command="index"
                            >
                                {{ shortcut.text }}
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>

                <el-date-picker
                    v-model="dateRange"
                    type="daterange"
                    :editable="false"
                    @change="handleDateRangeChange"
                    range-separator="To"
                    :disabled-date="disabledDate"
                    value-format="YYYY-MM-DD"
                    :start-placeholder="$t('Start date')"
                    :end-placeholder="$t('End date')"
                    style="margin-right: 10px;"
                />

                <el-button size="small" class="fs_filter_button" @click="handleFilterClick">
                    <el-icon class="fs_button_icon">
                        <Filter />
                    </el-icon>
                    <span>{{ $t("Filter") }}</span>
                </el-button>

                <el-button size="small" class="fs_export_button" @click="handleExportClick">
                    <el-icon class="fs_button_icon">
                        <Download />
                    </el-icon>
                    <span>{{ $t("Export") }}</span>
                </el-button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="fs_overview_content" v-loading="loading">
            <el-row :gutter="24">
                <!-- Left Column -->
                <el-col :xs="24" :sm="24" :md="16" :lg="16">
                    <!-- Current Support Workload -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Current Support Workload") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_stat_cards_row">
                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--danger">
                                        <el-icon class="fs_stat_icon_el--danger">
                                            <AlarmClock />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Tickets Awaiting Reply") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.awaitingReply || 0) }}</span>
                                            <span class="fs_stat_meta">{{ $t("Max-time: --") }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--warning">
                                        <el-icon class="fs_stat_icon_el--warning">
                                            <User />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Unassigned Tickets") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.unassignedTickets || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--info">
                                        <el-icon class="fs_stat_icon_el--info">
                                            <Refresh />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Avg. Waiting Time") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ stats.avgWaitingTime || 0 }}</span>
                                            <span class="fs_stat_meta">{{ $t("Max-time: --") }}</span>
                                        </div>
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
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <Document />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("New Tickets") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.newTickets || 0) }}</span>
                                            <span class="fs_stat_badge" v-if="stats.newTicketsChange">
                                                {{ stats.newTicketsChange > 0 ? '+' : '' }}{{ stats.newTicketsChange }}%
                                            </span>
                                            <span class="fs_stat_meta">{{ stats.closedTickets || 0 }} {{ $t("closed") }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <ChatLineRound />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Not Responded Yet") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.notResponded || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <CircleCheck />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Currently Responded") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.currentlyResponded || 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_chart_divider"></div>

                            <!-- Hourly Ticket Distribution Chart -->
                            <div class="fs_chart_section">
                                <div class="fs_chart_header">
                                    <h5 class="fs_chart_title">{{ $t("HOURLY TICKET DISTRIBUTION") }}</h5>
                                    <div class="fs_chart_legend">
                                        <span class="fs_legend_dot fs_legend_dot--secondary"></span>
                                        <span class="fs_legend_text">{{ $t("Number of tickets") }}</span>
                                    </div>
                                </div>
                                <div class="fs_chart_container" style="height: 300px;">
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
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <ChatLineRound />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Total Responses") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.totalResponses || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <Tickets />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Tickets Handled") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.ticketsHandled || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <UserFilled />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Active Agents") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.activeAgents || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fs_stat_divider"></div>

                                <div class="fs_stat_card">
                                    <div class="fs_stat_icon fs_stat_icon--default">
                                        <el-icon class="fs_stat_icon_el--default">
                                            <Message />
                                        </el-icon>
                                    </div>
                                    <div class="fs_stat_content">
                                        <p class="fs_stat_label">{{ $t("Average Response") }}</p>
                                        <div class="fs_stat_value_row">
                                            <span class="fs_stat_value">{{ formatStatValue(stats.averageResponse || 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_chart_divider"></div>

                            <!-- Hourly Response Distribution Chart -->
                            <div class="fs_chart_section">
                                <h5 class="fs_chart_title">{{ $t("HOURLY RESPONSE DISTRIBUTION") }}</h5>
                                <div class="fs_chart_container" style="height: 300px;">
                                    <bar-chart-base
                                        v-if="hourlyResponseChartData"
                                        :chartData="hourlyResponseChartData"
                                        :chartOptions="hourlyResponseChartOptions"
                                    ></bar-chart-base>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-col>

                <!-- Right Column -->
                <el-col :xs="24" :sm="24" :md="8" :lg="8">
                    <!-- Product Distribution by Tickets -->
                    <div class="fs_overview_section">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Product Distribution by Tickets") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_product_total">
                                <span class="fs_total_label">{{ $t("Total Tickets") }}</span>
                                <span class="fs_total_value">{{ formatStatValue(productStats.totalTickets || 0) }}</span>
                            </div>
                            <div class="fs_horizontal_bar_chart" style="margin: 20px 0;">
                                <div
                                    class="fs_horizontal_bar_item"
                                    v-for="(product, index) in productStats.products"
                                    :key="index"
                                >
                                    <div class="fs_horizontal_bar_track">
                                        <div
                                            class="fs_horizontal_bar_fill"
                                            :style="{
                                                width: getProductPercentage(product.tickets, productStats.totalTickets) + '%',
                                                background: getProductColor(index)
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div class="fs_product_list">
                                <div
                                    class="fs_product_item"
                                    v-for="(product, index) in productStats.products"
                                    :key="index"
                                >
                                    <div class="fs_product_info">
                                        <span class="fs_product_dot" :style="{ background: getProductColor(index) }"></span>
                                        <span class="fs_product_name">{{ product.name }}</span>
                                    </div>
                                    <span class="fs_product_count">{{ formatStatValue(product.tickets) }}</span>
                                </div>
                            </div>
                            <el-button
                                type="default"
                                size="small"
                                class="fs_view_details_btn"
                                @click="handleViewDetails('tickets')"
                            >
                                {{ $t("View Details") }}
                            </el-button>
                        </div>
                    </div>

                    <!-- Product Distribution by Responses -->
                    <div class="fs_overview_section" style="margin-top: 24px;">
                        <div class="fs_section_header">
                            <h4 class="fs_section_title">{{ $t("Product Distribution by Responses") }}</h4>
                        </div>
                        <div class="fs_section_body">
                            <div class="fs_product_total">
                                <span class="fs_total_label">{{ $t("Total Responses") }}</span>
                                <span class="fs_total_value">{{ formatStatValue(productStats.totalResponses || 0) }}</span>
                            </div>
                            <div class="fs_chart_container" style="height: 250px; margin: 20px 0;">
                                <vue-apex-charts
                                    v-if="productResponseChartData"
                                    type="donut"
                                    height="250"
                                    :options="productResponseChartOptions"
                                    :series="productResponseChartData.series"
                                ></vue-apex-charts>
                            </div>
                            <div class="fs_product_list">
                                <div
                                    class="fs_product_item"
                                    v-for="(product, index) in productStats.products"
                                    :key="index"
                                >
                                    <div class="fs_product_info">
                                        <span class="fs_product_dot" :style="{ background: getProductResponseColor(index) }"></span>
                                        <span class="fs_product_name">{{ product.name }}</span>
                                    </div>
                                    <span class="fs_product_count">{{ formatStatValue(product.responses) }}</span>
                                </div>
                            </div>
                            <el-button
                                type="default"
                                size="small"
                                class="fs_view_details_btn"
                                @click="handleViewDetails('responses')"
                            >
                                {{ $t("View Details") }}
                            </el-button>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
import BarChartBase from "./Charts/BarChartBase";
import VueApexCharts from 'vue3-apexcharts';
import { formatStatValue, isFutureDate } from "./Utils/reportHelpers";
import dayjs from "dayjs";

export default {
    name: "Overview",
    props: ["url"],
    components: {
        Document,
        BarChartBase,
        VueApexCharts
    },
    data() {
        return {
            loading: false,
            dateRange: [],
            selectedDateRangeIndex: 0,
            dateShortcuts: [
                {
                    text: this.$t("Last 7 days"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        return [start, end];
                    })(),
                },
                {
                    text: this.$t("Last 30 days"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        return [start, end];
                    })(),
                },
                {
                    text: this.$t("Last 90 days"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        return [start, end];
                    })(),
                },
            ],
            stats: {
                awaitingReply: 0,
                unassignedTickets: 0,
                avgWaitingTime: 0,
                newTickets: 0,
                newTicketsChange: 0,
                closedTickets: 0,
                notResponded: 0,
                currentlyResponded: 0,
                totalResponses: 0,
                ticketsHandled: 0,
                activeAgents: 0,
                averageResponse: 0,
            },
            productStats: {
                totalTickets: 0,
                totalResponses: 0,
                products: []
            },
            hourlyTicketChartData: null,
            hourlyTicketChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        beginAtZero: true,
                        ticks: {
                            stepSize: 20,
                            max: 80
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                drawBorder: false,
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
                        categoryPercentage: 0.8
                    }
                }
            },
            hourlyResponseChartData: null,
            hourlyResponseChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        beginAtZero: true,
                        ticks: {
                            stepSize: 2,
                            max: 12,
                            callback: function(value) {
                                return value + 'hr';
                            }
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                drawBorder: false,
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
                        categoryPercentage: 0.8
                    }
                }
            },
            productTicketChartData: null,
            productTicketChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        beginAtZero: true,
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                drawBorder: false,
                layout: {
                    padding: {
                        left: 16,
                        right: 24,
                        top: 12,
                        bottom: 24
                    }
                }
            },
            productResponseChartData: null,
            productResponseChartOptions: {
                chart: {
                    type: 'donut',
                },
                labels: [],
                colors: this.getChartColorPalette(),
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                }
            },
        };
    },
    computed: {
        selectedDateRangeLabel() {
            if (this.selectedDateRangeIndex >= 0 && this.dateShortcuts[this.selectedDateRangeIndex]) {
                return this.dateShortcuts[this.selectedDateRangeIndex].text;
            }
            return this.$t("Last 7 days");
        },
    },
    methods: {
        formatStatValue,
        handleDateRangeShortcutChange(index) {
            this.selectedDateRangeIndex = index;
            const shortcut = this.dateShortcuts[index];
            if (shortcut && shortcut.value) {
                this.dateRange = [
                    dayjs(shortcut.value[0]).format("YYYY-MM-DD"),
                    dayjs(shortcut.value[1]).format("YYYY-MM-DD"),
                ];
                this.fetchData();
            }
        },
        disabledDate: isFutureDate,
        handleDateRangeChange() {
            this.fetchData();
        },
        handleFilterClick() {
            // Implement filter logic
            this.fetchData();
        },
        handleExportClick() {
            // Implement export logic
        },
        handleViewDetails(type) {
            // Navigate to detailed view
        },
        getChartColorPalette() {
            const computedStyle = getComputedStyle(document.documentElement);
            return [
                computedStyle.getPropertyValue('--fs-chart-quinary').trim() || '#FF8447',
                computedStyle.getPropertyValue('--fs-chart-tertiary').trim() || '#22d3bb',
                computedStyle.getPropertyValue('--fs-chart-quaternary').trim() || '#7D52F4',
            ];
        },
        getProductColor(index) {
            const computedStyle = getComputedStyle(document.documentElement);
            const colors = [
                computedStyle.getPropertyValue('--fs-chart-secondary').trim() || '#47c2ff',
                computedStyle.getPropertyValue('--fs-chart-quaternary').trim() || '#7D52F4',
                computedStyle.getPropertyValue('--fs-chart-quinary').trim() || '#FF8447',
            ];
            return colors[index % colors.length];
        },
        getProductResponseColor(index) {
            const computedStyle = getComputedStyle(document.documentElement);
            const colors = [
                computedStyle.getPropertyValue('--fs-chart-quinary').trim() || '#FF8447',
                computedStyle.getPropertyValue('--fs-chart-tertiary').trim() || '#22d3bb',
                computedStyle.getPropertyValue('--fs-chart-quaternary').trim() || '#7D52F4',
            ];
            return colors[index % colors.length];
        },
        getProductPercentage(value, total) {
            if (!total || total === 0) return 0;
            return (value / total) * 100;
        },
        fetchData() {
            // TRACE-FE1: reports/overview endpoint not yet implemented on backend.
        },
        setupHourlyTicketChart(data) {
            const hours = ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM', '12AM'];
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-secondary').trim() || '#47c2ff';
            const chartData = {
                labels: hours,
                datasets: [{
                    label: 'Number of tickets',
                    backgroundColor: chartColor,
                    data: hours.map((hour, index) => data[index] || 0),
                    barThickness: 24,
                }]
            };
            this.hourlyTicketChartData = chartData;
        },
        setupHourlyResponseChart(data) {
            const hours = ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM', '12AM'];
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-tertiary').trim() || '#22d3bb';
            const chartData = {
                labels: hours,
                datasets: [{
                    label: 'Number of Responses',
                    backgroundColor: chartColor,
                    data: hours.map((hour, index) => data[index] || 0),
                    barThickness: 24,
                }]
            };
            this.hourlyResponseChartData = chartData;
        },
        setupProductCharts(products) {
            // Setup donut chart for responses
            const responseLabels = products.map(p => p.name);
            const responseSeries = products.map(p => p.responses);
            const responseColors = products.map((p, i) => this.getProductResponseColor(i));

            this.productResponseChartData = {
                series: responseSeries
            };

            this.productResponseChartOptions = {
                ...this.productResponseChartOptions,
                labels: responseLabels,
                colors: responseColors
            };
        }
    },
    beforeMount() {
        if (this.dateShortcuts.length > 0) {
            const defaultShortcut = this.dateShortcuts[this.selectedDateRangeIndex];
            if (defaultShortcut && defaultShortcut.value) {
                this.dateRange = [
                    dayjs(defaultShortcut.value[0]).format("YYYY-MM-DD"),
                    dayjs(defaultShortcut.value[1]).format("YYYY-MM-DD"),
                ];
            }
        }
    },
    mounted() {
        this.fetchData();
    }
};
</script>

<style lang="scss" scoped>
.fs_overview_page {
    padding: 0;
}

.fs_overview_content {
    margin-top: 24px;
}

.fs_overview_section {
    background: var(--fs-bg-primary);
    border-radius: var(--fs-radius-md, 8px);
    margin-bottom: 24px;
    overflow: hidden;
}

.fs_section_header {
    border-bottom: 1px solid var(--fs-border-default);
    padding: 12px 20px;
    height: 56px;
    display: flex;
    align-items: center;
}

.fs_section_title {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 16px;
    line-height: 24px;
    color: var(--fs-text-primary);
    margin: 0;
}

.fs_section_body {
    padding: 20px;
}

.fs_stat_cards_row {
    display: flex;
    gap: 24px;
    align-items: center;
}

.fs_stat_card {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.fs_stat_icon {
    width: 32px;
    height: 32px;
    border-radius: var(--fs-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;

    &--default {
        background: var(--fs-stat-icon-bg);
    }

    &--danger {
        background: var(--fs-stat-icon-danger-bg);
    }

    &--warning {
        background: var(--fs-stat-icon-warning-bg);
    }

    &--info {
        background: var(--fs-stat-icon-info-bg);
    }
}

.fs_stat_icon_el {
    &--default {
        color: var(--fs-stat-icon-color);
        font-size: 20px;
    }

    &--danger {
        color: var(--fs-stat-icon-danger-color);
        font-size: 20px;
    }

    &--warning {
        color: var(--fs-stat-icon-warning-color);
        font-size: 20px;
    }

    &--info {
        color: var(--fs-stat-icon-info-color);
        font-size: 20px;
    }
}

.fs_stat_content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
}

.fs_stat_label {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    color: var(--fs-text-secondary);
    margin: 0;
}

.fs_stat_value_row {
    display: flex;
    gap: 12px;
    align-items: center;
}

.fs_stat_value {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 24px;
    line-height: 32px;
    color: var(--fs-text-primary);
}

.fs_stat_meta {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary);
}

.fs_stat_badge {
    background: var(--fs-badge-success-bg);
    color: var(--fs-badge-success-text);
    padding: 2px 8px;
    border-radius: var(--fs-radius-full);
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
}

.fs_stat_divider {
    width: 1px;
    height: 100px;
    background: var(--fs-border-default);
    flex-shrink: 0;
}

.fs_chart_divider {
    height: 1px;
    background: var(--fs-border-default);
    margin: 20px 0;
}

.fs_chart_section {
    margin-top: 20px;
}

.fs_chart_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.fs_chart_title {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary);
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
    border-radius: var(--fs-radius-sm);
    border: 1px solid var(--fs-bg-primary);

    &--secondary {
        background: var(--fs-chart-secondary);
    }
}

.fs_legend_text {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary);
}

.fs_chart_container {
    width: 100%;
    position: relative;
}

.fs_product_total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fs_total_label {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    color: var(--fs-text-secondary);
}

.fs_total_value {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 24px;
    line-height: 32px;
    color: var(--fs-text-primary);
}

.fs_product_list {
    margin: 20px 0;
}

.fs_product_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--fs-border-default);

    &:last-child {
        border-bottom: none;
    }
}

.fs_product_info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.fs_product_dot {
    width: 12px;
    height: 12px;
    border-radius: var(--fs-radius-sm);
}

.fs_product_name {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 14px;
    line-height: 20px;
    color: var(--fs-text-secondary);
}

.fs_product_count {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    color: var(--fs-text-primary);
}

.fs_view_details_btn {
    width: 100%;
    margin-top: 16px;
}

.fs_horizontal_bar_chart {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.fs_horizontal_bar_item {
    width: 100%;
}

.fs_horizontal_bar_track {
    width: 100%;
    height: 8px;
    background: var(--fs-state-faded-lighter);
    border-radius: var(--fs-radius-sm);
    overflow: hidden;
}

.fs_horizontal_bar_fill {
    height: 100%;
    border-radius: var(--fs-radius-sm);
    transition: width 0.3s ease;
}
</style>

