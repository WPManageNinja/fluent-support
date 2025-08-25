<template>
    <div class="fc_box_email_settings">
        <el-skeleton v-if="!configs.length" :rows="5" animated/>
        <el-table v-else :data="configs" stripe>
            <el-table-column :label="translate('Title')" prop="title" width="400" />
            <el-table-column :label="translate('Status')">
                <template #default="scope">
                    <CircleCheck v-if="scope.row.status == 'yes'" style="width: 1.3em; height: 1.3em;" color="#67c23a" />
                    <CircleClose v-else style="width: 1.3em; height: 1.3em;" color="#f56c6c" />
                </template>
            </el-table-column>
            <el-table-column :label="translate('Manage')">
                <template #default="scope">
                    <el-button @click="editEmail(scope.row)"   text icon="EditPen" />
                </template>
            </el-table-column>
        </el-table>
    </div>

    <Teleport to="body">
        <modal :show="edit_modal" :title="active_email_settings.title" @close="edit_modal = false">
            <template #body>
                <h3>{{active_email_settings.description}}</h3>
                <el-form class="fs_email_settings_form" :data="active_email_settings" label-position="top">
                    <el-form-item>
                        <template #label>
                            <div class="fs_subject_label_wrapper">
                                <span>{{ translate('Email Subject') }}</span>
                                <el-dropdown
                                    v-if="active_email_settings.can_edit_subject !== 'no'"
                                    trigger="click"
                                    :popper-options="{ strategy: 'fixed' }"
                                    class="fs_subject_shortcode_dropdown"
                                >
                                    <el-button size="small" type="primary">
                                        {{translate('Shortcodes')}} <el-icon><ArrowDown /></el-icon>
                                    </el-button>
                                    <template #dropdown>
                                        <el-dropdown-menu class="fs_shortcode_dropdown">
                                            <el-dropdown-item
                                                v-for="(codeName, code) in shortCodes"
                                                :key="code"
                                                :value="code"
                                                @click="insertShortcodeInSubject"
                                            >
                                                {{ codeName }}
                                            </el-dropdown-item>
                                        </el-dropdown-menu>
                                    </template>
                                </el-dropdown>
                            </div>
                        </template>
                        <div class="fs_subject_input_wrapper">
                            <el-input
                                ref="subjectInput"
                                :disabled="active_email_settings.can_edit_subject == 'no'"
                                v-model="active_email_settings.email_subject"
                                :placeholder="translate('Email Subject')"
                            />
                        </div>
                        <p v-if="active_email_settings.can_edit_subject == 'no'">{{translate('can_not_edit_subject')}}</p>
                    </el-form-item>
                    <el-form-item :label="translate('Email Body')">
                        <wp-editor :editor_id="active_email_settings.key" v-model="active_email_settings.email_body" :editor_shortcodes="shortCodes"/>
                    </el-form-item>
                    <el-form-item>
                        <el-checkbox true-value="yes" false-value="no" v-model="active_email_settings.status">
                            {{translate('enable_email')}}
                        </el-checkbox>
                        <el-checkbox true-value="yes" false-value="no" v-if="active_email_settings.status=='yes' && allowed_mails_for_attachments.includes(active_email_settings.key)" v-model="active_email_settings.send_attachments">
                            {{translate('Send Attachments')}}
                        </el-checkbox>
                    </el-form-item>
                    <el-button @click="saveSettings" :disabled="saving" v-loading="saving" type="success">{{translate('Save Settings')}}</el-button>
                </el-form>
            </template>
        </modal>
    </Teleport>

</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
import Modal from "../../Pieces/Modal";
import { DocumentCopy } from '@element-plus/icons-vue';
import { computed, onMounted, reactive, toRefs, nextTick } from 'vue';
import {useFluentHelper, useNotify} from "@/admin/Composable/FluentFrameworkHelper";

