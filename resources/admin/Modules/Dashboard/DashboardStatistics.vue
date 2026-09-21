<template>
    <div class="fs_dashboard_statistics">
        <div class="fs_stats_grid">
            <div v-for="(stat, statKey) in stats" :key="statKey" class="fs_stat_card">
                <div class="fs_stat_icon">
                    <IconPack :iconKey="getIconKey(statKey)" :width="24" :height="24" />
                </div>
                <div class="fs_stat_content">
                    <p class="fs_stat_label">{{ stat.title }}</p>
                    <p class="fs_stat_number">{{ stat.count }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import IconPack from "@/admin/Components/IconPack.vue"

export default {
    name: 'DashboardStatistics',
    components: {
        IconPack
    },
    props: {
        stats: {
            type: Object,
            default: () => ({})
        }
    },
    methods: {
        // Map stat keys to icon keys
        getIconKey(statKey) {
            const iconMap = {
                'new_tickets': 'new_tickets',
                'open_tickets': 'open_tickets',
                'active_tickets': 'open_tickets',
                'closed_tickets': 'closed_tickets',
                'active_agents': 'active_agents',
                'responses': 'responses',
                'interactions': 'interactions',
                'unassigned_tickets': 'unassigned_tickets'
            }

            // Try to match the stat key or fallback to a default
            return iconMap[statKey] || 'new_tickets'
        }
    }
}
</script>
