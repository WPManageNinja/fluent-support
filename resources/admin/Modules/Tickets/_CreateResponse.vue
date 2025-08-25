<template>
    <div class="fs_create_response" :class="'fs_reply_type_'+type">
        <div class="fs_row" v-if="ticket.source === 'email'">
            <el-form-item :label="translate('Cc')" v-if="selected_cc?.length || show_cc_option">
                <el-select v-model="selected_cc" multiple filterable remote
                           placeholder="Please enter email"
                           allow-create
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
        <wp-editor :autofocus="true" v-if="editor_ready" v-model="response_body" :ticketId="ticket.id" :productID="ticket.product_id" :is_agent="is_agent" :is_direct_paste="true" :editor_shortcodes="shortcodes" :openAIIntegration="openAIIntegration" :fluentBotIntegration="fluentBotIntegration"
                   :show-saved-replies="true" :show-cc-toggle-button="ticket.source === 'email' && type !== 'note'" :add_cc="selected_cc?.length > 0 || show_cc_option" @toggleCcOption="toggleCcOption"/>
        <div class="fs_row">
            <div class="fs_half">
                <div  style="text-align: left" class="fs_response_actions">
                    <el-button v-loading="creating" :disabled="creating" @click="create('no')" size="large"
                               type="success">
                        <span v-if="type === 'draft_response'">{{ translate('Add Draft Reply') }}</span>
                        <span v-else-if="type === 'note'">{{ translate('Add Internal Note') }}</span>
                        <span v-else>{{ translate('Add Reply') }}</span>
                    </el-button>
                    <el-button v-if="type != 'note' && type !== 'draft_response' " :disabled="creating" @click="create('yes')" size="large"
                               type="danger">
                        {{ translate('Reply and Close') }}
                    </el-button>

                    <p v-if="type== 'note'">{{ translate('internal_note_warning') }}</p>
                </div>
                <div v-if="type === 'response'" class="fs_informational_reply_container">
                    <el-tooltip effect="dark" :content="$t('informational_reply_info')" placement="bottom">
                        <el-checkbox
                            v-model="informational_reply"
                            class="fs_informational_reply"
                        >
                            {{ translate('Send as Informational Reply')}}
                        </el-checkbox>
                    </el-tooltip>
                </div>
            </div>
            <div class="fs_half">
                <attachment-form is_agent="yes" v-if="appVars.has_file_upload" :ticket="ticket" :attachments="attachments"/>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">

import {onMounted, reactive, toRefs, watch} from "vue";
import {
    useFluentHelper,
    useNotify,
} from "@/admin/Composable/FluentFrameworkHelper";
import WpEditor from '../../Pieces/_wp_editor';
import AttachmentForm from './_AttachmentForm';
export default {
    name: 'CreateResponse',
    props: ['ticket', 'type','draft', 'openAIIntegration', 'is_agent', 'fluentBotIntegration'],
    components: {
        WpEditor,
        AttachmentForm
    },

    setup(props, {emit}) {

        const {
            post, translate, handleError, appVars, get
        } = useFluentHelper();
        const {notify} = useNotify();

        const state = reactive({
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
        });
        let isCreatingResponse = false;

        if(appVars.enable_draft_mode === 'yes' && Array.isArray(props.ticket) === false) {
            if (props.draft) {
                state.response_body = props.draft.value.content;
                state.draftID = props.draft.id;
                state.selected_cc = props.draft.value.selected_cc;
            }

            let saveResponseDraftTimer = null;

            const saveResponseDraft = () => {
                if (saveResponseDraftTimer) {
                    clearTimeout(saveResponseDraftTimer);
                }

                saveResponseDraftTimer = setTimeout(() => {
                    const data = {
                        content: state.response_body,
                        draftID: state.draftID,
                        selected_cc: state.selected_cc,
                        conversation_type: props.type,
                    };

                    if (state.response_body !== '' && isCreatingResponse === false) {
                        let action = `tickets/${props.ticket.id}/draft`;

                        post(action, data)
                            .then((response) => {
                                state.draftID = response.draftID;
                            })
                            .catch((errors) => {
                                handleError(errors);
                            });
                    }
                }, 5000);
            };

            watch([() => state.response_body, () => state.selected_cc], () => {
                 if(state.response_body === '' && state.draftID ) {
                    removeDraft();
                }

                 if(props.type === 'response' || props.type === 'draft_response' ){
                     saveResponseDraft();
                 }
            });
        }

        watch(() => props.type, (type) => {
            if(type === 'note') {
                state.show_cc_option = false;
                state.selected_cc = [];
            }
        })

        const removeDraft = () => {
            emit('discardDraft', state.draftID);
            state.draftID = '';
        }

        const toggleCcOption = (command) => {
            if(command === 'show'){
                state.show_cc_option = true;
            }else{
                state.selected_cc = [];
                state.show_cc_option = false;
            }
        };

        const create = (closed = 'no') => {
            isCreatingResponse = true;
            const data = {
                content: state.response_body,
                conversation_type: props.type,
                close_ticket: closed,
                attachments: state.attachments,
                cc_emails: state.selected_cc,
                informational_reply: state.informational_reply,
            };

            let action = `tickets/${props.ticket.id}/responses`;
            if (Array.isArray(props.ticket)) {
                data.ticket_ids = props.ticket;
                data.bulk_action = 'reply_tickets';
                action = 'tickets/bulk-reply';
            }
            state.creating = true;
            post(action, data)
                .then(response => {
                    notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right',
                        offset: 50,
                    });
                    isCreatingResponse = false;
                    if(state.draftID){
                        removeDraft();
                    }

                    state.response_body = '';
                    state.selected_cc = [];
                    emit('created', response.response, response);
                    state.attachments = [];
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.creating = false;
                });
        }

        const handleCcChange = (value) => {
            if(!value.length){
                state.selected_cc = [];
                state.show_cc_option = false;
            }
        }

        onMounted(() => {
            if(!Array.isArray(props.ticket)){
                if(props.ticket.responses.length === 0){
                    if(props.ticket.carbon_copy && props.ticket.carbon_copy !== ''){
                        state.selected_cc = props.ticket.carbon_copy?.split(',') || [];
                    }
                }else{
                    let conversation = props.ticket.responses[0];
                    if(conversation.cc_info && state.selected_cc?.length === 0){
                        state.selected_cc = conversation.cc_info;
                    }
                }
            }else{
                state.selected_cc = [];
            }
        })

        return {
            ...toRefs(state),
            create,
            translate,
            toggleCcOption,
            handleCcChange,
        };
    }
}
</script>


