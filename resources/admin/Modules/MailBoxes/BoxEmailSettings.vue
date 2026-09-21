<template>
    <div class="fs_box_email_settings">
        <!-- Header Section -->
        <div class="fs_mailbox_settings_header">
            <div class="fs_mailbox_settings_header_left">
                <h1 class="fs_main_header_text">{{ $t('Email Settings') }}</h1>
                <p class="fs_sub_text">{{ $t('The following settings will be applied to the newly created') }}</p>
            </div>
        </div>

        <div v-if="!configs.length" class="fs_skeleton_loader">
            <el-skeleton :rows="5" animated/>
        </div>

        <!-- Custom Table -->
        <div v-else class="fs_email_settings_table">
            <table class="fs_table">
                <thead>
                    <tr>
                        <th class="fs_table_header_cell fs_title_col">{{ $t('Title') }}</th>
                        <th class="fs_table_header_cell fs_status_col">{{ $t('Status') }}</th>
                        <th class="fs_table_header_cell fs_manage_col">{{ $t('Manage') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="config in configs" :key="config.key" class="fs_table_row">
                        <td class="fs_table_cell">{{ config.title }}</td>
                        <td class="fs_table_cell">
                            <div :class="['fs_status_badge', config.status === 'yes' ? 'fs_status_active' : 'fs_status_inactive']">
                                <span>{{ config.status === 'yes' ? $t('Active') : $t('Closed') }}</span>
                            </div>
                        </td>
                        <td class="fs_table_cell">
                            <el-button
                                @click="editEmail(config)"
                                text
                                class="fs_edit_btn"
                            >
                                <img :src="appVars.asset_url + 'images/edit.svg'" alt="">
                            </el-button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <el-dialog
        :append-to-body="true"
        :title="active_email_settings && active_email_settings.title ? active_email_settings.title : $t('Email Settings')"
        v-model="edit_modal"
        v-if="active_email_settings"
        width="60%"
        class="fs_dialog fs_email_settings_modal"
    >
        <el-form label-position="top" :data="active_email_settings">
            <h3 class="fs_email_settings_modal_title" v-if="active_email_settings.description">{{active_email_settings.description}}</h3>
            <el-form-item class="fs_form_item">
                <template #label>
                    <div class="fs_field_label">
                        <span style="display: flex; align-items: center; gap: 4px;">
                            {{ $t('Email Subject') }}
                            <span
                                class="fs-email-subject-tooltip"
                                :data-tooltip="$t('can_not_edit_subject')"
                            >
                                <el-icon><InfoFilled /></el-icon>
                            </span>
                        </span>
                        <el-dropdown
                            v-if="active_email_settings.can_edit_subject !== 'no'"
                            trigger="click"
                            :popper-options="{ strategy: 'fixed' }"
                            class="fs_subject_shortcode_dropdown"
                        >
                            <el-button size="small" type="primary">
                                {{$t('Smart Codes')}} <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu class="fs_global_dropdown fs_shortcode_dropdown fs_table_more_actions_dropdown_menu">
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
                <el-input
                    ref="subjectInput"
                    class="fs_text_input fs_text_input_40 fs_subject_input_wrapper"
                    :disabled="active_email_settings.can_edit_subject == 'no'"
                    v-model="active_email_settings.email_subject"
                    :placeholder="$t('Email Subject')"
                />
            </el-form-item>

            <el-form-item :label="$t('Email Body')" class="fs_form_item">
                <wp-editor :editor_id="active_email_settings.key" v-model="active_email_settings.email_body" :editor_shortcodes="shortCodes"/>
            </el-form-item>

            <el-form-item class="fs_form_item">
                <div class="fs_checkbox_group">
                    <el-checkbox true-value="yes" false-value="no" v-model="active_email_settings.status">
                        {{$t('enable_email')}}
                    </el-checkbox>
                    <el-checkbox
                        true-value="yes"
                        false-value="no"
                        v-if="active_email_settings.status=='yes' && allowed_mails_for_attachments.includes(active_email_settings.key)"
                        v-model="active_email_settings.send_attachments"
                    >
                        {{$t('Send Attachments')}}
                    </el-checkbox>
                </div>
            </el-form-item>

            <div class="fs_smartcodes_section">
                <span>{{$t('Available Shortcodes')}}</span>
                <div class="fs_smartcodes_list">
                    <div
                        v-for="(codeName, code) in shortCodes"
                        :key="code"
                        class="fs_smartcode_item"
                        @click="insertShortcodeInSubject(code)"
                    >
                        <span class="fs_smartcode_text">{{ codeName }}: {{ code }}</span>
                    </div>
                </div>
            </div>
        </el-form>

        <template #footer>
            <span class="fs_dialog_footer">
                <el-button
                    class="fs_outline_btn"
                    @click="() => edit_modal = false">
                    {{ $t('Cancel') }}
                </el-button>

                <el-button
                    v-loading="saving"
                    :disabled="saving"
                    @click="saveSettings"
                    class="fs_filled_btn"
                >
                    {{ $t('Save Settings') }}
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
import Modal from "../../Pieces/Modal";

export default {
    name: 'BoxEmailSettings',
    components: {
        WpEditor,
        Modal
    },
    props: ['box_id', 'mailbox'],

    data() {
        return {
            active_email: '',
            email_types: [],
            active_email_settings: false,
            configs: [],
            edit_modal: false,
            loading: false,
            saving: false,
            allowed_mails_for_attachments: [
                'ticket_created_email_to_customer',
                'ticket_created_email_to_admin',
                'ticket_created_by_agent_email_to_customer',
                'ticket_created_by_agent_on_behalf_email_to_customer',
                'ticket_replied_by_agent_email_to_customer',
                'ticket_replied_by_customer_email_to_admin'
            ]
        };
    },

    computed: {
        shortCodes() {
            if (
                this.active_email == 'ticket_created_email_to_customer' ||
                this.active_email == 'ticket_created_email_to_admin'
            ) {
                return {
                    '{{customer.first_name}}': this.$t('Customer First Name'),
                    '{{customer.last_name}}': this.$t('Customer Last Name'),
                    '{{customer.full_name}}': this.$t('Customer Full Name'),
                    '{{customer.email}}': this.$t('Customer Email'),
                    '{{ticket.admin_url}}': this.$t('Ticket Link(Agent)'),
                    '{{ticket.public_url}}': this.$t('Ticket Link(Customer)'),
                    '{{ticket.id}}': this.$t('Ticket ID'),
                    '{{ticket.public_id}}': this.$t('Public Ticket ID'),
                    '{{ticket.title}}': this.$t('Ticket Title'),
                    '{{ticket.content}}': this.$t('Ticket Content'),
                    '{{business.name}}': this.$t('Business Name')
                }
            } else if (this.active_email == 'ticket_agent_on_change') {
                return {
                    '{{ticket.admin_url}}': this.$t('Ticket Link(Agent)'),
                    '{{ticket.id}}': this.$t('Ticket ID'),
                    '{{ticket.public_id}}': this.$t('Public Ticket ID'),
                    '{{ticket.title}}': this.$t('Ticket Title'),
                    '{{ticket.content}}': this.$t('Ticket Content'),
                    '{{business.name}}': this.$t('Business Name'),
                    '{{agent.first_name}}': this.$t('Assigned Agent First Name'),
                    '{{agent.last_name}}': this.$t('AssignedAgent Last Name'),
                    '{{agent.full_name}}': this.$t('Assigned Agent Full Name'),
                    '{{assigner.first_name}}': this.$t('Assigner First Name'),
                    '{{assigner.last_name}}': this.$t('Assigner Last Name'),
                    '{{assigner.full_name}}': this.$t('Assigner Full Name'),
                }
            } else {
                return {
                    '{{customer.first_name}}': this.$t('Customer First Name'),
                    '{{customer.last_name}}': this.$t('Customer Last Name'),
                    '{{customer.full_name}}': this.$t('Customer Full Name'),
                    '{{customer.email}}': this.$t('Customer Email'),
                    '{{ticket.admin_url}}': this.$t('Ticket Link(Agent)'),
                    '{{ticket.public_url}}': this.$t('Ticket Link(Customer)'),
                    '{{ticket.id}}': this.$t('Ticket ID'),
                    '{{ticket.public_id}}': this.$t('Public Ticket ID'),
                    '{{ticket.title}}': this.$t('Ticket Title'),
                    '{{ticket.content}}': this.$t('Ticket Content'),
                    '{{business.name}}': this.$t('Business Name'),
                    '{{agent.first_name}}': this.$t('Agent First Name'),
                    '{{agent.last_name}}': this.$t('Agent Last Name'),
                    '{{agent.full_name}}': this.$t('Agent Full Name'),
                    '{{response.title}}': this.$t('Response Title'),
                    '{{response.content}}': this.$t('Response Content'),
                    '{{agent.title}}': this.$t('Agent Title'),
                }
            }
        }
    },

    methods: {
        getConfigs() {
            this.loading = true;

            this.$get(`mailboxes/${this.box_id}/email_configs`)
                .then((response) => {
                    this.configs = response.email_configs;
                    this.email_types = response.email_keys;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        editEmail(email) {
            this.$get(`mailboxes/${this.box_id}/email_settings`, {
                email_type: email.key,
            })
                .then(response => {
                    this.active_email_settings = response.email_settings;
                    this.edit_modal = !this.edit_modal;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });

            this.active_email = email.key;
            this.active_email_settings = false;
        },

        saveSettings() {
            this.saving = true;

            this.$put(`mailboxes/${this.box_id}/email_settings`, {
                email_type: this.active_email_settings.key,
                email_settings: this.active_email_settings
            })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right'
                    });
                    this.edit_modal = false;
                    this.loading = true;
                    this.getConfigs();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        insertShortcodeInSubject(payload) {
            let shortcode = '';

            if (typeof payload === 'string') {
                shortcode = payload;
            } else if (payload && payload.target && '_value' in payload.target) {
                shortcode = payload.target._value;
            } else if (payload && payload.target && 'value' in payload.target) {
                shortcode = payload.target.value;
            }

            if (!shortcode) {
                return;
            }

            this.$nextTick(() => {
                const subjectInput = document.querySelector('.fs_subject_input_wrapper .el-input__inner');
                if (subjectInput) {
                    const start = subjectInput.selectionStart;
                    const end = subjectInput.selectionEnd;
                    const currentValue = this.active_email_settings.email_subject || '';

                    const before = currentValue.substring(0, start);
                    const after = currentValue.substring(end);
                    const newValue = before + shortcode + after;

                    this.active_email_settings.email_subject = newValue;

                    // Set cursor position after the inserted shortcode
                    this.$nextTick(() => {
                        subjectInput.focus();
                        subjectInput.setSelectionRange(start + shortcode.length, start + shortcode.length);
                    });
                }
            });
        }
    },

    mounted() {
        this.getConfigs();
    }
};
</script>
