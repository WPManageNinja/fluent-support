<template>
    <span
        v-if="activities && activities.length"
        class="fs_inline_avatars avatars_small fs_live_activity_avatars"
    >
        <span>
            <img
                v-for="activity in activities.slice(0, 2)"
                :key="activity.id"
                :title="activity.full_name || activity.name"
                :src="activity.photo"
                :alt="activity.full_name || $t('Agent')"
            />
        </span>
        <el-popover
            v-if="activities.length > 2"
            placement="top"
            trigger="hover"
            popper-class="fs_popover fs_live_activity_popover"
        >
            <div class="fs_live_activity_popover_content">
                <img
                    v-for="activity in activities.slice(1)"
                    :key="'more-' + activity.id"
                    :title="activity.full_name || activity.name"
                    :src="activity.photo"
                    :alt="activity.full_name || $t('Agent')"
                />
            </div>
            <template #reference>
                <span class="fs_live_activity_count">
                    +{{ activities.length - 2 }}
                </span>
            </template>
        </el-popover>
    </span>
</template>

<script>
export default {
    name: 'LiveActivityAvatars',
    props: {
        activities: {
            type: Array,
            default: () => [],
        },
    },
};
</script>
