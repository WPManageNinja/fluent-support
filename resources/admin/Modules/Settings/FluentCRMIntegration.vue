<template>
    <div class="fs_fluentcrm_settings">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t('FluentCRM Integration') }}</h3>
                    <div class="fs_save_settings_container" v-if="has_pro && is_installed" >
                        <el-button
                            @click="saveSettings()"
                            :disabled="saving"
                            v-loading="saving"
                            class="fs_save_settings_btn fs_fluentcrm_save_btn"
                            type="success"
                        >
                            <span class="fs_save_full">{{ $t("Save Settings") }}</span>
                            <span class="fs_save_short">{{ $t("Save") }}</span>
                        </el-button>
                    </div>
                </div>
            </div>
            <div v-if="!fetching" class="fs_box_body fs_business_settings_wrapper">
                <template v-if="is_installed">
                    <form-builder :fields="settings_fields" :formData="settings"/>
                </template>
                <NarrowPromo
                    v-else
                    :image-src="fluentcrm_logo"
                    :heading="$t('email_marketing_automation_newsletter_crm')"
                >
                    <p><a target="_blank" rel="noopener" href="https://fluentcrm.com">FluentCRM</a> {{$t('email_marketing_automation_newsletter_crm_infos')}}</p>
                    <p>{{ $t('integrate_support_customers_with_fluentcrm') }}</p>
                    <el-button class="fs_promo_btn" v-loading="installing" :disabled="installing" @click="installFluentCRM()" type="success">
                        {{ $t('install_fluentcrm') }}
                    </el-button>
                </NarrowPromo>
            </div>
            <div style="padding: 20px; background: white;" class="fs_box_body" v-else>
                <el-skeleton :rows="5" animated/>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: 'FluentCRMIntegration',
    components: {
        FormBuilder,
        NarrowPromo
    },
    data() {
        return {
            crm_config: this.appVars.fluentcrm_config,
            fetching: true,
            settings: {},
            settings_fields: {},
            is_installed: false,
            saving: false,
            installing: false,
            fluentcrm_logo: ''
        }
    },
    methods: {
        fetchSettings() {
            this.fetching = true;
            this.$get('settings/fluentcrm-settings')
                .then(response => {
                    if (response.is_installed) {
                        this.settings = response.settings;
                        this.settings_fields = response.settings_fields;
                        let tags = this.settings.assigned_tags;
                        let lists = this.settings.assigned_list;

                        if(Array.isArray(tags)) {
                            this.settings.assigned_tags = tags.map((item) => !isNaN(item) ? parseInt(item) : item);
                        }else {
                            this.settings.assigned_tags = !isNaN(tags) ? parseInt(tags) : tags;
                        }

                        if(Array.isArray(lists)) {
                            this.settings.assigned_list = lists.map((list) => !isNaN(list) ? parseInt(list) : list);
                        }else {
                            this.settings.assigned_list = !isNaN(lists) ?  parseInt(lists) : lists;
                        }

                        this.fluentcrm_logo = response.fluentcrm_logo;
                        this.is_installed = true
                    } else {
                        this.is_installed = false
                        this.fluentcrm_logo = response.fluentcrm_logo;
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {

            if (this.settings.enabled == 'yes' && !this.settings.assigned_tags.length) {
                this.$notify.error({
                    message: 'Please select at least one FluentCRM Tag',
                    position: 'bottom-right'
                });
                return false;
            }

            this.saving = true;
            this.$post('settings', {
                settings_key: '_fluentcrm_intergration_settings',
                settings: this.settings
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    })
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        installFluentCRM() {
            this.installing = true;
            this.$post('settings/install-fluentcrm')
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    this.fetchSettings();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.installing = false;
                });
        }
    },
    mounted() {
        this.fetchSettings();
        this.$setTitle('FluentCRM Integration');
    }
}
</script>
