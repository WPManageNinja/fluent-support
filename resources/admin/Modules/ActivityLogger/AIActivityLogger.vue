<template>
    <div v-if="settings.disable_logs == 'yes'" class="fs_activity_disabled_banner">
        <div class="fs_banner_icon">
            <IconPack icon-key="warning" :width="20" :height="20" class="fs_warn_icon" />
        </div>
        <span class="fs_banner_text">{{ $t("Activity Logs are currently disabled") }}</span>
    </div>
    <div class="fs_inside_menu_component_header">
        <div class="fs_component_head">
            <h3 class="fs_page_title">{{ $t("AI Activities") }}</h3>
        </div>
        <div class="fs_box_actions">
            <button
                class="fs_refresh_button"
                v-loading="loading"
                @click="fetchAIActivities()"
            >
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.59725 1.82476C3.95817 0.645503 5.69924 -0.0025049 7.5 7.27699e-06C11.6423 7.27699e-06 15 3.35776 15 7.50001C15 9.10201 14.4975 10.587 13.6425 11.805L11.25 7.50001H13.5C13.5001 6.32373 13.1544 5.17336 12.506 4.19195C11.8576 3.21054 10.9349 2.44138 9.85288 1.9801C8.77082 1.51882 7.57704 1.38578 6.41997 1.59752C5.2629 1.80926 4.19359 2.35643 3.345 3.17101L2.59725 1.82476ZM12.4028 13.1753C11.0418 14.3545 9.30076 15.0025 7.5 15C3.35775 15 0 11.6423 0 7.50001C0 5.89801 0.5025 4.41301 1.3575 3.19501L3.75 7.50001H1.5C1.4999 8.67629 1.84556 9.82665 2.494 10.8081C3.14244 11.7895 4.06505 12.5586 5.14712 13.0199C6.22918 13.4812 7.42296 13.6142 8.58003 13.4025C9.7371 13.1908 10.8064 12.6436 11.655 11.829L12.4028 13.1753Z" fill="#525866"/>
                </svg>

                <span>{{ $t('Refresh') }}</span>
            </button>
        </div>
    </div>
    <div class="fs_activity_logger">
        <div class="fs_activity_filters_section">
            <div class="fs_activity_filters">
                <div class="fs_filter_group">
                    <el-select
                        clearable
                        filterable
                        @change="fetchAIActivities()"
                        v-model="filters.agent_id"
                        :placeholder="$t('All Support Staff')"
                        class="fs_select_field fs_staff_filter"
                    >
                        <el-option
                            v-for="agent in appVars.support_agents"
                            :key="agent.id"
                            :value="agent.id"
                            :label="agent.full_name"
                        ></el-option>
                    </el-select>
                </div>
                <div class="fs_filter_group">
                    <div class="fs_date_button_group">
                        <div class="fs_date_button_group_item fs_date_picker_wrapper">
                            <div class="fs_date_display">
                                <IconPack icon-key="calendar" :width="20" :height="20" class="fs_calendar_icon" />
                                <span v-if="formattedDateRange" class="fs_date_text">{{ formattedDateRange }}</span>
                                <span v-else class="fs_date_placeholder">{{ $t('Select date range') }}</span>
                            </div>
                            <el-date-picker
                                v-model="date_range"
                                type="daterange"
                                :editable="false"
                                :range-separator="$t('To')"
                                :start-placeholder="$t('Start')"
                                :end-placeholder="$t('End')"
                                :unlink-panels="true"
                                :shortcuts="shortcuts"
                                @change="fetchAIActivities"
                                value-format="YYYY-MM-DD"
                                class="fs_date_range_picker"
                            >
                                <template #prefix-icon>
                                    <el-icon><Calendar /></el-icon>
                                </template>
                            </el-date-picker>
                        </div>
                    </div>
                    <button
                        v-if="me.permissions.indexOf('fst_manage_settings') != -1"
                        @click="showSettingsModal = true"
                        class="fs_settings_button"
                    >
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.26954 5.72492C3.38204 5.48742 3.51329 5.26242 3.66329 5.04367L3.63829 3.41867C3.63829 3.26867 3.70079 3.12492 3.81954 3.02492C4.40079 2.53742 5.06329 2.14367 5.79454 1.88117C5.93829 1.83117 6.09454 1.84367 6.21954 1.92492L7.61329 2.76242C7.87579 2.74367 8.13829 2.74367 8.40079 2.76242L9.79454 1.92492C9.92579 1.84992 10.082 1.83117 10.2258 1.88117C10.9383 2.13742 11.607 2.51867 12.2008 3.01867C12.3133 3.11242 12.382 3.26242 12.3758 3.41242L12.3508 5.03742C12.5008 5.25617 12.632 5.48117 12.7445 5.71867L14.1633 6.50617C14.2945 6.58117 14.3883 6.70617 14.4133 6.85617C14.5445 7.59992 14.5508 8.37492 14.4133 9.13117C14.3883 9.28117 14.2945 9.40617 14.1633 9.48117L12.7445 10.2687C12.632 10.5062 12.5008 10.7312 12.3508 10.9499L12.3758 12.5749C12.3758 12.7249 12.3133 12.8687 12.1945 12.9687C11.6133 13.4562 10.9508 13.8499 10.2195 14.1124C10.0758 14.1624 9.91954 14.1499 9.79454 14.0687L8.40079 13.2312C8.13829 13.2499 7.87579 13.2499 7.61329 13.2312L6.21954 14.0687C6.08829 14.1437 5.93204 14.1624 5.78829 14.1124C5.07579 13.8562 4.40704 13.4749 3.81329 12.9749C3.70079 12.8812 3.63204 12.7312 3.63829 12.5812L3.66329 10.9562C3.51329 10.7374 3.38204 10.5124 3.26954 10.2749L1.85079 9.48742C1.71954 9.41242 1.62579 9.28742 1.60079 9.13742C1.46954 8.39367 1.46329 7.61867 1.60079 6.86242C1.62579 6.71242 1.71954 6.58742 1.85079 6.51242L3.26954 5.72492Z" stroke="#525866" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.00073 10.9999C9.65759 10.9999 11.0007 9.65679 11.0007 7.99994C11.0007 6.34308 9.65759 4.99994 8.00073 4.99994C6.34388 4.99994 5.00073 6.34308 5.00073 7.99994C5.00073 9.65679 6.34388 10.9999 8.00073 10.9999Z" stroke="#525866" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="fs_table_wrapper" v-if="!loading">
            <el-table
                :data="aiActivities"
                row-class-name="fs_table_row"
                header-row-class-name="fs_table_header_row"
                cell-class-name="fs_table_cell"
                header-cell-class-name="fs_table_header_cell"
                style="width: 100%"
            >
                <el-table-column prop="person.full_name" :label="$t('Agent')" width="200" min-width="200" sortable>
                    <template #header>
                        <span>{{ $t('Agent') }}</span>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Ticket')" width="220" min-width="220" sortable>
                    <template #header>
                        <span>{{ $t('Ticket') }}</span>
                    </template>
                    <template v-slot="scope">
                        <router-link v-if="scope.row.ticket && scope.row.ticket.id"
                                     class="fs_ticket_link_preview"
                                     :to="{ name: 'view_ticket', params: { ticket_id: scope.row.ticket.id } }">
                            <p>{{ scope.row.ticket.title }}</p>
                        </router-link>
                        <span v-else class="fs_ticket_not_available">#{{ scope.row.ticket_id }} - This ticket has been deleted or is not available.</span>
                    </template>
                </el-table-column>
                <el-table-column width="120" min-width="120" prop="model_name" :label="$t('Model')" sortable>
                    <template #header>
                        <span>{{ $t('Model') }}</span>
                    </template>
                </el-table-column>
                <el-table-column width="110" min-width="110" prop="tokens" :label="$t('Tokens')" sortable>
                    <template #header>
                        <span>{{ $t('Tokens') }}</span>
                    </template>
                </el-table-column>
                <el-table-column min-width="300" prop="prompt" :label="$t('Prompt')" sortable>
                    <template #header>
                        <span>{{ $t('Prompt') }}</span>
                    </template>
                </el-table-column>
            </el-table>

            <div class="fs_pagination_wrapper" v-if="aiActivities.length">
                <div class="fs_pagination_left">
                    <p>{{ $t('Page') }} {{ pagination.current_page }} {{ $t('of') }} {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                    <pagination
                        @fetch="fetchAIActivities()"
                        :pagination="pagination"
                        layout="sizes"
                    />
                </div>
                <div class="fs_pagination_right">
                    <pagination
                        @fetch="fetchAIActivities()"
                        :pagination="pagination"
                        :background="true"
                        layout="prev, pager, next"
                    />
                </div>
            </div>
        </div>
        <div v-else class="fs_skeleton_loader">
            <el-skeleton :rows="3" animated />
        </div>
    </div>

    <el-dialog
        v-model="showSettingsModal"
        :title="$t('Activity Log Settings')"
        width="60%"
        :append-to-body="true"
        class="fs_dialog"
    >
        <AIActivitySettings
            @updated="
                showSettingsModal = false;
                fetchAIActivities();
            "
        />
    </el-dialog>

</template>

<script>
import IconPack from '@/admin/Components/IconPack';
import Pagination from "../../Pieces/Pagination";
import AIActivitySettings from "./_AIActivitySettings";
import dayjs from "dayjs";
import ActivitySettings from "@/admin/Modules/ActivityLogger/_ActivitySettings.vue";
import {shortcuts} from "../Reports/Utils/dateShortCuts";
import { formatDateRangeForDisplay } from "../Reports/Utils/reportHelpers";

export default {
    name: "ActivityLogger",
    components:{
        ActivitySettings,
        AIActivitySettings,
        Pagination,
        IconPack
    },
    data() {
        return {
            aiActivities: [],
            loading: false,
            me: this.appVars.me,
            showSettingsModal: false,
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0,
            },
            settings: {},
            filters: {},
            date_range: null,
            shortcuts: shortcuts,
        }
    },
    computed: {
        formattedDateRange() {
            return formatDateRangeForDisplay(this.date_range);
        },
    },
    methods: {
        fetchAIActivities() {
            this.loading = true;
            this.$get("ai-activity-logger", {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                filters: this.filters,
                from: this.date_range && this.date_range.length > 0
                    ? this.date_range[0]
                    : "",
                to: this.date_range && this.date_range.length > 1
                    ? this.date_range[1]
                    : "",
            })
            .then((response) => {
                this.aiActivities = response.data;
                this.pagination.total = response.total;
                this.settings = response.settings;
                this.loading = false;
            })
        },
    },
    mounted() {
        this.fetchAIActivities();
    },
}
</script>
