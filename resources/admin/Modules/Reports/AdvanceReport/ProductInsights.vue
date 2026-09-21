<template>
    <div class="fs_product_support_page">
        <!-- Header -->
        <div class="fs_product_support_header">
            <div class="fs_product_support_header_row">
                <h3 class="fs_page_title">{{ $t("Product Insights") }}</h3>
                <LiveSnapshotSelector
                    v-model="liveMode"
                    v-model:selected-snapshot="selectedSnapshot"
                    report-type="product_insights"
                    @change="handleLiveModeChange"
                />
            </div>
            <div class="fs_product_support_header_row fs_product_support_toolbar">
                <div class="fs_toolbar_left">
                    <el-dropdown trigger="click" :disabled="isSnapshotMode" @command="handleProductChange">
                        <button class="fs_pill_dropdown_btn" :disabled="isSnapshotMode">
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
        </div>

        <!-- Main Content -->
        <div class="fs_product_support_content" v-loading="loading">
            <div class="fs_page_error" v-if="fetchError">
                <p>{{ $t("Couldn't load this data.") }}</p>
                <el-button size="small" @click="retryFetch">{{ $t("Retry") }}</el-button>
            </div>
            <template v-else>
            <!-- Stat Cards -->
            <div class="fs_stat_cards_grid">
                <div class="fs_stat_card_tile" v-for="(stat, index) in statCards" :key="index">
                    <span class="fs_stat_card_icon" :class="'fs_stat_card_icon--' + stat.variant">
                        <IconPack :icon-key="stat.icon" :width="20" :height="20" />
                    </span>
                    <div class="fs_stat_card_text">
                        <p class="fs_stat_label">{{ stat.label }}</p>
                        <span class="fs_stat_value">{{ stat.value }}</span>
                    </div>
                </div>
            </div>

            <!-- Ticket Volume by Product -->
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ $t("Ticket Volume by Product") }}</h4>
                </div>
                <div class="fs_section_body">
                    <div class="fs_chart_container" style="height: 400px;">
                        <comparison-bar-chart
                            :items="productComparisonItems"
                            :series-name="$t('Tickets')"
                            :entity-label="$t('Product')"
                            :value-label="$t('Ticket Volume')"
                            :caption-text="$t('Ticket volume by product')"
                            :window-size="8"
                        ></comparison-bar-chart>
                    </div>
                </div>
            </div>

            <!-- Product Breakdown -->
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ $t("Product Breakdown") }}</h4>
                </div>
                <div
                    class="fs_table_wrap"
                    :class="{ 'is-scrolled': tableScrolled }"
                    @scroll="onTableScroll"
                >
                    <table class="fs_data_table">
                        <thead>
                            <tr>
                                <th>{{ $t("Product") }}</th>
                                <th>{{ $t("Ticket Volume") }}</th>
                                <th>{{ $t("Response Activity") }}</th>
                                <th>{{ $t("Interactions") }}</th>
                                <th>{{ $t("Open Tickets") }}</th>
                                <th>{{ $t("Closed") }}</th>
                                <th>{{ $t("Unanswered") }}</th>
                                <th>{{ $t("Waiting Workload") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in paginatedRows" :key="row.id">
                                <td>{{ row.name }}</td>
                                <td>{{ formatStatValue(row.volume) }}</td>
                                <td>{{ formatStatValue(row.responses) }}</td>
                                <td>{{ formatStatValue(row.interactions) }}</td>
                                <td>{{ formatStatValue(row.opens) }}</td>
                                <td>{{ formatStatValue(row.closed) }}</td>
                                <td>{{ formatStatValue(row.unanswered) }}</td>
                                <td>{{ formatStatValue(row.waiting) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="fs_pagination_wrapper" v-if="filteredRows.length">
                    <div class="fs_pagination_left">
                        <p>{{ $t("Page") }} {{ pagination.current_page }} {{ $t("of") }} {{ totalPages }}</p>
                        <pagination :pagination="pagination" layout="sizes" />
                    </div>
                    <div class="fs_pagination_right">
                        <pagination
                            :pagination="pagination"
                            :background="true"
                            layout="prev, pager, next"
                        />
                    </div>
                </div>
            </div>
            </template>
        </div>
    </div>
</template>

<script type="text/babel">
import ComparisonBarChart from "../Charts/ComparisonBarChart.vue";
import LiveSnapshotSelector from "./Parts/LiveSnapshotSelector.vue";
import IconPack from "@/admin/Components/IconPack.vue";
import Pagination from "@/admin/Pieces/Pagination";
import { ArrowDown } from "@element-plus/icons-vue";
import { shortcuts } from "../Utils/dateShortCuts";
import { formatDateRangeForDisplay, formatStatValue, isFutureDate, isSameDateRange } from "../Utils/reportHelpers";

export default {
    name: "ProductInsights",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        ComparisonBarChart,
        LiveSnapshotSelector,
        IconPack,
        Pagination,
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
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            tableRows: [],
            // Drives the pinned-column shadow; only shown once scrolled.
            tableScrolled: false,
            // Paged client-side over `filteredRows`; `total` is kept in sync
            // by the `filteredRows` watcher.
            pagination: { current_page: 1, per_page: 10, total: 0 }
        };
    },
    computed: {
        productOptions() {
            return [
                { id: "all", name: this.$t("All Products") },
                ...this.tableRows.map(row => ({ id: row.id, name: row.name }))
            ];
        },
        selectedProductLabel() {
            const product = this.productOptions.find(item => item.id === this.selectedProductId);
            return product ? product.name : this.$t("All Products");
        },
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        productComparisonItems() {
            return this.filteredRows.map(row => ({ id: row.id, name: row.name, value: row.volume }));
        },
        filteredRows() {
            if (this.selectedProductId === "all") {
                return this.tableRows;
            }
            return this.tableRows.filter(row => row.id === this.selectedProductId);
        },
        paginatedRows() {
            const start = (this.pagination.current_page - 1) * this.pagination.per_page;
            return this.filteredRows.slice(start, start + this.pagination.per_page);
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.pagination.total / this.pagination.per_page));
        },
        statCards() {
            const rows = this.filteredRows;
            const totalTickets = rows.reduce((sum, row) => sum + row.volume, 0);
            const unanswered = rows.reduce((sum, row) => sum + row.unanswered, 0);
            const waiting = rows.reduce((sum, row) => sum + row.waiting, 0);
            const responses = rows.reduce((sum, row) => sum + row.responses, 0);

            return [
                { icon: "product_support", variant: "information", label: this.$t("Total Tickets"), value: this.formatStatValue(totalTickets) },
                { icon: "error_warning_line", variant: "warning", label: this.$t("Unanswered Tickets"), value: this.formatStatValue(unanswered) },
                { icon: "hourglass_line", variant: "highlighted", label: this.$t("Waiting Workload"), value: this.formatStatValue(waiting) },
                { icon: "line_chart_line", variant: "feature", label: this.$t("Response Activity"), value: this.formatStatValue(responses) },
            ];
        },
        isSnapshotMode() {
            return this.liveMode === "snapshot" && !!this.selectedSnapshot;
        }
    },
    watch: {
        filteredRows() {
            this.pagination.total = this.filteredRows.length;
        },
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
                this.fetchProductInsights();
            },
            deep: true
        }
    },
    methods: {
        formatStatValue,
        handleLiveModeChange({ mode, snapshot }) {
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
                this.fetchProductInsights();
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
                    this.applyProductInsightsResponse(response.data);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.tableRows = [];
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
                this.fetchProductInsights();
            }
        },
        handleProductChange(productId) {
            this.selectedProductId = productId;
            this.pagination.current_page = 1;
        },
        disabledDate: isFutureDate,
        isSameDateRange,
        handleDateChange() {
            this.$emit('date-change', this.localDateRange);
            this.fetchProductInsights();
        },
        onTableScroll(event) {
            this.tableScrolled = event.target.scrollLeft > 0;
        },
        applyProductInsightsResponse(response) {
            if (response.snapshot_range) {
                this.localDateRange = [response.snapshot_range.from, response.snapshot_range.to];
            }
            this.tableRows = response.products.map((product) => ({
                id: product.id,
                name: product.name,
                volume: product.volume,
                responses: product.responses,
                interactions: product.interactions,
                opens: product.opens,
                closed: product.closed,
                unanswered: product.unanswered,
                waiting: product.waiting
            }));
            this.pagination.current_page = 1;
        },
        fetchProductInsights() {
            if (!this.localDateRange || this.localDateRange.length !== 2) {
                // Invalidate any in-flight request so its callbacks can't
                // repopulate the page after the range has been cleared.
                this.fetchRequestId++;
                this.loading = false;
                this.fetchError = false;
                this.tableRows = [];
                this.pagination.current_page = 1;
                this.selectedProductId = "all";
                return;
            }
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            const [from, to] = this.localDateRange;
            this.$get("advanced-reports/product-insights", { from, to })
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyProductInsightsResponse(response);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    // Clear stale data rather than leaving the previous
                    // range's rows rendered under the newly selected range.
                    this.tableRows = [];
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
    },
    mounted() {
        this.fetchProductInsights();
    }
};
</script>
