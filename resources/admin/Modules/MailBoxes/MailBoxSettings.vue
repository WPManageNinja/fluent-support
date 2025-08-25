<template>
    <div class="fs_mailbox_settings">
        <el-form :data="mailbox" label-position="top">
            <el-form-item :label="translate('Inbox Name')">
                <el-input type="text" v-model="mailbox.name"></el-input>
            </el-form-item>
            <el-form-item :label="translate('Support From Email')">
                <el-input :disabled="mailbox.mapped_email && mailbox.box_type == 'email'" type="email" v-model="mailbox.email"></el-input>
                <p>{{translate('email_can_be_send')}}</p>
            </el-form-item>
            <el-form-item :label="translate('Admin Email Address')">
                <el-input type="email" v-model="mailbox.settings.admin_email_address"></el-input>
                <p>{{translate('admin_get_email')}}</p>
                <p v-if="mailbox.box_type == 'email' && mailbox.settings.admin_email_address == mailbox.email" style="color: red;">
                    {{translate('different_email_between_admin_and_support')}}
                </p>
            </el-form-item>

            <el-form-item v-if="mailbox.box_type == 'email'" :label="translate('Mapped Email')">
                <template v-if="mailbox.mapped_email">
                    <el-input :disabled="true" type="email" v-model="mailbox.mapped_email"></el-input>
                    <p>{{translate('mapped_webhook_email')}}</p>
                </template>
                <div v-else>
                    <h4>{{translate('Please configure')}} <router-link :to="{name: 'email_piping', params: { box_id: mailbox.id }}">
                        {{translate('your email piping settings first')}}
                    </router-link></h4>
                </div>
            </el-form-item>

            <el-form-item :label="translate('Email Footer For Customers')" v-if="has_pro">
                <wp-editor :height="100" v-model="mailbox.email_footer" :editor_shortcodes="shortCodes"/>
            </el-form-item>

            <el-form-item :label="translate('Inbox Color')">
                <el-color-picker v-model="mailbox.settings.color" size="large" />
            </el-form-item>

            <el-form-item>
                <el-checkbox class="fs_show_hide_box_badge" v-model="mailbox.settings.hide_business_box" true-label="yes" false-label="no">Hide Badge In Tickets</el-checkbox>
            </el-form-item>

            <el-form-item>
                <el-button v-loading="saving" :disabled="saving" @click="saveSettings()" type="success">
                    {{translate('Save Settings')}}
                </el-button>
            </el-form-item>
        </el-form>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
import { onMounted, reactive, toRefs,computed } from 'vue';
import {useFluentHelper, useNotify} from "@/admin/Composable/FluentFrameworkHelper";

export default {
    name: 'MailBoxSettings',
    props: ['mailbox'],
    components: {
        WpEditor
    },

    setup(props){
        const {
            handleError,
            translate,
            put,
            has_pro

        } = useFluentHelper();

        const { notify } = useNotify();

        const state = reactive({
            shortCodes: {
                '{{customer.first_name}}': translate('Customer First name'),
                '{{customer.last_name}}': translate('Customer Last name'),
                '{{customer.email}}': translate('Customer Email'),
                '{{ticket.id}}': translate('Ticket ID'),
                '{{ticket.public_url}}': translate('Ticket Public URL'),
                '{{ticket.title}}': translate('Ticket Title')
            },
            saving: false

        })

        const filtered_client_notifications = computed(() => {
            if(props.mailbox.box_type == 'email') {
                return {
                    ticket_created: translate('Ticket Received Welcome Email'),
                    ticket_closed_by_agent: translate('Ticket Closed By Agent')
                }
            }
            // return this.client_notifications;
        });

        const saveSettings = async () => {

            if(props.mailbox.box_type == 'email' && props.mailbox.settings.admin_email_address == props.mailbox.email) {
                notify.error({
                    message: 'Your Admin Email Address and Support From Email should not be same. Please use a different email address.',
                    position: 'bottom-right'
                });
                return false;
            }

            state.saving = true;
                put(`mailboxes/${props.mailbox.id}`, {
                business: props.mailbox
            })
                .then(response => {
                    notify({
                        type: 'success',
                        position: 'bottom-right',
                        message: response.message
                    });
                })
                .catch((errors) => {
                    handleError(errors)
                })
                .always(() => {
                    state.saving = false;
                });
        }

        onMounted(() => {
            if ( !props.mailbox.settings.color ) {
                props.mailbox.settings.color = '#0CBE7E';
            }
        });

        return{
            saveSettings,
            translate,
            filtered_client_notifications,
            has_pro,
            ...toRefs(state)
        }
    }

    }
</script>
