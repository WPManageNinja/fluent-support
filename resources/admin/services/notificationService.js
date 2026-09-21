import Rest from '@/admin/Bits/Rest';

export default {
    fetchNotifications(params = {}) {
        return Rest.get('notifications', params);
    },

    fetchUnread(params = {}) {
        return Rest.get('notifications/unread', params);
    },

    fetchUnreadCount(params = {}) {
        return Rest.get('notifications/unread-count', params);
    },

    markAsRead(notificationId) {
        return Rest.post(`notifications/${notificationId}/mark-read`);
    },

    markAllAsRead() {
        return Rest.post('notifications/mark-all-read');
    }
};
