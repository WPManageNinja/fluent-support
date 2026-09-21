<template>
    <div style="margin-top: 20px;" class="fs_agent_reports">
        <div v-if="!loading">
            <div class="fs_report_table_container">
                <div class="fs_table_header">
                    <div class="fs_box_actions">
                        <span class="fs_table_title">{{ $t('Agent Group Summary') }}</span>
                    </div>
                </div>
                <div class="fs_table_wrapper">
                    <el-table
                        :data="sortedReports"
                        :summary-method="getSummaries"
                        show-summary
                        @sort-change="handleSorting"
                        v-loading="loading"
                        row-class-name="fs_table_row"
                        header-row-class-name="fs_table_header_row"
                        cell-class-name="fs_table_cell"
                        header-cell-class-name="fs_table_header_cell"
                        table-layout="fixed">

                        <el-table-column min-width="200px" :label="$t('Group')">
                            <template #default="scope">
                                {{ scope.row.title }}
                            </template>
                        </el-table-column>
                        <el-table-column sortable="custom" prop="agents_count" :label="$t('Agents')">
                            <template #default="scope">
                                {{ scope.row.agents_count }}
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

export default {
    name: "AgentGroupReportSummary",
    props: {
        url: String,
        date_range: {
            type: Array,
            default: () => ["", ""]
        }
    },
    data() {
        return {
            reports: [],
            loading: false,
            sort_column: "responses",
            sort_type: "descending",
        };
    },
    computed: {
        sortedReports() {
            let reports = [...this.reports];

            if (this.sort_column === 'agents_count') {
                if (this.sort_type === "ascending") {
                    return reports.sort((a, b) => parseInt(a.agents_count) - parseInt(b.agents_count));
                }
                return reports.sort((a, b) => parseInt(b.agents_count) - parseInt(a.agents_count));
            }

            if (this.sort_type === "ascending") {
                return reports.sort((a, b) =>
                    parseInt(a.stats[this.sort_column]) > parseInt(b.stats[this.sort_column]) ? 1 : -1
                );
            }
            return reports.sort((a, b) =>
                parseInt(a.stats[this.sort_column]) < parseInt(b.stats[this.sort_column]) ? 1 : -1
            );
        },
        totals() {
            const summary = {
                agents_count: 0,
                responses: 0,
                interactions: 0,
                opens: 0,
                closed: 0,
            };
            each(this.reports, (report) => {
                summary.agents_count += parseInt(report.agents_count);
                summary.responses += parseInt(report.stats.responses);
                summary.interactions += parseInt(report.stats.interactions);
                summary.opens += parseInt(report.stats.opens);
                summary.closed += parseInt(report.stats.closed);
            });
            return summary;
        },
    },
    watch: {
        date_range: {
            handler(newVal) {
                if (newVal && newVal[0] && newVal[1]) {
                    this.getReport();
                }
            },
            deep: true
        },
        sortedReports: {
            handler() {
                this.$nextTick(() => {
                    this.addClassToSummaryRow();
                });
            }
        }
    },
    methods: {
        async getReport() {
            if (!this.date_range || !this.date_range[0] || !this.date_range[1]) {
                return;
            }

            this.loading = true;
            await this.$get(this.url, {
                from: this.date_range[0] ? dayjs(this.date_range[0]).format('YYYY-MM-DD') : '',
                to: this.date_range[1] ? dayjs(this.date_range[1]).format('YYYY-MM-DD') : '',
            })
                .then(response => {
                    this.reports = response.summary;
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
.fs_skeleton_loader {
    padding: 20px;
    background: var(--fs-bg-primary);
}
</style>
