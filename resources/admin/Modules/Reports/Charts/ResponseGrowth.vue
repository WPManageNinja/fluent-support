<template>
    <div style="width: 100%; height: 400px">
        <bar-chart-base
            v-if="chartData"
            :chartData="chartData"
            :chartOptions="chartOptions"
        ></bar-chart-base>
    </div>
</template>

<script type="text/babel">
import each from "lodash/each";
import BarChartBase from "./BarChartBase";

export default {
    name: "ResponseGrowth",
    props: ["date_range", "url", "agent_id", "product_id", "mailbox_id", "agent_group_id", "type", "compact"],
    components: {
        BarChartBase,
    },
    data() {
        return {
            fetching: false,
            stats: {},
            chartData: false,
            maxCumulativeValue: 0,
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: "linear",
                        position: "left",
                        grid: {
                            drawOnChartArea: true,
                            color: "#E1E4EA",
                        },
                        border: {
                            display: false,
                            dash: [4, 4],
                        },
                        ticks: {
                            callback: function (value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            },
                        },
                        beginAtZero: true,
                    },
                    x: {
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10,
                        },
                    },
                },
                layout: {
                    padding: {
                        left: 16,
                        right: 24,
                        top: 12,
                        bottom: 24,
                    },
                },
                datasets: {
                    bar: {
                        barThickness: 8,
                        categoryPercentage: 0.7,
                    },
                },
            },
        };
    },
    methods: {
        async fetchReport() {
            this.fetching = true;
            const data = {
                date_range: this.date_range,
                agent_id: this.agent_id,
                product_id: this.product_id,
                mailbox_id: this.mailbox_id,
                agent_group_id: this.agent_group_id,
                type: this.type
            };
            await this.$get(this.url + "/response-growth", data).then((response) => {
                this.setupChartItems(response.stats);
            });
        },
        setupChartItems(stats) {
            const chartData = {
                labels: [],
                datasets: [],
            };

            const statData = [];
            each(stats, (count, label) => {
                chartData.labels.push(label);
                statData.push(parseInt(count));
            });
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0cbe7e';
            chartData.datasets.push({
                label: this.$t("Response Stats"),
                backgroundColor: chartColor,
                data: statData,
            });
            this.chartData = chartData;
        },
    },
    mounted() {
        this.fetchReport();
    },
    watch: {
        date_range: {
            handler(newVal) {
                if (newVal && newVal.length === 2) {
                    this.fetchReport();
                }
            },
            deep: true
        }
    },
};
</script>
