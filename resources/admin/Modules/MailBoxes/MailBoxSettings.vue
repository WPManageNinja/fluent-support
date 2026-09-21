<template>
    <div class="fs_mailbox_settings_container">
        <div class="fs_mailbox_settings_header">
            <div class="fs_mailbox_settings_header_left">
                <h1 class="fs_main_header_text">{{ $t('Inbox Settings') }}</h1>
                <p class="fs_sub_text">{{ $t('The following settings will be applied to the newly created') }}</p>
            </div>
            <el-button
                type="primary"
                class="fs_save_settings_btn"
                v-loading="saving"
                :disabled="saving"
                @click="saveSettings()"
            >
                {{ $t('Save Settings') }}
            </el-button>
        </div>

        <div class="fs_settings_form">
            <div class="fs_form_group">
                <label class="fs_form_label">{{ $t('Inbox Name') }}</label>
                <el-input
                    v-model="mailbox.name"
                    :placeholder="$t('Inbox Name')"
                    class="fs_text_input fs_text_input_40"
                />
            </div>

            <div class="fs_form_group">
                <span class="fs_field_label">
                    {{ $t('Support Inbox Email') }}
                    <el-tooltip
                        effect="dark"
                        :content="$t('select_fallback_business')"
                        placement="top"
                        popper-class="fs-tooltip"
                    >
                        <el-icon> <InfoFilled /> </el-icon>
                    </el-tooltip>
                </span>
                <el-input
                    v-model="mailbox.email"
                    :disabled="mailbox.mapped_email && mailbox.box_type == 'email'"
                    type="email"
                    :placeholder="$t('Support Inbox Email')"
                    class="fs_text_input fs_text_input_40"
                />
            </div>

            <div class="fs_form_group">
                <span class="fs_field_label">
                    {{ $t('Admin Email Address') }}
                    <el-tooltip
                        effect="dark"
                        :content="$t('admin_get_email')"
                        placement="top"
                        popper-class="fs-tooltip"
                    >
                        <el-icon> <InfoFilled /> </el-icon>
                    </el-tooltip>
                </span>
                <el-input
                    v-model="mailbox.settings.admin_email_address"
                    type="email"
                    class="fs_text_input fs_text_input_40"
                />
            </div>

            <div v-if="mailbox.box_type == 'email' && !mailbox.mapped_email" class="fs_form_group">
                <p class="fs_sub_text">
                    {{ $t('Please configure') }} <br/>
                    <router-link
                        :to="{name: 'email_piping', params: { box_id: mailbox.id }}"
                        class="fs_doc_link"
                    >
                        {{ $t('your email piping settings first') }}
                    </router-link>
                </p>
            </div>

            <div v-if="has_pro" class="fs_form_group">
                <label class="fs_form_label">{{ $t('Email Footer For Customers') }}</label>
                <wp-editor
                    :height="200"
                    v-model="mailbox.email_footer"
                    :editor_shortcodes="shortCodes"
                    class="fs_custom_editor"
                />
            </div>

            <div class="fs_form_group">
                <label class="fs_form_label">{{ $t('Inbox Color') }}</label>
                <div class="fs_color_picker_wrapper">
                    <el-color-picker
                        v-model="mailbox.settings.color"
                        size="large"
                        style="width: 30px; height: 30px;"
                    />
                    <span class="fs_color_value">{{ mailbox.settings.color || '#189877' }}</span>
                </div>
            </div>

            <div class="fs_form_group">
                <el-checkbox
                    v-model="mailbox.settings.hide_business_box"
                    true-value="yes"
                    false-value="no"
                    class="fs_custom_checkbox"
                >
                    {{ $t('Hide Badge in Tickets') }}
                </el-checkbox>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';

export default {
    name: 'MailBoxSettings',
    components: {
        WpEditor,
    },
    props: ['mailbox'],
    data() {
        return {
            shortCodes: {
                '{{customer.first_name}}': this.$t('Customer First name'),
                '{{customer.last_name}}': this.$t('Customer Last name'),
                '{{customer.email}}': this.$t('Customer Email'),
                '{{ticket.id}}': this.$t('Ticket ID'),
                '{{ticket.public_id}}': this.$t('Public Ticket ID'),
                '{{ticket.public_url}}': this.$t('Ticket Public URL'),
                '{{ticket.title}}': this.$t('Ticket Title')
            },
            saving: false
        }
    },
    mounted() {
        if (!this.mailbox.settings.color) {
            this.mailbox.settings.color = '#189877';
        }
    },
    methods: {
        saveSettings() {
            if (this.mailbox.box_type == 'email' && this.mailbox.settings.admin_email_address == this.mailbox.email) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Your Admin Email Address and Support From Email should not be same. Please use a different email address.'),
                    position: 'bottom-right'
                });
                return false;
            }

            this.saving = true;

            this.$put(`mailboxes/${this.mailbox.id}`, {
                business: this.mailbox
            })
                .then((response) => {
                    this.$notify({
                        type: 'success',
                        position: 'bottom-right',
                        message: response.message
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        }
    }
}
</script>

