<template>
    <div v-if="app_ready" class="fs_conditional_group">
        <div v-for="(setting, settingIndex) in settings" :key="settingIndex">
            <div class="fs_condition_line">
                <div class="fs_cond_block fs_cond_data">
                    <el-select
                        @change="maybeValueReset(setting, 'data_key')"
                        v-model="setting.data_key"
                        class="fs_select_field"
                    >
                        <el-option-group
                            v-for="(group, groupKey) in conditional_fields"
                            :key="groupKey"
                            :label="groupKey"
                        >
                            <el-option
                                v-for="(
                                    condition, conditionKey
                                ) in group.children"
                                :key="condition.condition_key"
                                :label="condition.title"
                                :value="condition.condition_key"
                            />
                        </el-option-group>
                    </el-select>
                </div>
                <template
                    v-if="setting.data_key && all_conditions[setting.data_key]"
                >
                    <div class="fs_cond_block fs_cond_operator">
                        <el-select
                            @change="maybeValueReset(setting, 'operator')"
                            :placeholder="$t('Operator')"
                            v-model="setting.data_operator"
                            class="fs_select_field"
                        >
                            <template
                                v-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'string'
                                "
                            >
                                <el-option
                                    value="contains"
                                    :label="$t('Contains')"
                                ></el-option>
                                <el-option
                                    value="not_contains"
                                    :label="$t('Not contains')"
                                ></el-option>
                            </template>
                            <template
                                v-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'single_dropdown'
                                "
                            >
                                <el-option
                                    value="equal"
                                    :label="$t('Equal')"
                                ></el-option>
                                <el-option
                                    value="not_equal"
                                    :label="$t('Not Equal')"
                                ></el-option>
                            </template>

                            <template
                                v-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'multiple_select' ||
                                    all_conditions[setting.data_key]
                                        .data_type == 'remote_select'
                                "
                            >
                                <el-option
                                    value="includes_in"
                                    :label="$t('Includes In')"
                                ></el-option>
                                <el-option
                                    value="not_includes_in"
                                    :label="$t('Not Includes In')"
                                ></el-option>
                            </template>

                            <template
                                v-else-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'yes_no'
                                "
                            >
                                <el-option
                                    v-for="(
                                        option, optionKey
                                    ) in all_conditions[setting.data_key]
                                        .options"
                                    :value="optionKey"
                                    :key="optionKey"
                                    :label="option"
                                ></el-option>
                            </template>

                            <template
                                v-else-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'date_range'
                                "
                            >
                                <el-option
                                    value="<"
                                    :label="$t('Is before')"
                                ></el-option>
                                <el-option
                                    value=">"
                                    :label="$t('Is after')"
                                ></el-option>
                                <el-option
                                    value="range"
                                    :label="$t('Is between')"
                                ></el-option>
                            </template>
                            <template
                                v-else-if="
                                    all_conditions[setting.data_key]
                                        .data_type == 'time_range'
                                "
                            >
                                <el-option
                                    value="range"
                                    :label="$t('Is between')"
                                ></el-option>
                            </template>
                        </el-select>
                    </div>
                    <div
                        v-if="setting.data_operator"
                        class="fs_cond_block fc_cond_value"
                    >
                        <el-input
                            v-if="
                                all_conditions[setting.data_key].data_type ==
                                'string'
                            "
                            type="text"
                            v-model="setting.data_value"
                            :placeholder="$t('Condition Value')"
                            class="fs_text_input"
                        />
                        <el-select
                            v-if="
                                all_conditions[setting.data_key].data_type ==
                                'single_dropdown'
                            "
                            v-model="setting.data_value"
                            class="fs_select_field"
                        >
                            <el-option
                                v-for="(option, optionKey) in all_conditions[
                                    setting.data_key
                                ].options"
                                :value="optionKey"
                                :key="optionKey"
                                :label="option"
                            ></el-option>
                        </el-select>
                        <el-select
                            v-if="
                                all_conditions[setting.data_key].data_type ==
                                'multiple_select'
                            "
                            v-model="setting.data_value"
                            multiple
                            collapse-tags
                            class="fs_select_field"
                        >
                            <el-option
                                v-for="(option, optionKey) in all_conditions[
                                    setting.data_key
                                ].options"
                                :value="optionKey"
                                :key="optionKey"
                                :label="option"
                            ></el-option>
                        </el-select>
                        <remote-commerce-select
                            v-if="
                                all_conditions[setting.data_key].data_type ==
                                'remote_select'
                            "
                            :key="setting.data_key"
                            v-model="setting.data_value"
                            :provider="
                                all_conditions[setting.data_key].option_provider
                            "
                            :option-type="
                                all_conditions[setting.data_key].option_type
                            "
                        />
                        <template
                            v-else-if="
                                all_conditions[setting.data_key].data_type ==
                                'date_range'
                            "
                        >
                            <el-date-picker
                                value-format="YYYY-MM-DD HH:mm"
                                v-if="
                                    setting.data_operator == '>' ||
                                    setting.data_operator == '<'
                                "
                                v-model="setting.data_value"
                                type="datetime"
                                :placeholder="$t('Pick a day')"
                            />
                            <div
                                v-else-if="setting.data_operator == 'range'"
                                class="fs_range_inputs"
                            >
                                <el-date-picker
                                    value-format="YYYY-MM-DD HH:mm"
                                    v-model="setting.value_1"
                                    type="datetime"
                                    :placeholder="$t('From')"
                                />
                                <el-date-picker
                                    value-format="YYYY-MM-DD HH:mm"
                                    v-model="setting.value_2"
                                    type="datetime"
                                    :placeholder="$t('To')"
                                />
                            </div>
                        </template>
                        <template
                            v-else-if="
                                all_conditions[setting.data_key].data_type ==
                                'time_range'
                            "
                        >
                            <div
                                v-if="setting.data_operator == 'range'"
                                class="fs_range_inputs"
                            >
                                <el-time-select
                                    start="00:00"
                                    step="00:10"
                                    end="23:59"
                                    v-model="setting.value_1"
                                    :placeholder="$t('From')"
                                />
                                <el-time-select
                                    start="00:00"
                                    step="00:10"
                                    end="23:59"
                                    v-model="setting.value_2"
                                    :placeholder="$t('To')"
                                />
                            </div>
                        </template>
                    </div>
                </template>
                <div class="fs_cond_block fs_cond_action">
                    <div class="fs_delete_condition_btn" @click="removeCondition(settingIndex)">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.25 3H15V4.5H13.5V14.25C13.5 14.4489 13.421 14.6397 13.2803 14.7803C13.1397 14.921 12.9489 15 12.75 15H2.25C2.05109 15 1.86032 14.921 1.71967 14.7803C1.57902 14.6397 1.5 14.4489 1.5 14.25V4.5H0V3H3.75V0.75C3.75 0.551088 3.82902 0.360322 3.96967 0.21967C4.11032 0.0790176 4.30109 0 4.5 0H10.5C10.6989 0 10.8897 0.0790176 11.0303 0.21967C11.171 0.360322 11.25 0.551088 11.25 0.75V3ZM12 4.5H3V13.5H12V4.5ZM5.25 6.75H6.75V11.25H5.25V6.75ZM8.25 6.75H9.75V11.25H8.25V6.75ZM5.25 1.5V3H9.75V1.5H5.25Z" fill="#525866"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="fs_group_footer">
            <button
                type="button"
                class="fs_or_btn_wrapper"
                @click="addMore()"
            >
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.5 4.5V0H6V4.5H10.5V6H6V10.5H4.5V6H0V4.5H4.5Z" fill="currentColor"/>
                </svg>
                <span class="fs_or_text">{{ $t("OR") }}</span>
            </button>
        </div>
    </div>
