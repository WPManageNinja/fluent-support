<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t("Support Staff") }}</h3>
            </div>
        </div>
        <div class="fs_table_container fs_support_staffs_container">
            <div class="fs_settings_sub_menu">
                <ul>
                    <li @click="active_tab = 'members'" :class="{ fs_sub_active: active_tab === 'members' }">
                        <span>{{ $t("Members") }}</span>
                    </li>
                    <li @click="active_tab = 'groups'" :class="{ fs_sub_active: active_tab === 'groups' }">
                        <span>{{ $t("Groups") }}</span>
                    </li>
                </ul>
            </div>
            <AgentMembers v-if="active_tab === 'members'" />
            <AgentGroups v-if="active_tab === 'groups'" />
        </div>
    </div>
</template>
<script type="text/babel">
import AgentMembers from "./AgentMembers.vue";
import AgentGroups from "./AgentGroups.vue";

export default {
    name: "support-staffs",
    components: {
        AgentMembers,
        AgentGroups
    },
    data() {
        return {
            active_tab: "members"
        };
    },
    mounted() {
        this.$setTitle(this.$t("Support Staff"));
    }
};
</script>
