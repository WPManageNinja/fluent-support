<template>
    <div class="fs_agent_comparison_chart_wrap">
        <button
            type="button"
            class="fs_pagination_nav_btn fs_chart_reset_btn"
            :title="$t('Reset zoom')"
            @click="resetZoom"
        >
            <IconPack icon-key="refresh" :width="14" :height="14" />
        </button>
        <div ref="chartEl" class="fs_agent_comparison_chart" aria-hidden="true"></div>
        <table class="screen-reader-text">
            <caption>{{ captionText }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ entityLabel }}</th>
                    <th scope="col">{{ valueLabel }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.id">
                    <td>{{ item.name }}</td>
                    <td>{{ item.value }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script type="text/babel">
// Modular echarts/core import instead of the aggregate entry point, which registers every chart type/component.
import * as echarts from "echarts/core";
import { BarChart } from "echarts/charts";
import { TooltipComponent, GridComponent, DataZoomComponent } from "echarts/components";
import { SVGRenderer } from "echarts/renderers";
import Theme from "@/admin/Bits/Theme";
import IconPack from "@/admin/Components/IconPack";

echarts.use([BarChart, TooltipComponent, GridComponent, DataZoomComponent, SVGRenderer]);

// Tooltip formatter inserts raw HTML — escape values before concatenating.
const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
}[char]));

// Shared horizontal-bar "comparison" chart — used by Agent Comparison
// (Performance Overview) and Ticket Volume by Product (Product Insights).
// Both render inside a fixed-height container, so a null/large default zoom
// would cram every row into it — windowSize pre-zooms to the last N and the
// rest stay reachable through the zoom/scroll.
export default {
    name: "ComparisonBarChart",
    props: {
        items: {
            type: Array,
            default: () => []
        },
        seriesName: {
            type: String,
            required: true
        },
        entityLabel: {
            type: String,
            required: true
        },
        valueLabel: {
            type: String,
            required: true
        },
        captionText: {
            type: String,
            required: true
        },
        windowSize: {
            type: Number,
            default: null
        }
    },
    components: {
        IconPack
    },
    data() {
        return {
            isDarkTheme: Theme.isDark(),
            zoomStart: 0,
            zoomEnd: 100
        };
    },
    computed: {
        defaultZoomStart() {
            if (!this.windowSize || !this.items.length || this.items.length <= this.windowSize) {
                return 0;
            }
            return Math.max(0, 100 - (this.windowSize / this.items.length) * 100);
        }
    },
    watch: {
        items: {
            handler() {
                this.zoomStart = this.defaultZoomStart;
                this.zoomEnd = 100;
                // Wait a tick so resize() measures the container after Vue
                // has patched the surrounding layout for the new items.
                this.$nextTick(() => {
                    this.renderChart();
                });
            },
            deep: true
        }
    },
    methods: {
        getCssVar(name, fallback) {
            const value = getComputedStyle(document.body).getPropertyValue(name).trim();
            return value || fallback;
        },
        buildOption() {
            const baseColor = this.getCssVar("--fs-chart-primary", "#0cbe7e");
            const axisLabelColor = this.getCssVar("--fs-text-secondary", this.isDarkTheme ? "#CACFD8" : "#525866");
            const splitLineColor = this.getCssVar("--fs-stroke-soft", this.isDarkTheme ? "#333D4C" : "#E1E4EA");

            return {
                tooltip: {
                    trigger: "axis",
                    axisPointer: { type: "shadow" },
                    backgroundColor: this.isDarkTheme ? "#253241" : "#ffffff",
                    borderColor: this.isDarkTheme ? "#2C3C4E" : "#c0c4ca",
                    borderWidth: 1,
                    textStyle: { color: this.isDarkTheme ? "#ffffff" : "#565865" },
                    formatter: (params) => {
                        const point = params[0];
                        // point.marker is ECharts' own generated <span> — safe as-is; everything else is escaped.
                        return `${escapeHtml(point.name)}<br/>${point.marker}${escapeHtml(point.seriesName)}: <strong>${escapeHtml(point.value)}</strong>`;
                    }
                },
                grid: {
                    left: 8,
                    right: 28,
                    top: 12,
                    bottom: 8,
                    containLabel: true
                },
                dataZoom: [
                    { type: "inside", yAxisIndex: 0, start: this.zoomStart, end: this.zoomEnd },
                    {
                        type: "slider",
                        yAxisIndex: 0,
                        start: this.zoomStart,
                        end: this.zoomEnd,
                        width: 12,
                        right: 4,
                        showDetail: false,
                        brushSelect: false,
                        textStyle: { color: axisLabelColor }
                    }
                ],
                xAxis: {
                    type: "value",
                    axisLabel: { color: axisLabelColor },
                    splitLine: { lineStyle: { color: splitLineColor } }
                },
                yAxis: {
                    type: "category",
                    data: this.items.map((item) => item.name),
                    axisLabel: { color: axisLabelColor, fontSize: 12 },
                    axisLine: { lineStyle: { color: splitLineColor } },
                    axisTick: { show: false }
                },
                series: [
                    {
                        name: this.seriesName,
                        type: "bar",
                        data: this.items.map((item) => item.value),
                        barMaxWidth: 24,
                        itemStyle: {
                            color: baseColor,
                            borderRadius: [0, 4, 4, 0]
                        }
                    }
                ]
            };
        },
        renderChart() {
            if (!this.$refs.chartEl) {
                return;
            }
            if (!this.chartInstance) {
                this.chartInstance = echarts.init(this.$refs.chartEl, null, { renderer: "svg" });
                this.chartInstance.on("dataZoom", (event) => {
                    const range = event.batch ? event.batch[0] : event;
                    this.zoomStart = range.start;
                    this.zoomEnd = range.end;
                });
            }
            this.chartInstance.setOption(this.buildOption(), { notMerge: true });
            this.chartInstance.resize();
        },
        resetZoom() {
            this.zoomStart = this.defaultZoomStart;
            this.zoomEnd = 100;
            if (this.chartInstance) {
                this.chartInstance.dispatchAction({
                    type: "dataZoom",
                    start: this.zoomStart,
                    end: this.zoomEnd
                });
            }
        },
        handleResize() {
            if (this.chartInstance) {
                this.chartInstance.resize();
            }
        },
        handleThemeChange() {
            this.isDarkTheme = Theme.isDark();
            this.renderChart();
        }
    },
    mounted() {
        this.zoomStart = this.defaultZoomStart;
        this.$nextTick(() => {
            this.renderChart();
        });
        window.addEventListener("resize", this.handleResize);
        window.addEventListener("fs-theme-changed", this.handleThemeChange);
    },
    beforeUnmount() {
        window.removeEventListener("resize", this.handleResize);
        window.removeEventListener("fs-theme-changed", this.handleThemeChange);
        if (this.chartInstance) {
            this.chartInstance.dispose();
            this.chartInstance = null;
        }
    }
};
</script>