</template>

<script type="text/babel">
import RemoteCommerceSelect from "./_RemoteCommerceSelect";

export default {
    name: "TriggerConditionGroup",
    props: ["settings", "conditional_fields", "all_conditions"],
    emits: ["deleteGroup"],
    components: {
        RemoteCommerceSelect,
    },
    data() {
        return {
            app_ready: false,
        };
    },
    mounted() {
        if (!this.settings || !this.settings.length) {
            this.settings.push({
                data_key: "",
                data_operator: "",
                data_value: "",
            });
        }

        this.app_ready = true;
    },
    methods: {
        addMore() {
            this.settings.push({
                data_key: "",
                data_operator: "",
                data_value: "",
            });
        },
        maybeValueReset(setting, type) {
            if (type === "data_key") {
                setting.data_operator = "";
                setting.data_value = "";
            } else if (
                type === "operator" &&
                setting.data_key &&
                setting.data_operator
            ) {
                if (setting.data_operator === "range") {
                    setting.value_1 = "";
                    setting.value_2 = "";
                }
            }
        },
        removeCondition(index) {
            if (this.settings.length == 1) {
                this.$emit("deleteGroup");
            } else {
                this.settings.splice(index, 1);
            }
        },
        deleteSection() {
            this.$emit("deleteGroup");
        },
    },
};
</script>
