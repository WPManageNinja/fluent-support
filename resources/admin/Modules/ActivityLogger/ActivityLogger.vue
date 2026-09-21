<template>
    <!-- Header Section -->
    <div v-if="settings.disable_logs == 'yes'" class="fs_activity_disabled_banner">
        <div class="fs_banner_icon">
            <IconPack icon-key="warning" :width="20" :height="20" class="fs_warn_icon" />
        </div>
        <span class="fs_banner_text">{{ $t("Activity Logs are currently disabled") }}</span>
    </div>
    <div class="fs_inside_menu_component_header">
        <div class="fs_component_head">
            <h3 class="fs_page_title">{{ $t("Overall Activities") }}</h3>
        </div>
        <div class="fs_box_actions">
            <button 
                class="fs_refresh_button"
                v-loading="loading"
                @click="fetchActivities()"
            >
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.59725 1.82476C3.95817 0.645503 5.69924 -0.0025049 7.5 7.27699e-06C11.6423 7.27699e-06 15 3.35776 15 7.50001C15 9.10201 14.4975 10.587 13.6425 11.805L11.25 7.50001H13.5C13.5001 6.32373 13.1544 5.17336 12.506 4.19195C11.8576 3.21054 10.9349 2.44138 9.85288 1.9801C8.77082 1.51882 7.57704 1.38578 6.41997 1.59752C5.2629 1.80926 4.19359 2.35643 3.345 3.17101L2.59725 1.82476ZM12.4028 13.1753C11.0418 14.3545 9.30076 15.0025 7.5 15C3.35775 15 0 11.6423 0 7.50001C0 5.89801 0.5025 4.41301 1.3575 3.19501L3.75 7.50001H1.5C1.4999 8.67629 1.84556 9.82665 2.494 10.8081C3.14244 11.7895 4.06505 12.5586 5.14712 13.0199C6.22918 13.4812 7.42296 13.6142 8.58003 13.4025C9.7371 13.1908 10.8064 12.6436 11.655 11.829L12.4028 13.1753Z" fill="#525866"/>
                </svg>
                <span>{{ $t('Refresh') }}</span>
            </button>
        </div>
    </div>
    <div class="fs_activity_logger">
        <!-- Filters Section -->
        <div class="fs_activity_filters_section">
            <div class="fs_activity_filters">
                <div class="fs_filter_group">
                    <el-select
                        clearable
                        filterable
                        @change="fetchActivities()"
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
                                @change="fetchActivities"
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
        <!-- Activity Timeline -->
        <div v-if="activities">
            <div class="fs_activity_timeline_container" v-if="!loading">
                <div v-if="activities.length > 0" class="fs_activity_timeline">
                    <div
                        v-for="activity in activities"
                        @click.prevent="handleClick(activity, $event)"
                        :key="activity.id"
                        class="fs_timeline_item"
                    >
                        <div class="fs_timeline_icon">
                            <div class="fs_activity_icon" :class="getActivityIconClass(activity)">
                                <svg v-if="getActivityIcon(activity)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path :d="getActivityIcon(activity)"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="fs_timeline_content">
                            <div class="fs_activity_description" v-html="sanitizeDescription(activity.description)"></div>
                            <div class="fs_activity_time">{{ formatActivityTime(activity.created_at) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fs_pagination_wrapper" v-if="activities.length">
                <div class="fs_pagination_left">
                    <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                    <pagination
                        @fetch="fetchActivities()"
                        :pagination="pagination"
                        layout="sizes"
                    />
                </div>
                <div class="fs_pagination_right">
                    <pagination
                        @fetch="fetchActivities()"
                        :pagination="pagination"
                        :background="true"
                        layout="prev, pager, next"
                    />
                </div>
            </div>
            
            <div v-if="loading" class="fs_loading_skeleton">
                <el-skeleton :rows="5" animated />
            </div>
        </div>

        <div class="fs_no_activities" v-if="activities.length < 1">
            <img :src="appVars.asset_url + 'images/empty.svg'" alt="">
            <p>{{ $t('No Results Found') }}</p>
        </div>

        <el-dialog
            v-model="showSettingsModal"
            :title="$t('Activity Log Settings')"
            width="60%"
            :append-to-body="true"
            class="fs_dialog"
        >
            <activity-settings
                @updated="
                    showSettingsModal = false;
                    fetchActivities();
                "
            />
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import IconPack from '@/admin/Components/IconPack';
import Pagination from "../../Pieces/Pagination";
import ActivitySettings from "./_ActivitySettings";
import dayjs from "dayjs";
import DOMPurify from 'dompurify';
import { stripReservedElementPlusClasses } from '@/common/domPurifyGuards';
import {shortcuts} from "../Reports/Utils/dateShortCuts";
import { formatDateRangeForDisplay } from "../Reports/Utils/reportHelpers";

stripReservedElementPlusClasses(DOMPurify);

export default {
    name: "ActivityLogger",
    components: {
        ActivitySettings,
        Pagination,
        IconPack
    },
    data() {
        return {
            activities: [],
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
        async fetchActivities() {
            this.loading = true;
            await this.$get("activity-logger", {
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
                    this.activities = response.activities.data;
                    this.pagination.total = response.activities.total;
                    this.settings = response.settings;
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        handleClick(activity, $event) {
            const route = $event.target.getAttribute("href");

            if (!route) {
                return false;
            }

            const routerData = {
                name: "view_ticket",
                params: { ticket_id: activity.object_id },
            };

            if (this.settings.open_link_in_new_tab == 'yes' && route == '#view_ticket') {
                const routeData = this.$router.resolve(routerData);

                window.open(routeData.href, '_blank');
            } else {
                this.$router.push(routerData);
            }
        },
        getActivityIcon(activity) {
            const description = activity.description.toLowerCase();
            if (description.includes('created')) {
                return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
            } else if (description.includes('response') && description.includes('customer')) {
                return 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
            } else if (description.includes('response') && description.includes('agent')) {
                return 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z';
            } else if (description.includes('note')) {
                return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
            } else if (description.includes('closed')) {
                return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
            } else if (description.includes('reopened')) {
                return 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15';
            } else if (description.includes('assign')) {
                return 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z';
            } else if (description.includes('deleted')) {
                return 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
            }
            return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
        },
        getActivityIconClass(activity) {
            const description = activity.description.toLowerCase();
            if (description.includes('created')) return 'fs_icon_created';
            if (description.includes('response') && description.includes('customer')) return 'fs_icon_customer';
            if (description.includes('response') && description.includes('agent')) return 'fs_icon_agent';
            if (description.includes('note')) return 'fs_icon_note';
            if (description.includes('closed')) return 'fs_icon_closed';
            if (description.includes('reopened')) return 'fs_icon_reopened';
            if (description.includes('assign')) return 'fs_icon_assigned';
            if (description.includes('deleted')) return 'fs_icon_deleted';
            return 'fs_icon_default';
        },
        sanitizeDescription(description) {
            if (!description) return '';
            return DOMPurify.sanitize(description);
        },
        formatActivityTime(createdAt) {
            const date = new Date(createdAt);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },
    },
    mounted() {
        this.fetchActivities();
        this.$setTitle('Activities');
    },
};
</script>