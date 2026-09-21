<template>
    <div class="fs_inside_activity_menu_tab_header">
        <div class="fs_box_head">
            <div class="fs-inside-activity-menu-tabs">
                <button 
                    :class="['fs_activity_nav_tab', { 'fs_activity_nav_tab_active': activeName === 'overall_activities' }]"
                    @click="activeName = 'overall_activities'"
                >
                    {{ $t('Overall') }}
                </button>
                <button 
                    v-if="ai_enabled"
                    :class="['fs_activity_nav_tab', { 'fs_activity_nav_tab_active': activeName === 'ai_activities' }]"
                    @click="activeName = 'ai_activities'"
                >
                    {{ $t('AI Activities') }}
                </button>
            </div>
        </div>
    </div>
    <div class="fs_box_wrapper">
        <div class="fs_component_dashboard fs_activity_logger_component">
            <!-- Main Content -->
            <activity-logger v-if="activeName === 'overall_activities'"></activity-logger>
            <AIActivityLogger v-if="activeName === 'ai_activities'"></AIActivityLogger>
        </div>
    </div>
</template>

<script type="text/babel">
import ActivityLogger from "./ActivityLogger.vue";
import AIActivityLogger from "./AIActivityLogger.vue";

export default {
    name: 'Activity',
    components:{
        ActivityLogger,
        AIActivityLogger,
    },

    data() {
        return {
            activeName: 'overall_activities',
            me: this.appVars.me,
            ai_enabled: !!(this.appVars.open_ai_integration || this.appVars.fluent_bot_integration),
        }
    }
}
</script>

