import { defineStore } from 'pinia';
import notificationService from '@/admin/services/notificationService';
import {
    defaultNotificationCategories,
    normalizeNotifications
} from '@/admin/Modules/Notifications/Utils/notificationHelpers';

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        notifications: [],
        unreadNotifications: [],
        unreadCount: 0,
        loading: false,
        unreadLoading: false,
        working: false,
        notificationCategories: defaultNotificationCategories,
        pagination: {
            current_page: 1,
            total: 0,
            per_page: 10,
        }
    }),

    actions: {
        async fetchNotifications(filters = {}) {
            this.loading = true;

            try {
                const response = await notificationService.fetchNotifications(filters);
                this.setNotificationMeta(response);
                this.notifications = normalizeNotifications(response.notifications.data || []);
                this.pagination.current_page = response.notifications.current_page || 1;
                this.pagination.total = response.notifications.total || 0;
                this.pagination.per_page = response.notifications.per_page || filters.per_page || this.pagination.per_page;

                if (typeof response.unread_count !== 'undefined') {
                    this.unreadCount = parseInt(response.unread_count, 10) || 0;
                }

                return response;
            } finally {
                this.loading = false;
            }
        },

        async fetchUnread(filters = {}) {
            this.unreadLoading = true;

            try {
                const response = await notificationService.fetchUnread(filters);
                this.setNotificationMeta(response);
                this.unreadNotifications = normalizeNotifications(response.notifications || []);
                this.unreadCount = parseInt(response.unread_count, 10) || 0;
                return response;
            } finally {
                this.unreadLoading = false;
            }
        },

        async fetchUnreadCount(filters = {}) {
            const response = await notificationService.fetchUnreadCount(filters);
            this.unreadCount = parseInt(response.count, 10) || 0;
            return this.unreadCount;
        },

        async markAsRead(notificationId) {
            const response = await notificationService.markAsRead(notificationId);

            this.notifications = this.notifications.map((notification) => {
                if (notification.id === notificationId) {
                    return { ...notification, is_read: true };
                }

                return notification;
            });

            this.unreadNotifications = this.unreadNotifications
                .map((notification) => {
                    if (notification.id === notificationId) {
                        return { ...notification, is_read: true };
                    }

                    return notification;
                })
                .filter((notification) => !notification.is_read);

            if (typeof response.unread_count !== 'undefined') {
                this.unreadCount = parseInt(response.unread_count, 10) || 0;
            } else {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }

            return response;
        },

        async markAllAsRead() {
            this.working = true;

            try {
                const response = await notificationService.markAllAsRead();

                this.notifications = this.notifications.map((notification) => ({
                    ...notification,
                    is_read: true
                }));
                this.unreadNotifications = [];
                this.unreadCount = parseInt(response.unread_count, 10) || 0;

                return response;
            } finally {
                this.working = false;
            }
        },

        setNotificationMeta(response = {}) {
            const categories = response.meta?.categories || response.notification_categories;

            if (Array.isArray(categories) && categories.length) {
                this.notificationCategories = categories;
            }
        }
    }
});
