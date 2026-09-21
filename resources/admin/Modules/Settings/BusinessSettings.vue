<template>
    <div class="fs_box_wrapper" @click="handleClick">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t("Global Settings") }}</h3>
                <div class="fs_save_settings_container">
                    <el-button
                        size="default"
                        type="success"
                        class="fs_save_settings_btn"
                        @click="saveSettings()"
                    >{{ $t("Save Settings") }}</el-button>
                </div>
            </div>
            <div class="fs_box_actions"></div>
        </div>
        <div
            v-if="!fetching"
            v-loading="loading"
            class="fs_box_body fs_business_settings_wrapper"
        >
            <form-builder
                v-if="app_ready"
                :fields="processedFields"
                :form-data="settings"
                label_position="top"
            >
            </form-builder>
        </div>
        <div
            class="fs_box_body fs_skeleton_loader"
            v-else
        >
            <el-skeleton :rows="5" animated />
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';
export default {
    name: 'BusinessSettings',
    components: {
        FormBuilder
    },
    data() {
        return {
            settings: {},
            fields: {},
            fetching: false,
            loading: false,
            app_ready: false,
            settings_key: 'global_business_settings'
        }
    },
    computed: {
        processedFields() {
            const processed = {};
            Object.keys(this.fields).forEach(key => {
                const field = { ...this.fields[key] };
                field.inline_help = this.makeShortcodesClickable(field.inline_help);

                if (key === 'portal_page_id' && this.settings.portal_page_id) {
                    const pageId = this.settings.portal_page_id;
                    const editUrl = (field.admin_url || '') + 'post.php?post=' + pageId + '&action=edit';
                    const previewUrl = (field.home_url || '') + '?page_id=' + pageId;
                    field.inline_help += ' <a href="' + editUrl + '" target="_blank" class="fs_doc_link fs_doc_link_empty_icon">' + this.$t('Edit') + '</a>'
                        + ' | <a href="' + previewUrl + '" target="_blank" rel="noopener noreferrer" class="fs_doc_link">' + this.$t('Preview') + '</a>';
                }

                if (key === 'ticket_link_portal') {
                    field.wrapper_class = [
                        field.wrapper_class,
                        this.settings.ticket_link_portal === 'default' ? 'fs_portal_destination_has_page' : '',
                        this.settings.ticket_link_portal && this.settings.ticket_link_portal !== 'default' ? 'fs_portal_destination_compact' : ''
                    ].filter(Boolean).join(' ');
                }

                processed[key] = field;
            });
            return processed;
        }
    },
    methods: {
        makeShortcodesClickable(helpText) {
            if (!helpText) return helpText;
            return helpText.replace(
                /<code>(\[.*?\])<\/code>/g,
                '<code class="fs_clickable_shortcode" data-shortcode="$1">$1</code>'
            );
        },
        fetchSettings() {
            this.fetching = true;
            this.$get('settings', {
                settings_key: this.settings_key,
                with: ['fields']
            })
                .then(response => {
                    this.settings = response.settings;
                    this.fields = response.fields;
                    this.app_ready = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {
            this.loading = true;
            this.$post('settings', {
                settings_key: this.settings_key,
                settings: this.settings
            })
                .then(response => {
                    if (Object.prototype.hasOwnProperty.call(this.settings, 'internal_notifications_enabled')) {
                        window.fluentSupportAdmin.internal_notification_settings = {
                            ...(window.fluentSupportAdmin.internal_notification_settings || {}),
                            enabled: this.settings.internal_notifications_enabled
                        };
                        window.dispatchEvent(new CustomEvent('fluent_support_internal_notification_settings_changed', {
                            detail: {
                                settings: window.fluentSupportAdmin.internal_notification_settings
                            }
                        }));
                        window.fluentSupportAdmin.refreshNotificationPolling?.();
                    }

                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        copyShortcode(shortcode) {
            const successMessage = this.$t('Shortcode copied to clipboard: ') + shortcode;
            const errorMessage = this.$t('Failed to copy shortcode');

            // Use copyToClipboard helper which handles notifications properly
            if (!shortcode) {
                this.$notify({
                    type: 'warning',
                    title: this.$t('Warning'),
                    message: this.$t('Nothing to copy'),
                    position: 'bottom-right'
                });
                return Promise.resolve(false);
            }

            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(shortcode).then(() => {
                    this.$notify({
                        type: 'success',
                        title: this.$t('Success'),
                        message: successMessage,
                        position: 'bottom-right'
                    });
                    return true;
                }).catch((err) => {
                    console.error('Failed to copy:', err);
                    this.$notify({
                        type: 'error',
                        title: this.$t('Error'),
                        message: errorMessage,
                        position: 'bottom-right'
                    });
                    return false;
                });
            } else {
                this.$notify({
                    type: 'error',
                    title: this.$t('Error'),
                    message: this.$t('Copy to clipboard requires HTTPS'),
                    position: 'bottom-right'
                });
                return Promise.resolve(false);
            }
        },
        copyPortalLink(portalLink) {
            if (!portalLink) {
                return Promise.resolve(false);
            }

            return this.$copyToClipboard(
                portalLink,
                this.$t('Copied to clipboard') + ': ' + portalLink,
                this.$t('Failed to copy link')
            );
        },
        handleClick(e) {
            if (
                e.target.classList.contains("fs_clickable_shortcode") ||
                e.target.closest(".fs_clickable_shortcode")
            ) {
                const shortcodeElement = e.target.classList.contains("fs_clickable_shortcode")
                    ? e.target
                    : e.target.closest(".fs_clickable_shortcode");
                const shortcode = shortcodeElement.getAttribute("data-shortcode");
                if (shortcode) {
                    this.copyShortcode(shortcode);
                }
            }

            if (
                e.target.classList.contains("fs_portal_link_token") ||
                e.target.closest(".fs_portal_link_token")
            ) {
                const linkElement = e.target.classList.contains("fs_portal_link_token")
                    ? e.target
                    : e.target.closest(".fs_portal_link_token");
                this.copyPortalLink(linkElement.getAttribute("data-portal-link"));
            }
        }
    },
    mounted() {
        this.fetchSettings();
        this.$setTitle('Business Settings');
    }
}
</script>
