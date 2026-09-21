<template>
    <div class="fs_performance_overview_page">
        <!-- Header -->
        <div class="fs_performance_overview_header">
            <div class="fs_performance_overview_header_row">
                <h3 class="fs_page_title">{{ $t("Agent Performance") }}</h3>
                <LiveSnapshotSelector
                    v-model="liveMode"
                    v-model:selected-snapshot="selectedSnapshot"
                    report-type="performance_overview"
                    @change="handleLiveModeChange"
                />
            </div>
            <div class="fs_performance_overview_header_row fs_performance_overview_toolbar">
                <div class="fs_toolbar_left">
                    <el-dropdown trigger="click" :hide-on-click="false" :disabled="isSnapshotMode" @command="() => {}">
                        <button class="fs_pill_dropdown_btn fs_team_pill_btn" :disabled="isSnapshotMode" :title="selectedTeamLabel">
                            <span class="fs_pill_dropdown_label">{{ selectedTeamLabel }}</span>
                            <el-icon class="fs_dropdown_arrow"><ArrowDown /></el-icon>
                        </button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_global_dropdown">
                                <el-dropdown-item
                                    v-for="team in teamOptions"
                                    :key="team.id"
                                    class="fs_team_option_item"
                                    :aria-checked="isTeamSelected(team.id)"
                                    @click="toggleTeam(team.id)"
                                >
                                    <el-checkbox
                                        :model-value="isTeamSelected(team.id)"
                                        class="fs_team_option_checkbox"
                                    />
                                    {{ team.name }}
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>

                    <div class="fs_agent_chip_select">
                        <el-dropdown
                            trigger="click"
                            :hide-on-click="false"
                            :disabled="isSnapshotMode"
                            @command="() => {}"
                            @visible-change="onAgentDropdownToggle"
                        >
                            <button type="button" class="fs_agent_chip_trigger" :disabled="isSnapshotMode">
                                <div class="fs_agent_chip_row" v-if="selectedAgentChips.length">
                                    <span
                                        class="fs_agent_chip"
                                        v-for="agent in visibleAgentChips"
                                        :key="agent.id"
                                    >
                                        {{ agent.name }}
                                        <IconPack icon-key="close" :width="8" :height="8" fill="#717784" @click.stop="removeAgentChip(agent.id)" />
                                    </span>
                                    <span class="fs_agent_chip fs_agent_chip--overflow" v-if="hiddenAgentChipCount > 0">
                                        {{ hiddenAgentChipCount }}+
                                    </span>
                                </div>
                                <span class="fs_agent_chip_placeholder" v-else>{{ $t('Select Agents') }}</span>
                            </button>
                            <template #dropdown>
                                <div class="fs_agent_dropdown_panel">
                                    <div class="fs_dropdown_search" @click.stop>
                                        <el-input
                                            v-model="agentSearchQuery"
                                            :placeholder="$t('Search agents') + '...'"
                                            clearable
                                            size="small"
                                            class="fs_text_input"
                                            @click.stop
                                        >
                                            <template #prefix>
                                                <IconPack icon-key="search" :width="16" :height="16" />
                                            </template>
                                        </el-input>
                                    </div>
                                    <el-dropdown-menu class="fs_global_dropdown">
                                        <el-dropdown-item
                                            v-for="agent in filteredAgentOptions"
                                            :key="agent.id"
                                            class="fs_team_option_item"
                                            :aria-checked="selectedAgentIds.includes(agent.id)"
                                            @click="toggleAgentSelection(agent.id)"
                                        >
                                            <el-checkbox
                                                :model-value="selectedAgentIds.includes(agent.id)"
                                                class="fs_team_option_checkbox"
                                            />
                                            {{ agent.name }}
                                        </el-dropdown-item>
                                        <div class="fs_dropdown_no_results" v-if="!filteredAgentOptions.length">
                                            {{ $t('No agents found') }}
                                        </div>
                                    </el-dropdown-menu>
                                </div>
                            </template>
                        </el-dropdown>
                        <button
                            type="button"
                            class="fs_agent_clear_btn"
                            :disabled="isSnapshotMode || !selectedAgentIds.length"
                            :title="$t('Clear selected agents')"
                            @click="clearSelectedAgents"
                        >
                            <IconPack icon-key="close" :width="10" :height="10" />
                        </button>
                    </div>

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

                    <button type="button" class="fs_reset_filter_link" :disabled="isSnapshotMode" @click="resetFilters">
                        {{ $t("Reset Filter") }}
                    </button>
                </div>

                <div class="fs_toolbar_right">
                    <button type="button" class="fs_pill_dropdown_btn" @click="handleExport">
                        {{ $t("Export") }}
                    </button>
                </div>
            </div>
        </div>

        <el-dialog
            :append-to-body="true"
            v-model="open_export_options"
            class="fs_dialog fs_export_options_dialog"
            width="45%"
            @close="closeExportModal"
        >
            <template #header>
                <div class="fs_agent_summary_dialog_header">
                    <p class="fs_agent_summary_title">{{ $t('exclude_or_include_summary_column') }}</p>
                    <p class="fs_dialog_description_wrapper">
                        {{ $t("If you don't select any column, by default system will take all.") }}
                    </p>
                </div>
            </template>
            <div class="fs_summary_settings fframe_body">
                <el-checkbox v-model="checkAll" :indeterminate="isIndeterminate" @change="handleCheckAllChange">
                    <template v-if="!checkAll">{{ $t('Check all') }}</template>
                    <template v-else>{{ $t('Uncheck all') }}</template>
                </el-checkbox>
                <el-checkbox-group v-model="selected_options" class="fs_summary_export_items" @change="handleColumnChanges">
                    <el-checkbox v-for="(item, index) in repost_export_options" :key="index" :model-value="index" :label="item" />
                </el-checkbox-group>
            </div>
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button class="fs_outline_btn" @click="closeExportModal">{{ $t('Cancel') }}</el-button>
                    <el-button class="fs_filled_btn" type="primary" @click="confirmExport">{{ $t('Export Agents Summary') }}</el-button>
                </span>
            </template>
        </el-dialog>

        <!-- Main Content -->
        <div class="fs_performance_overview_content" v-loading="loading">
            <div class="fs_page_error" v-if="filtersError || fetchError">
                <p>{{ $t("Couldn't load this data.") }}</p>
                <el-button size="small" @click="handleRetry">{{ $t("Retry") }}</el-button>
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
                        <div class="fs_stat_card_value_row">
                            <span class="fs_stat_value">{{ stat.value }}</span>
                            <span class="fs_stat_value_suffix" v-if="stat.suffix">{{ stat.suffix }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agents Statistics -->
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
                    <div class="fs_chart_container" style="height: 400px;">
                        <bar-chart-base
                            :chartData="growthChartData"
                            :chartOptions="growthChartOptions"
                        ></bar-chart-base>
                    </div>
                </div>
            </div>

            <!-- Agent Comparison (only meaningful when comparing more than one agent) -->
            <div class="fs_overview_section" v-if="selectedAgents.length > 1">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ $t("Agent Comparison") }}</h4>
                </div>
                <div class="fs_section_body">
                    <div class="fs_chart_container" style="height: 400px;">
                        <comparison-bar-chart
                            :items="agentComparisonItems"
                            :series-name="$t('Responses')"
                            :entity-label="$t('Agent')"
                            :value-label="$t('Responses')"
                            :caption-text="$t('Agent response comparison')"
                            :window-size="8"
                        ></comparison-bar-chart>
                    </div>
                </div>
            </div>

            <!-- Hourly Activity -->
            <div class="fs_overview_section">
                <div class="fs_section_header">
                    <h4 class="fs_section_title">{{ hourlyActivityTitle }}</h4>
                    <div class="fs_chart_legend" v-if="legendItems.length > 1">
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
                        <bar-chart-base
                            v-if="hourlyActivityChartData"
                            :chartData="hourlyActivityChartData"
                            :chartOptions="hourlyActivityChartOptions"
                        ></bar-chart-base>
                    </div>
                </div>
            </div>

            <!-- Agents Report Summary — live only. This fetches its own
                 date-range report, which a snapshot payload has no frozen
                 equivalent of, so showing it in snapshot mode would put live
                 totals next to frozen charts. -->
            <agent-reports
                v-if="!isSnapshotMode"
                :url="'reports/agents-summary'"
                :date_range="localDateRange"
                :agent_ids="selectedAgentIds"
            />

            </template>
        </div>
    </div>
