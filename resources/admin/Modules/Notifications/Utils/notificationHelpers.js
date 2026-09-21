export const defaultNotificationCategories = [
    { key: 'all', label: 'All' },
    { key: 'mentions', label: 'Mentions' },
    { key: 'ticket_activity', label: 'Ticket Activity' },
    { key: 'automation_triggers', label: 'Automation Triggers' }
];

export function getReadState(notification) {
    const recipient = Array.isArray(notification.recipients) ? notification.recipients[0] : null;
    return !!(recipient && parseInt(recipient.is_read, 10));
}

export function normalizeNotification(notification) {
    return {
        ...notification,
        is_read: getReadState(notification),
        event_label: notification.event_label || 'updated a ticket',
        event_icon: notification.event_icon || 'reply'
    };
}

export function normalizeNotifications(notifications = []) {
    return notifications.map(normalizeNotification);
}

export function getNotificationActor(notification) {
    if (notification.actor && notification.actor.full_name) {
        return notification.actor.full_name;
    }

    if (notification.payload?.customer?.name) {
        return notification.payload.customer.name;
    }

    if (notification.payload?.actor?.name) {
        return notification.payload.actor.name;
    }

    if (notification.payload?.assigner?.name) {
        return notification.payload.assigner.name;
    }

    return 'Someone';
}

export function getNotificationActorPhoto(notification) {
    if (notification.actor && notification.actor.photo) {
        return notification.actor.photo;
    }

    if (notification.payload?.customer?.photo) {
        return notification.payload.customer.photo;
    }

    if (notification.payload?.actor?.photo) {
        return notification.payload.actor.photo;
    }

    if (notification.payload?.assigner?.photo) {
        return notification.payload.assigner.photo;
    }

    return '';
}

export function getNotificationActorInitials(notification) {
    const name = getNotificationActor(notification).trim();

    if (!name) {
        return 'S';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function getNotificationPreview(notification) {
    return notification.payload?.content_preview || '';
}

export function getNotificationTicketTitle(notification) {
    if (notification.ticket && notification.ticket.title) {
        return notification.ticket.title;
    }

    return notification.payload?.ticket_title || '';
}

export function truncateText(text, limit = 80) {
    text = String(text || '');

    if (text.length <= limit) {
        return text;
    }

    return `${text.slice(0, limit).trim()}...`;
}

export function getNotificationSummary(notification) {
    if (notification.summary) {
        return notification.summary;
    }

    const actor = getNotificationActor(notification);
    const eventLabel = notification.event_label || 'updated a ticket';
    const ticketTitle = getNotificationTicketTitle(notification);

    if (ticketTitle) {
        return `${actor} ${eventLabel} in ${truncateText(ticketTitle)}`;
    }

    return `${actor} ${eventLabel}`;
}
