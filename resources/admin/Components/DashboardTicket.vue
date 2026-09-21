<template>
    <div 
        class="fs_ticket_item"
        @click="handleTicketClick"
        style="cursor: pointer;"
    >
        <!-- Row 1: Title, Author, Ticket ID, Source -->
        <div class="fs_ticket_title_row">
            <el-checkbox
                v-if="showCheckbox"
                @click.stop
                :model-value="isSelected"
                @change="handleSelection"
                class="fs_ticket_checkbox"
            />
            <h3 class="fs_ticket_title">{{ ticket.title }}</h3>
            <span class="fs_ticket_author" v-if="ticket.customer">by {{ ticket.customer.first_name || ticket.customer.email }}</span>
            <span class="fs_ticket_id">#{{ ticket.id }}</span>
            <span v-if="ticket.status === 'new'" class="fs_status_dot fs_status_dot_new"></span>
            <el-tooltip
                v-if="ticket.source"
                effect="dark"
                :content="ucFirst(ticket.source)"
                placement="top"
            >
                <span class="fs_ticket_source">
                    <img
                        v-if="$getSourceIcon(ticket.source)"
                        :src="$getSourceIcon(ticket.source)"
                        :alt="ucFirst(ticket.source)"
                    >
                    <span v-else class="fs_ticket_source_text">{{ ucFirst(ticket.source) }}</span>
                </span>
            </el-tooltip>
        </div>
        
        <!-- Row 2: Status, Priority, Waiting Time (left) | Tags (right) -->
        <div class="fs_ticket_meta_row">
            <div class="fs_ticket_meta_left">
                <!-- Status Badge -->
                <el-tooltip v-if="ticket.status !== 'new'" effect="dark" :content="$t('Status') + ': ' + (appVars.ticket_statuses[ticket.status] || ticket.status)" placement="top" trigger="hover">
                <span class="fs_status_badge" :class="`fs_status_${(ticket.status || '').toLowerCase()}`">
                    {{ appVars.ticket_statuses[ticket.status] || ticket.status }}
                </span>
                </el-tooltip>

                <!-- Priority -->
                <el-tooltip v-if="ticket.client_priority" effect="dark" :content="$t('Client Priority') + ': ' + (appVars.client_priorities[ticket.client_priority] || ticket.client_priority)" placement="top" trigger="hover">
                <span class="fs_priority_indicator">
                    <span class="fs_priority_dot" :class="`fs_priority_dot_${(ticket.client_priority || 'normal').toLowerCase()}`"></span>
                    <span class="fs_priority_text">{{ appVars.client_priorities[ticket.client_priority] || ticket.client_priority }}</span>
                </span>
                </el-tooltip>

                <!-- Waiting Time -->
                <el-tooltip v-if="ticket.waiting_since" effect="dark" :content="$t('Waiting Time') + ': ' + ticket.waiting_since" placement="top" trigger="hover">
                <span class="fs_waiting_time">
                    <IconPack iconKey="clock" :width="16" :height="16" />
                    <span class="fs_waiting_text">{{ $timeDiff(ticket.waiting_since) }}</span>
                </span>
                </el-tooltip>
            </div>
            
            <div class="fs_ticket_meta_right">
                <!-- Tags -->
                <ticket-tags
                    v-if="ticket.tags && ticket.tags.length"
                    :tags="ticket.tags"
                    :ticket_id="ticket.id"
                    mode="ticket_list"
                    class="fs_ticket_tags"
                    @click.stop
                />
            </div>
        </div>
    </div>
</template>

<script>
import TicketTags from '../Modules/Tickets/parts/_Tags';
import IconPack from '../Components/IconPack';

export default {
    name: 'Ticket',
    components: {
        TicketTags,
        IconPack
    },
    props: {
        ticket: {
            type: Object,
            required: true
        },
        showCheckbox: {
            type: Boolean,
            default: false
        },
        isSelected: {
            type: Boolean,
            default: false
        },
        assetUrl: {
            type: String,
            default: '/wp-content/plugins/fluent-support-dev/assets/'
        }
    },
    methods: {
        handleTicketClick() {
            this.$emit('ticket-click', this.ticket, event);
        },
        
        handleSelection(value) {
            this.$emit('ticket-select', this.ticket.id, value);
        }
    }
};
</script>