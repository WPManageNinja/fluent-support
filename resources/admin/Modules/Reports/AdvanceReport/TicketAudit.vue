<template>
    <div class="fs_ticket_audit_page">
        <!-- Header -->
        <div class="fs_ticket_audit_header">
            <div class="fs_ticket_audit_header_row">
                <h3 class="fs_page_title">{{ $t("AI Ticket Audit") }}</h3>
            </div>
            <div class="fs_ticket_audit_header_row fs_ticket_audit_toolbar">
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
                                :disabled="runState === 'running'"
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
                <div class="fs_toolbar_right">
                    <el-button
                        class="fs_filled_btn"
                        type="primary"
                        :loading="runState === 'running'"
                        :disabled="runState === 'running'"
                        @click="runAudit"
                    >
                        {{ runState === 'running' ? $t('Running…') : $t('Run Audit') }}
                    </el-button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="fs_ticket_audit_content" v-loading="loading">
            <div class="fs_page_error" v-if="fetchError">
                <p>{{ $t("Couldn't load this data.") }}</p>
                <el-button size="small" @click="fetchResults">{{ $t("Retry") }}</el-button>
            </div>

            <template v-else>
                <!-- Idle -->
                <div class="fs_audit_empty_state" v-if="runState === 'idle'">
                    <span class="fs_audit_empty_icon">
                        <IconPack icon-key="ai_ticket_audit" :width="22" :height="22" />
                    </span>
                    <h4>{{ $t('No audit for this range yet') }}</h4>
                    <p>{{ $t('Pick a date range and run an audit to get mood, sentiment score, and a one-line summary for every ticket — tickets that already have a fresh audit are skipped automatically.') }}</p>
                    <el-button class="fs_filled_btn" type="primary" @click="runAudit">{{ $t('Run Audit') }}</el-button>
                </div>

                <!-- Running -->
                <div class="fs_audit_run_card" v-if="runState === 'running'">
                    <div class="fs_audit_run_head">
                        <h4><span class="fs_audit_spinner"></span> {{ $t('Auditing tickets…') }}</h4>
                    </div>
                    <div class="fs_audit_progress_track">
                        <div class="fs_audit_progress_fill" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <div class="fs_audit_run_meta">
                        <span>{{ processedCount }} {{ $t('of') }} {{ totalCandidates }} {{ $t('tickets processed') }}</span>
                        <span>{{ progressPercent }}% {{ $t('complete') }}</span>
                    </div>
                    <div class="fs_audit_run_ticker" v-if="recentAudited.length">
                        <div class="fs_audit_ticker_row" v-for="item in recentAudited" :key="item.ticket_id">
                            <span class="fs_dist_dot" :class="'fs_mood_dot--' + moodKey(item.mood)"></span>
                            <span>
                                #{{ item.ticket_id }} “{{ item.title }}” &rarr;
                                <b>{{ item.status === 'failed' ? $t('Failed') : item.mood }}</b>
                                <template v-if="item.status !== 'failed'">, {{ $t('score') }} {{ item.score }}</template>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Done -->
                <template v-if="runState === 'done'">
                    <div class="fs_stat_cards_grid">
                        <div class="fs_stat_card_tile">
                            <span class="fs_stat_card_icon fs_stat_card_icon--information">
                                <IconPack icon-key="ai_ticket_audit" :width="20" :height="20" />
                            </span>
                            <div class="fs_stat_card_text">
                                <p class="fs_stat_label">{{ $t('Tickets audited') }}</p>
                                <span class="fs_stat_value">{{ summary.total }}</span>
                            </div>
                        </div>
                        <div class="fs_stat_card_tile">
                            <span class="fs_stat_card_icon fs_stat_card_icon--feature">
                                <IconPack icon-key="active_agents" :width="20" :height="20" />
                            </span>
                            <div class="fs_stat_card_text">
                                <p class="fs_stat_label">{{ $t('Completed successfully') }}</p>
                                <span class="fs_stat_value">{{ summary.success }}</span>
                            </div>
                        </div>
                        <div class="fs_stat_card_tile">
                            <span class="fs_stat_card_icon fs_stat_card_icon--warning">
                                <IconPack icon-key="error_warning_line" :width="20" :height="20" />
                            </span>
                            <div class="fs_stat_card_text">
                                <p class="fs_stat_label">{{ $t('Failed') }}</p>
                                <span class="fs_stat_value">{{ summary.failed }}</span>
                            </div>
                        </div>
                        <div class="fs_stat_card_tile">
                            <span class="fs_stat_card_icon fs_stat_card_icon--away">
                                <IconPack icon-key="warning" :width="20" :height="20" />
                            </span>
                            <div class="fs_stat_card_text">
                                <p class="fs_stat_label">{{ $t('Avg. Sentiment Score') }}</p>
                                <span class="fs_stat_value">{{ summary.average_score }}</span>
                            </div>
                        </div>
                        <div class="fs_stat_card_tile">
                            <span class="fs_stat_card_icon fs_stat_card_icon--highlighted">
                                <IconPack icon-key="alert_fill" :width="20" :height="20" />
                            </span>
                            <div class="fs_stat_card_text">
                                <p class="fs_stat_label">{{ $t('Need attention') }}</p>
                                <span class="fs_stat_value">{{ summary.needs_attention }}</span>
                            </div>
                        </div>
                    </div>

                    <template v-if="summary.total">
                        <div class="fs_audit_split_row">
                            <div class="fs_overview_section">
                                <div class="fs_section_header">
                                    <h4 class="fs_section_title">{{ $t('Sentiment distribution') }}</h4>
                                </div>
                                <div class="fs_section_body">
                                    <div class="fs_dist_row" v-for="mood in moods" :key="mood">
                                        <span class="fs_dist_label">
                                            <span class="fs_dist_dot" :class="'fs_mood_dot--' + moodKey(mood)"></span>
                                            {{ $t(mood) }}
                                        </span>
                                        <div class="fs_dist_track">
                                            <div
                                                class="fs_dist_fill"
                                                :class="'fs_mood_fill--' + moodKey(mood)"
                                                :style="{ width: distPercent(mood) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="fs_dist_count">{{ distribution[mood] || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_overview_section">
                                <div class="fs_section_header">
                                    <h4 class="fs_section_title">{{ $t('Needs attention first') }}</h4>
                                </div>
                                <div class="fs_section_body">
                                    <div
                                        class="fs_attention_item"
                                        v-for="row in needsAttentionRows"
                                        :key="row.ticket_id"
                                        @click="goToTicket(row.ticket_id)"
                                    >
                                        <span class="fs_dist_dot" :class="'fs_mood_dot--' + moodKey(row.mood)"></span>
                                        <span class="fs_att_title">#{{ row.ticket_id }} {{ row.title }}</span>
                                        <span class="fs_att_score">{{ row.score }}</span>
                                    </div>
                                    <p class="fs_section_empty" v-if="!needsAttentionRows.length">
                                        {{ $t('No tickets need attention in this range.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="fs_overview_section">
                            <div class="fs_section_header fs_audit_table_header">
                                <div class="fs_status_tabs">
                                    <div class="fs_segmented_control">
                                        <button
                                            v-for="chip in moodChips"
                                            :key="chip.key"
                                            class="fs_segment_button"
                                            :class="{ 'fs_segment_active': activeMoodFilter === chip.key }"
                                            @click="selectMoodFilter(chip.key)"
                                        >
                                            <span
                                                class="fs_dist_dot"
                                                :class="'fs_mood_dot--' + moodKey(chip.key)"
                                                v-if="chip.key !== 'all' && chip.key !== 'failed'"
                                            ></span>
                                            {{ chip.label }} ({{ chip.count }})
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="fs_table_wrap"
                                :class="{ 'is-scrolled': tableScrolled }"
                                @scroll="onTableScroll"
                            >
                                <table class="fs_data_table">
                                    <thead>
                                        <tr>
                                            <th>{{ $t("Ticket") }}</th>
                                            <th>{{ $t("Product") }}</th>
                                            <th>{{ $t("Agent") }}</th>
                                            <th>{{ $t("Mood") }}</th>
                                            <th>{{ $t("Score") }}</th>
                                            <th>{{ $t("AI Summary") }}</th>
                                            <th>{{ $t("Audited") }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in rows" :key="row.ticket_id">
                                            <td class="fs_ticket_cell">
                                                <div class="fs_ticket_cell_inner">
                                                    <span class="fs_ticket_id_muted">#{{ row.ticket_id }}</span>
                                                    <a href="javascript:void(0)" class="fs_ticket_link" :title="row.title" @click="goToTicket(row.ticket_id)">{{ row.title }}</a>
                                                    <span class="fs_stale_badge" v-if="row.stale">{{ $t('Updated since audit') }}</span>
                                                </div>
                                            </td>
                                            <td>{{ row.product || '—' }}</td>
                                            <td>{{ row.agent || '—' }}</td>
                                            <td>
                                                <span v-if="row.status === 'failed'" class="fs_fail_tag">{{ $t('Failed') }}</span>
                                                <span v-else class="fs_mood_badge" :class="'fs_mood_badge--' + moodKey(row.mood)">{{ row.mood }}</span>
                                            </td>
                                            <td>
                                                <template v-if="row.score !== null && row.score !== undefined">{{ row.score }}<span class="fs_score_scale">/10</span></template>
                                                <template v-else>—</template>
                                            </td>
                                            <td class="fs_summary_cell">{{ row.summary || '—' }}</td>
                                            <td>{{ formatDate(row.audited_at) }}</td>
                                            <td>
                                                <el-button class="fs_outline_btn" size="small" v-if="row.status !== 'failed'" @click="openEscalate(row)">
                                                    {{ $t('Escalate') }}
                                                </el-button>
                                            </td>
                                        </tr>
                                        <tr v-if="!rows.length">
                                            <td colspan="8">
                                                <p class="fs_section_empty">{{ $t('No Data') }}</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="fs_pagination_wrapper" v-if="rows.length">
                                <span class="fs_pagination_left">
                                    <p>{{ $t("Page") }} {{ pagination.current_page }} {{ $t("of") }} {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                                    <pagination
                                        @fetch="fetchResults(false)"
                                        :pagination="pagination"
                                        layout="sizes"
                                    />
                                </span>
                                <span class="fs_pagination_right">
                                    <pagination
                                        @fetch="fetchResults(false)"
                                        :pagination="pagination"
                                        :background="true"
                                        layout="prev, pager, next"
                                    />
                                </span>
                            </div>
                        </div>
                    </template>

                    <div class="fs_audit_empty_state" v-else>
                        <p>{{ $t('No tickets found for this range.') }}</p>
                    </div>
                </template>
            </template>
        </div>

        <el-dialog v-model="escalateVisible" :title="$t('Escalate ticket')" width="440px" class="fs_dialog fs_escalate_dialog">
            <p class="fs_escalate_ticket_name" v-if="escalateRow">#{{ escalateRow.ticket_id }} — {{ escalateRow.title }}</p>
            <span class="fs_priority_badge">{{ $t('Sets priority to Critical') }}</span>

            <div class="fs_field">
                <label>{{ $t('Assign to') }}</label>
                <el-select
                    v-model="escalateAgentId"
                    :placeholder="$t('Select agent')"
                    :loading="agentsFilterLoading"
                    :disabled="agentsFilterError"
                    filterable
                    class="fs_select_field"
                    style="width: 100%"
                >
                    <el-option v-for="agent in agentsFilter" :key="agent.id" :label="agent.name" :value="agent.id" />
                </el-select>
                <p class="fs_field_error" v-if="agentsFilterError">
                    {{ $t("Couldn't load agents.") }}
                    <a href="javascript:void(0)" @click="fetchAgentsFilter">{{ $t('Retry') }}</a>
                </p>
            </div>

            <div class="fs_field">
                <label>{{ $t('Internal note') }}</label>
                <el-input v-model="escalateNote" type="textarea" :rows="3" class="fs_textarea_input" />
            </div>

            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button class="fs_outline_btn" @click="escalateVisible = false">{{ $t('Cancel') }}</el-button>
                    <el-button
                        class="fs_filled_btn"
                        type="primary"
                        :loading="escalateSubmitting"
                        :disabled="agentsFilterLoading || agentsFilterError || !agentsFilter.length"
                        @click="submitEscalate"
                    >
                        {{ $t('Escalate ticket') }}
                    </el-button>
                </span>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import IconPack from "@/admin/Components/IconPack.vue";
import Pagination from "@/admin/Pieces/Pagination.vue";
import { shortcuts } from "../Utils/dateShortCuts";
import { formatDateRangeForDisplay, isFutureDate, isSameDateRange } from "../Utils/reportHelpers";

const MOODS = ["Happy", "Neutral", "Frustrated", "Very Unhappy"];
const BATCH_SIZE = 5;

export default {
    name: "TicketAudit",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        IconPack,
        Pagination
    },
    data() {
        return {
            loading: false,
            fetchError: false,
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            moods: MOODS,

            runState: "idle", // idle | running | done
            totalCandidates: 0,
            processedCount: 0,
            recentAudited: [],

            summary: { total: 0, success: 0, failed: 0, average_score: 0, needs_attention: 0 },
            distribution: {},
            rows: [],
            needsAttentionRows: [],
            activeMoodFilter: "all",

            // Drives the pinned-column shadow; only shown once scrolled.
            tableScrolled: false,

            pagination: { current_page: 1, per_page: 20, total: 0 },

            resultsRequestToken: 0,
            pendingExternalDateRange: null,

            agentsFilter: [],
            agentsFilterLoading: false,
            agentsFilterError: false,
            escalateVisible: false,
            escalateRow: null,
            escalateAgentId: null,
            escalateNote: "",
            escalateSubmitting: false,
            escalateStepsDone: { agent: false, priority: false, note: false }
        };
    },
    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        progressPercent() {
            if (!this.totalCandidates) {
                return 0;
            }
            return Math.min(100, Math.round((this.processedCount / this.totalCandidates) * 100));
        },
        moodChips() {
            return [
                { key: "all", label: this.$t("All"), count: this.summary.total },
                ...MOODS.map(mood => ({ key: mood, label: this.$t(mood), count: this.distribution[mood] || 0 })),
                { key: "failed", label: this.$t("Failed"), count: this.summary.failed }
            ];
        }
        // Mood filtering and "needs attention" are resolved server-side now (see
        // fetchResults()) — this.rows is already the current page's filtered set,
        // and this.needsAttentionRows is a separate bounded query over the whole
        // range, not just whatever happens to be on the current page.
    },
    watch: {
        date_range: {
            handler(newVal) {
                // Guard against this component's own date-change emission
                // round-tripping back through the parent prop — only externally
                // supplied ranges (e.g. browser back/forward restoring
                // date_range via Report.vue's popstate handler, or this tab
                // being kept alive while another report tab changes the range)
                // should trigger a refetch here.
                if (this.isSameDateRange(newVal, this.localDateRange)) {
                    return;
                }
                if (this.runState === "running") {
                    // Don't yank the range out from under an in-flight audit run
                    // — the picker is disabled while running anyway, so this can
                    // only be an external change. Queue it so runAudit() applies
                    // it once the run finishes, instead of dropping it silently.
                    this.pendingExternalDateRange = newVal;
                    return;
                }
                this.localDateRange = newVal;
                this.activeMoodFilter = "all";
                this.pagination.current_page = 1;
                this.fetchResults();
            },
            deep: true
        }
    },
    methods: {
        isSameDateRange,
        onTableScroll(event) {
            this.tableScrolled = event.target.scrollLeft > 0;
        },
        moodKey(mood) {
            return String(mood || "").toLowerCase().replace(/\s+/g, "-");
        },
        distPercent(mood) {
            if (!this.summary.success) {
                return 0;
            }
            return Math.round(((this.distribution[mood] || 0) / this.summary.success) * 100);
        },
        disabledDate: isFutureDate,
        formatDate(value) {
            return value ? this.$dayjs(value).format("MMM D, h:mm A") : "—";
        },
        handleDateChange() {
            this.$emit("date-change", this.localDateRange);
            this.activeMoodFilter = "all";
            this.pagination.current_page = 1;
            this.fetchResults();
        },
        goToTicket(ticketId) {
            this.$router.push({ name: "view_ticket", params: { ticket_id: ticketId } });
        },

        selectMoodFilter(key) {
            if (this.activeMoodFilter === key) {
                return;
            }
            this.activeMoodFilter = key;
            this.pagination.current_page = 1;
            // Summary/distribution/needs-attention depend only on the date
            // range, not the mood filter — no need to re-scan the whole
            // range for them just because which page of rows is shown changed.
            this.fetchResults(false);
        },

        async fetchResults(withAggregates = true) {
            if (!this.localDateRange || this.localDateRange.length !== 2) {
                // Invalidate any request already in flight so its response can't
                // land afterward and populate the table for a range that's no
                // longer selected — an empty range gets a clean idle state, not
                // whatever the previous range's stale results happen to be.
                this.resultsRequestToken++;
                this.loading = false;
                this.fetchError = false;
                this.summary = { total: 0, success: 0, failed: 0, average_score: 0, needs_attention: 0 };
                this.distribution = {};
                this.needsAttentionRows = [];
                this.rows = [];
                this.pagination.current_page = 1;
                this.pagination.total = 0;
                this.runState = "idle";
                return;
            }
            this.loading = true;
            this.fetchError = false;
            const [from, to] = this.localDateRange;
            // A newer fetchResults() call (e.g. the date range changed again) may resolve
            // before this one — only the response for the *current* token may apply.
            const requestToken = ++this.resultsRequestToken;
            try {
                const response = await this.$get("advanced-reports/audit/results", {
                    from,
                    to,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    mood_filter: this.activeMoodFilter,
                    with_aggregates: withAggregates ? 1 : 0
                });
                if (requestToken !== this.resultsRequestToken) {
                    return;
                }
                if (withAggregates) {
                    this.summary = response.summary;
                    this.distribution = response.distribution;
                    this.needsAttentionRows = response.needs_attention_rows || [];
                }
                this.rows = response.rows.data || [];
                this.pagination.current_page = response.rows.current_page;
                // Synced from the response rather than trusted as-sent — the
                // backend is the source of truth for what per_page it actually
                // applied, so page-count math can't drift out of sync with a
                // server-side cap even if that cap changes independently of
                // this page's size options.
                this.pagination.per_page = response.rows.per_page;
                this.pagination.total = response.rows.total;
                this.runState = this.summary.total ? "done" : "idle";
            } catch (error) {
                if (requestToken !== this.resultsRequestToken) {
                    return;
                }
                this.fetchError = true;
                this.$handleError(error);
                // If this was the refresh at the end of runAudit(), don't leave
                // the toolbar (date picker / Run Audit button, both disabled
                // while runState === "running") stuck unusable just because
                // this particular refresh failed — the error banner above
                // already covers retry via the Retry button.
                if (this.runState === "running") {
                    this.runState = "idle";
                }
            } finally {
                if (requestToken === this.resultsRequestToken) {
                    this.loading = false;
                }
            }
        },

        async fetchAgentsFilter() {
            this.agentsFilterLoading = true;
            this.agentsFilterError = false;
            try {
                const response = await this.$get("advanced-reports/performance-overview/filters");
                this.agentsFilter = response.agents || [];
            } catch (error) {
                // Surfaced in the Escalate modal (error message + Retry, select
                // and submit disabled) rather than silently leaving an empty,
                // unusable dropdown with no way to recover.
                this.agentsFilterError = true;
            } finally {
                this.agentsFilterLoading = false;
            }
        },

        async runAudit() {
            if (!this.localDateRange || this.localDateRange.length !== 2) {
                return;
            }

            this.runState = "running";
            this.processedCount = 0;
            this.recentAudited = [];
            const [from, to] = this.localDateRange;

            let candidateResponse;
            try {
                candidateResponse = await this.$post("advanced-reports/audit/run", { from, to });
            } catch (error) {
                this.runState = "idle";
                this.$handleError(error);
                return;
            }

            this.totalCandidates = candidateResponse.queued;
            const ticketIds = candidateResponse.ticket_ids || [];

            if (candidateResponse.total > candidateResponse.queued) {
                this.$notify({
                    type: "warning",
                    title: this.$t("Batch limited"),
                    message: this.$t("{queued} of {total} tickets queued — narrow the date range or run again to cover the rest.")
                        .replace("{queued}", candidateResponse.queued)
                        .replace("{total}", candidateResponse.total)
                });
            }

            let failedCount = 0;

            for (let i = 0; i < ticketIds.length; i += BATCH_SIZE) {
                const chunk = ticketIds.slice(i, i + BATCH_SIZE);
                try {
                    const batchResponse = await this.$post("advanced-reports/audit/process-batch", { ticket_ids: chunk, from, to });
                    const results = batchResponse.results || [];
                    this.recentAudited = results.concat(this.recentAudited).slice(0, 5);
                    this.processedCount += results.length;
                    // Ticket IDs the server didn't return a result for (e.g. a per-ticket
                    // lock timeout) — only confirmed results count as processed.
                    failedCount += Math.max(0, chunk.length - results.length);
                } catch (error) {
                    // one failed batch shouldn't abort the whole run — keep going, but
                    // it must still be counted as unprocessed rather than silently skipped
                    failedCount += chunk.length;
                }
            }

            if (failedCount > 0) {
                this.$notify({
                    type: "warning",
                    title: this.$t("Audit incomplete"),
                    message: this.$t("{count} ticket(s) weren't audited in this run. Click Run Audit again to retry any that still need it.")
                        .replace("{count}", failedCount)
                });
            }

            // Apply any date_range prop change that arrived externally while this
            // run was in flight (queued by the date_range watcher below) instead
            // of leaving localDateRange silently out of sync with the parent.
            if (this.pendingExternalDateRange) {
                this.localDateRange = this.pendingExternalDateRange;
                this.pendingExternalDateRange = null;
            }

            this.activeMoodFilter = "all";
            this.pagination.current_page = 1;
            await this.fetchResults();
        },

        openEscalate(row) {
            this.escalateRow = row;
            this.escalateAgentId = null;
            this.escalateNote = row.summary ? this.$t("AI audit: ") + row.summary : "";
            // Escalation is 3 separate, non-atomic REST calls (no backend endpoint
            // combines them) — track which have already landed so a retry after a
            // partial failure only re-attempts what's left, instead of re-running
            // steps that already succeeded.
            this.escalateStepsDone = { agent: false, priority: false, note: false };
            this.escalateVisible = true;
        },

        async submitEscalate() {
            if (!this.escalateAgentId) {
                this.$handleError(this.$t("Please select an agent to assign"));
                return;
            }

            this.escalateSubmitting = true;
            const ticketId = this.escalateRow.ticket_id;

            try {
                if (!this.escalateStepsDone.agent) {
                    await this.$put(`tickets/${ticketId}/property`, { prop_name: "agent_id", prop_value: this.escalateAgentId });
                    this.escalateStepsDone.agent = true;
                }

                if (!this.escalateStepsDone.priority) {
                    await this.$put(`tickets/${ticketId}/property`, { prop_name: "priority", prop_value: "critical" });
                    this.escalateStepsDone.priority = true;
                }

                if (this.escalateNote && !this.escalateStepsDone.note) {
                    await this.$post(`tickets/${ticketId}/responses`, {
                        content: this.escalateNote,
                        conversation_type: "internal_info"
                    });
                    this.escalateStepsDone.note = true;
                }

                this.$notify({
                    type: "success",
                    title: this.$t("Escalated"),
                    message: this.$t("Ticket has been escalated.")
                });
                this.escalateVisible = false;
            } catch (error) {
                const completedSteps = [];
                if (this.escalateStepsDone.agent) completedSteps.push(this.$t("agent assigned"));
                if (this.escalateStepsDone.priority) completedSteps.push(this.$t("priority set to Critical"));
                if (this.escalateStepsDone.note) completedSteps.push(this.$t("note added"));

                if (completedSteps.length) {
                    // Some steps already committed server-side — say so explicitly
                    // rather than a generic error that implies nothing happened,
                    // and keep the dialog open so clicking Escalate again only
                    // retries what's left.
                    this.$notify({
                        type: "warning",
                        title: this.$t("Escalation incomplete"),
                        message: this.$t("Already done: {done}. The rest failed — click Escalate ticket again to retry it.")
                            .replace("{done}", completedSteps.join(", "))
                    });
                } else {
                    this.$handleError(error);
                }
            } finally {
                this.escalateSubmitting = false;
            }
        }
    },
    mounted() {
        this.fetchAgentsFilter();
        this.fetchResults();
    }
};
</script>

