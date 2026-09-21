<template>
    <div class="fs_notifications_page">
        <div class="fs_inside_activity_menu_tab_header">
            <div class="fs_box_head">
                <div class="fs-inside-activity-menu-tabs">
                    <button
                        v-for="category in categories"
                        :key="category.key"
                        type="button"
                        :class="[
                            'fs_activity_nav_tab',
                            { 'fs_activity_nav_tab_active': filters.category === category.key }
                        ]"
                        @click="setCategory(category.key)"
                    >
                        {{ category.label }}
                    </button>
                </div>
            </div>
        </div>

        <div class="fs_box_wrapper">
            <div class="fs_component_dashboard fs_activity_logger_component">
                <div class="fs_inside_menu_component_header">
                    <div class="fs_component_head">
                        <h3 class="fs_page_title">{{ $t('Notifications') }}</h3>
                    </div>
                    <div class="fs_box_actions fs_notifications_header_actions">
                        <label class="fs_notifications_switch">
                            <el-switch
                                :model-value="filters.status === 'unread'"
                                @change="toggleUnreadOnly"
                            />
                            <span>{{ $t('Unread only') }}</span>
                        </label>
                        <button
                            class="fs_refresh_button"
                            v-loading="loading"
                            @click="refreshList"
                        >
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.59725 1.82476C3.95817 0.645503 5.69924 -0.0025049 7.5 7.27699e-06C11.6423 7.27699e-06 15 3.35776 15 7.50001C15 9.10201 14.4975 10.587 13.6425 11.805L11.25 7.50001H13.5C13.5001 6.32373 13.1544 5.17336 12.506 4.19195C11.8576 3.21054 10.9349 2.44138 9.85288 1.9801C8.77082 1.51882 7.57704 1.38578 6.41997 1.59752C5.2629 1.80926 4.19359 2.35643 3.345 3.17101L2.59725 1.82476ZM12.4028 13.1753C11.0418 14.3545 9.30076 15.0025 7.5 15C3.35775 15 0 11.6423 0 7.50001C0 5.89801 0.5025 4.41301 1.3575 3.19501L3.75 7.50001H1.5C1.4999 8.67629 1.84556 9.82665 2.494 10.8081C3.14244 11.7895 4.06505 12.5586 5.14712 13.0199C6.22918 13.4812 7.42296 13.6142 8.58003 13.4025C9.7371 13.1908 10.8064 12.6436 11.655 11.829L12.4028 13.1753Z" fill="#525866"/>
                            </svg>
                            <span>{{ $t('Refresh') }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="loading" class="fs_notifications_empty">
                    {{ $t('Loading...') }}
                </div>

                <div v-else-if="notifications.length" class="fs_notifications_page_list">
                    <NotificationItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        :notification="notification"
                        @open="openNotification"
                        @mark-read="markRead"
                    />
                </div>

                <div v-else class="fs_notifications_empty">
                    <img :src="appVars.asset_url + 'images/empty.svg'" alt="">
                    <p>{{ $t('No Results Found') }}</p>
                </div>

                <div v-if="notifications.length" class="fs_pagination_wrapper">
                    <div class="fs_pagination_left">
                        <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                        <Pagination @fetch="fetchNotifications" :pagination="pagination" layout="sizes" />
                    </div>
                    <div class="fs_pagination_right">
                        <Pagination @fetch="fetchNotifications" :pagination="pagination" :background="true" layout="prev, pager, next" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'pinia';
import Pagination from '@/admin/Pieces/Pagination.vue';
import { useNotificationStore } from '@/admin/stores/notification.store';
import NotificationItem from './NotificationItem.vue';

export default {
    name: 'NotificationsPage',
    components: {
        NotificationItem,
        Pagination
    },
    data() {
        return {
            filters: {
                category: 'all',
                status: 'all'
            }
        };
    },
    computed: {
        ...mapState(useNotificationStore, {
            notifications: 'notifications',
            pagination: 'pagination',
            loading: 'loading',
            categories: 'notificationCategories'
        })
    },
    methods: {
        async fetchNotifications() {
            const store = useNotificationStore();

            try {
                await store.fetchNotifications({
                    category: this.filters.category,
                    status: this.filters.status,
                    page: store.pagination.current_page,
                    per_page: store.pagination.per_page
                });
            } catch (error) {
                this.$handleError(error);
            }
        },
        async refreshList() {
            await this.fetchNotifications();
        },
        async setCategory(category) {
            const store = useNotificationStore();
            this.filters.category = category;
            store.pagination.current_page = 1;
            await this.fetchNotifications();
        },
        async toggleUnreadOnly(value) {
            const store = useNotificationStore();
            this.filters.status = value ? 'unread' : 'all';
            store.pagination.current_page = 1;
            await this.fetchNotifications();
        },
        async markRead(notification) {
            const store = useNotificationStore();

            try {
                await store.markAsRead(notification.id);

                if (this.filters.status === 'unread') {
                    await this.fetchNotifications();
                }
            } catch (error) {
                this.$handleError(error);
            }
        },
        async openNotification(notification) {
            if (!notification.is_read) {
                await this.markRead(notification);
            }

            if (notification.ticket_id) {
                this.$router.push({
                    name: 'view_ticket',
                    params: {
                        ticket_id: notification.ticket_id
                    }
                });
            }
        }
    },
    async mounted() {
        this.$setTitle('Notifications');
        await this.fetchNotifications();
    }
};
</script>
