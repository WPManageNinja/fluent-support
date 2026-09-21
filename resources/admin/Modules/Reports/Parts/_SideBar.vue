<template>
    <div v-if="!loading" class="fs_stat_boxes_row">
        <StatBox
            v-for="(stat, index) in orderedStats"
            :key="stat.label || index"
            :label="stat.label"
            :value="stat.value"
            :icon="stat.icon"
            :icon-bg="stat.iconBg"
            :icon-color="stat.iconColor"
        />
    </div>
    <div class="fs_padded_20 fs_skeleton_loader" v-else>
        <el-skeleton :rows="3" animated />
    </div>
</template>

<script>
import StatBox from "./StatBox";

const iconMap = {
    'tickets': 'new_tickets',
    'new_tickets': 'new_tickets',
    'closed': 'closed_tickets',
    'closed_tickets': 'closed_tickets',
    'responses': 'responses',
    'active': 'active_agents',
    'active_tickets': 'active_tickets',
    'open': 'open_tickets',
    'open_tickets': 'open_tickets',
    'pending': 'new_tickets',
    'replies': 'reply',
    'total_replies': 'reply',
    'resolved': 'closed_tickets',
};

const defaultIcon = 'new_tickets';

export default {
    name: "SideBar",
    components: {
        StatBox,
    },
    props: {
        mailbox_id: {
            type: [String, Number],
            default: null
        },
        product_id: {
            type: [String, Number],
            default: null
        },
        agent_group_id: {
            type: [String, Number],
            default: null
        },
        date_range: {
            type: Array,
            default: null
        }
    },
    data() {
        return {
            loading: false,
            overall_reports: {},
            isInitialized: false,
        };
    },
    computed: {
        orderedStats() {
            const stats = [];

            // Convert object to array while preserving order
            Object.keys(this.overall_reports || {}).forEach((key, index) => {
                const stat = this.overall_reports[key];
                const statKey = key.toLowerCase();

                stats.push({
                    label: stat.title || key,
                    value: stat.count || 0,
                    icon: iconMap[statKey] || iconMap[statKey.replace('_', '')] || iconMap[statKey.split('_')[0]] || defaultIcon,
                    iconBg: ''
                });
            });

            return stats;
        }
    },
    watch: {
        mailbox_id() {
            if (this.isInitialized) {
                this.fetchReports();
            }
        },
        product_id() {
            if (this.isInitialized) {
                this.fetchReports();
            }
        },
        agent_group_id() {
            if (this.isInitialized) {
                this.fetchReports();
            }
        },
        date_range: {
            handler() {
                if (this.isInitialized) {
                    this.fetchReports();
                }
            },
            deep: true
        }
    },
    mounted() {
        this.$nextTick(() => {
            this.fetchReports();
            this.isInitialized = true;
        });
    },
    methods: {
        fetchReports() {
            this.loading = true;
            const params = {};

            if (this.mailbox_id) {
                params.mailbox_id = this.mailbox_id;
            }

            if (this.product_id) {
                params.product_id = this.product_id;
            }

            if (this.agent_group_id) {
                params.agent_group_id = this.agent_group_id;
            }

            if (this.date_range && Array.isArray(this.date_range) && this.date_range.length === 2) {
                if (this.date_range[0] && this.date_range[1]) {
                    params.date_range = this.date_range;
                }
            }

            this.$get("reports/stats", params)
                .then((response) => {
                    this.overall_reports = response.overall_reports || {};
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    }
}
</script>
