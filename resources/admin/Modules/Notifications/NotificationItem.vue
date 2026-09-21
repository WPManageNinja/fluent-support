<template>
    <div
        class="fs_notification_item"
        :class="{ 'is-unread': !notification.is_read, 'is-clickable': true }"
        role="button"
        tabindex="0"
        @click="handleOpen"
        @keydown.enter.prevent="handleOpen"
        @keydown.space.prevent="handleOpen"
    >
        <div class="fs_notification_media">
            <div class="fs_notification_avatar">
                <img v-if="actorPhoto" :src="actorPhoto" :alt="actorName">
                <span v-else>{{ actorInitials }}</span>
            </div>
            <div class="fs_notification_event_icon">
                <IconPack :icon-key="notification.event_icon" :width="12" :height="12" />
            </div>
        </div>
        <div class="fs_notification_body">
            <p class="fs_notification_summary">
                {{ summary }}
            </p>
            <p v-if="preview" class="fs_notification_preview">{{ preview }}</p>
            <p class="fs_notification_meta">{{ $timeDiff(notification.created_at) }}</p>
        </div>
        <button
            v-if="!notification.is_read"
            class="fs_notification_mark_read"
            type="button"
            :aria-label="$t('Mark read')"
            @click.stop="$emit('mark-read', notification)"
            @keydown.enter.stop
            @keydown.space.stop
        >
            <span class="fs_status_dot fs_status_dot_new"></span>
        </button>
    </div>
</template>

<script>
import IconPack from '@/admin/Components/IconPack.vue';
import {
    getNotificationActor,
    getNotificationActorInitials,
    getNotificationActorPhoto,
    getNotificationPreview,
    getNotificationSummary
} from './Utils/notificationHelpers';

export default {
    name: 'NotificationItem',
    components: {
        IconPack
    },
    props: {
        notification: {
            type: Object,
            required: true
        }
    },
    emits: ['open', 'mark-read'],
    computed: {
        actorName() {
            return getNotificationActor(this.notification);
        },
        actorPhoto() {
            return getNotificationActorPhoto(this.notification);
        },
        actorInitials() {
            return getNotificationActorInitials(this.notification);
        },
        preview() {
            return getNotificationPreview(this.notification);
        },
        summary() {
            return getNotificationSummary(this.notification);
        }
    },
    methods: {
        handleOpen() {
            this.$emit('open', this.notification);
        }
    }
};
</script>
