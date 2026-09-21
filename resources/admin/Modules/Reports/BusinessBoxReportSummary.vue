<template>
    <div style="margin-top: 20px;" class="fs_agent_reports">
        <div v-if="!loading">
            <div class="fs_report_table_container">
                <div class="fs_table_header">
                    <div class="fs_box_actions">
                        <span class="fs_table_title">{{ $t('Business Box Summary') }}</span>
                    </div>
                </div>
                <div class="fs_table_wrapper">
                    <el-table
                        :data="sortedReports"
                        :summary-method="getSummaries"
                        :show-summary="showOrHideSummaries"
                        @sort-change="handleSorting"
                        v-loading="loading"
                        row-class-name="fs_table_row"
                        header-row-class-name="fs_table_header_row"
                        cell-class-name="fs_table_cell"
                        header-cell-class-name="fs_table_header_cell"
                        table-layout="fixed">

                        <el-table-column min-width="200px" :label="$t('MailBox')">
                            <template #default="scope">
                                {{ scope.row.name }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="responses" :label="$t('Responses')">
                            <template #default="scope">
                                {{ scope.row.stats.responses }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="interactions" :label="$t('Interactions')">
                            <template #default="scope">
                                {{ scope.row.stats.interactions }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="opens" :label="$t('Open Tickets')">
                            <template #default="scope">
                                {{ scope.row.stats.opens }}
                            </template>
                        </el-table-column>

                        <el-table-column sortable="custom" prop="closed" :label="$t('Closed')">
                            <template #default="scope">
                                {{ scope.row.stats.closed }}
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </div>
        </div>
        <div class="fs_box_body fs_skeleton_loader" v-else>
            <el-skeleton :rows="5" animated/>
        </div>
    </div>
</template>

<script type="text/babel">
import dayjs from "dayjs";
import each from "lodash/each";
import Modal from "../../Pieces/Modal";

export default {
    name: "BusinessBoxReportSummary",
    components: { Modal },
    props: {
        url: String,
        show_settings: Boolean,
        show_export_btn: Boolean,
        date_range: {
            type: Array,
            default: () => ["", ""]
        },
        mailbox_id: {
            type: [String, Number],
            default: ""
        }
    },

    data() {
        return {
            reports: [],
            loading: false,
            sorts: {
                prop: "responses",
                order: "descending",
            },
            sort_column: "response",
            sort_type: "descending",
            valueFormat: "YYYY-MM-DD",
            open_setting: false,
            include_or_exclude_agents: [],
            include_or_exclude: "include",
            open_export_options: false,
            repost_export_options: this.appVars.repost_export_options,
            selected_options: [],
            checkAll: false,
            isIndeterminate: false,
        };
    },

    computed: {
        sortedReports() {
            let reports = this.reports;

            const settings = this.$getData("agents_summary_setting");

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
            const summary = {
                responses: 0,
                opens: 0,
                closed: 0,
                interactions: 0
            };
            each(this.reports, (report) => {
                summary.responses += parseInt(report.stats.responses);
                summary.interactions += parseInt(report.stats.interactions);
                summary.opens += parseInt(report.stats.opens);
                summary.closed += parseInt(report.stats.closed);
            });
            return summary;
        },

        showOrHideSummaries() {
            if (this.url == "my-reports/my-summary") {
                return false;
            }
            return true;
        },
    },

    watch: {
        date_range: {
            handler(newDateRange) {
                if (newDateRange && newDateRange[0] && newDateRange[1]) {
                    this.getReport();
                }
            },
            deep: true
        },
        mailbox_id() {
            if (this.date_range && this.date_range[0] && this.date_range[1]) {
                this.getReport();
            }
        }
    },

    methods: {
        async getReport() {
            if (!this.date_range || !this.date_range[0] || !this.date_range[1]) {
                return;
            }
            this.loading = true;
            const params = {
                from: this.date_range[0] ? dayjs(this.date_range[0]).format('YYYY-MM-DD') : '',
                to: this.date_range[1] ? dayjs(this.date_range[1]).format('YYYY-MM-DD') : '',
            };
            
            // Add mailbox_id if provided
            if (this.mailbox_id) {
                params.mailbox_id = this.mailbox_id;
            }
            
            await this.$get(this.url, params)
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
                sums[index] = this.totals[column.property];
            });

            this.$nextTick(() => {
                this.addClassToSummaryRow();
            });

            return sums;
        },
        addClassToSummaryRow() {
            const row = this.$el
            ?.querySelector('.fs_table_wrapper .el-table__footer-wrapper tfoot tr, .fs_table_wrapper .el-table__footer-wrapper tr');

            if (row) {
            row.classList.add('fs_table_row');
            row.querySelectorAll('td').forEach(td => td.classList.add('fs_table_cell'));
            }
        },
    },

    mounted() {
        if (this.date_range && this.date_range[0] && this.date_range[1]) {
            this.getReport();
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
