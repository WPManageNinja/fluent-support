<template>
    <li v-if="internalNotificationsEnabled" class="fs_nav_item fs_item_notifications">
        <el-popover
            v-model:visible="visible"
            popper-class="fs_notifications_popover"
            placement="bottom-end"
            :width="500"
            trigger="click"
            @show="handleShow"
        >
            <template #reference>
                <button class="fs_notifications_trigger" type="button" :aria-label="$t('Notifications')">
                    <svg width="19" height="19" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2.5C7.92894 2.5 6.25 4.17894 6.25 6.25V8.3975C6.25 9.12875 5.99125 9.83625 5.52 10.395L4.0145 12.1812C3.815 12.4175 3.77225 12.745 3.90425 13.025C4.03625 13.305 4.31825 13.4837 4.62875 13.4837H15.3712C15.6817 13.4837 15.9637 13.305 16.0957 13.025C16.2277 12.745 16.185 12.4175 15.9855 12.1812L14.48 10.395C14.0087 9.83625 13.75 9.12875 13.75 8.3975V6.25C13.75 4.17894 12.0711 2.5 10 2.5ZM8.5 15.25C8.5 16.0784 9.17157 16.75 10 16.75C10.8284 16.75 11.5 16.0784 11.5 15.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span v-if="unreadCount" class="fs_notifications_badge">
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </button>
            </template>

            <div class="fs_notifications_pop">
                <div class="fs_notifications_pop_header">
                    <h3>{{ $t('Unread Notifications') }}</h3>
                    <div class="fs_notifications_pop_header_actions">
                        <button
                            class="fs_notifications_settings_btn"
                            type="button"
                            :aria-label="$t('Notification Settings')"
                            @click="openNotificationSettings"
                        >
                            <el-icon><Setting /></el-icon>
                        </button>
                        <el-button
                            size="small"
                            :loading="working"
                            :disabled="working || !unreadCount"
                            @click="handleMarkAllRead"
                        >
                            {{ $t('Mark all as read') }}
                        </el-button>
                    </div>
                </div>

                <div class="fs_notifications_tabs">
                    <ul class="fs_notifications_tabs_list">
                        <li v-for="category in categories" :key="category.key">
                            <button
                                type="button"
                                class="fs_notifications_tab"
                                :class="{ 'is-active': selectedCategory === category.key }"
                                @click="switchCategory(category.key)"
                            >
                                {{ category.label }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="fs_notifications_pop_body">
                    <div v-if="loading" class="fs_notifications_loading">
                        <el-skeleton :rows="3" animated />
                    </div>

                    <div v-else-if="notifications.length" class="fs_notifications_list">
                        <NotificationItem
                            v-for="notification in notifications"
                            :key="notification.id"
                            :notification="notification"
                            @open="openNotification"
                            @mark-read="markRead"
                        />
                    </div>

                    <div v-else class="fs_notifications_empty">
                        {{ $t('No unread notifications found') }}
                    </div>
                </div>

                <div class="fs_notifications_pop_footer">
                    <el-button class="fs_outline_btn" @click="gotoNotifications">
                        {{ $t('View All') }}
                    </el-button>
                </div>
            </div>
        </el-popover>

        <el-dialog
            v-model="settingsModalVisible"
            :title="$t('Notification Settings')"
            width="60%"
            :append-to-body="true"
            class="fs_dialog"
        >
            <InternalNotifications
                settings-scope="preferences"
                display-mode="modal"
                @updated="settingsModalVisible = false"
            />
        </el-dialog>
    </li>
</template>

<script>
import { mapState } from 'pinia';
import { Setting } from '@element-plus/icons-vue';
import { useNotificationStore } from '@/admin/stores/notification.store';
import NotificationItem from './NotificationItem.vue';
import InternalNotifications from '@/admin/Modules/Settings/InternalNotifications.vue';

export default {
    name: 'NotificationsPop',
    components: {
        InternalNotifications,
        NotificationItem,
        Setting
    },
    data() {
        return {
            visible: false,
            settingsModalVisible: false,
            selectedCategory: 'all',
            internalNotificationsEnabled: window.fluentSupportAdmin?.internal_notification_settings?.enabled === 'yes'
        };
    },
    computed: {
        ...mapState(useNotificationStore, {
            notifications: 'unreadNotifications',
            unreadCount: 'unreadCount',
            loading: 'unreadLoading',
            working: 'working',
            categories: 'notificationCategories'
        }),
    },
    methods: {
        async handleShow() {
            await this.refreshUnread();
        },
        async refreshUnread() {
            const store = useNotificationStore();
            try {
                await store.fetchUnread({
                    category: this.selectedCategory,
                    limit: 8
                });
            } catch (error) {
                this.$handleError(error);
            }
        },
        async switchCategory(category) {
            this.selectedCategory = category;
            await this.refreshUnread();
        },
        async markRead(notification) {
            const store = useNotificationStore();

            try {
                await store.markAsRead(notification.id);
            } catch (error) {
                this.$handleError(error);
            }
        },
        async handleMarkAllRead() {
            const store = useNotificationStore();

            try {
                await store.markAllAsRead();
            } catch (error) {
                this.$handleError(error);
            }
        },
        async openNotification(notification) {
            if (!notification.is_read) {
                await this.markRead(notification);
            }

            this.visible = false;

            if (notification.ticket_id) {
                this.$router.push({
                    name: 'view_ticket',
                    params: {
                        ticket_id: notification.ticket_id
                    }
                });
            } else {
                this.$router.push({ name: 'notifications' });
            }
        },
        gotoNotifications() {
            this.visible = false;
            this.$router.push({ name: 'notifications' });
        },
        openNotificationSettings() {
            this.visible = false;
            this.settingsModalVisible = true;
        },
        handleSettingsChanged(event) {
            const settings = event.detail?.settings || window.fluentSupportAdmin?.internal_notification_settings || {};
            this.internalNotificationsEnabled = settings.enabled === 'yes';

            if (!this.internalNotificationsEnabled) {
                this.visible = false;
                this.settingsModalVisible = false;
            }
        }
    },
    mounted() {
        window.addEventListener('fluent_support_internal_notification_settings_changed', this.handleSettingsChanged);
    },
    beforeUnmount() {
        window.removeEventListener('fluent_support_internal_notification_settings_changed', this.handleSettingsChanged);
    }
};
</script>
