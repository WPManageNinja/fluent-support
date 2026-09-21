<template>
    <div class="fs_all_workflows">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <el-breadcrumb separator="/">
                    <el-breadcrumb-item :to="{ name: 'workflows' }">{{
                            $t("Workflows")
                        }}</el-breadcrumb-item>
                    <template v-if="workflow">
                        <el-breadcrumb-item>
                            <template v-if="!editingTitle">
                                {{ workflow.title }}
                                <el-button
                                    link
                                    type="primary"
                                    class="fs_inline_edit_btn"
                                    @click="startEditTitle"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 11 12" fill="none">
                                        <path d="M2.0484 8.38247L8.1336 2.29727L7.2852 1.44887L1.2 7.53407V8.38247H2.0484ZM2.5458 9.58247H0V7.03667L6.861 0.175672C6.97352 0.0631893 7.1261 0 7.2852 0C7.4443 0 7.59688 0.0631893 7.7094 0.175672L9.4068 1.87307C9.51928 1.98559 9.58247 2.13817 9.58247 2.29727C9.58247 2.45637 9.51928 2.60896 9.4068 2.72147L2.5458 9.58247ZM0 10.7825H10.8V11.9825H0V10.7825Z" fill="#525866"/>
                                    </svg>
                                </el-button>
                            </template>
                            <template v-else>
                                <el-input
                                    ref="titleInputRef"
                                    v-model="workflow.title"
                                    size="small"
                                    class="fs_inline_title_input fs_text_input"
                                    @blur="finishEditTitle"
                                    @keyup.enter="finishEditTitle"
                                />
                            </template>
                        </el-breadcrumb-item>
                    </template>
                </el-breadcrumb>
            </div>
            <div v-if="workflow" class="fs_box_actions fs-gap-16">
                <div class="fs_workflow_status">
                    <el-switch
                        @change="updateWorkFlow()"
                        active-value="published"
                        inactive-value="draft"
                        v-model="workflow.status"
                    />
                    <div class="fs_workflow_status_text">
                        <span class="fs_workflow_status_text_label">{{ $t("Status") }}: </span>  {{ workflow.status ? workflow.status.charAt(0).toUpperCase() + workflow.status.slice(1) : '' }}
                    </div>
                </div>
                <el-button
                    :disabled="saving"
                    v-loading="saving"
                    class="fs_filled_btn"
                    type="success"
                    @click="updateWorkFlow()"
                >{{ $t("Update Workflow") }}
                </el-button>
            </div>
        </div>
        <div class="fs_box_wrapper">
            <div
                v-if="workflow"
                v-loading="saving"
                class="fs_workflow_edit_wrap"
            >
                <div
                    v-if="workflow.trigger_type == 'automatic'"
                    class="fs_box fs_triggers_wrap"
                >
                    <div
                        class="fs_box_edit_header"
                    >
                        <div class="fs_box_head">
                            <h3>
                                {{ $t("Set Your Trigger & Conditions") }}
                            </h3>
                        </div>
                    </div>
                    <div class="fs_box_body fs_padded_24">
                        <el-form label-position="top" :data="workflow">
                            <trigger-mappers
                                :workflow_conditions="workflow_conditions"
                                :workflow="workflow"
                                :trigger_fields="trigger_fields"
                            />
                        </el-form>
                    </div>
                </div>
                <div v-else class="fs_box fs_triggers_wrap">
                    <div
                        class="fs_box_edit_header"
                    >
                        <div class="fs_box_head">
                            <h3>{{ $t("Manual Trigger") }}</h3>
                        </div>
                    </div>
                    <div class="fs_box_body fs_padded_20">
                        {{ $t("This is a") }}
                        <b>{{ $t("manual workflow") }}</b
                        >{{
                            $t(
                                ". You can set your actions and run the workflow actions from ticket page."
                            )
                        }}
                    </div>
                </div>

                <div class="fs_box fs_actions_wrap">
                    <div
                        class="fs_box_edit_header"
                    >
                        <div class="fs_box_head">
                            <h3>{{ $t("Workflow Actions (Tasks)") }}</h3>
                        </div>
                    </div>
                    <div class="fs_box_body fs_padded_24">
                        <action-mappers
                            :actions="actions"
                            :all_actions="filtred_action_fields"
                            @update:actions="val => actions = val"
                            @updateActionSequence="updateActionSequence"
                            @updateWorkFlow="updateWorkFlow"
                        />

                    </div>
                </div>
            </div>
            <div
                v-else
                class="fs_workflow_edit_wrap fs_padded_20"
            >
                <div>
                    <el-skeleton :rows="3" animated />
                    <el-skeleton :rows="4" animated />
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import ActionMappers from "./_ActionMappers";
import TriggerMappers from "./_TiggerMappers";

