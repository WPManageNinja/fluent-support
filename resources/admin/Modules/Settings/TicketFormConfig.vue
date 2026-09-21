<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t("Ticket Form Settings") }}</h3>
                <div class="fs_save_settings_container" v-if="has_pro" >
                    <el-button
                        @click="saveSettings()"
                        :disabled="saving"
                        v-loading="saving"
                        class="fs_save_settings_btn"
                        type="success"
                    >
                        {{ $t("Save Settings") }}
                    </el-button>
                </div>
            </div>
            <div class="fs_box_actions"></div>
        </div>
        <template v-if="has_pro">
            <div class="fs_box_body fs_business_settings_wrapper" v-if="!fetching">
                <form-builder :fields="settings_fields" :form-data="settings" />
            </div>

            <div
                class="fs_box_body fs_skeleton_loader"
                v-else
            >
                <el-skeleton :rows="5" animated />
            </div>
        </template>
            <NarrowPromo
                v-else
                :heading="$t('ticket_form_notice_for_pro')"
                :description="$t('pro_promo')"
                :button-text="$t('Upgrade To Pro')"
                utm-content="feature_lock_ticket_form_customization"
            />
    </div>
</template>

<script type="text/babel">
import FormBuilder from "../../Pieces/FormElements/_FormBuilder";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: "TicketFormConfig",
    components: {
        FormBuilder,
        NarrowPromo,
    },
    data() {
        return {
            settings: {},
            fetching: false,
            settings_fields: {},
            saving: false,
            settings_key: "ticket_form_settings",
        };
    },
    computed: {
        has_pro() {
            return this.appVars?.has_pro || false;
        },
    },
    watch: {
        'settings.disabled_fields'() {
            if (this.settings.disabled_fields && this.settings.disabled_fields.includes("product_services")) {
                this.settings.product_required_field = 'no';
            }
        }
    },
    methods: {
        fetchSettings() {
            this.fetching = true;
            this.$get("pro/form-settings", {
                with: ["fields"],
            })
                .then((response) => {
                    this.settings = response.settings;
                    this.settings_fields = response.settings_fields;
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
            this.$post("pro/form-settings", {
                settings: this.settings,
            })
                .then((response) => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        if (this.has_pro) {
            this.fetchSettings();
        }
        this.$setTitle(this.$t("Ticket Form Config"));
    }
};
</script>
