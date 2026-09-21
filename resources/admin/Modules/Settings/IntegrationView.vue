<template>
    <div class="fs_integration">
        <div  class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t("Notification Integrations") }}</h3>
                    <div class="fs_save_settings_container">
                        <el-button
                            size="default"
                            type="success"
                            class="fs_filled_btn"
                            @click="saveSettings()"
                        >{{ $t("Save Settings") }}</el-button>
                    </div>
                </div>
            </div>
            <div class="fs_box_body">
                <div class="fs_settings_sub_menu" v-if="drivers && drivers.length">
                    <ul>
                        <li v-for="driver in drivers" :key="driver.key"
                            @click="switchIntegration(driver.key)"
                            :class="{ fs_sub_active: driver.key == integration_key }">
                            <span>{{ driver.title }}</span>
                        </li>
                    </ul>
                </div>

                <div class="fs_notification_integration_settings_form" v-if="!loading">
                    <NarrowPromo
                        v-if="current_integration && current_integration.require_pro"
                        :heading="current_integration.promo_heading"
                        :description="$t('pro_promo')"
                        :button-text="$t('Upgrade To Pro')"
                        :utm-content="'feature_lock_' + current_integration.key + '_integration'"
                    />

                    <template v-else-if="fields">
                        <div class="fs_integration_header" v-if="current_integration">
                            <h3>{{ current_integration.title }} {{ $t('Integration Settings') }}</h3>
                            <p v-html="current_integration.description"></p>
                        </div>
                        <form-builder :fields="fields.fields" :formData="settings"/>
                    </template>

                    <template v-else>
                        <h3>{{ $t('Settings could not be found') }}!</h3>
                    </template>
                </div>
                <div class="fs_skeleton_wrap" v-else>
                    <el-skeleton :rows="5" animated />
                </div>
            </div>

        </div>

    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';
import NarrowPromo from '@/admin/Components/NarrowPromo.vue';
import { useRouter } from 'vue-router';

export default {
    name: 'IntegrationView',
    components: {
        FormBuilder,
        NarrowPromo
    },

    data() {
        const router = useRouter();
        return {
            integration_key: router.currentRoute.value.query.integration_key,
            loading: false,
            settings: false,
            fields: false,
            saving: false,
            drivers: this.appVars.notification_integrations,
            router, // keep router instance in data
        };
    },

    computed: {
        current_integration() {
            return this.drivers.find(driver => driver.key === this.integration_key);
        }
    },

    methods: {
        async fetchSettings() {
            if (!this.current_integration || this.current_integration.require_pro) {
                return;
            }
            this.loading = true;
            await this.$get('settings/integration', {
                integration_key: this.integration_key
            })
                .then(response => {
                    this.settings = response.settings;
                    this.fields = response.fields;
                    if (response.fields) {
                        this.$setTitle(response.fields.title);
                    }
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        async saveSettings() {
            this.saving = true;
            await this.$post('settings/integration', {
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
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        switchIntegration(integrationKey) {
            this.router.push({ name: 'integration', query: { integration_key: integrationKey } });
            this.integration_key = integrationKey;
            this.fetchSettings();
        }
    },

    mounted() {
        if (this.integration_key) {
            this.fetchSettings();
        } else if (this.drivers && this.drivers.length) {
            this.integration_key = this.drivers[0].key;
            this.router.push({ name: 'integration', query: { integration_key: this.drivers[0].key } });
            this.fetchSettings();
        }
    }
};
</script>