export default {
    name: "EditWorkFlow",
    components: {
        ActionMappers,
        TriggerMappers
    },
    props: ["workflow_id"],

    data() {
        return {
            workflow: false,
            workflow_conditions: [],
            actions: [],
            action_fields: {},
            trigger_fields: {},
            loading: false,
            saving: false,
            filtred_action_fields: {},
            actionSequence: [],
            editingTitle: false,
            originalTitle: '',
        };
    },

    created() {
        if (this.has_pro) {
            this.fetch();
        }
    },

    watch: {
        "workflow.trigger_key"(value) {
            const { action_fields } = this;
            const filtered = {};

            if (value === "fluent_support/ticket_closed") {
                const exclude = [
                    "fs_action_create_response",
                    "fs_action_close_ticket",
                    "fs_action_assign_agent",
                    "fs_block_customer",
                    "fs_delete_ticket",
                ];

                for (let field in action_fields) {
                    if (!exclude.includes(field)) {
                        filtered[field] = action_fields[field];
                    }
                }
            } else if (value === "fluent_support/ticket_created") {
                const exclude = [
                    "fs_action_remove_bookmarks",
                    "fs_action_remove_tags",
                ];

                for (let field in action_fields) {
                    if (!exclude.includes(field)) {
                        filtered[field] = action_fields[field];
                    }
                }
            } else if (value === "fluent_support/ticket_created") {
                Object.assign(filtered, action_fields);
            } else {
                Object.assign(filtered, action_fields);
            }

            this.filtred_action_fields = filtered;
        },
    },

    methods: {
        startEditTitle() {
            this.originalTitle = this.workflow.title || '';
            this.editingTitle = true;
            this.$nextTick(() => {
                if (this.$refs.titleInputRef && this.$refs.titleInputRef.focus) {
                    this.$refs.titleInputRef.focus();
                }
            });
        },
        finishEditTitle() {
            this.editingTitle = false;
            const currentTitle = this.workflow.title || '';
            const originalTitle = this.originalTitle || '';
            
            if (currentTitle !== originalTitle) {
                this.updateWorkFlow();
            }
        },
        async fetch() {
            this.loading = true;

            this.$get("workflows/" + this.workflow_id, {
                with: ["action_fields", "trigger_fields"],
            })
                .then((response) => {
                    if (response.workflow.trigger_type === "automatic") {
                        if (!response.workflow.settings) {
                            response.workflow.settings = {};
                        }
                        this.workflow_conditions = response.workflow.settings.conditions || [];
                    }
                    this.workflow = response.workflow;
                    this.actions = response.actions;
                    this.trigger_fields = response.trigger_fields;
                    this.action_fields = response.action_fields;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        async updateWorkFlow() {
            this.saving = true;
            const workFlow = JSON.parse(JSON.stringify(this.workflow));

            if (workFlow.trigger_type === "automatic") {
                if (!workFlow.settings) {
                    workFlow.settings = {};
                }
                workFlow.settings.conditions = this.workflow_conditions || [];
            }

            this.$post("workflows/" + this.workflow_id, {
                actions: this.actions,
                workflow: workFlow,
            })
                .then((response) => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });
                    if (response.workflow.trigger_type === "automatic") {
                        if (!response.workflow.settings) {
                            response.workflow.settings = {};
                        }
                        this.workflow_conditions =
                            response.workflow.settings.conditions || [];
                    }
                    this.workflow = response.workflow;
                    this.actions = response.actions;
                    if ("published" === this.workflow.status) {
                        this.renewOptions("workflows/options");
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        async updateActionSequence(sequence) {
            this.actionSequence = sequence;
            this.$post("workflows/action-sequence", {
                workflow_id: this.workflow_id,
                action_sequence: sequence,
            })
                .then(() => {})
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
    },
};
</script>
