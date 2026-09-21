<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{$t('Global Email Settings')}}</h3>
            </div>
            <div class="fs_box_actions">
            </div>
        </div>
        <div style="padding: 20px" class="fs_box_body fs_business_settings_wrapper">
            <form-builder v-if="app_ready" :fields="fields" :form-data="settings" label_position="top" />
            <el-button size="default" type="success" @click="saveSettings()">{{$t('Save Settings')}}</el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../../Pieces/FormElements/_FormBuilder';
export default {
    name: 'GlobalEmailSettings',
    components: {
        FormBuilder
    },
    data() {
        return {
            settings: {},
            settings_key: 'global_email_settings',
            loading: false,
            fields: [],
            app_ready: false
        }
    },
    emits: ['saveSettings'],
    methods: {
        fetchSettings() {
            this.loading = true;
            this.$get('settings', {
                with: ['fields'],
                settings_key: this.settings_key
            })
                .then((response) => {
                    this.settings = response.settings;
                    this.fields = response.fields;
                    this.app_ready = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        saveSettings() {
            this.$emit('saveSettings', this.settings_key, this.settings);
        }
    },
    mounted() {
        this.fetchSettings();
    }
}
</script>
