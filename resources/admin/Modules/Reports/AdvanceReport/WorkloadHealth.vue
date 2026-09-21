<template>
    <div class="fs_workload_health_page">
        <!-- Header -->
        <div class="fs_workload_health_header">
            <div class="fs_workload_health_header_row">
                <h3 class="fs_page_title">{{ $t("Business Boxes") }}</h3>
                <LiveSnapshotSelector
                    v-model="liveMode"
                    v-model:selected-snapshot="selectedSnapshot"
                    report-type="workload_health"
                    @change="handleLiveModeChange"
                />
            </div>
            <div class="fs_workload_health_header_row fs_workload_health_toolbar">
                <div class="fs_toolbar_left">
                    <el-dropdown trigger="click" :disabled="isSnapshotMode" @command="handleMailboxChange">
                        <button class="fs_pill_dropdown_btn" :disabled="isSnapshotMode">
                            {{ selectedMailboxLabel }}
                            <el-icon class="fs_dropdown_arrow"><ArrowDown /></el-icon>
                        </button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_global_dropdown">
                                <el-dropdown-item
                                    v-for="mailbox in mailboxOptions"
                                    :key="mailbox.id"
                                    :command="mailbox.id"
                                >
                                    {{ mailbox.name }}
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
        <div class="fs_workload_health_content" v-loading="loading">
            <div class="fs_page_error" v-if="fetchError">
                <p>{{ $t("Couldn't load this data.") }}</p>
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

            <!-- Business Box Statistics — live only. The chart fetches its own
                 date-range report and the snapshot payload has no frozen chart
                 series, so showing it in snapshot mode would put a live chart
                 beside frozen stats. -->
            <div class="fs_box" v-if="!isSnapshotMode">
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
                        :is="currently_showing"
                        :key="currently_showing + '-' + chartMailboxId"
                        :date_range="localDateRange"
                        :url="'mailbox-reports'"
                        :mailbox_id="chartMailboxId"
                        type="mailbox"
                    ></component>
                </div>
            </div>

            <!-- Tickets Waiting Longest -->
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ $t("Tickets Waiting Longest") }}</h4>
                </div>
                <div
                    class="fs_table_wrap"
                    :class="{ 'is-scrolled': tableScrolled }"
                    @scroll="onTableScroll"
                >
                    <table class="fs_data_table">
                        <thead>
                            <tr>
                                <th>{{ $t("ID") }}</th>
                                <th>{{ $t("Subject") }}</th>
                                <th>{{ $t("Customer") }}</th>
                                <th>{{ $t("Waiting Since") }}</th>
                                <th>{{ $t("Wait Time") }}</th>
                                <th>{{ $t("Product") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in paginatedRows" :key="row.id">
                                <td>#{{ row.id }}</td>
                                <td class="fs_ellipsis_cell">{{ row.subject }}</td>
                                <td>{{ row.customer }}</td>
                                <td>
                                    <div class="fs_stacked_cell">
                                        <span class="fs_stacked_cell_primary">{{ row.waitingSince }}</span>
                                        <span class="fs_stacked_cell_secondary">{{ row.waitingSinceRelative }}</span>
                                    </div>
                                </td>
                                <td>{{ row.waitTime }}</td>
                                <td>{{ row.product }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="fs_pagination_wrapper" v-if="tableRows.length">
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
import LiveSnapshotSelector from "./Parts/LiveSnapshotSelector.vue";
import TicketsChart from "../Charts/TicketsGrowth";
import ResolveChart from "../Charts/ResolveGrowth";
import ResponseChart from "../Charts/ResponseGrowth";
import IconPack from "@/admin/Components/IconPack.vue";
import Pagination from "@/admin/Pieces/Pagination";
import { ArrowDown } from "@element-plus/icons-vue";
import { shortcuts } from "../Utils/dateShortCuts";
import { formatDateRangeForDisplay, formatStatValue, isFutureDate } from "../Utils/reportHelpers";
import dayjs from "dayjs";

export default {
    name: "WorkloadHealth",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        LiveSnapshotSelector,
        TicketsChart,
        ResolveChart,
        ResponseChart,
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
            selectedMailboxId: "all",
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            mailboxes: [],
            allStats: { awaiting_reply: 0, unassigned: 0, avg_wait: 0 },
            currently_showing: "tickets-chart",
            chartMaps: {
                "tickets-chart": this.$t("Ticket Stats"),
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            tableRows: [],
            // Drives the pinned-column shadow; only shown once scrolled.
            tableScrolled: false,
            // Paged client-side over `tableRows`; `total` is kept in sync by
            // the `tableRows` watcher.
            pagination: { current_page: 1, per_page: 10, total: 0 },
        };
    },
    computed: {
        mailboxOptions() {
            return [
                { id: "all", name: this.$t("All Mailboxes") },
                ...this.mailboxes.map(mailbox => ({ id: mailbox.id, name: mailbox.name }))
            ];
        },
        selectedMailboxLabel() {
            const mailbox = this.mailboxOptions.find(item => item.id === this.selectedMailboxId);
            return mailbox ? mailbox.name : this.$t("All Mailboxes");
        },
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        // The charts treat an empty mailbox id as "every mailbox"; this page
        // uses the "all" sentinel for the same thing.
        chartMailboxId() {
            return this.selectedMailboxId === "all" ? "" : this.selectedMailboxId;
        },
        selectedStats() {
            if (this.selectedMailboxId === "all") {
                return this.allStats;
            }
            const mailbox = this.mailboxes.find(item => item.id === this.selectedMailboxId);
            return mailbox || this.allStats;
        },
        statCards() {
            return [
                { icon: "time_sheet", variant: "warning", label: this.$t("Tickets Awaiting Reply"), value: this.formatStatValue(this.selectedStats.awaiting_reply) },
                { icon: "user_line", variant: "away", label: this.$t("Unassigned Tickets"), value: this.formatStatValue(this.selectedStats.unassigned) },
                { icon: "hourglass_line", variant: "highlighted", label: this.$t("Avg. Customer Wait"), value: this.selectedStats.avg_wait },
            ];
        },
        paginatedRows() {
            const start = (this.pagination.current_page - 1) * this.pagination.per_page;
            return this.tableRows.slice(start, start + this.pagination.per_page);
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.pagination.total / this.pagination.per_page));
        },
        isSnapshotMode() {
            return this.liveMode === "snapshot" && !!this.selectedSnapshot;
        }
    },
    watch: {
        date_range: {
            handler(newVal) {
                this.localDateRange = newVal;
            },
            deep: true
        },
        tableRows() {
            this.pagination.total = this.tableRows.length;
            // A shorter result set can leave the current page out of range.
            if (this.pagination.current_page > this.totalPages) {
                this.pagination.current_page = 1;
            }
        }
    },
    methods: {
        formatStatValue,
        onTableScroll(event) {
            this.tableScrolled = event.target.scrollLeft > 0;
        },
        handleLiveModeChange({ mode, snapshot }) {
            this.liveMode = mode;
            this.selectedSnapshot = snapshot;
            if (mode === "snapshot" && snapshot) {
                this.fetchSnapshotData(snapshot.id);
            } else {
                this.fetchWorkloadHealth();
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
                    this.applyWorkloadHealthResponse(response.data, response.snapshot_time);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.allStats = { awaiting_reply: 0, unassigned: 0, avg_wait: 0 };
                    this.mailboxes = [];
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
        handleDateChange() {
            this.$emit("date-change", this.localDateRange);
        },
        disabledDate: isFutureDate,
        handleComponentChange(item) {
            this.currently_showing = item;
        },
        handleMailboxChange(mailboxId) {
            this.selectedMailboxId = mailboxId;
            this.pagination.current_page = 1;
            this.fetchWorkloadHealth();
        },
        formatWaitingSinceTime(datetime) {
            return dayjs(datetime).format("h:mm A");
        },
        // `now` defaults to the actual current time for the live page, but a
        // snapshot must compare against the moment it was frozen — otherwise
        // "Wait Time" would keep growing against today's clock for an old,
        // frozen snapshot instead of reflecting what it looked like then.
        formatWaitingSinceRelative(datetime, now = dayjs()) {
            const date = dayjs(datetime);
            if (date.isSame(now, "day")) {
                return this.$t("Today");
            }
            if (date.isSame(dayjs(now).subtract(1, "day"), "day")) {
                return this.$t("Yesterday");
            }
            return date.format("MMM D");
        },
        formatWaitTime(datetime, now = dayjs()) {
            const totalMinutes = Math.max(0, dayjs(now).diff(dayjs(datetime), "minute"));
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            return `${hours}h ${minutes}m`;
        },
        applyWorkloadHealthResponse(response, referenceTime) {
            const now = referenceTime ? dayjs(referenceTime) : dayjs();
            this.allStats = response.all;
            this.mailboxes = response.mailboxes;
            this.tableRows = response.waiting_tickets.map((ticket) => ({
                id: ticket.id,
                subject: ticket.subject,
                customer: ticket.customer,
                waitingSince: this.formatWaitingSinceTime(ticket.waiting_since),
                waitingSinceRelative: this.formatWaitingSinceRelative(ticket.waiting_since, now),
                waitTime: this.formatWaitTime(ticket.waiting_since, now),
                product: ticket.product,
                mailboxId: ticket.mailbox_id
            }));
            this.pagination.current_page = 1;
        },
        fetchWorkloadHealth() {
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            const params = this.selectedMailboxId === "all" ? {} : { mailbox_id: this.selectedMailboxId };
            this.$get("advanced-reports/workload-health", params)
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyWorkloadHealthResponse(response);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.allStats = { awaiting_reply: 0, unassigned: 0, avg_wait: 0 };
                    this.mailboxes = [];
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
        }
    },
    mounted() {
        this.fetchWorkloadHealth();
    }
};
</script>

<style lang="scss" scoped>
.fs_workload_health_page {
    padding: 0;
    .fs_agent_report_header{
        border-radius: 8px 8px 0 0;
    }
    .fs_box_body{
        border-radius: 0 0 8px 8px;
    }
}

.fs_workload_health_header {
    margin: 0 calc(-1 * var(--fs-report-gutter, 32px)) 24px;
    background: var(--fs-bg-primary, #FFF);
}

.fs_workload_health_header_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 57px;
    padding: 10px var(--fs-report-gutter, 32px);
    border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
    flex-wrap: wrap;
}

.fs_workload_health_toolbar {
    justify-content: flex-start;
}

.fs_toolbar_left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    flex-wrap: wrap;
}

.fs_pill_dropdown_btn {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 6px;
    min-height: 40px;
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

.fs_workload_health_content {
    padding-bottom: 32px;
    display: flex;
    flex-direction: column;
    gap: 24px;
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

.fs_stat_cards_grid {
    display: flex;
    gap: 24px;

    @media (max-width: 768px) {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

.fs_stat_card_tile {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px;
    background: var(--fs-bg-primary, #FFF);
    border-radius: var(--fs-radius-lg, 12px);
}

.fs_stat_card_icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 6px;
    border-radius: var(--fs-radius-md, 8px);

    &--warning {
        background: var(--fs-state-warning-lighter, #FFF1EB);
        color: #FF8447;
    }

    &--away {
        background: var(--fs-state-away-lighter, #FFFAEB);
        color: #E2A109;
    }

    &--highlighted {
        background: var(--fs-state-highlighted-lighter, #FFEBF4);
        color: #EF4A9E;
    }
}

.fs_stat_label {
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
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

.fs_overview_section {
    background: var(--fs-bg-primary, #FFF);
    border-radius: var(--fs-radius-md, 8px);
    overflow: hidden;
}

.fs_section_header {
    padding: 12px 20px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
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

.fs_table_wrap {
    width: 100%;
    overflow-x: auto;
}

.fs_data_table {
    width: 100%;
    border-collapse: collapse;

    th, td {
        text-align: left;
        padding: 12px 20px;
        font-size: 14px;
        line-height: 20px;
        letter-spacing: -0.084px;
        white-space: nowrap;
    }

    th {
        background: var(--fs-table-header-bg, #F9FAFB);
        border-top: 1px solid var(--fs-stroke-soft, #E1E4EA);
        border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
        font-weight: 500;
        color: var(--fs-text-secondary, #525866);
    }

    td {
        border-bottom: 1px solid var(--fs-stroke-soft, #E1E4EA);
        color: var(--fs-text-primary, #0E121B);
        font-weight: 400;
    }

    tr:last-child td {
        border-bottom: none;
    }
}

.fs_ellipsis_cell {
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.fs_stacked_cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.fs_stacked_cell_primary {
    font-size: 14px;
    line-height: 20px;
    letter-spacing: -0.084px;
    color: var(--fs-text-primary, #0E121B);
}

.fs_stacked_cell_secondary {
    font-size: 12px;
    line-height: 16px;
    color: var(--fs-text-secondary, #525866);
}

.fs_pagination_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 20px;
    flex-wrap: wrap;
}

.fs_pagination_left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.fs_pagination_page_label {
    font-size: 14px;
    line-height: 20px;
    letter-spacing: -0.084px;
    color: var(--fs-text-secondary, #525866);
}

.fs_page_size_btn {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 4px 6px;
    border-radius: var(--fs-radius-md, 8px);
    border: 1px solid var(--fs-stroke-soft, #E1E4EA);
    background: var(--fs-subtle-btn-bg, #F9FAFB);
    box-shadow: 0px 1px 2px 0px rgba(10, 13, 20, 0.03);
    color: var(--fs-text-secondary, #525866);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    letter-spacing: -0.084px;
    cursor: pointer;
    white-space: nowrap;
}

.fs_pagination_group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.fs_pagination_nav_btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    border-radius: var(--fs-radius-md, 8px);
    background: none;
    border: none;
    color: var(--fs-text-secondary, #525866);
    cursor: pointer;

    &:hover:not(:disabled) {
        background: var(--fs-bg-subtle, #F5F7FA);
    }

    &:disabled {
        opacity: 0.4;
        cursor: default;
    }
}

.fs_pagination_pages {
    display: flex;
    align-items: center;
    gap: 8px;
}

.fs_pagination_cell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 4px;
    border-radius: var(--fs-radius-md, 8px);
    border: 1px solid var(--fs-stroke-soft, #E1E4EA);
    background: var(--fs-bg-primary, #FFF);
    color: var(--fs-text-secondary, #525866);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    letter-spacing: -0.084px;
    cursor: pointer;

    &.is-active {
        border-color: var(--fs-stroke-strong, #0E121B);
        color: var(--fs-text-primary, #0E121B);
    }

    &:disabled {
        cursor: default;
        border-color: transparent;
    }
}
</style>
