<template>
    <div style="margin-top: 20px;" class="fs_agent_reports">
        <div v-if="!loading" class="fs_box_wrapper">
            <div class="fs_box">
                <div class="fs_box_header">
                    <div class="fs_box_head">
                        {{ url === "my-reports/my-summary" ? translate('Individual Performance') : translate('Agents Report Summary')  }}
                    </div>
                    <div class="fs_box_actions">
                        <el-icon v-if="show_settings" @click="open_setting=true" class="fs_summary_settings_icon" :size="18" title="Filter Agent">
                            <Setting />
                        </el-icon>

                        <el-date-picker
                            @change="getReport()"
                            v-model="date_range"
                            type="daterange"
                            size="small"
                            :range-separator="translate('To')"
                            :disabledDate="onlyPastDates"
                            :start-placeholder="translate('Start')"
                            :end-placeholder="translate('End')"
                            :shortcuts="dateShortcuts"
                        />
                        <el-icon v-if="show_export_btn && has_pro" @click="open_export_options=true" class="fs_summary_export_icon" title="Export Report"><Download /></el-icon>
                    </div>
                </div>
                <div class="fs_box_body">
                    <el-table
                        :data="sortedReports"
                        stripe
                        :summary-method="getSummaries"
                        :show-summary="showOrHideSummaries"
                        @sort-change="handleSorting"
                        v-loading="loading"
                        style="width: 100%">
                        <el-table-column min-width="200px" :label="translate('Agent')">
                            <template #default="scope">
                                {{ scope.row.full_name }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="responses" :label="translate('Responses')">
                            <template #default="scope">
                                {{ scope.row.stats?.responses }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="interactions" :label="translate('Interactions')">
                            <template #default="scope">
                                {{ scope.row.stats?.interactions }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="opens" :label="translate('Open Tickets')">
                            <template #default="scope">
                                {{ scope.row.stats?.opens }}
                            </template>
                        </el-table-column>

                        <el-table-column sortable="custom" prop="closed" :label="translate('Closed')">
                            <template #default="scope">
                                {{ scope.row.stats?.closed }}
                            </template>
                        </el-table-column>

                        <el-table-column v-if="has_pro && appVars.agent_feedback_rating === 'yes'" sortable="custom"  prop="likes" :label="translate('Likes')">
                            <template #default="scope">
                                {{ scope.row.stats?.likes }}
                            </template>
                        </el-table-column>

                        <el-table-column v-if="has_pro && appVars.agent_feedback_rating === 'yes'" sortable="custom"  prop="dislikes" :label="translate('Dislikes')">
                            <template #default="scope">
                                {{ scope.row.stats?.dislikes }}
                            </template>
                        </el-table-column>

                        <el-table-column min-width="150px" :label="translate('Current Overall')">
                            <template #default="scope">
                                <template v-if="scope.row.active_stat">
                                    <ul style="margin: 0; padding: 0; list-style: none;">
                                        <li>{{ translate('Waiting Tickets') }}: {{ scope.row.active_stat.waiting_tickets }}
                                        </li>
                                        <li>{{ translate('Average Waiting') }}: {{ scope.row.active_stat.average_waiting }}
                                        </li>
                                        <li>{{ translate('Max Waiting') }}: {{ scope.row.active_stat.max_waiting }}</li>
                                    </ul>
                                </template>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>

                <Teleport to="body">
                    <modal :show="open_setting" :title="translate('exclude_or_include_in_summary')" @close="open_setting = false">
                        <template #body>
                            <div class="fs_summary_settings">
                                <el-row :gutter="20">
                                    <el-col :span="18">
                                        <span> {{translate("If you don't select any agents then all the agents report will be displayed here otherwise it will show only selected agents report.")}}</span>
                                    </el-col>
                                </el-row>

                                <el-transfer
                                    v-model="include_or_exclude_agents"
                                    :data="sortedAgents"
                                    :titles="['Available Agents', 'Selected Agents']"
                                    filterable
                                />
                            </div>
                        </template>
                        <template #footer>
                            <el-button @click="open_setting = false">{{ translate('Cancel') }}</el-button>
                            <el-button type="primary" @click="syncSummary">{{ translate('Save') }}</el-button>
                        </template>
                    </modal>
                </Teleport>

                <Teleport to="body">
                    <modal :show="open_export_options" :title="translate('exclude_or_include_summary_column')" @close="open_export_options = false">
                        <template #body>
                            <div class="fs_summary_settings">
                                <el-row :gutter="20">
                                    <el-col :span="18">
                                        <span> {{translate("If you don't select any column, by default system will take all.")}}</span>
                                    </el-col>
                                </el-row>
                                <el-checkbox v-model="checkAll" :indeterminate="isIndeterminate" @change="handleCheckAllChange">
                                    <template v-if="!checkAll">{{translate('Check all')}}</template>
                                    <template v-else>{{translate('Uncheck all')}}</template>
                                </el-checkbox>
                                <el-checkbox-group v-model="selected_options" class="fs_summary_export_items" @change="handleColumnChanges">
                                    <el-checkbox v-for="(item, index) in repost_export_options" :key="index" :model-value="index" :label="item" />
                                </el-checkbox-group>

                            </div>
                        </template>
                        <template #footer>
                            <el-button @click="open_export_options = false">{{ translate('Cancel') }}</el-button>
                            <el-button type="primary" @click="exportReport">{{ translate('Export Agents Summary') }}</el-button>
                        </template>
                    </modal>
                </Teleport>

            </div>
        </div>
        <div style="padding: 20px; background: white;" class="fs_box_body" v-else>
            <el-skeleton :rows="5" animated/>
        </div>
    </div>
</template>

<script type="text/babel">
import dayjs from "dayjs";
import each from "lodash/each";
import Modal from "../../Pieces/Modal";
import { computed, onMounted, reactive, toRefs } from "vue";
import { useFluentHelper } from "@/admin/Composable/FluentFrameworkHelper";

export default {
    name: "AgentReports",
    components: { Modal },
    props: ["url", "show_settings", "show_export_btn"],

    setup(props) {
        const {
            appVars,
            get,
            translate,
            handleError,
            saveData,
            getData,
            has_pro
        } = useFluentHelper();

        const state = reactive({
            reports: [],
            loading: false,
            sorts: {
                prop: "responses",
                order: "descending",
            },
            sort_column: "response",
            sort_type: "descending",
            date_range: [new Date(), new Date()],
            dateShortcuts: [
                {
                    text: translate("Today"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        return [start, end];
                    })(),
                },
                {
                    text: translate("Yesterday"),
                    value: (() => {
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24);
                        return [start, start];
                    })(),
                },
                {
                    text: translate("Last Week"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        return [start, end];
                    })(),
                },
                {
                    text: translate("Last Month"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        return [start, end];
                    })(),
                },
                {
                    text: translate("Last 3 Months"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        return [start, end];
                    })(),
                },
                {
                    text: translate("Last 6 Months"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 180);
                        return [start, end];
                    })(),
                },
                {
                    text: translate("Last 1 Year"),
                    value: (() => {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 360);
                        return [start, end];
                    })(),
                },
            ],
            valueFormat: "YYYY-MM-DD",
            open_setting: false,
            include_or_exclude_agents: [],
            include_or_exclude: "include", // Define if we are including or excluding agents, default is include
            open_export_options: false,
            repost_export_options: appVars.repost_export_options,
            selected_options: [],
            checkAll: false,
            isIndeterminate: false,
        });

        const sortedAgents = computed(() => {
            let agents = [];
            each(appVars.support_agents, (agent) => {
                agents.push({
                    key: parseInt(agent.id),
                    label: agent.full_name,
                });
            });
            return agents;
        });

        const handleCheckAllChange = computed(() => {
            state.selected_options = state.checkAll
                ? Object.keys(state.repost_export_options)
                : [];
            state.isIndeterminate = true;
        });

        const handleColumnChanges = computed(() => {
            if (
                state.selected_options.length ===
                Object.keys(state.repost_export_options).length
            ) {
                state.checkAll = true;
                state.isIndeterminate = false;
            } else if (state.selected_options.length === 0) {
                state.checkAll = false;
                state.isIndeterminate = false;
            } else {
                state.isIndeterminate = true;
            }
        });

        const sortedReports = computed(() => {
            let reports = state.reports;

            const settings = getData("agents_summary_setting");

            if (props.url != "my-reports/my-summary" && settings) {
                let reportsToInclude = [];
                each(reports, (report) => {
                    if (settings.agents.includes(report.id)) {
                        reportsToInclude.push(report);
                    }
                });
                reports = reportsToInclude;
            }

            if (state.sort_type == "ascending") {
                return reports.sort((a, b) =>
                    parseInt(a.stats[state.sort_column]) >
                        parseInt(b.stats[state.sort_column])
                        ? 1
                        : -1
                );
            }
            return reports.sort((a, b) =>
                parseInt(a.stats[state.sort_column]) <
                    parseInt(b.stats[state.sort_column])
                    ? 1
                    : -1
            );
        });

        const totals = computed(() => {

            let summary = {
                responses: 0,
                interactions: 0,
                opens: 0,
                closed: 0,
            };

            if (has_pro && appVars.agent_feedback_rating === "yes") {
                summary = {
                    ...summary,
                    likes: 0,
                    dislikes: 0,
                };
            }
            each(state.reports, (report) => {
                summary.responses += parseInt(report.stats.responses);
                summary.interactions += parseInt(report.stats.interactions);
                summary.opens += parseInt(report.stats.opens);
                summary.closed += parseInt(report.stats.closed);
                if (has_pro && appVars.agent_feedback_rating === "yes") {
                    summary.likes += parseInt(report.stats.likes);
                    summary.dislikes += parseInt(report.stats.dislikes);
                }
            });
            return summary;
        });

        const showOrHideSummaries = computed(() => {
            if (props.url == "my-reports/my-summary") {
                return false;
            }
            return true;
        });

        const getReport = async () => {
            state.loading = true;
            await get(props.url, {
                from: (state.date_range) ? dayjs(state.date_range[0]).format('YYYY-MM-DD') : '',
                to: (state.date_range) ? dayjs(state.date_range[1]).format('YYYY-MM-DD') : '',
            })
                .then(response => {
                    state.reports = response.summary
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.loading = false;
                });
        };

        const fetchSummarySettings = () => {
            const settings =  getData('agents_summary_setting');
            return state.include_or_exclude_agents = settings.agents;
        };

        const syncSummary = () => {
            state.loading = true;
            saveData('agents_summary_setting', {
                agents: state.include_or_exclude_agents
            });
            state.open_setting = false;
            getReport();
        };

        const handleSorting = (props) => {
            state.sort_column = props.prop;
            state.sort_type = props.order;
        };

        const onlyPastDates = (val) => {
            return new Date() <= val;
        };

        const getSummaries = (param) => {
            const { columns } = param;
            const sums = [];

            columns.forEach((column, index) => {
                if (index === 0) {
                    sums[index] = translate("Total Summaries");
                    return;
                }
                sums[index] = totals.value[column.property];
            });

            return sums;
        };

        const exportReport = () => {
            let from = (state.date_range) ? dayjs(state.date_range[0]).format('YYYY-MM-DD') : '';
            let to = (state.date_range) ? dayjs(state.date_range[1]).format('YYYY-MM-DD') : '';
            let agents_summary_setting = getData('agents_summary_setting');
            let agents = [];
            if (!agents_summary_setting) {
                each(appVars.support_agents, (agent) => {
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
                handleError(translate('No agent found, Please select or make sure you have agents to export'));
                return false;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fs_export_agent_report',
                _wpnonce: fluentSupportAdmin.nonce,
                columns: state.selected_options,
                from_date: from,
                to_date: to,
                agents: agents,
                format: 'csv'
            });
        };

        onMounted(() => {
            getReport();
            fetchSummarySettings();
        });

        return {
            ...toRefs(state),
            translate,
            sortedAgents,
            handleCheckAllChange,
            handleColumnChanges,
            sortedReports,
            totals,
            showOrHideSummaries,
            getReport,
            fetchSummarySettings,
            syncSummary,
            handleSorting,
            onlyPastDates,
            getSummaries,
            exportReport,
            has_pro,
            appVars
        }
    },
};
</script>

<style lang="scss" scoped>
.fs_summary_settings div {
    margin: 10px 0;
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
    color: #bd0909;
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
</style>
