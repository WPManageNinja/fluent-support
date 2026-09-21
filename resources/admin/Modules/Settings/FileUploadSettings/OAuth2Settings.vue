<template>
    <div class="fs_storage_oauth_settings">
        <el-skeleton v-if="loading" :animated="true" :rows="4"></el-skeleton>
        <div v-else-if="settings">
            <el-form label-position="top" :data="settings">
                <div v-if="fieldsConfig.description" class="fs_storage_oauth_description" v-html="fieldsConfig.description"></div>
                <form-builder :fields="fieldsConfig.fields" :formData="settings"/>
            </el-form>

            <div class="fs_storage_oauth_footer">
                <div class="fs_storage_oauth_actions">
                    <el-button
                        v-if="settings.status != 'yes'"
                        class="fs_filled_btn"
                        @click="saveSettings"
                        :loading="saving"
                    >
                        {{ fieldsConfig.button_text }}
                    </el-button>
                    <el-button
                        v-else
                        class="fs_filled_btn"
                        @click="reconnect()"
                        :loading="saving"
                    >
                        {{ $t('Reconnect') }}
                    </el-button>
                    <el-button
                        v-if="settings.status == 'yes'"
                        @click="disconnect()"
                        class="fs_outline_btn fs_danger_btn"
                        plain
                    >
                        {{ $t('Disconnect') }}
                    </el-button>
                </div>
            </div>
        </div>
        <div v-else class="fs_storage_oauth_error">
            <p>{{ $t('Sorry, settings could not be loaded') }}</p>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../../Pieces/FormElements/_FormBuilder';

export default {
    name: 'OAuth2Settings',
    props: ['driver_name', 'driver'],
    components: {
        FormBuilder
    },
    $emits: ['driverRemoved'],
    data() {
        return {
            settings: {},
            fieldsConfig: {
                fields: {}
            },
            loading: false,
            saving: false
        }
    },
    methods: {
        getSettings() {
            this.loading = true;
            this.$get('settings/upload_integration', {
                integration_key: this.driver_name
            })
                .then(response => {
                    this.settings = response.settings;
                    this.fieldsConfig = response.fieldsConfig;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        saveSettings() {
            this.saving = true;
            this.$post('settings/upload_integration', {
                integration_key: this.driver_name,
                settings: this.settings
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    if (response.is_discarded) {
                        this.$emit('driverRemoved');
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        reconnect() {
            this.settings.do_reconnect = 'yes';
            this.saveSettings();
        },
        disconnect() {
            this.settings.access_token = '';
            this.settings.refresh_tokn = '';
            this.settings.client_id = '';
            this.settings.client_secret = '';
            this.saveSettings();
        }
    },
    mounted() {
        this.getSettings();
    }
}
</script>
