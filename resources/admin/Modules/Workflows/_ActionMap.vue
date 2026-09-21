<template>
    <div v-loading="loading" class="fs_trigger_view">
        <el-collapse
            v-model="localActiveName"
        >
            <el-collapse-item
                :name="action.activeName"
                class="fs_actions_board"
            >
                <template #title>
                    <el-tooltip
                        class="box-item"
                        effect="dark"
                        :content="$t('Drag and Drop Action')"
                        placement="top-start"
                    >
                        <img :src="appVars.asset_url + '/images/icons/dragAndDrop.svg'">
                    </el-tooltip>
                    <div
                        class="fs_trigger_name"
                    >
                        {{ actions[action.action_name]?.title || action.title || '' }}
                    </div>
                </template>
                <div  class="fs_trigger_details">
                    <div class="fs_trigger_editor">
                        <h3>{{ $t("Action") }}</h3>
                        <el-select
                            @change="triggerEventChanged()"
                            :placeholder="$t('Select Integration Event')"
                            v-model="action.action_name"
                            class="fs_select_field"
                        >
                            <el-option
                                v-for="(actionItem, triggerName) in actions"
                                :key="triggerName"
                                :value="triggerName"
                                :label="actionItem.title"
                            />
                        </el-select>
                        <div v-if="action.action_name && actions[action.action_name] && builder_ready">
                            <form-builder
                                :key="dragKey"
                                :fields="actions[action.action_name]?.fields || []"
                                :form-data="action.settings"
                            >
                                <template v-slot:form_end>
                                    <el-form-item :label="$t('Action Title')" class="fs_action_title_form_item">
                                        <el-input
                                            link
                                            :placeholder="$t('Action Title')"
                                            v-model="action.title"
                                        ></el-input>
                                    </el-form-item>
                                </template>
                            </form-builder>

                            <div class="fs_action_footer">
                                <el-popconfirm
                                    :width="310"
                                    :title="$t('delete_action_warning')"
                                    @confirm="deleteAction()"
                                >
                                    <template #reference>
                                        <el-button
                                            class="fs_delete_action_btn"
                                            link
                                        >
                                            <el-icon><Delete /></el-icon>
                                            <span>{{ $t("Delete Action") }}</span>
                                        </el-button>
                                    </template>
                                    <template #actions="{ confirm, cancel }">
                                        <div class="fs_popconfirm_actions">
                                            <el-button class="fs_outline_btn" size="small" @click="cancel">{{ $t("Cancel") }}</el-button>
                                            <el-button
                                                class="fs_filled_btn"
                                                size="small"
                                                @click="confirm"
                                            >
                                                {{ $t("Yes, Delete this Action") }}
                                            </el-button>
                                        </div>
                                    </template>
                                </el-popconfirm>
                                <el-button 
                                    class="fs_save_action_btn" 
                                    @click="emitSave()" 
                                    type="primary"
                                >
                                    {{ $t("Save") }}
                                </el-button>
                            </div>
                        </div>
                    </div>
                </div>
            </el-collapse-item>
        </el-collapse>
    </div>
</template>

<script type="text/babel">
import FormBuilder from "../../Pieces/FormElements/_FormBuilder";

export default {
    name: "ActionMap",
    props: ["action", "actions", "activeName", "dragKey"],
    components: {
        FormBuilder
    },
    data() {
        return {
            settings_fields: {},
            loading: false,
            builder_ready: true,
            localActiveName: this.activeName,
        };
    },
    watch: {
        activeName(newVal) {
            this.localActiveName = newVal;
        },
    },
    mounted() {
        if (!this.action.settings) {
            this.action.settings = {};
        }
    },
    methods: {
        triggerEventChanged() {
            if (!this.action.action_name) {
                this.action.settings = {};
                return false;
            }
            
            if (!this.actions[this.action.action_name]) {
                return false;
            }
            
            this.builder_ready = false;
            const selectedAction = JSON.parse(
                JSON.stringify(this.actions[this.action.action_name])
            );
            this.action.settings = selectedAction.settings_defaults || {};
            this.action.title = selectedAction.title || '';
            setTimeout(() => {
                this.builder_ready = true;
            }, 100);
        },
        emitSave() {
            this.action.is_open = !this.action.is_open;
            this.$emit("update");
        },
        deleteAction() {
            this.action.is_open = !this.action.is_open;
            this.$emit("deleteAction");
        },
    },
};
</script>