export default {
    name: 'BoxEmailSettings',
    components: {
        WpEditor,
        Modal,
        DocumentCopy
    },
    props: ['box_id','mailbox'],

    setup(props){

        const {get, put, translate, handleError} = useFluentHelper();
        const { notify } = useNotify();

        const state = reactive({
            active_email: '',
            email_types: [],
            active_email_settings: false,
            configs: [],
            edit_modal:false,
            loading: false,
            saving: false,
            allowed_mails_for_attachments: [
                'ticket_created_email_to_customer',
                'ticket_created_email_to_admin',
                'ticket_replied_by_agent_email_to_customer',
                'ticket_replied_by_customer_email_to_admin'
            ]
        });

        function getConfigs() {

            state.loading = true;

            get(`mailboxes/${props.box_id}/email_configs`)
                .then((response) => {
                    state.configs = response.email_configs;
                    state.email_types = response.email_keys;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.saving = false;
                });
        }

        function editEmail(email) {
            get(`mailboxes/${props.box_id}/email_settings`,{
                email_type: email.key,
            })
                .then(response => {
                    state.active_email_settings = response.email_settings;
                    state.edit_modal = !state.edit_modal;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.loading = false;
                });

            state.active_email = email.key;
            state.active_email_settings = false;
        }

        function saveSettings() {

            state.saving = true;

            put(`mailboxes/${props.box_id}/email_settings`, {
                email_type: state.active_email_settings.key,
                email_settings: state.active_email_settings
            })
                .then((response) => {
                    notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right'
                    });
                    state.edit_modal=false;
                    state.loading = true;
                    getConfigs();
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.saving = false;
                });
        }

        function insertShortcodeInSubject(event) {
            const shortcode = event.target._value;

            nextTick(() => {
                const subjectInput = document.querySelector('.fs_subject_input_wrapper .el-input__inner');
                if (subjectInput) {
                    const start = subjectInput.selectionStart;
                    const end = subjectInput.selectionEnd;
                    const currentValue = state.active_email_settings.email_subject || '';

                    const before = currentValue.substring(0, start);
                    const after = currentValue.substring(end);
                    const newValue = before + shortcode + after;

                    state.active_email_settings.email_subject = newValue;

                    // Set cursor position after the inserted shortcode
                    nextTick(() => {
                        subjectInput.focus();
                        subjectInput.setSelectionRange(start + shortcode.length, start + shortcode.length);
                    });
                }
            });
        }


        const shortCodes = computed(() =>{
            if (
                state.active_email == 'ticket_created_email_to_customer' ||
                state.active_email == 'ticket_created_email_to_admin'
            ) {
                return {
                    '{{customer.first_name}}': translate('Customer First Name'),
                    '{{customer.last_name}}': translate('Customer Last Name'),
                    '{{customer.full_name}}': translate('Customer Full Name'),
                    '{{customer.email}}': translate('Customer Email'),
                    '{{ticket.admin_url}}': translate('Ticket Link(Agent)'),
                    '{{ticket.public_url}}': translate('Ticket Link(Customer)'),
                    '{{ticket.id}}': translate('Ticket ID'),
                    '{{ticket.title}}': translate('Ticket Title'),
                    '{{ticket.content}}': translate('Ticket Content'),
                    '{{business.name}}': translate('Business Name')
                }
            } else if (state.active_email== 'ticket_agent_on_change') {
                return {
                    '{{ticket.admin_url}}' : translate('Ticket Link(Agent)'),
                    '{{ticket.id}}': translate('Ticket ID'),
                    '{{ticket.title}}': translate('Ticket Title'),
                    '{{ticket.content}}': translate('Ticket Content'),
                    '{{business.name}}': translate('Business Name'),
                    '{{agent.first_name}}': translate('Assigned Agent First Name'),
                    '{{agent.last_name}}': translate('AssignedAgent Last Name'),
                    '{{agent.full_name}}': translate('Assigned Agent Full Name'),
                    '{{assigner.first_name}}': translate('Assigner First Name'),
                    '{{assigner.last_name}}': translate('Assigner Last Name'),
                    '{{assigner.full_name}}': translate('Assigner Full Name'),
                }
            }
            else {
                return {
                    '{{customer.first_name}}':  translate('Customer First Name'),
                    '{{customer.last_name}}': translate('Customer Last Name'),
                    '{{customer.full_name}}': translate('Customer Full Name'),
                    '{{customer.email}}': translate('Customer Email'),
                    '{{ticket.admin_url}}' : translate('Ticket Link(Agent)'),
                    '{{ticket.public_url}}': translate('Ticket Link(Customer)'),
                    '{{ticket.id}}': translate('Ticket ID'),
                    '{{ticket.title}}': translate('Ticket Title'),
                    '{{ticket.content}}': translate('Ticket Content'),
                    '{{business.name}}': translate('Business Name'),
                    '{{agent.first_name}}': translate('Agent First Name'),
                    '{{agent.last_name}}': translate('Agent Last Name'),
                    '{{agent.full_name}}': translate('Agent Full Name'),
                    '{{response.title}}': translate('Response Title'),
                    '{{response.content}}': translate('Response Content'),
                    '{{agent.title}}': translate('Agent Title'),
                }
            }
        })
    onMounted(()=> {
        getConfigs();
    })


    return {
        ...toRefs(state),
        getConfigs,
        editEmail,
        saveSettings,
        insertShortcodeInSubject,
        shortCodes,
        get,
        put,
        translate,
        handleError
    }
    },
}
</script>
