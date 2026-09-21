<template>
    <div class="fs_all_tickets">
        <div class="fs_box_body" v-if="component_data.suggested_tickets.length">
            <div class="fs_tickets_list">
                <template v-for="(ticket, index) in component_data.suggested_tickets" :key="ticket.id">
                    <DashboardTicket
                        :ticket="ticket"
                        :show-checkbox="false"
                        @ticket-click="navigateToTicket"
                    />
                    <div 
                        v-if="index < component_data.suggested_tickets.length - 1" 
                        class="fs_ticket_divider"
                    ></div>
                </template>
            </div>
        </div>

        <div v-else class="fs_no_book_tk">
            <span>
                {{ $t("dashboard_all_catch_up") }}
                <b>{{ $t("Good Job") }}, {{ appVars.me.full_name }}!</b>
            </span>
        </div>

        <div class="fs_stats_info" v-if="component_data.overall_stats">
            <p class="fs_stat_helper">
                <span>
                    {{ component_data.overall_stats.waiting_tickets }}
                    {{ $t("tickets") }}</span
                >
                {{ $t("are waiting for reply with") }}
                <span>
                    {{ $t("average") }}
                    {{ component_data.overall_stats.average_waiting }}
                    {{ $t("wait time") }}</span
                >
                & {{ $t("max wait time") }}
                <span>{{
                    component_data.overall_stats.max_waiting
                }}</span>
            </p>
        </div>
    </div>
</template>
<script>
import DashboardTicket from "@/admin/Components/DashboardTicket.vue";

export default {
    name: "SuggestedTicket",
    components: {
        DashboardTicket
    },
    props: {
        component_data: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['navigate-to-ticket'],
    methods: {
        navigateToTicket(ticket) {
            this.$emit('navigate-to-ticket', ticket);
        }
    }
};
</script>
