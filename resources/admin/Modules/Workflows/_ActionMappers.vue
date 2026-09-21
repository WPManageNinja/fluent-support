<template>
    <div class="fs_triggers_wrap">
        <div>
            <draggable
                v-if="actionsParam.length"
                v-model="actionsParam"
                ghost-class="ghost"
                class="fs_all_triggers"
                item-key="id"
                @end="onDragEnd"
            >
                <template #item="{ element, index }">
                    <div :key="element.id" class="fs_trigger_map">
                        <action-map
                            :dragKey="dragKey"
                            @deleteAction="removeAction(index)"
                            @update="triggerUpdate"
                            :action="element"
                            :activeName="element.activeName"
                            :actions="all_actions"
                        />
                    </div>
                </template>
            </draggable>
        </div>
        <action-adder
            @success="appendAction"
            v-show="show_adder"
            :all_actions="all_actions"
            :actions_param_size="actionsParam.length"
        />
        <el-button
            type="info"
            @click="show_adder = true"
            v-if="!show_adder"
            class="fs_outline_btn fs_add_another_action_btn"
        >
            <svg class="fs_add_icon" width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4.5 4.5V0H6V4.5H10.5V6H6V10.5H4.5V6H0V4.5H4.5Z" fill="currentColor"/>
            </svg>
            <span>{{ $t("Add Another Action") }}</span>
        </el-button>
    </div>
</template>

<script>
import ActionMap from "./_ActionMap.vue";
import draggable from "vuedraggable";
import ActionAdder from "./_ActionAdder.vue";

export default {
    name: "ActionMappers",
    props: ["workflow_id", "actions", "all_actions"],
    components: {
        ActionMap,
        ActionAdder,
        draggable,
    },
    data() {
        return {
            actionsParam: [],
            show_adder: false,
            dragKey: Date.now(),
        };
    },
    watch: {
        actionsParam: {
            handler(newVal, oldVal) {
                if (oldVal && newVal.length === oldVal.length && JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
                    const updatedSequence = newVal.map(action => action.id);
                    this.$emit("updateActionSequence", updatedSequence);
                }

                this.emitUpdatedActions();
            },
            deep: true,
        },
    },
    mounted() {
        if (!this.actions.length) {
            this.show_adder = true;
        }

        this.actionsParam = JSON.parse(JSON.stringify(this.actions));
    },
    methods: {
        appendAction(action) {
            this.show_adder = false;
            const newAction = {
                ...action,
                workflow_id: this.workflow_id,
                activeName: `action_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            };
            this.actionsParam.push(newAction);
        },
        removeAction(actionIndex) {
            this.actionsParam.splice(actionIndex, 1);
            this.$emit("updateActions", this.actionsParam);
        },
        onDragEnd() {
            this.dragKey = Date.now();
            this.$emit("updateActions", this.actionsParam);
        },
        triggerUpdate() {
            this.$emit("updateWorkFlow");
        },
        emitUpdatedActions() {
            this.$emit("update:actions", this.actionsParam);
        },
    },
};
</script>
