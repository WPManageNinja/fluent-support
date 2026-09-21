<template>
    <div class="fs_integration">
        <div v-if="!loading" class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ fields.title }}</h3>
                </div>
                <div class="fs_box_actions">

                </div>
            </div>
            <div v-if="fields" class="fs_box_body fs_padded_20 fs_business_settings_wrapper">
                <form-builder :fields="fields.fields" :formData="settings" />
                <el-button @click="saveSettings()" size="small" v-loading="saving" :disabled="saving" type="success">{{fields.button_text}}</el-button>
            </div>
            <div class="fs_box_body fs_padded_20" v-else>
                <h3>{{$t('Settings could not be found')}}!</h3>
            </div>
        </div>
        <div style="padding: 20px; background: white;" class="fs_box_body" v-else>
            <el-skeleton :rows="5" animated/>
        </div>
    </div>
</template>

<script>
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';
export default {
    name: 'TwilioIntegration',
    components: {
        FormBuilder
    },
    data() {
        return {
            integration_key: this.$route.query.item_key,
            loading: true,
            settings: false,
            fields: false,
            saving: false
        }
    },
    methods: {
        fetchSettings() {
            this.loading = true;
            this.$get('settings/twilio-integration', {
                integration_key: this.integration_key
            })
                .then(response => {
                    this.settings = response.settings;
                    this.fields = response.fields;
                    this.$setTitle(response.fields.title);
                })
                .catch((errors) => {
                    this.$handleError(errors)
                })
                .always(() => {
                    this.loading = false;
                });
        },
        saveSettings() {
            this.saving = true;
            this.$post('settings/twilio-integration', {
                integration_key: this.integration_key,
                settings: this.settings
            })
                .then(response => {
                    this.settings = response.settings;
                    this.$notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right'
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                })
        }
    },
    mounted() {
        this.fetchSettings();
    }
}
</script>
