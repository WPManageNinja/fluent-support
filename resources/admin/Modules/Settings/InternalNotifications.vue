<template>
    <div :class="rootClass">
        <div v-if="!isModal" class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ pageTitle }}</h3>
                <div class="fs_save_settings_container">
                    <el-button
                        size="default"
                        type="success"
                        class="fs_save_settings_btn"
                        :loading="saving"
                        @click="saveSettings"
                    >
                        {{ $t('Save Settings') }}
                    </el-button>
                </div>
            </div>
        </div>

        <div v-if="!fetching" v-loading="saving" :class="bodyClass">
            <form-builder
                v-if="appReady"
                :fields="visibleFields"
                :form-data="settings"
                label_position="top"
            />

            <div v-if="isModal" class="fs_dialog_footer fs_text_right">
                <el-button
                    v-loading="saving"
                    :disabled="saving"
                    type="success"
                    @click="saveSettings"
                    class="fs_filled_btn"
                >
                    {{ $t('Update Settings') }}
                </el-button>
            </div>
        </div>

        <div v-else :class="skeletonClass">
            <el-skeleton :rows="6" animated />
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';

export default {
    name: 'InternalNotifications',
    components: {
        FormBuilder
    },
    props: {
        settingsScope: {
            type: String,
            default: 'enable'
        },
        displayMode: {
            type: String,
            default: 'page'
        }
    },
    emits: ['updated'],
    data() {
        return {
            settings: {},
            fields: {},
            fetching: false,
            saving: false,
            appReady: false,
            settingsKey: '_internal_notification_settings'
        };
    },
    computed: {
        isModal() {
            return this.displayMode === 'modal';
        },
        rootClass() {
            return this.isModal ? 'fs_internal_notification_settings_modal' : 'fs_box_wrapper';
        },
        bodyClass() {
            return this.isModal
                ? 'fs_internal_notification_settings_modal_body'
                : 'fs_box_body fs_business_settings_wrapper';
        },
        skeletonClass() {
            return this.isModal
                ? 'fs_internal_notification_settings_modal_body fs_skeleton_loader'
                : 'fs_box_body fs_skeleton_loader';
        },
        pageTitle() {
            return this.settingsScope === 'preferences'
                ? this.$t('Notification Settings')
                : this.$t('Internal Notifications');
        },
        visibleFields() {
            const fields = { ...this.fields };

            if (this.settingsScope === 'preferences') {
                delete fields.enabled;
                return {
                    mention_notifications: this.mentionNotificationField(),
                    ...fields
                };
            }

            return fields.enabled ? { enabled: fields.enabled } : {};
        }
    },
    methods: {
        fetchSettings() {
            this.fetching = true;

            this.$get('settings', {
                settings_key: this.settingsKey,
                with: ['fields']
            })
                .then((response) => {
                    this.settings = response.settings || {};
                    this.settings.mention_notifications = 'yes';
                    this.fields = response.fields || {};
                    this.appReady = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {
            this.saving = true;
            const settings = this.sanitizedSettings();

            this.$post('settings', {
                settings_key: this.settingsKey,
                settings
            })
                .then((response) => {
                    window.fluentSupportAdmin.internal_notification_settings = { ...settings };
                    window.dispatchEvent(new CustomEvent('fluent_support_internal_notification_settings_changed', {
                        detail: {
                            settings: window.fluentSupportAdmin.internal_notification_settings
                        }
                    }));
                    window.fluentSupportAdmin.refreshNotificationPolling?.();

                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });

                    this.$emit('updated');
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        mentionNotificationField() {
            return {
                type: 'inline-checkbox',
                true_label: 'yes',
                false_label: 'no',
                checkbox_label: this.$t('Enable Mention Notification'),
                disabled: true,
                help: this.$t('Agents will receive notifications when they are mentioned in replies or internal notes. This cannot be disabled from these settings.')
            };
        },
        sanitizedSettings() {
            const settings = { ...this.settings };
            delete settings.mention_notifications;

            return settings;
        }
    },
    mounted() {
        this.fetchSettings();
        if (!this.isModal) {
            this.$setTitle(this.pageTitle);
        }
    }
};
</script>
