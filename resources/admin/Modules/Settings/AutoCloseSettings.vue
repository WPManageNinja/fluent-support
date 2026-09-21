<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t("Auto Close Settings") }}</h3>
                <div class="fs_save_settings_container" v-if="has_pro">
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
        <NarrowPromo
            v-if="!has_pro"
            :heading="$t('Auto Close tickets based on active days or based on tags or waiting time.')"
            :description="$t('pro_promo')"
            :button-text="$t('Upgrade To Pro')"
            utm-content="feature_lock_auto_close"
        />
        <template v-else>
            <div
                style="padding: 20px"
                v-if="!fetching"
                v-loading="loading"
                class="fs_box_body"
            >
                <div>
                    <el-checkbox
                        v-model="settings.enabled"
                        true-value="yes"
                        false-value="no"
                    >
                        {{ $t("Enable Auto Closing Inactive Tickets") }}
                    </el-checkbox>
                </div>
                <form-builder
                    v-if="app_ready && settings.enabled == 'yes'"
                    :fields="fields"
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
        </template>
    </div>
</template>

<script type="text/babel">
import FormBuilder from "../../Pieces/FormElements/_FormBuilder";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: "AutoCloseSettings",
    components: {
        FormBuilder,
        NarrowPromo
    },
    data() {
        return {
            settings: {},
            fields: {},
            fetching: false,
            loading: false,
            app_ready: false,
            settings_key: "auto_close_settings"
        }
    },
    methods: {
        fetchSettings() {
            this.fetching = true;
            this.$get("settings/auto-close", {
                settings_key: this.settings_key,
                with: ["fields"]
            })
                .then(response => {
                    this.settings = response.settings;
                    this.fields = response.fields;
                    this.app_ready = true;
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {
            this.loading = true;
            this.$post("settings/auto-close", {
                settings_key: this.settings_key,
                settings: this.settings
            })
                .then(response => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right"
                    });
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        if (this.has_pro) {
            this.fetchSettings();
        }
        this.$setTitle(this.$t("Auto Close Settings"));
    }
}
</script>

