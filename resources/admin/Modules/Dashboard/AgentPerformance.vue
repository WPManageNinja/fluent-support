<template>
    <div class="fs_box_body" v-if="todayStats.length">
        <div class="fs_agent_performance_list">
            <div
                v-for="(agent, index) in todayStats"
                :key="agent.agent_id"
                class="fs_agent_performance_item"
            >
                <!-- Agent Info Row -->
                <div class="fs_agent_info_row">
                    <!-- Left: Avatar + Name/Email -->
                    <div class="fs_agent_info">
                        <div class="fs_agent_avatar">
                            <img
                                :src="agent.avatar || getDefaultAvatar(agent.agent_name)"
                                :alt="agent.agent_name"
                                class="fs_avatar_image"
                            />
                        </div>
                        <div class="fs_agent_details">
                            <p class="fs_agent_name">{{ agent.agent_name.trim().length > 0 ? agent.agent_name : "No name" }}</p>
                            <p class="fs_agent_email">{{ agent.agent_email || 'agent@example.com' }}</p>
                        </div>
                    </div>

                    <!-- Right: Stats Column -->
                    <div class="fs_agent_stats_column">
                        <!-- Closed Tickets Badge -->
                        <div class="fs_closed_badge">
                            <IconPack icon-key="closed_tickets"/>
                            <span>Closed {{ agent.stats.closed_tickets.count }} tickets</span>
                        </div>

                        <!-- Stats Row -->
                        <div class="fs_agent_stats_row">
                            <!-- Waiting -->
                            <div class="fs_agent_stat">
                                <IconPack iconKey="clock" :width="16" :height="16" class="fs_meta_icon" />
                                <span>{{ formatWaitingTime(agent.stats.waiting_today.count) }}</span>
                            </div>

                            <!-- Responses -->
                            <div class="fs_agent_stat">
                                <IconPack iconKey="responses" :width="16" :height="16" class="fs_meta_icon" />
                                <span>{{ agent.stats.responses.count }} responses</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Divider (except for last item) -->
                <div v-if="index < todayStats.length - 1" class="fs_agent_divider"></div>
            </div>
        </div>
    </div>

    <div v-else class="fs_no_agents_data">
        <span>
            {{ $t("No agent performance data available") }}
        </span>
    </div>
</template>

<script type="text/babel">
import IconPack from "../../Components/IconPack.vue";

export default {
    name: 'AgentPerformance',
    components: {
        IconPack
    },
    data() {
        return {
            fetching: false,
            todayStats: [],
        };
    },
    methods: {
        async fetchAgentPerformance() {
            this.fetching = true;
            try {
                const response = await this.$get('tickets/agent_performance');
                this.todayStats = response.agent_today_stats;
            } catch (errors) {
                this.$handleError(errors);
            } finally {
                this.fetching = false;
            }
        },
        getDefaultAvatar(name) {
            // Generate a simple avatar based on initials or use a default
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=40&background=f0f0f0&color=525866&font-size=0.33`;
        },
        formatWaitingTime(count) {
            if (count === 0) return '0 days';
            if (count === 1) return '1 day';
            return `${count} days`;
        }
    },
    mounted() {
        this.fetchAgentPerformance();
    }
}
</script>
