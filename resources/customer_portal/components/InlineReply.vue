<template>
    <div class="fs_ticket_reply_box" :class="is_focused === true ? 'fs_ticket_reply_header' : '' ">
        <h3>{{$t('Write a reply')}}</h3>
        <textarea @click="is_focused = true" v-if="!is_focused" class="fs_ticket_reply_text" :placeholder="$t('Click Here to Write a reply')"></textarea>

        <div v-else class="fs_reply_wrap">
            <div>
                <simple-wp-editor v-model="response_body" :autofocus="true"  :height="150" :ticketId="ticket.serial_number || ticket.id" :is_direct_paste="true" />
                <error :error="errors.get('content')"/>
            </div>

            <div class="fs_row">
                <div v-if="appVars.has_file_upload" class="fs_full">
                    <attachment-form :ticket="ticket" :attachments="attachments" />
                </div>
                <div :class="{fs_no_attachment: !appVars.has_file_upload}" class="fs_full">
                    <div class="fs_customer_response_actions">
                        <p @click="handleReplyAndClose">{{$t('Reply and Close')}}</p>
                        <el-button v-loading="creating" class="fs_reply_btn" :disabled="creating" @click="reply()">
                            <span>{{$t('Reply')}} </span>
                            <svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.75 12L15.25 6L7.75 0V3.75C3.60775 3.75 0.25 7.10775 0.25 11.25C0.25 11.4548 0.2575 11.6572 0.274 11.8575C1.37125 9.777 3.5215 8.33925 6.01525 8.25375L6.25 8.25H7.75V12ZM9.25 6.75H6.2245L5.96425 6.75525C5.0005 6.7875 4.07125 6.98775 3.20725 7.32975C4.3075 6.05625 5.935 5.25 7.75 5.25H9.25V3.12075L12.8485 6L9.25 8.87925V6.75Z" fill="currentColor"/>
                            </svg>
                        </el-button>
                    </div>
                </div>
            </div>

            <error :error="errors.get('permission_error')"/>
        </div>
    </div>
</template>

<script type="text/babel">
import SimpleWpEditor from '@/common/SimpleWpEditor.vue';
import AttachmentForm from './_AttachmentForm';
import Error from '@/common/Error.vue';
import Errors from '@/common/Errors.js';

export default {
    name: 'InlineReply',
    props: ['ticket'],
    components: {
        SimpleWpEditor,
        AttachmentForm,
        Error
    },
    data() {
        return {
            errors: new Errors(),
            is_focused: false,
            response_body: '',
            close_ticket: 'no',
            creating: false,
            attachments: []
        }
    },
    methods: {
        handleReplyAndClose(){
            this.close_ticket = 'yes';
            this.reply();
        },

        reply() {
            this.creating = true;
            this.errors.clear();
            this.$post(`tickets/${this.ticket.serial_number || this.ticket.id}/responses`, {
                content: this.response_body,
                conversation_type: this.type,
                close_ticket: this.close_ticket,
                attachments: this.attachments,
                intended_ticket_hash: this.appVars.intended_ticket_hash
            })
                .then(response => {
                    this.response_body = '';
                    this.is_focused = false;
                    this.attachments = [];
                    this.$emit('created', response.response, response.ticket);
                })
                .catch((errors) => {
                    if (errors.responseJSON && errors.responseJSON.message) {
                        errors.responseJSON.permission_error = [errors.responseJSON.message];
                    }

                    this.errors.record(errors);

                    console.log(errors.responseJSON);
                })
                .finally(() => {
                    this.creating = false;
                    this.close_ticket = 'no';
                });
        }
    }
}
</script>
