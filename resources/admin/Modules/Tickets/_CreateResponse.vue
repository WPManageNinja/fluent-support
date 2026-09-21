<template>
    <div class="fs_create_response" :class="'fs_reply_type_'+type">
        <div class="fs_row" v-if="ticket.source === 'email'">
            <el-form-item :label="$t('Cc')" v-if="selected_cc?.length || show_cc_option">
                <el-select v-model="selected_cc" multiple filterable remote
                           :placeholder="$t('Please enter email')"
                           allow-create
                           class="fs_select_field fs_cc_selector"
                           default-first-option
                           :reserve-keyword="false"
                           @change="handleCcChange"
                           :loading="loading"
                           style="padding-left: 10px"
                >
                    <el-option
                        v-for="item in cc_emails"
                        :key="item"
                        :label="item"
                        :value="item"
                    />
                </el-select>
            </el-form-item>
        </div>
        <wp-editor :autofocus="true" v-if="editor_ready" v-model="response_body" :height="170" :ticketId="ticket.id" :productID="ticket.product_id" :is_agent="is_agent" :is_direct_paste="true" :editor_shortcodes="shortcodes" :openAIIntegration="openAIIntegration" :fluentBotIntegration="fluentBotIntegration"
                   :show-fluent-booking="type === 'response' && !!appVars.fluent_booking?.active"
                   :show-saved-replies="true" :show-cc-toggle-button="ticket.source === 'email' && type !== 'note'" :add_cc="selected_cc?.length > 0 || show_cc_option" @toggleCcOption="toggleCcOption" @toggleFluentBot="$emit('toggleFluentBot')"/>
        <div class="fs_row fs_attachment_section">
            <div class="fs_full">
                <attachment-form
                    is_agent="yes"
                    v-if="appVars.has_file_upload"
                    :ticket="ticket"
                    :attachments="attachments"
                    @upload-state-change="updateAttachmentUploadState"
                />
            </div>
        </div>
        <div class="fs_draft_response_actions fs_row fs_actions_row">
            <div class="fs_half fs_left_section">
                <div v-if="type === 'response'" class="fs_informational_reply_container">
                    <el-tooltip effect="dark" :content="$t('informational_reply_info')" placement="bottom" popper-class="fs_tooltip">
                        <el-checkbox
                            v-model="informational_reply"
                            class="fs_informational_reply"
                        >
                            {{ $t('Send as Informational Reply')}}
                        </el-checkbox>
                    </el-tooltip>
                </div>
                <p v-if="type== 'note'" class="fs_note_warning">{{ $t('You are adding internal Note. Only support staff can see this note.') }}</p>
            </div>
            <div class="fs_half fs_right_section">
                <div class="fs_response_actions fs_button_group">
                    <el-button v-if="type != 'note' && type !== 'draft_response'" :disabled="creating || attachmentUploadState.uploading" @click="create('yes')" size="large"
                               class="fs_reply_close_btn">
                        {{ $t('Reply and Close Ticket') }}
                    </el-button>
                    <el-button class="fs_reply_btn fs_filled_btn" v-loading="creating" :disabled="creating || attachmentUploadState.uploading" @click="create('no')"
                               >
                        <img class="fs_header_icon" :src="appVars.asset_url + 'images/replyIconWhite.svg'" alt="">
                        <span v-if="type === 'draft_response'">{{ $t('Add Draft Reply') }}</span>
                        <span v-else-if="type === 'note'">{{ $t('Add Internal Note') }}</span>
                        <span v-else>{{ $t('Reply') }}</span>
                    </el-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
import AttachmentForm from './_AttachmentForm';

