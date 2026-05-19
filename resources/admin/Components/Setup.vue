<template>
    <div class="fs_setup">
        <div class="fs_box_wrapper">
            <div class="fs_card fs_table_container">
                <div class="fs_table_wrapper">
                <template v-if="step === 'mailbox'">
                    <!-- Header Section with Logo and Progress -->
                    <div class="fs_setup_header">
                        <div class="fs_header_content">
                        <BrandLogo :asset-url="appVars.asset_url" />
                            <div class="fs_step_indicator">
                                <span>{{ $t('Step 1 of 2') }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="fs_progress_container">
                            <div class="fs_progress_track"></div>
                            <div class="fs_progress_fill"></div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="fs_setup_content">
                        <!-- Welcome Section -->
                        <div class="fs_welcome_section">
                            <h1 class="fs_welcome_title">{{ $t('Welcome aboard!') }}👋🏻</h1>
                            <p class="fs_welcome_subtitle">{{ $t('Please setup your support portal first') }}</p>
                        </div>

                        <!-- Form Section -->
                        <div class="fs_form_section">
                            <el-form :data="mailbox" label-position="top">
                                <el-form-item :label="$t('Business name')" class="fs_form_item">
                                    <el-input
                                        :placeholder="$t('eg: Awesome Business Inc')"
                                        type="text"
                                        v-model="mailbox.name"
                                        class="fs_text_input">
                                    </el-input>
                                </el-form-item>

                                <el-form-item :label="$t('Business Email')" class="fs_form_item">
                                    <el-input
                                        :placeholder="$t('From Email Address')"
                                        type="email"
                                        v-model="mailbox.email"
                                        class="fs_text_input">
                                    </el-input>
                                </el-form-item>

                                <el-form-item :label="$t('Support Portal Page (For Customers)')" class="fs_form_item">
                                    <el-select
                                        :disabled="global_settings.create_portal_page === 'yes'"
                                        clearable
                                        v-loading="loading_pages"
                                        filterable
                                        :placeholder="$t('Select a Page')"
                                        v-model="global_settings.portal_page_id"
                                        class="fs_select_field">
                                        <el-option
                                            v-for="item in pages"
                                            :key="item.id"
                                            :label="item.title"
                                            :value="item.id">
                                            <span style="float: left">{{ item.title }}</span>
                                            <span style="float: right; color: #8492a6; font-size: 13px">{{ item.id }}</span>
                                        </el-option>
                                    </el-select>

                                    <!-- Shortcode Information -->
                                    <div class="fs_shortcode_info">
                                        <span class="fs_shortcode_text">{{ $t('Use Shortcode') }}</span>
                                        <code class="fs_shortcode_badge">[fluent_support_portal]</code>
                                        <span class="fs_shortcode_text">in the selected page</span>
                                    </div>
                                </el-form-item>

                                <!-- Checkbox for auto page creation -->
                                <div>
                                    <el-checkbox
                                        v-if="!global_settings.portal_page_id"
                                        true-label="yes"
                                        false-label="no"
                                        v-model="global_settings.create_portal_page"
                                        class="fs_auto_page_checkbox">
                                        {{ $t('Create a page automatically with the shortcode') }}
                                    </el-checkbox>
                                </div>


                            </el-form>
                        </div>
                    </div>
                </template>
                <template v-else-if="step === 'maintenance'">
                    <!-- Header Section with Logo and Progress -->
                    <div class="fs_setup_header">
                        <div class="fs_header_content">
                            <BrandLogo :asset-url="appVars.asset_url" />
                            <div class="fs_step_indicator">
                                <span>{{ $t('Step 2 of 2') }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar - Full -->
                        <div class="fs_progress_container">
                            <div class="fs_progress_track"></div>
                            <div class="fs_progress_fill fs_progress_complete"></div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="fs_setup_content">
                        <!-- Welcome Section -->
                        <div class="fs_welcome_section">
                            <h1 class="fs_welcome_title">{{ $t("You're Almost There!") }}👏🏻</h1>
                            <p class="fs_welcome_subtitle">{{ $t('Please setup your support portal first') }}</p>
                        </div>

                        <!-- Form Section -->
                        <div class="fs_form_section fs_maintenance_form">
                            <!-- Newsletter Section -->
                            <div class="fs_newsletter_section">
                                <div class="fs_section_header">
                                    <h3 class="fs_section_title">{{ $t('Subscribe to our Bi-Monthly newsletter') }}</h3>
                                    <p class="fs_section_subtitle">{{ $t('We will send tips and advanced usage of FluentSupport (monthly)') }}</p>
                                </div>

                                <div class="fs_input_group">
                                    <el-input
                                        :placeholder="$t('Email address for bi-monthly newsletter')"
                                        type="email"
                                        v-model="email_address"
                                        class="fs_text_input">
                                    </el-input>
                                </div>
                            </div>

                            <!-- Share Essentials Section -->
                            <div class="fs_essentials_section">
                                <div class="fs_section_header">
                                    <h3 class="fs_section_title">{{ $t('Help us to make FluentSupport better') }}</h3>
                                    <p class="fs_section_subtitle">
                                        {{ $t('Allow Fluent Support to collect non-sensitive data and usage information.') }}
                                        <span
                                            class="fs_link_text"
                                            @click="show_essential = !show_essential">
                                            {{ $t('What we collect') }}
                                        </span>
                                    </p>
                                </div>

                                <div class="fs_checkbox_group">
                                    <el-checkbox
                                        true-label="yes"
                                        false-label="no"
                                        v-model="share_essentials"
                                        class="fs_share_checkbox">
                                        {{ $t('Share Essentials') }}
                                    </el-checkbox>
                                </div>

                                <!-- Expandable details -->
                                <div v-if="show_essential" class="fs_essential_details">
                                    <p>
                                        Server environment details (php, mysql, server, WordPress versions), Site language, Number
                                        of active plugins, Site name and url, Your name and email address. No sensitive data is
                                        tracked.
                                    </p>
                                </div>
                            </div>

                            <!-- Hidden FluentForms section for backward compatibility -->
                            <div v-if="!has_fluentform">
                                <el-checkbox
                                    true-label="yes"
                                    false-label="no"
                                    v-model="install_fluentforms">
                                    {{ $t('Install Fluent Forms Plugin for custom Ticket Forms.') }}
                                </el-checkbox>
                            </div>
                        </div>
                    </div>


                </template>
                <template v-else>
                    <!-- Header Section with Logo and Progress -->
                    <div class="fs_setup_header">
                        <div class="fs_header_content">
                            <BrandLogo :asset-url="appVars.asset_url" />
                            <div class="fs_step_indicator">
                                <span>{{ $t('Step 2 of 2') }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar - Complete -->
                        <div class="fs_progress_container">
                            <div class="fs_progress_track"></div>
                            <div class="fs_progress_fill fs_progress_complete"></div>
                        </div>
                    </div>

                    <!-- Main Content - Completion -->
                    <div class="fs_setup_content">
                        <!-- Completion Title Section -->
                        <div class="fs_welcome_section">
                            <h1 class="fs_welcome_title">{{ $t('Congrats! Your Support Portal') }} 🎉</h1>
                            <p class="fs_welcome_subtitle">{{ $t('Your support portal is now configured and ready to help your customers.') }}</p>
                        </div>

                        <!-- Quick Actions Section -->
                        <div class="fs_form_section fs_completion_section">
                            <div class="fs_section_header">
                                <h3 class="fs_section_title">{{ $t('Quick Setup Options') }}</h3>
                                <p class="fs_section_subtitle">{{ $t('Configure these settings to get the most out of FluentSupport') }}</p>
                            </div>

                            <div class="fs_quick_actions_grid">
                                <router-link
                                    :to="{ name: 'email_settings', params: { box_id: created_box.id } }"
                                    class="fs_action_card">
                                    <div class="fs_action_icon">
                                        <el-icon><Message /></el-icon>
                                    </div>
                                    <div class="fs_action_content">
                                        <h4 class="fs_action_title">{{ $t('Ticket Email Settings') }}</h4>
                                        <p class="fs_action_description">{{ $t('Configure email notifications and responses') }}</p>
                                    </div>
                                    <div class="fs_action_arrow">
                                        <el-icon><ArrowRight /></el-icon>
                                    </div>
                                </router-link>

                                <router-link
                                    :to="{ name: 'products' }"
                                    class="fs_action_card">
                                    <div class="fs_action_icon">
                                        <el-icon><Box /></el-icon>
                                    </div>
                                    <div class="fs_action_content">
                                        <h4 class="fs_action_title">{{ $t('Products & Services') }}</h4>
                                        <p class="fs_action_description">{{ $t('Setup products/services for ticket categorization') }}</p>
                                    </div>
                                    <div class="fs_action_arrow">
                                        <el-icon><ArrowRight /></el-icon>
                                    </div>
                                </router-link>

                                <router-link
                                    :to="{ name: 'support-staffs' }"
                                    class="fs_action_card">
                                    <div class="fs_action_icon">
                                        <el-icon><UserFilled /></el-icon>
                                    </div>
                                    <div class="fs_action_content">
                                        <h4 class="fs_action_title">{{ $t('Support Staff') }}</h4>
                                        <p class="fs_action_description">{{ $t('Add team members to handle support tickets') }}</p>
                                    </div>
                                    <div class="fs_action_arrow">
                                        <el-icon><ArrowRight /></el-icon>
                                    </div>
                                </router-link>

                                <router-link
                                    :to="{ name: 'global_settings' }"
                                    class="fs_action_card">
                                    <div class="fs_action_icon">
                                        <el-icon><Setting /></el-icon>
                                    </div>
                                    <div class="fs_action_content">
                                        <h4 class="fs_action_title">{{ $t('Global Settings') }}</h4>
                                        <p class="fs_action_description">{{ $t('Customize your support portal preferences') }}</p>
                                    </div>
                                    <div class="fs_action_arrow">
                                        <el-icon><ArrowRight /></el-icon>
                                    </div>
                                </router-link>
                            </div>

                        </div>
                    </div>
                </template>

                <!-- Sticky Footer Bar for Step 2 -->
                <div class="fs_sticky_footer">
                    <div class="fs_footer_content">
                                <div class="fs_help_section">
                            <span class="fs_help_text">{{ $t('Need Help?') }}</span>
                            <a
                                href="https://fluentsupport.com/docs/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="fs_doc_link">
                                {{ $t('Read Documentation') }}
                            </a>
                        </div>
                        <div class="fs_footer_actions" v-if="step === 'mailbox'">
                            <el-button
                                v-loading="saving"
                                :disabled="saving"
                                @click="completeSetup()"
                                class="fs_filled_btn">
                                {{ $t('Next Step') }}
                            </el-button>
                        </div>

                        <div class="fs_footer_actions" v-else-if="step === 'maintenance'">
                            <el-button
                                @click="step = 'mailbox'"
                                class="fs_outline_btn">
                                {{ $t('Back') }}
                            </el-button>
                            <el-button
                                v-loading="saving"
                                :disabled="saving"
                                @click="completeTrack()"
                                class="fs_filled_btn">
                                {{ $t('Finish') }}
                            </el-button>
                        </div>

                        <div class="fs_footer_actions" v-else-if="step === 'completed'">
                            <el-button
                                @click="step = 'maintenance'"
                                class="fs_outline_btn">
                                {{ $t('Go Back') }}
                            </el-button>
                            <el-button
                                class="fs_filled_btn"
                                @click="$router.push({ name: 'dashboard' })">
                                {{ $t('Go to Dashboard') }}
                            </el-button>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <el-dialog
            :title="$t('Build a better Fluent Support')"
            :close-on-click-modal="false"
            :show-close="false"
            :append-to-body="true"
            top="30vh"
            custom-class="fc_essential_modal"
            v-model="show_essential_modal"
            class="fs_dialog"
            width="90%"
            style="max-width: 420px;">
            <p>
                Get improved features and faster fixes by sharing non-sensitive data via usage tracking that shows us
                how FluentSupport is used. No personal data is tracked or stored.
            </p>
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button class="fs_outline_btn" @click="handleDataShare('no')">{{ $t('No Thanks') }}</el-button>
                    <el-button class="fs_filled_btn" @click="handleDataShare('yes')">{{ $t('Yes, Count me in!') }}</el-button>
                </span>
            </template>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import BrandLogo from './BrandLogo.vue';

export default {
    name: 'FluentSupportSetup',
    components: { BrandLogo },
    data() {
        return {
            global_settings: {
                portal_page_id: '',
                create_portal_page: 'no'
            },
            mailbox: {
                name: '',
                email: '',
                box_type: 'web',
                is_default: 'yes'
            },
            created_box: {},
            step: 'mailbox',
            pages: [],
            loading_pages: false,
            saving: false,
            has_fluentform: false,
            install_fluentforms: 'no',
            share_essentials: 'no',
            show_essential: false,
            email_address: '',
            show_essential_modal: false
        }
    },
    methods: {
        fetchPages() {
            this.loading_pages = true;
            this.$get('settings/pages')
                .then(response => {
                    this.pages = response.pages;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading_pages = false;
                });
        },
        completeSetup() {
            this.saving = true;
            this.$post('settings/setup', {
                mailbox: this.mailbox,
                global_settings: this.global_settings
            })
                .then(response => {
                    this.created_box = response.mailbox;
                    this.appVars.mailboxes = response.mailboxes;
                    this.has_fluentform = response.has_fluentform;
                    this.step = 'maintenance';
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });

        },
        handleDataShare(status) {
            this.share_essentials = status;
            this.show_essential_modal = false;
            this.completeTrack(true);
        },
        completeTrack(force = false) {
            if (this.share_essentials !== 'yes' && !force) {
                this.show_essential_modal = true;
                return;
            }

            this.saving = true;
            this.$post('settings/setup-installation', {
                install_fluentform: this.install_fluentforms,
                share_essentials: this.share_essentials,
                optin_email: this.email_address
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .always(() => {
                    this.saving = false;
                    this.step = 'completed';
                });
        },
        // Show the main admin navbar (remove inline hide)
        showNavbar() {
            const navbar = document.querySelector('.fs_main_navbar');
            if (navbar) {
                // remove inline display override so CSS controls visibility
                navbar.style.removeProperty('display');
            }
        },
        // Hide the main admin navbar while on the setup flow
        hideNavbar() {
            const navbar = document.querySelector('.fs_main_navbar');
            if (navbar) {
                navbar.style.display = 'none';
            }
        }
    },
    mounted() {
        if (this.appVars.mailboxes.length) {
            this.$router.push({name: 'dashboard', query: {t: Date.now()}});
        } else {
            this.fetchPages();
        }

        // Hide the main navbar
        this.hideNavbar();
    },

    // Ensure navbar is visible when this component is destroyed/navigated away
    beforeUnmount() {
        this.showNavbar();
    }
}
</script>
