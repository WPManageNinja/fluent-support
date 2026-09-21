<template>
    <div class="fs_trigger_mappers">
        <el-form-item :label="$t('Workflow Trigger')">
            <el-select v-model="workflow.trigger_key" class="fs_select_field">
                <el-option
                    v-for="(trigger, triggerKey) in trigger_fields.triggers"
                    :value="triggerKey"
                    :key="triggerKey"
                    :label="trigger.title"
                ></el-option>
            </el-select>
        </el-form-item>

        <div
            class="fs_trigger_conditions"
            v-if="workflow.trigger_key && app_ready"
        >
            <h3>{{ $t("Conditions") }}</h3>
            <div
                class="fs_each_cond_group"
                v-for="(conditionGroup, condIndex) in workflow_conditions"
                :key="condIndex"
            >
                <trigger-conditional-group
                    @deleteGroup="deleteGroup(condIndex)"
                    :conditional_fields="conditional_fields"
                    :all_conditions="trigger_fields.conditions"
                    :settings="conditionGroup"
                />
                <div
                    v-if="condIndex + 1 != workflow_conditions.length"
                    class="fs_filter_separator"
                >
                    <div class="fs_condition_sign">{{ $t("AND") }}</div>
                </div>
            </div>

            <div class="fs_filter_separator" @click="addMore()">
                <div class="fs_condition_sign fs_button">
                    <svg class="fs_add_and_icon" width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.5 4.5V0H6V4.5H10.5V6H6V10.5H4.5V6H0V4.5H4.5Z" fill="#0E121B"/>
                    </svg>
                    {{ $t("AND") }}
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import each from "lodash/each";
import TriggerConditionalGroup from "./_TriggerConditionalGroup";

export default {
    name: "TriggerMappers",
    props: ["workflow", "trigger_fields", "workflow_conditions"],
    components: {
        TriggerConditionalGroup,
    },
    data() {
        return {
            app_ready: false,
        };
    },
    computed: {
        conditional_fields() {
            if (!this.workflow.trigger_key) {
                return [];
            }

            const validKeys =
                this.trigger_fields.triggers[this.workflow.trigger_key]
                    .supported_conditions;

            const validConditions = {};

            each(this.trigger_fields.conditions, (condition, conditionKey) => {
                if (validKeys.indexOf(conditionKey) !== -1) {
                    if (!validConditions[condition.group]) {
                        validConditions[condition.group] = {
                            title: condition.group,
                            children: [],
                        };
                    }
                    condition.condition_key = conditionKey;
                    validConditions[condition.group].children.push(condition);
                }
            });

            return validConditions;
        }
    },
    mounted() {
        this.app_ready = true;
    },
    methods: {
        addMore() {
            this.workflow_conditions.push([
                {
                    data_key: "",
                    data_operator: "",
                    data_value: "",
                },
            ]);
        },
        deleteGroup(condIndex) {
            if (this.workflow_conditions.length > 1) {
                this.workflow_conditions.splice(condIndex, 1);
            } else {
                this.workflow_conditions.splice(condIndex, 1);
                this.workflow_conditions.push([
                    {
                        data_key: "",
                        data_operator: "",
                        data_value: "",
                    },
                ]);
            }
        },
    },
};
</script>
