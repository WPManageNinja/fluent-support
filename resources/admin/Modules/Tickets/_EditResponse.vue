<template>
    <div class="fs_edit_response">
        <wp-editor
            v-if="editor_ready"
            v-model="response.content"
            :ticketId="resolvedTicketId"
            is_agent="yes"
        />

        <div class="fs_edit_response_actions">
            <el-button class="fs_filled_btn" size="large" type="success" @click="editResponse()">
            {{ draftReplyApprovePermission && response.conversation_type === 'draft_response' ? $t('Update And Approve Response') : $t('Update Response') }}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
export default {
    name: 'EditResponse',
    props: {
        response: {
            type: Object,
            required: true,
        },
        ticketId: {
            type: [String, Number],
            default() {
                return ''
            }
        },
    },
    components: {
        WpEditor,
    },
    data() {
        return {
            saving: false,
            filteredAgents: this.appVars.support_agents,
            popup: false,
            editor_ready: true,
            draftReplyApprovePermission: this.appVars.me.permissions.includes('fst_approve_draft_reply'),
        };
    },

    computed: {
        resolvedTicketId() {
            return this.response.ticket_id || this.ticketId;
        },
    },

    methods: {
        editResponse() {
            this.saving = true;
            this.$put(
                `tickets/${this.resolvedTicketId}/responses/${this.response.id}`,
                { content: this.response.content }
            )
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                    });

                    this.$emit("updated", response.response);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        }
    },
};
</script>
