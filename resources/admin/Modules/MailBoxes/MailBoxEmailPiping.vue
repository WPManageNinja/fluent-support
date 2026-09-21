<template>
    <div class="fs_box_email_settings">
        <!-- Header Section -->
        <div class="fs_mailbox_settings_header" v-if="email_pipe.status === 'not_issued'">
            <div class="fs_mailbox_settings_header_left">
                <h1 class="fs_main_header_text">{{ $t('Email Piping') }}</h1>
            </div>
        </div>

        <div v-if="loading_pipe" class="fs_skeleton_loader">
            <el-skeleton :rows="5" animated/>
        </div>

        <div v-else-if="email_pipe" class="fs_email_piping_content">
            <!-- Not Issued State -->
            <div class="fs_not_issued_state" v-if="email_pipe.status === 'not_issued'">
                <h2 class="fs_piping_title">{{ $t("You are almost done") }}🎉</h2>
                <p class="fs_piping_description">
                    {{ $t('Connect your email address with Fluent Support Email parser, so when your customers send an email or reply from email it will be synced with your support portal') }}
                </p>

                <div class="fs_piping_form">
                    <div class="fs_checkbox_wrapper">
                        <el-checkbox
                            v-model="terms_agree"
                            class="fs_terms_checkbox"
                        >
                            {{ $t('I agree with the Fluent Support email parsing') }}
                            <a
                                target="_blank"
                                rel="noopener"
                                href="https://fluentsupport.com/privacy-policy/email-piping-data-policy/"
                                class="fs_terms_link"
                            >
                                {{ $t('Terms & Condition') }}
                            </a>
                        </el-checkbox>
                    </div>

                    <el-button
                        @click="issueEmail()"
                        type="primary"
                        class="fs_save_settings_btn"
                        :disabled="!terms_agree"
                        :loading="issuing"
                    >
                        {{ $t('Get Piping Email Details') }}
                    </el-button>
                </div>
            </div>

            <!-- Issued State -->
            <div class="fs_masked_email_form" v-else-if="email_pipe.status == 'issued'">
                <h2 class="fs_piping_title">{{ $t('Your masked mailbox email has been issued') }}</h2>
                <p class="fs_piping_description">
                    {{ $t('Please enable auto-forwarding your emails from') }}
                    <strong>{{ mailbox.email }}</strong>
                    {{ $t('to this following address.') }}
                </p>

                <div class="fs_piping_form">
                    <div class="fs_email_input_wrapper">
                        <el-input
                            :readonly="true"
                            v-model="email_pipe.mapped_email"
                            class="fs_text_input fs_text_input_40"
                        />
                        <el-button
                            @click="copyToClipboard(email_pipe.mapped_email)"
                            class="fs_copy_btn"
                            size="small"
                        >
                            <img :src="appVars.asset_url + 'images/copy.svg'" alt="">
                        </el-button>
                    </div>

                    <el-button
                        @click="loadPipeStatus()"
                        type="primary"
                        style="width: fit-content"
                        class="fs_save_settings_btn"
                    >
                        {{ $t('I have done it') }}
                    </el-button>

                    <div class="fs_highlight">
                        <p>{{ $t('Once you activate the auto-forward for') }} <strong>{{ mailbox.email }}</strong>, {{ $t('the status will be active.') }}</p>
                        <p>{{ $t('For the confirmation email from your email service provider, please check the latest ticket. A new ticket should be created with confirmation code.') }}</p>
                        <p>
                            <a
                                target="_blank"
                                rel="noopener"
                                href="https://fluentsupport.com/docs/email-piping-email-based-support-ticket/"
                                class="fs_doc_link"
                            >
                                {{ $t('Read the email piping documentation') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Active State -->
            <div class="fs_active_state" v-else-if="email_pipe.status == 'active'">
                <h2 class="fs_piping_title">{{ $t('Your mailbox is active.') }}</h2>
                <p class="fs_piping_description">{{ $t('All the forwarded emails to this address will be synced with your tickets. Below is your mask email address') }}</p>

                <div class="fs_piping_form">
                    </div>
                <div class="fs_email_input_wrapper">
                    <el-input
                        :readonly="true"
                        v-model="email_pipe.mapped_email"
                        class="fs_text_input fs_text_input_40"
                    />
                    <el-button
                        @click="copyToClipboard(email_pipe.mapped_email)"
                        class="fs_copy_btn"
                        size="small"
                    >
                        <img :src="appVars.asset_url + 'images/copy.svg'" alt="">
                    </el-button>
                </div>
            </div>

            <!-- Error State -->
            <div class="fs_error_state" v-else>
                <h2 class="fs_piping_title fs_error_title">{{ error_message }}</h2>
                <p class="fs_piping_description">
                    {{ $t('Please contact with') }}
                    <a href="https://wpmanageninja.com/support-tickets/#/" target="_blank" class="fs_support_link">
                        WPManageNinja Support
                    </a>
                </p>
            </div>

            <!-- Custom Webhook Section -->
            <div class="fs_webhook_section" v-if="is_custom_supported" >
                <div class="fs_webhook_content">
                    <p class="fs_webhook_description">
                        {{ $t('If you want to connect with your own email parser please use this following URL to send payload data') }}
                    </p>
                    <div class="fs_email_input_wrapper">
                        <el-input
                            :readonly="true"
                            v-model="webhook_url"
                            class="fs_text_input fs_text_input_40"
                        />
                        <el-button
                            @click="copyToClipboard(webhook_url)"
                            class="fs_copy_btn"
                            size="small"
                        >
                            <img :src="appVars.asset_url + 'images/copy.svg'" alt="">
                        </el-button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="fs_error_message">
            {{ $t('Settings could not be loaded') }}
        </div>
    </div>
</template>

<script type="text/babel">
import {useRouter} from "vue-router";
export default {
    name: 'MailBoxEmailPiping',
    props: ['mailbox'],
    data() {
        return {
            status: false,
            email_pipe: false,
            loading_pipe: false,
            webhook_url: false,
            is_custom_supported: false,
            terms_agree: false,
            issuing: false,
            error_message: '',
            copied: false
        }
    },
    methods: {
        async loadPipeStatus() {
            this.loading_pipe = true;
            this.error_message = '';
            await this.$get('email-box/' + this.mailbox.id + '/status')
                .then(response => {
                    this.email_pipe = response.email_pipe;
                    this.webhook_url = response.webhook_url;
                    this.is_custom_supported = response.is_custom_supported;
                    this.error_message = response.error_message;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading_pipe = false;
                });
        },

        async issueEmail() {
            this.issuing = true;
            await this.$post('email-box/' + this.mailbox.id + '/issue-email')
                .then(response => {
                    this.email_pipe = response.email_pipe;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.issuing = false;
                });
        },

        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                this.$notify({
                    type: 'success',
                    title: this.$t('Success'),
                    message: this.$t('Copied to clipboard'),
                    position: 'bottom-right'
                });
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
            }
        }
    },
    mounted() {
        if(this.mailbox.box_type !== 'email'){
            this.$router.push({name: 'box_settings', params: {box_id: this.mailbox.id}});
        }else{
            this.loadPipeStatus();
        }
    }
}
</script>