export default {
    name: 'CreateResponse',
    props: ['ticket', 'type','draft', 'openAIIntegration', 'is_agent', 'fluentBotIntegration'],
    components: {
        WpEditor,
        AttachmentForm
    },
    emits: ['discardDraft', 'created', 'toggleFluentBot'],
    data() {
        return {
            informational_reply: false,
            response_body: '',
            show_cc_option: false,
            selected_cc: [],
            cc_emails: [],
            creating: false,
            close_ticket: 'no',
            attachments: [],
            editor_ready: true,
            shortcodes: {
                '{{customer.first_name}}': 'Customer First Name',
                '{{customer.last_name}}': 'Customer Last Name',
                '{{customer.full_name}}': 'Customer Full Name',
                '{{customer.email}}': 'Customer Email',
                '{{agent.first_name}}': 'Agent First Name',
                '{{agent.last_name}}': 'Agent Last Name',
                '{{agent.full_name}}': 'Agent Full Name',
                '{{agent.email}}': 'Agent Email',
            },
            draftID:'',
            loading: false,
            saveResponseDraftTimer: null,
            isCreatingResponse: false,
            attachmentUploadState: {
                uploading: false,
                count: 0
            }
        };
    },
    watch: {
        response_body() {
            if (this.appVars.enable_draft_mode === 'yes' && !Array.isArray(this.ticket)) {
                if (this.response_body === '' && this.draftID) {
                    this.removeDraft();
                }

                if (this.type === 'response' || this.type === 'draft_response') {
                    this.saveResponseDraft();
                }
            }
        },
        selected_cc() {
            if (this.appVars.enable_draft_mode === 'yes' && !Array.isArray(this.ticket)) {
                if (this.response_body === '' && this.draftID) {
                    this.removeDraft();
                }

                if (this.type === 'response' || this.type === 'draft_response') {
                    this.saveResponseDraft();
                }
            }
        },
        'type'(newType) {
            if (newType === 'note') {
                this.show_cc_option = false;
                this.selected_cc = [];
            }
        }
    },
    methods: {
        saveResponseDraft() {
            if (this.saveResponseDraftTimer) {
                clearTimeout(this.saveResponseDraftTimer);
            }

            this.saveResponseDraftTimer = setTimeout(() => {
                const data = {
                    content: this.response_body,
                    draftID: this.draftID,
                    selected_cc: this.selected_cc,
                    conversation_type: this.type,
                };

                if (this.response_body !== '' && this.isCreatingResponse === false) {
                    let action = `tickets/${this.ticket.id}/draft`;

                    this.$post(action, data)
                        .then((response) => {
                            this.draftID = response.draftID;
                        })
                        .catch((errors) => {
                            this.$handleError(errors);
                        });
                }
            }, 5000);
        },

        removeDraft() {
            this.$emit('discardDraft', this.draftID);
            this.draftID = '';
        },

        toggleCcOption(command) {
            if (command === 'show') {
                this.show_cc_option = true;
            } else {
                this.selected_cc = [];
                this.show_cc_option = false;
            }
        },

        updateAttachmentUploadState(state) {
            this.attachmentUploadState = state;
        },

        notifyAttachmentUploadPending() {
            const message = this.attachmentUploadState.count > 1
                ? this.$t('Please wait for all attachments to finish uploading before submitting the reply.')
                : this.$t('Please wait for the attachment to finish uploading before submitting the reply.');

            this.$notify({
                message,
                type: 'warning',
                position: 'bottom-right',
                offset: 50,
            });
        },

        create(closed = 'no') {
            if (this.attachmentUploadState.uploading) {
                this.notifyAttachmentUploadPending();
                return;
            }

            this.isCreatingResponse = true;
            const data = {
                content: this.response_body,
                conversation_type: this.type,
                close_ticket: closed,
                attachments: this.attachments,
                cc_emails: this.selected_cc,
                informational_reply: this.informational_reply,
            };

            let action = `tickets/${this.ticket.id}/responses`;
            if (Array.isArray(this.ticket)) {
                data.ticket_ids = this.ticket;
                data.bulk_action = 'reply_tickets';
                action = 'tickets/bulk-reply';
            }
            this.creating = true;
            this.$post(action, data)
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right',
                        offset: 50,
                    });
                    this.isCreatingResponse = false;
                    if (this.draftID) {
                        this.removeDraft();
                    }

                    this.response_body = '';
                    this.selected_cc = [];
                    this.$emit('created', response.response, response);
                    this.attachments = [];
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.creating = false;
                });
        },

        handleCcChange(value) {
            if (!value.length) {
                this.selected_cc = [];
                this.show_cc_option = false;
            }
        }
    },
    mounted() {
        if (this.appVars.enable_draft_mode === 'yes' && !Array.isArray(this.ticket)) {
            if (this.draft) {
                this.response_body = this.draft.value.content;
                this.draftID = this.draft.id;
                this.selected_cc = this.draft.value.selected_cc;
            }
        }

        if (!Array.isArray(this.ticket)) {
            if (this.ticket.responses.length === 0) {
                if (this.ticket.carbon_copy && this.ticket.carbon_copy !== '') {
                    this.selected_cc = this.ticket.carbon_copy?.split(',') || [];
                }
            } else {
                let conversation = this.ticket.responses[0];
                if (conversation.cc_info && this.selected_cc?.length === 0) {
                    this.selected_cc = conversation.cc_info;
                }
            }
        } else {
            this.selected_cc = [];
        }
    },
    beforeUnmount() {
        if (this.saveResponseDraftTimer) {
            clearTimeout(this.saveResponseDraftTimer);
        }
    }
}
</script>
