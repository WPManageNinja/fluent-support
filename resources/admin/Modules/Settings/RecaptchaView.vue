<template>
    <div class="fs_reCaptcha_settings">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t("reCAPTCHA Settings") }}</h3>
                    <div class="fs_save_settings_container">
                        <div class="fs_actions" v-if="hasSavedSettings">
                            <el-button class="fs_stroke_btn" @click="clearSettings">
                                {{ $t("Clear Settings") }}
                            </el-button>
                        </div>
                        <div>
                            <el-button
                                size="default"
                                type="success"
                                class="fs_save_settings_btn"
                                @click="saveSettings()"
                                :disabled="!isValid"
                            >{{ $t("Save Settings") }}</el-button>
                        </div>
                    </div>
                </div>
                <div class="fs_box_actions"></div>
            </div>
            <div
                v-if="!fetching"
                v-loading="loading"
                class="fs_box_body"
            >
                <div class="fs_reCaptcha_settings_body">
                    <div class="settings-body">
                        <p
                            v-if="appVars.auth_provider == 'fluent_auth'"
                            class="fs_recaptcha_fluent_auth_notice"
                        >
                            {{ $t("Signin and Registration forms are handled by the FluentAuth plugin. The settings below only apply to the Ticket Creation Form.") }}
                        </p>
                        <el-form label-position="top" label-width="140px">
                            <el-form-item class="fs_form_item" :label="$t('Enable reCAPTCHA')">
                                <el-switch
                                    v-model="reCaptchaEnabled"
                                    :active-text="$t('Enable reCAPTCHA for enhanced security')"
                                    @change="loadRecaptchaResponse"
                                />
                            </el-form-item>

                            <hr class="fs_divider">

                            <el-form-item class="fs_form_item fs_recaptcha_version" :label="$t('reCAPTCHA Version')">
                                <el-radio-group
                                    @change="loadRecaptchaResponse"
                                    v-model="reCaptchaVersion"
                                >
                                    <el-radio value="recaptcha_v2">{{ $t("Version 2") }}</el-radio>
                                    <el-radio value="recaptcha_v3">{{ $t("Version 3") }}</el-radio>
                                </el-radio-group>
                            </el-form-item>

                            <el-form-item class="fs_form_item" :label="$t('Site Key')">
                                <el-input
                                    class="fs_text_input"
                                    v-model="siteKey"
                                    @change="loadRecaptchaResponse"
                                    :placeholder="$t('Enter your reCAPTCHA site key')"
                                />
                            </el-form-item>

                            <el-form-item class="fs_form_item" :label="$t('Secret Key')">
                                <el-input
                                    class="fs_text_input"
                                    type="password"
                                    v-model="secretKey"
                                    @change="loadRecaptchaResponse"
                                    :placeholder="$t('Enter your reCAPTCHA secret key')"
                                />
                            </el-form-item>

                            <el-form-item class="fs_form_item" :label="$t('Use reCAPTCHA on')">
                                <div class="fs_recaptcha_checkboxes">
                                    <el-checkbox
                                        v-if="appVars.auth_provider != 'fluent_auth'"
                                        v-model="formContainingReCaptcha.login_form"
                                        @change="loadRecaptchaResponse"
                                        true-value="yes"
                                        false-value="no"
                                    >{{ $t("Login Form") }}</el-checkbox>
                                    <el-checkbox
                                        v-if="appVars.auth_provider != 'fluent_auth'"
                                        v-model="formContainingReCaptcha.signup_form"
                                        @change="loadRecaptchaResponse"
                                        true-value="yes"
                                        false-value="no"
                                    >{{ $t("Signup Form") }}</el-checkbox>
                                    <el-checkbox
                                        v-model="formContainingReCaptcha.ticket_form"
                                        @change="loadRecaptchaResponse"
                                        true-value="yes"
                                        false-value="no"
                                    >{{ $t("Ticket Creation Form") }}</el-checkbox>
                                </div>
                            </el-form-item>

                            <el-form-item
                                v-if="'recaptcha_v2' === reCaptchaVersion && siteKey"
                                class="fs_form_item"
                                :label="$t('Validate Captcha')"
                            >
                                <div
                                    class="g-recaptcha"
                                    id="recaptchaContainer"
                                    :data-sitekey="siteKey"
                                ></div>
                            </el-form-item>

                        </el-form>
                    </div>
                </div>
            </div>
            <div
                class="fs_box_body fs_skeleton_loader"
                v-else
            >
                <el-skeleton :rows="5" animated />
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'RecaptchaView',
    data() {
        return {
            reCaptchaVersion: "recaptcha_v2",
            formContainingReCaptcha: {
                login_form: 'no',
                signup_form: 'no',
                ticket_form: 'no',
            },
            siteKey: '',
            secretKey: '',
            captchaResponse: '',
            reCaptchaEnabled: false,
            fetching: false,
            loading: false,
            isValid: false,
            hasSavedSettings: false
        }
    },
    methods: {
        fetchSettings() {
            this.fetching = true;
            this.$get('settings/recaptcha-settings')
                .then(response => {
                    const data = response;
                    if (data != null && Object.keys(data).length > 0) {
                        this.reCaptchaVersion = data.reCaptcha_version;
                        this.siteKey = data.siteKey;
                        this.secretKey = data.secretKey;
                        this.formContainingReCaptcha = {
                            login_form: 'no',
                            signup_form: 'no',
                            ticket_form: 'no',
                            ...data.formContainingReCaptcha
                        };
                        this.reCaptchaEnabled = data.is_enabled === "true";
                        this.hasSavedSettings = true;
                    } else {
                        this.hasSavedSettings = false;
                    }
                    this.loadRecaptchaV3Script();
                    this.validate();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {
            if (!this.validate()) {
                return this.$notify({
                    message: this.$t("Missing required fields."),
                    type: "error",
                    position: "bottom-right",
                });
            }

            this.loading = true;
            this.$post('settings/recaptcha-settings', {
                key: "reCaptcha",
                reCaptcha: {
                    reCaptchaVersion: this.reCaptchaVersion,
                    formContainingReCaptcha: this.formContainingReCaptcha,
                    siteKey: this.siteKey,
                    secretKey: this.secretKey,
                    captchaResponse: this.captchaResponse,
                    reCaptchaEnabled: this.reCaptchaEnabled,
                }
            })
                .then(response => {
                    this.hasSavedSettings = true;
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
                    this.loading = false;
                });
        },
        clearSettings() {
            this.loading = true;
            this.$post('settings/recaptcha-settings', {
                key: "reCaptcha",
                reCaptcha: "clear-reCaptcha-settings",
            })
                .then(response => {
                    this.reCaptchaVersion = "recaptcha_v2";
                    this.siteKey = "";
                    this.secretKey = "";
                    this.formContainingReCaptcha = {
                        login_form: "no",
                        signup_form: "no",
                        ticket_form: "no",
                    };
                    this.reCaptchaEnabled = false;
                    this.hasSavedSettings = false;
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
                    this.loading = false;
                });
        },
        loadRecaptchaResponse() {
            this.validate();

            if ("recaptcha_v3" === this.reCaptchaVersion) {
                this.loadRecaptchaV3Script();

                if (window.grecaptcha) {
                    window.grecaptcha.ready(() => {
                        window.grecaptcha
                            .execute(this.siteKey, { action: "submit" })
                            .then((token) => {
                                this.captchaResponse = token;
                            });
                    });
                }
            } else {
                document.querySelector(".grecaptcha-badge")?.remove();

                let reCaptcha = document.getElementById("recaptchaContainer");
                if (reCaptcha) {
                    reCaptcha.innerHTML = "";
                }

                if (window.___grecaptcha_cfg) {
                    window.___grecaptcha_cfg.clients = {};
                }

                if (window.grecaptcha && reCaptcha) {
                    window.grecaptcha.render("recaptchaContainer", {
                        sitekey: this.siteKey,
                        callback: (token) => {
                            this.captchaResponse = token;
                        },
                    });
                }
            }
        },
        loadRecaptchaV3Script() {
            if ("recaptcha_v3" === this.reCaptchaVersion) {
                var v3Script = document.createElement("script");
                v3Script.src = `https://www.google.com/recaptcha/api.js?render=${this.siteKey}`;
                document.head.appendChild(v3Script);
            }
        },
        validate() {
            this.isValid = !!(this.siteKey && this.secretKey);
            return this.isValid;
        }
    },
    mounted() {
        this.$setTitle('reCAPTCHA Settings');
        var v2Script = document.createElement("script");
        v2Script.src = `https://www.google.com/recaptcha/api.js`;
        document.head.appendChild(v2Script);

        this.fetchSettings();
    },
    beforeUnmount() {
        document.querySelector(".grecaptcha-badge")?.remove();
    }
}
</script>