</template>

<script type="text/babel">
import BarChartBase from "../Charts/BarChartBase";
import ComparisonBarChart from "../Charts/ComparisonBarChart.vue";
import LiveSnapshotSelector from "./Parts/LiveSnapshotSelector.vue";
import IconPack from "@/admin/Components/IconPack.vue";
import AgentReports from "../AgentReports.vue";
import { ArrowDown } from "@element-plus/icons-vue";
import { shortcuts } from "../Utils/dateShortCuts";
import {
    formatDateRangeForDisplay,
    formatStatValue,
    getDefaultDateRange,
    handleCheckAllChange as handleCheckAllChangeUtil,
    handleColumnChanges as handleColumnChangesUtil,
    isFutureDate,
    isSameDateRange
} from "../Utils/reportHelpers";
import dayjs from "dayjs";
import debounce from "lodash/debounce";

const AGENT_CHIP_VISIBLE_LIMIT = 3;

// Maps the "Agents Statistics" segmented tabs to the matching key inside the
// performance-overview response's `growth` payload.
const GROWTH_SERIES_KEYS = {
    "tickets-chart": "tickets",
    "resolve-chart": "resolved",
    "response-chart": "responses"
};

export default {
    name: "PerformanceOverview",
    props: ["url", "date_range"],
    emits: ["date-change"],
    components: {
        BarChartBase,
        ComparisonBarChart,
        LiveSnapshotSelector,
        IconPack,
        AgentReports,
        ArrowDown
    },
    data() {
        return {
            loading: false,
            filtersLoading: false,
            filtersError: false,
            fetchError: false,
            fetchRequestId: 0,
            liveMode: "live",
            selectedSnapshot: null,
            preSnapshotDateRange: null,
            agents: [],
            teams: [],
            selectedTeamIds: ["all"],
            selectedAgentIds: [],
            agentSearchQuery: "",
            open_export_options: false,
            repost_export_options: this.appVars.repost_export_options,
            selected_options: [],
            checkAll: false,
            isIndeterminate: false,
            agentStats: [],
            ticketTotals: { closed: null, open: null },
            localDateRange: this.date_range,
            shortcuts: shortcuts,
            activityGranularity: "hour",
            hourlyLabels: [],
            activitySeries: [],
            currently_showing: "tickets-chart",
            chartMaps: {
                "tickets-chart": this.$t("Ticket Stats"),
                "resolve-chart": this.$t("Resolve Stats"),
                "response-chart": this.$t("Response Stats"),
            },
            growthData: { tickets: {}, resolved: {}, responses: {} },
            growthChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: "linear",
                        position: "left",
                        grid: {
                            drawOnChartArea: true,
                            color: "#E1E4EA"
                        },
                        border: {
                            display: false,
                            dash: [4, 4]
                        },
                        ticks: {
                            callback: function (value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            }
                        },
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
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
                        barThickness: 8,
                        categoryPercentage: 0.7
                    }
                }
            },
            hourlyActivityChartData: null,
            hourlyActivityChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: true
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
                },
                datasets: {
                    bar: {
                        // Same bar width as the Agents Statistics chart above.
                        barThickness: 8,
                        categoryPercentage: 0.7,
                        borderRadius: 3
                    }
                }
            }
        };
    },
    computed: {
        selectedAgents() {
            return this.agentStats;
        },
        agentComparisonItems() {
            return this.selectedAgents.map(agent => ({ id: agent.id, name: agent.name, value: agent.responses }));
        },
        teamOptions() {
            return [
                { id: "all", name: this.$t("All Teams"), agent_ids: this.agents.map(agent => agent.id) },
                ...this.teams
            ];
        },
        selectedTeamLabel() {
            if (this.selectedTeamIds.includes("all")) {
                return this.$t("All Teams");
            }
            if (!this.selectedTeamIds.length) {
                return this.$t("No Teams");
            }
            if (this.selectedTeamIds.length === 1) {
                const team = this.teamOptions.find(item => item.id === this.selectedTeamIds[0]);
                return team ? team.name : this.$t("All Teams");
            }
            return this.selectedTeamIds.length + " " + this.$t("Teams");
        },
        formattedDateRange() {
            return formatDateRangeForDisplay(this.localDateRange);
        },
        hourlyActivityTitle() {
            const isDaily = this.activityGranularity === "day";
            const singleAgent = this.selectedAgents.length === 1;
            if (isDaily) {
                const label = singleAgent ? this.$t("Daily Activity") : this.$t("Daily Team Activity");
                return `${label} — ${this.formattedDateRange}`;
            }
            const label = singleAgent ? this.$t("Hourly Activity") : this.$t("Hourly Team Activity");
            const dayLabel = this.localDateRange && this.localDateRange[0] ? dayjs(this.localDateRange[0]).format("MMM D, YYYY") : "";
            return `${label} — ${dayLabel}`;
        },
        totalResponses() {
            return this.selectedAgents.reduce((sum, agent) => sum + agent.responses, 0);
        },
        activeAgentCount() {
            return this.selectedAgents.filter(agent => agent.responses > 0).length;
        },
        statCards() {
            return [
                {
                    icon: "pulse_line",
                    variant: "information",
                    label: this.$t("Total Responses"),
                    value: formatStatValue(this.totalResponses, "--")
                },
                {
                    icon: "time_sheet",
                    variant: "feature",
                    label: this.$t("Tickets closed"),
                    value: formatStatValue(this.ticketTotals.closed, "--")
                },
                {
                    icon: "line_chart_line",
                    variant: "warning",
                    label: this.$t("Open Ticket"),
                    value: formatStatValue(this.ticketTotals.open, "--")
                },
                {
                    icon: "time_sheet",
                    variant: "highlighted",
                    label: this.$t("Active agents"),
                    value: formatStatValue(this.activeAgentCount, "--"),
                    suffix: "/" + this.selectedAgents.length
                },
            ];
        },
        chartPalette() {
            const computedStyle = getComputedStyle(document.documentElement);
            return [
                computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0cbe7e',
                computedStyle.getPropertyValue('--fs-chart-secondary').trim() || '#47c2ff',
                computedStyle.getPropertyValue('--fs-chart-quaternary').trim() || '#7D52F4',
                computedStyle.getPropertyValue('--fs-chart-quinary').trim() || '#FF8447',
                computedStyle.getPropertyValue('--fs-chart-tertiary').trim() || '#22d3bb',
            ];
        },
        othersColor() {
            const computedStyle = getComputedStyle(document.documentElement);
            return computedStyle.getPropertyValue('--fs-text-muted').trim() || '#99A0AE';
        },
        topActivitySeries() {
            const limit = this.chartPalette.length;
            if (this.activitySeries.length <= limit) {
                return this.activitySeries;
            }
            const ranked = this.activitySeries
                .map(series => ({
                    ...series,
                    total: series.data.reduce((sum, value) => sum + value, 0)
                }))
                .sort((a, b) => b.total - a.total);
            const top = ranked.slice(0, limit);
            const rest = ranked.slice(limit);
            const othersData = this.hourlyLabels.map((label, index) =>
                rest.reduce((sum, series) => sum + (series.data[index] || 0), 0)
            );
            return [
                ...top,
                {
                    agent_id: "others",
                    agent_name: `${this.$t("Others")} (${rest.length})`,
                    data: othersData,
                    isOthers: true
                }
            ];
        },
        legendItems() {
            return this.topActivitySeries.map((series, index) => ({
                key: series.agent_id,
                label: series.agent_name,
                color: series.isOthers ? this.othersColor : this.chartPalette[index % this.chartPalette.length]
            }));
        },
        isSnapshotMode() {
            return this.liveMode === "snapshot" && !!this.selectedSnapshot;
        },
        selectedAgentChips() {
            return this.agents.filter(agent => this.selectedAgentIds.includes(agent.id) && agent.name && agent.name.trim());
        },
        visibleAgentChips() {
            return this.selectedAgentChips.slice(0, AGENT_CHIP_VISIBLE_LIMIT);
        },
        hiddenAgentChipCount() {
            return Math.max(0, this.selectedAgentChips.length - AGENT_CHIP_VISIBLE_LIMIT);
        },
        filteredAgentOptions() {
            const query = this.agentSearchQuery.trim().toLowerCase();
            if (!query) {
                return this.agents;
            }
            return this.agents.filter(agent => (agent.name || "").toLowerCase().includes(query));
        },
        growthChartData() {
            const stats = this.growthData[GROWTH_SERIES_KEYS[this.currently_showing]] || {};
            const labels = Object.keys(stats);
            const computedStyle = getComputedStyle(document.documentElement);
            const chartColor = computedStyle.getPropertyValue('--fs-chart-primary').trim() || '#0cbe7e';
            return {
                labels,
                datasets: [
                    {
                        label: this.chartMaps[this.currently_showing],
                        backgroundColor: chartColor,
                        data: labels.map(label => stats[label])
                    }
                ]
            };
        }
    },
    watch: {
        selectedAgentIds() {
            this.persistAgentSelection();
        },
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
                this.fetchPerformanceOverview();
            },
            deep: true
        }
    },
    created() {
        this.debouncedFetchPerformanceOverview = debounce(() => this.fetchPerformanceOverview(), 300);
    },
    methods: {
        handleComponentChange(item) {
            this.currently_showing = item;
        },
        handleLiveModeChange({ mode, snapshot }) {
            this.liveMode = mode;
            this.selectedSnapshot = snapshot;
            if (mode === "snapshot" && snapshot) {
                this.debouncedFetchPerformanceOverview.cancel();
                if (!this.preSnapshotDateRange) {
                    this.preSnapshotDateRange = this.localDateRange;
                }
                this.fetchSnapshotData(snapshot.id);
            } else {
                if (this.preSnapshotDateRange) {
                    this.localDateRange = this.preSnapshotDateRange;
                    this.preSnapshotDateRange = null;
                }
                this.fetchPerformanceOverview();
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
                    this.applyPerformanceOverviewResponse(response.data);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.agentStats = [];
                    this.ticketTotals = { closed: null, open: null };
                    this.activitySeries = [];
                    this.hourlyLabels = [];
                    this.growthData = { tickets: {}, resolved: {}, responses: {} };
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
        isSameDateRange,
        handleAgentSelectionChange() {
            this.fetchPerformanceOverview();
        },
        removeAgentChip(agentId) {
            this.selectedAgentIds = this.selectedAgentIds.filter(id => id !== agentId);
            this.handleAgentSelectionChange();
        },
        clearSelectedAgents() {
            if (!this.selectedAgentIds.length) {
                return;
            }
            this.selectedAgentIds = [];
            this.handleAgentSelectionChange();
        },
        toggleAgentSelection(agentId) {
            if (this.selectedAgentIds.includes(agentId)) {
                this.selectedAgentIds = this.selectedAgentIds.filter(id => id !== agentId);
            } else {
                this.selectedAgentIds = [...this.selectedAgentIds, agentId];
            }
            this.handleAgentSelectionChange();
        },
        onAgentDropdownToggle(visible) {
            if (!visible) {
                this.agentSearchQuery = "";
            }
        },
        // Backward compatibility: the summary used to be filtered by an
        // include/exclude dialog that no longer exists. Seed the agent picker
        // with whatever that setting last held so upgrading users keep their
        // selection; the picker is editable from here on.
        defaultAgentSelection() {
            const allAgentIds = this.agents.map(agent => agent.id);
            const settings = this.$getData('agents_summary_setting');
            const savedIds = settings && Array.isArray(settings.agents) ? settings.agents.map(Number) : [];

            if (!savedIds.length) {
                return allAgentIds;
            }

            // `known` records which agents existed when the selection was last
            // written. An agent missing from it was created later, so it starts
            // selected rather than inheriting an exclusion it never was part of.
            // Legacy settings have no `known`: assume every current agent was
            // around back then, which preserves the saved exclusions exactly.
            const known = settings && Array.isArray(settings.known)
                ? settings.known.map(Number)
                : allAgentIds.map(Number);

            const selection = allAgentIds.filter(id => {
                return savedIds.includes(Number(id)) || !known.includes(Number(id));
            });

            return selection.length ? selection : allAgentIds;
        },

        // The include/exclude dialog is gone, so the agent picker is now the
        // control that owns it — persist under the same key it always used.
        persistAgentSelection() {
            if (!this.agents.length) {
                return;
            }
            this.$saveData('agents_summary_setting', {
                agents: this.selectedAgentIds.map(Number),
                known: this.agents.map(agent => Number(agent.id))
            });
        },

        resetFilters() {
            this.selectedTeamIds = ["all"];
            this.selectedAgentIds = this.defaultAgentSelection();
            this.localDateRange = getDefaultDateRange();
            this.$emit('date-change', this.localDateRange);
            this.fetchPerformanceOverview();
        },
        handleExport() {
            if (!this.localDateRange || this.localDateRange.length !== 2 || !this.selectedAgentIds.length) {
                this.$handleError(this.$t('No agent found, Please select or make sure you have agents to export'));
                return;
            }
            this.open_export_options = true;
        },
        handleCheckAllChange() {
            const result = handleCheckAllChangeUtil(this.checkAll, {
                exportOptions: this.repost_export_options
            });
            this.selected_options = result.selectedOptions;
            this.isIndeterminate = result.isIndeterminate;
        },
        handleColumnChanges() {
            const result = handleColumnChangesUtil(this.selected_options, {
                exportOptions: this.repost_export_options
            });
            this.checkAll = result.checkAll;
            this.isIndeterminate = result.isIndeterminate;
        },
        closeExportModal() {
            this.open_export_options = false;
        },
        confirmExport() {
            const [from, to] = this.localDateRange;
            // Reuses the existing fs_export_agent_report ajax action — the
            // same one AgentReports.vue's export dialog calls.
            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fs_export_agent_report',
                _wpnonce: this.appVars.nonce,
                columns: this.selected_options,
                from_date: from,
                to_date: to,
                agents: this.selectedAgentIds
            });
            this.closeExportModal();
        },
        isTeamSelected(teamId) {
            // "All Teams" implicitly selects every specific team too, so every
            // checkbox in the list should render as checked while it's active.
            if (this.selectedTeamIds.includes("all")) {
                return true;
            }
            return this.selectedTeamIds.includes(teamId);
        },
        toggleTeam(teamId) {
            const specificTeamIds = this.teamOptions.filter(team => team.id !== "all").map(team => team.id);

            if (teamId === "all") {
                // Clicking a checked "All Teams" clears the selection instead
                // of re-selecting it, so the checkbox can actually be unticked.
                this.selectedTeamIds = this.selectedTeamIds.includes("all") ? [] : ["all"];
            } else if (this.selectedTeamIds.includes("all")) {
                const remaining = specificTeamIds.filter(id => id !== teamId);
                this.selectedTeamIds = remaining.length ? remaining : ["all"];
            } else if (this.selectedTeamIds.includes(teamId)) {
                const remaining = this.selectedTeamIds.filter(id => id !== teamId);
                this.selectedTeamIds = remaining.length ? remaining : ["all"];
            } else {
                const updated = [...this.selectedTeamIds, teamId];
                this.selectedTeamIds = specificTeamIds.every(id => updated.includes(id)) ? ["all"] : updated;
            }

            const agentIdsSet = new Set();
            this.selectedTeamIds.forEach(id => {
                const team = this.teamOptions.find(item => item.id === id);
                if (team) {
                    team.agent_ids.forEach(agentId => agentIdsSet.add(agentId));
                }
            });
            this.selectedAgentIds = Array.from(agentIdsSet);
            this.debouncedFetchPerformanceOverview();
        },
        disabledDate: isFutureDate,
        handleDateChange() {
            this.$emit('date-change', this.localDateRange);
            this.fetchPerformanceOverview();
        },
        setupHourlyActivityChart() {
            const palette = this.chartPalette;
            const othersColor = this.othersColor;
            this.hourlyActivityChartData = {
                labels: this.hourlyLabels,
                datasets: this.topActivitySeries.map((series, index) => ({
                    label: series.agent_name,
                    backgroundColor: series.isOthers ? othersColor : palette[index % palette.length],
                    data: series.data,
                }))
            };
        },
        fetchFilters() {
            this.filtersLoading = true;
            this.filtersError = false;
            return this.$get("advanced-reports/performance-overview/filters")
                .then((response) => {
                    this.agents = response.agents;
                    this.teams = response.teams;
                    this.selectedTeamIds = ["all"];
                    this.selectedAgentIds = this.defaultAgentSelection();
                })
                .catch((error) => {
                    this.agents = [];
                    this.teams = [];
                    this.selectedAgentIds = [];
                    this.filtersError = true;
                    this.$handleError(error);
                })
                .always(() => {
                    this.filtersLoading = false;
                });
        },
        handleRetry() {
            if (this.isSnapshotMode) {
                this.fetchSnapshotData(this.selectedSnapshot.id);
                return;
            }
            if (this.filtersError) {
                this.fetchFilters().then(() => {
                    if (!this.filtersError) {
                        this.fetchPerformanceOverview();
                    }
                });
                return;
            }
            this.fetchPerformanceOverview();
        },
        applyPerformanceOverviewResponse(response) {
            if (response.snapshot_range) {
                this.localDateRange = [response.snapshot_range.from, response.snapshot_range.to];
            }
            this.agentStats = response.agents.map((agent) => ({
                id: agent.id,
                name: agent.name,
                responses: agent.responses,
                firstResponse: agent.first_response ? dayjs(agent.first_response).format("h:mm A") : "--",
                lastResponse: agent.last_response ? dayjs(agent.last_response).format("h:mm A") : "--",
                avgPerDay: agent.avg_per_day
            }));
            this.activityGranularity = response.activity.granularity;
            this.hourlyLabels = response.activity.labels;
            this.activitySeries = response.activity.series;
            this.ticketTotals = response.totals || { closed: null, open: null };
            this.growthData = response.growth || { tickets: {}, resolved: {}, responses: {} };
            this.setupHourlyActivityChart();
        },
        fetchPerformanceOverview() {
            if (!this.localDateRange || this.localDateRange.length !== 2 || !this.selectedAgentIds.length) {
                // Invalidate any in-flight request so its callbacks can't
                // repopulate the page after the range/selection has been cleared.
                this.fetchRequestId++;
                this.loading = false;
                this.fetchError = false;
                this.agentStats = [];
                this.ticketTotals = { closed: null, open: null };
                this.activitySeries = [];
                this.hourlyLabels = [];
                this.growthData = { tickets: {}, resolved: {}, responses: {} };
                this.setupHourlyActivityChart();
                return;
            }
            this.loading = true;
            this.fetchError = false;
            const requestId = ++this.fetchRequestId;
            const [from, to] = this.localDateRange;
            const params = {
                from,
                to,
                agent_ids: this.selectedAgentIds.join(",")
            };
            this.$get("advanced-reports/performance-overview", params)
                .then((response) => {
                    if (requestId !== this.fetchRequestId) {
                        return; // a newer request has already superseded this one
                    }
                    this.applyPerformanceOverviewResponse(response);
                })
                .catch((error) => {
                    if (requestId !== this.fetchRequestId) {
                        return;
                    }
                    this.agentStats = [];
                    this.ticketTotals = { closed: null, open: null };
                    this.activitySeries = [];
                    this.hourlyLabels = [];
                    this.growthData = { tickets: {}, resolved: {}, responses: {} };
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
        this.fetchFilters().then(() => {
            if (!this.filtersError) {
                this.fetchPerformanceOverview();
            }
        });
    },
    beforeUnmount() {
        this.debouncedFetchPerformanceOverview.cancel();
    }
};
</script>
