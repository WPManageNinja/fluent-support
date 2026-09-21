<template>
    <div class="fs_agent_reports" :class="{ 'fs_personal_reports': is_personal }">
        <!-- Personal View (is_personal = true) -->
        <MyPerformanceReport 
            v-if="is_personal" 
            :reports="reports" 
            :loading="loading" 
        />

        <!-- Non-Personal View (is_personal = false) -->
        <div v-if="!is_personal && !loading" class="fs_agent_report_summary_wrapper">
            <div class="fs_report_table_container">
                <div class="fs_table_header">
                    <div class="fs_box_actions">
                        <span class="fs_table_title">{{ $t('Agents Report Summary') }}</span>
                    </div>
                </div>
                <div class="fs_table_wrapper">
                    <el-table
                        class="fs_agent_reports_table"
                        :data="sortedReports"
                        max-height="600"
                        :summary-method="getSummaries"
                        :show-summary="showOrHideSummaries"
                        @sort-change="handleSorting"
                        v-loading="loading"
                        row-class-name="fs_table_row"
                        header-row-class-name="fs_table_header_row"
                        cell-class-name="fs_table_cell"
                        header-cell-class-name="fs_table_header_cell"
                        table-layout="fixed">
                        <el-table-column fixed width="260" :label="$t('Agent')">
                            <template #default="scope">
                                <div class="fs_customer_name_cell">
                                    <div class="fs_customer_avatar">
                                        <img
                                            v-if="scope.row.photo && !imageErrors[scope.row.id]"
                                            :src="scope.row.photo"
                                            :alt="scope.row.full_name"
                                            @error="imageErrors[scope.row.id] = true"
                                            class="fs_avatar_img"
                                        />
                                        <span v-else class="fs_avatar_initials">{{ getInitials(scope.row) }}</span>
                                    </div>
                                    <div class="fs_customer_info">
                                        {{ scope.row.full_name ? scope.row.full_name: 'N/A' }}
                                        <div class="fs_customer_email">
                                            {{ scope.row.email }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column width="145" sortable="custom" prop="responses" :label="$t('Responses')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.responses }}
                            </template>
                        </el-table-column>
                        <el-table-column width="150" sortable="custom" prop="interactions" :label="$t('Interactions')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.interactions }}
                            </template>
                        </el-table-column>
                        <el-table-column width="160" sortable="custom" prop="opens" :label="$t('Open Tickets')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.opens }}
                            </template>
                        </el-table-column>

                        <el-table-column width="120" sortable="custom" prop="closed" :label="$t('Closed')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.closed }}
                            </template>
                        </el-table-column>

                        <el-table-column v-if="has_pro && appVars.agent_feedback_rating === 'yes'" width="105" sortable="custom" prop="likes" :label="$t('Likes')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.likes }}
                            </template>
                        </el-table-column>

                        <el-table-column v-if="has_pro && appVars.agent_feedback_rating === 'yes'" width="125" sortable="custom" prop="dislikes" :label="$t('Dislikes')" align="center">
                            <template #default="scope">
                                {{ scope.row.stats?.dislikes }}
                            </template>
                        </el-table-column>

                        <el-table-column width="220" :label="$t('Current Overall')">
                            <template #default="scope">
                                <template v-if="scope.row.active_stat">
                                    <ul style="margin: 0; padding: 0; list-style: none;">
                                        <li>{{ $t('Waiting Tickets') }}: {{ scope.row.active_stat.waiting_tickets }}
                                        </li>
                                        <li>{{ $t('Average Waiting') }}: {{ scope.row.active_stat.average_waiting }}
                                        </li>
                                        <li>{{ $t('Max Waiting') }}: {{ scope.row.active_stat.max_waiting }}</li>
                                    </ul>
                                </template>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>

                <el-dialog
                    :append-to-body="true"
                    :model-value="open_setting"
                    @update:model-value="$emit('update:open_setting', $event)"
                    class="fs_dialog fs_agent_summary_dialog"
                    width="670px"
                    @close="closeSetting"
                >
                    <template #header>
                        <div class="fs_agent_summary_dialog_header">
                            <p class="fs_agent_summary_title">{{ $t('exclude_or_include_in_summary') }}</p>
                            <p class="fs_dialog_description_wrapper">
                                {{$t("If you don't select any agents then all the agents report will be displayed here otherwise it will show only selected agents report.")}}
                            </p>
                        </div>
                    </template>
                    <div class="fs_summary_settings">

                        <div class="fs_transfer_wrapper fframe_body">
                            <el-transfer
                                v-model="include_or_exclude_agents"
                                :data="sortedAgents"
                                :titles="['Available Agents', 'Selected Agents']"
                                filterable
                                class="fs_agent_transfer"
                            />
                        </div>
                    </div>
                    <template #footer>
                        <span class="fs_dialog_footer">
                            <el-button class="fs_outline_btn" @click="closeSetting">{{ $t('Cancel') }}</el-button>
                            <el-button class="fs_filled_btn" type="primary" @click="syncSummary">{{ $t('Save') }}</el-button>
                        </span>
                    </template>
                </el-dialog>

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
                                {{$t("If you don't select any column, by default system will take all.")}}
                            </p>
                        </div>
                    </template>
                    <div class="fs_summary_settings fframe_body">
                        <el-checkbox v-model="checkAll" :indeterminate="isIndeterminate" @change="handleCheckAllChange">
                            <template v-if="!checkAll">{{$t('Check all')}}</template>
                            <template v-else>{{$t('Uncheck all')}}</template>
                        </el-checkbox>
                        <el-checkbox-group v-model="selected_options" class="fs_summary_export_items" @change="handleColumnChanges">
                            <el-checkbox v-for="(item, index) in repost_export_options" :key="index" :model-value="index" :label="item" />
                        </el-checkbox-group>
                    </div>
                    <template #footer>
                        <span class="fs_dialog_footer">
                            <el-button class="fs_outline_btn" @click="closeExportModal">{{ $t('Cancel') }}</el-button>
                            <el-button class="fs_filled_btn" type="primary" @click="exportReport">{{ $t('Export Agents Summary') }}</el-button>
                        </span>
                    </template>
                </el-dialog>

            </div>
        </div>
        <div class="fs_box_body fs_skeleton_loader" v-if="loading">
            <el-skeleton :rows="5" animated/>
        </div>
    </div>
</template>

<script type="text/babel">
import each from "lodash/each";
import MyPerformanceReport from "./MyPerformanceReport";
import { formatDateRangeForAPI, handleCheckAllChange as handleCheckAllChangeUtil, handleColumnChanges as handleColumnChangesUtil } from "./Utils/reportHelpers";

export default {
    name: "AgentReports",
    components: {
        MyPerformanceReport,
    },
    props: {
        url: String,
        show_settings: Boolean,
        show_export_btn: Boolean,
        is_personal: Boolean,
        date_range: {
            type: Array,
            default: () => []
        },
        open_setting: Boolean,
        open_export: Boolean,
        agent_ids: {
            type: Array,
            default: () => []
        }
    },

    data() {
        return {
            imageErrors: {},
            reports: [],
            loading: false,
            sorts: {
                prop: "responses",
                order: "descending",
            },
            sort_column: "response",
            sort_type: "descending",
            include_or_exclude_agents: [],
            include_or_exclude: "include", // Define if we are including or excluding agents, default is include
            open_export_options: false,
            repost_export_options: this.appVars.repost_export_options,
            selected_options: [],
            checkAll: false,
            isIndeterminate: false,
        };
    },

    computed: {
        sortedAgents() {
            let agents = [];
            each(this.appVars.support_agents, (agent) => {
                agents.push({
                    key: parseInt(agent.id),
                    label: agent.full_name,
                });
            });
            return agents;
        },

        sortedReports() {
            let reports = this.reports;

            const settings = this.$getData("agents_summary_setting");

            if (this.url != "my-reports/my-summary" && settings && !this.agent_ids.length) {
                let reportsToInclude = [];
                each(reports, (report) => {
                    if (settings.agents.includes(report.id)) {
                        reportsToInclude.push(report);
                    }
                });
                reports = reportsToInclude;
            }

            if (this.agent_ids.length) {
                const selected = this.agent_ids.map(Number);
                reports = reports.filter(report => selected.includes(Number(report.id)));
            }

            if (this.sort_type == "ascending") {
                return reports.sort((a, b) =>
                    parseInt(a.stats[this.sort_column]) >
                        parseInt(b.stats[this.sort_column])
                        ? 1
                        : -1
                );
            }
            return reports.sort((a, b) =>
                parseInt(a.stats[this.sort_column]) <
                    parseInt(b.stats[this.sort_column])
                    ? 1
                    : -1
            );
        },

        totals() {
            let summary = {
                responses: 0,
                interactions: 0,
                opens: 0,
                closed: 0,
            };

            if (this.has_pro && this.appVars.agent_feedback_rating === "yes") {
                summary = {
                    ...summary,
                    likes: 0,
                    dislikes: 0,
                };
            }
            each(this.sortedReports, (report) => {
                summary.responses += parseInt(report.stats.responses);
                summary.interactions += parseInt(report.stats.interactions);
                summary.opens += parseInt(report.stats.opens);
                summary.closed += parseInt(report.stats.closed);
                if (this.has_pro && this.appVars.agent_feedback_rating === "yes") {
                    summary.likes += parseInt(report.stats.likes);
                    summary.dislikes += parseInt(report.stats.dislikes);
                }
            });
            return summary;
        },

        showOrHideSummaries() {
            if (this.url == "my-reports/my-summary" || this.is_personal) {
                return false;
            }
            return true;
        }
    },

    watch: {
        date_range: {
            handler(newDateRange) {
                if (newDateRange && newDateRange.length > 0) {
                    this.getReport();
                }
            },
            deep: true
        },
        open_export: {
            handler(newValue) {
                if (newValue) {
                    this.open_export_options = true;
                }
            },
            immediate: false
        }
    },

    methods: {
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

        async getReport() {
            this.loading = true;
            const dateParams = formatDateRangeForAPI(this.date_range);
            await this.$get(this.url, dateParams)
                .then(response => {
                    this.reports = response.summary
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        getInitials(agent) {
            if (!agent) return '?';
            const first = (agent.first_name || '')[0] || '';
            const last = (agent.last_name || '')[0] || '';
            return (first + last).toUpperCase() || '?';
        },

        fetchSummarySettings() {
            const settings = this.$getData('agents_summary_setting');
            if (settings) {
                this.include_or_exclude_agents = settings.agents;
            }
        },

        closeSetting() {
            this.$emit('update:open_setting', false);
        },

        closeExportModal() {
            this.open_export_options = false;
            this.$emit('update:open_export', false);
        },

        syncSummary() {
            this.loading = true;
            this.$saveData('agents_summary_setting', {
                agents: this.include_or_exclude_agents
            });
            this.$emit('update:open_setting', false);
            this.getReport();
        },

        handleSorting(sortProps) {
            this.sort_column = sortProps.prop;
            this.sort_type = sortProps.order;
        },

        getSummaries(param) {
            const { columns } = param;
            const sums = [];

            columns.forEach((column, index) => {
                if (index === 0) {
                    sums[index] = this.$t("Total Summaries");
                    return;
                }
                const value = this.totals[column.property];
                sums[index] = value !== undefined ? value : '';
            });

            return sums;
        },

        exportReport() {
            const dateParams = formatDateRangeForAPI(this.date_range);
            let from = dateParams.from;
            let to = dateParams.to;
            let agents_summary_setting = this.$getData('agents_summary_setting');
            let agents = [];
            if (!agents_summary_setting) {
                each(this.appVars.support_agents, (agent) => {
                    agents.push({
                        key: parseInt(agent.id),
                        label: agent.full_name
                    })
                })
                agents = agents.toString();
            } else {
                agents = agents_summary_setting.agents.toString();
            }

            if (agents === '') {
                this.$handleError(this.$t('No agent found, Please select or make sure you have agents to export'));
                return false;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fs_export_agent_report',
                _wpnonce: fluentSupportAdmin.nonce,
                columns: this.selected_options,
                from_date: from,
                to_date: to,
                agents: agents,
                format: 'csv'
            });
            
            // Close modal after export
            this.closeExportModal();
        },
    },

    mounted() {
        this.getReport();
        this.fetchSummarySettings();
    },
};
</script>

<style lang="scss" scoped>

/* Prevent double horizontal scroll: contain overflow to table wrapper only */
.fs_agent_report_summary_wrapper {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.fs_agent_report_summary_wrapper .fs_report_table_container {
    min-width: 0;
    overflow: hidden;
}

.fs_summary_settings_icon {
    margin-right: 10px;
    margin-top: 2px;
    cursor: pointer;
}

.fs_summary_export_icon {
    margin-left: 17px;
    margin-top: 2px;
    cursor: pointer;
    color: var(--fs-text-red);
    font-size: 18px;
}

.fs_summary_export_items {
    display: flex;
    flex-wrap: wrap;
}

.fs_summary_export_items label {
    margin-top: 10px;
    width: 45%;
}

.fs_skeleton_loader {
    padding: 20px;
    background: var(--fs-bg-primary);
}
</style>

