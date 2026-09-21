<template>
    <div class="fs_all_tickets" v-if="has_pro">
        <div class="fs_box_body" v-if="component_data.length">
            <div class="fs_tickets_list">
                <template v-for="(ticket, index) in component_data" :key="ticket.id">
                    <DashboardTicket
                        :ticket="ticket"
                        :show-checkbox="false"
                        @ticket-click="navigateToTicket"
                    />
                    <div
                        v-if="index < component_data.length - 1"
                        class="fs_ticket_divider"
                    ></div>
                </template>
            </div>
        </div>
        <div v-else class="fs_no_book_tk">
            <span>
                {{ $t('no_bookmarked_ticket') }}
            </span>
        </div>
    </div>
    <div v-else class="fs_all_tickets">
        <div class="fs_no_book_tk">
            <span>
                {{ $t('bookmarked_ticket_pro_feature') }}
                <a :href="upgradeUrl" target="_blank" rel="noopener noreferrer">{{ $t('upgrade_now') }}</a>
            </span>
        </div>
    </div>
</template>
<script>
import DashboardTicket from "@/admin/Components/DashboardTicket.vue";

export default {
    name: "MentionedTicket",
    components: {
        DashboardTicket
    },
    props: {
        component_data: {
            type: Array,
            default: () => []
        }
    },
    emits: ['navigate-to-ticket'],
    computed: {
        upgradeUrl() {
            return this.$upgradeUrl('feature_lock_bookmarked_tickets');
        }
    },
    methods: {
        navigateToTicket(ticket) {
            this.$emit('navigate-to-ticket', ticket);
        }
    }
};
</script>
