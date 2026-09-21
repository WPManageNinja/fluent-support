<template>
    <div v-if="!loading" class="fs_personal_performance_box">
        <div class="fs_personal_performance_header">
            <div class="fs_personal_performance_title">
                {{ $t('My Performance') }}
            </div>
        </div>
        <div class="fs_personal_performance_body">
            <div class="fs_personal_performance_list">
                <div class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="interactions" 
                            :width="20" 
                            :height="20"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Interactions') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.interactions || 0 }}</span>
                </div>
                <div class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="waiting_tickets" 
                            :width="15" 
                            :height="15"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Waiting Tickets') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.waiting_tickets || 0 }}</span>
                </div>
                <div class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="average_waiting" 
                            :width="12" 
                            :height="12"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Average Waiting') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.average_waiting || 0 }}</span>
                </div>
                <div class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="max_waiting" 
                            :width="13" 
                            :height="13"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Max Waiting') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.max_waiting || 0 }}</span>
                </div>
                <div v-if="has_pro && appVars.agent_feedback_rating === 'yes'" class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="like" 
                            :width="20" 
                            :height="20"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Liked') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.likes || 0 }}</span>
                </div>
                <div v-if="has_pro && appVars.agent_feedback_rating === 'yes'" class="fs_personal_performance_item">
                    <div class="fs_personal_performance_icon_label">
                        <IconPack 
                            icon-key="dislike" 
                            :width="20" 
                            :height="20"
                            :fill="iconColor"
                            class="fs_personal_performance_icon"
                        />
                        <span class="fs_personal_performance_label">{{ $t('Disliked') }}</span>
                    </div>
                    <span class="fs_personal_performance_value">{{ stats?.dislikes || 0 }}</span>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="fs_box_body fs_skeleton_loader">
        <el-skeleton :rows="5" animated/>
    </div>
</template>

<script type="text/babel">
import IconPack from "@/admin/Components/IconPack.vue";

export default {
    name: "MyPerformanceReport",
    components: {
        IconPack
    },
    props: {
        reports: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {

        };
    },

    computed: {
        iconColor() {
            const computedStyle = getComputedStyle(document.documentElement);
            return computedStyle.getPropertyValue('--fs-stat-icon-color').trim() || '#525866';
        },
        stats() {
            if (!this.reports || this.reports.length === 0) {
                return null;
            }
            // For personal view, get the first (and likely only) report
            const report = this.reports[0];
            if (report && report.active_stat) {
                return {
                    interactions: report.stats?.interactions || 0,
                    waiting_tickets: report.active_stat.waiting_tickets || 0,
                    average_waiting: report.active_stat.average_waiting || 0,
                    max_waiting: report.active_stat.max_waiting || 0,
                    likes: report.stats?.likes || 0,
                    dislikes: report.stats?.dislikes || 0,
                };
            }
            return {
                interactions: report?.stats?.interactions || 0,
                waiting_tickets: 0,
                average_waiting: 0,
                max_waiting: 0,
                likes: report?.stats?.likes || 0,
                dislikes: report?.stats?.dislikes || 0,
            };
        },

    }
};
</script>

<style lang="scss" scoped>
.fs_skeleton_loader {
    padding: 20px;
    background: var(--fs-bg-primary);
}
</style>
