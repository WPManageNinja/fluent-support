<template>
    <el-popover ref="estimated_time_pop" popper-class="fs_timer_log_popover" placement="left" :width="400" trigger="click">
        <template #reference>
            <div style="cursor: pointer;">{{ timeSheetTotal }}</div>
        </template>
        <el-table :stripe="true" :border="true" :data="timeItems" style="width: 100%">
            <el-table-column type="expand" width="30">
                <template #default="{row}">
                    <div class="fs_detail_log">
                        <p><b>{{$t('Logged at:')}}</b> {{ smartDate(row.created_at, true) }}</p>
                        <p><b>{{$t('Work Description:')}}</b> {{ row.message }}</p>
                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="user" :label="$t('Agent')">
                <template #default="{ row }">
                    <div class="fs_time_sheet_person">
                        <el-avatar :src="row.agent?.photo" size="small"></el-avatar>
                        <a
                            :href="`/wp-admin/user-edit.php?user_id=${row.agent?.user_id}`"
                            target="_blank"
                            class="fs_time_sheet_agent_link">
                            {{ row.agent?.full_name }}
                        </a>

                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="billable_minutes" :label="$t('Time')" width="80">
                <template #default="{ row }">
                    <span>{{ formatMinutes(row.billable_minutes) }}</span>
                </template>
            </el-table-column>
        </el-table>
    </el-popover>
</template>

<script>
import each from 'lodash/each';
import {timesheetUtils} from "@/admin/Modules/Reports/TimeSheet/Pieces/TimeSheetUtils";

export default {
    name: 'TicketDateSheetPop',
    props: ['ticket_id','date','timeSheets'],
    data() {
        return {
            timeItems: []
        };
    },
    computed: {
        timeSheetTotal() {
            return timesheetUtils.calculateTimeSheetTotal(this.timeSheets, this.date, this.ticket_id);
        }
    },
    mounted() {
        this.initItems();
    },
    methods: {
        initItems() {
            const sheets = this.timeSheets?.[this.date]?.[this.ticket_id] ?? [];
            each(sheets, sheet => {
                this.timeItems.push(sheet);
            });
        },
        formatMinutes(minutes) {
            return timesheetUtils.formatMinutes(minutes);
        }
    }
};
</script>
