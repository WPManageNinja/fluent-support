<template>
    <div class="fs_time_sheet" v-if="isLoaded">
        <div class="fs_table_wrapper">
            
            <el-table :data="customers" 
                row-class-name="fs_table_row"
                header-row-class-name="fs_table_header_row"
                cell-class-name="fs_table_cell"
                header-cell-class-name="fs_table_header_cell"
                table-layout="fixed">
                <el-table-column fixed="left" prop="customer" :label="$t('Customer')" min-width="150">
                    <template #default="{ row }">
                        <div class="fs_time_sheet_person">
                            <el-avatar :src="row.photo" size="small"></el-avatar>
                            <router-link
                                :to="{ name: 'view_customer', params: { customer_id: row.id }, }"
                                target="_blank"
                                class="fs_time_sheet_customer_link">
                                <strong>{{ row.full_name }}</strong>
                            </router-link>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column
                    :min-width="100"
                    v-for="date in dateLabels"
                    :key="date"
                    :prop="date"
                    :label="date"
                    min-width="100"
                >
                    <template #header="{ column }">
                        <span>{{ smartDate(column.label) }}</span>
                    </template>
                    <template #default="{ row }">
                        <UserAgentDateSheetPop
                            :user_id="row.id"
                            :date="date"
                            :timeSheets="timeSheets"
                        />
                    </template>
                </el-table-column>
                <el-table-column fixed="right" :label="$t('Total')" width="160">
                    <template #header="{ column }">
                        <span>{{ $t('Total') }} ({{ formatMinutes(totalMinutes) }})</span>
                    </template>
                    <template #default="{ row }">
                        <strong>{{ getUserTotal(row.id) }}</strong>
                    </template>
                </el-table-column>
            </el-table>
        </div>
    </div>
    <div class="fs_time_sheet_skeleton" v-else>
        <el-skeleton :animated="true" :rows="10"/>
    </div>
</template>

<script type="text/babel">
import Modal from "@/admin/Pieces/Modal.vue";
import {timesheetUtils} from "@/admin/Modules/Reports/TimeSheet/Pieces/TimeSheetUtils";
import UserAgentDateSheetPop from "@/admin/Modules/Reports/TimeSheet/_UserAgentDateSheetPop.vue";

export default {
    name: 'ByCustomers',
    components: {UserAgentDateSheetPop, Modal},
    props: {
        date_range: {
            type: Array,
            required: true
        },
        customers: {
            type: Array,
            default: () => []
        },
        timeSheets: {
            type: Object,
            default: () => ({})
        },
        dateLabels: {
            type: Array,
            default: () => []
        },
        totalMinutes: {
            type: Number,
            default: 0
        },
        isLoaded: {
            type: Boolean,
            default: false
        },
        selectedCustomer: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
        };
    },
    methods: {
        getUserTotal(userId) {
            return timesheetUtils.calculateEntityTotalMinutes(this.dateLabels, this.timeSheets, userId);
        },

        formatMinutes(minutes) {
            return timesheetUtils.formatMinutes(minutes);
        },

        exportReport() {
            timesheetUtils.exportReport({
                selectedItems: this.selectedCustomer,
                dateRange: this.date_range,
                action: "fluent_support_export_customers_timesheet",
                itemKey: "selectedCustomers"
            });
        },
    },
};
</script>


