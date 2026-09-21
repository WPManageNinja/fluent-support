<template>
    <div class="fs_license_manager">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t("License Management") }}</h3>
                </div>
                <div class="fs_box_actions fs_ticket_orders">
                    <el-button
                        text
                        icon="Refresh"
                        @click="fetchLicense()"
                    >
                    </el-button>
                </div>
            </div>

            <div v-if="!fetching" class="fs_box_body">
                <div v-loading="verifying" :class="'fs_license_' + status">
                    <!--
                      Every field below is guarded: an older Fluent Support Pro
                      does not send expires_human, license_key_masked or
                      in_grace_period, and the screen has to degrade rather than
                      render an empty card.
                    -->
                    <div
                        v-if="alert"
                        class="fs_license_alert"
                        :class="'is_' + alert.type"
                    >
                        <div class="fs_license_alert_body">
                            <strong v-text="alert.title"></strong>
                            <p v-if="alert.description" v-text="alert.description"></p>
                        </div>
                        <a
                            v-if="alert.action"
                            :href="alert.action.url"
                            target="_blank"
                            rel="noopener"
                            class="fs_license_alert_btn"
                            v-text="alert.action.label"
                        ></a>
                    </div>

                    <div v-if="showCard" class="fs_license_card">
                        <div class="fs_license_card_head">
                            <div class="fs_license_card_title">
                                <h3 v-text="licenseData.product_title || 'Fluent Support Pro'"></h3>
                                <p
                                    v-if="licenseData.variation_title"
                                    v-text="licenseData.variation_title"
                                ></p>
                            </div>
                            <span
                                class="fs_license_pill"
                                :class="'is_' + statusMeta.type"
                                v-text="statusMeta.label"
                            ></span>
                        </div>

                        <ul v-if="licenseRows.length" class="fs_license_meta">
                            <li v-for="row in licenseRows" :key="row.label">
                                <span class="fs_meta_label" v-text="row.label"></span>
                                <span class="fs_meta_value">
                                    <span
                                        v-if="row.badge"
                                        class="fs_license_pill is_soft"
                                        :class="'is_' + row.badge"
                                        v-text="row.value"
                                    ></span>
                                    <span v-else v-text="row.value"></span>
                                    <small v-if="row.hint" v-text="row.hint"></small>
                                </span>
                            </li>
                        </ul>

                        <div class="fs_license_card_footer">
                            <div class="fs_license_checked">
                                <span v-if="licenseData.last_checked_human">
                                    {{ $t('Last checked') }}: {{ licenseData.last_checked_human }}
                                </span>
                                <a href="#" @click.prevent="fetchLicense()">{{ $t('Refresh') }}</a>
                                <a
                                    v-if="licenseData.account_url"
                                    :href="licenseData.account_url"
                                    target="_blank"
                                    rel="noopener"
                                >{{ $t('Your Account') }}</a>
                                <a
                                    v-if="licenseData.support_url"
                                    :href="licenseData.support_url"
                                    target="_blank"
                                    rel="noopener"
                                >{{ $t('Get Support') }}</a>
                            </div>
                            <div class="fs_license_card_actions">
                                <a
                                    v-if="licenseData.manage_url"
                                    :href="licenseData.manage_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="el-button el-button--small"
                                >{{ $t('Manage Subscription') }}</a>
                                <el-button size="small" @click="deactivateLicense()">
                                    {{ $t('Deactivate') }}
                                </el-button>
                            </div>
                        </div>
                    </div>

                    <div v-if="showForm" class="fs_license_form_wrapper">
                        <div class="fs_license_form_content">
                            <div class="fs_license_form_header">
                                <h3 class="fs_license_form_title">
                                    {{ showCard ? $t('Activate a different license key') : $t("Customer Support Plugin for WordPress") }}
                                </h3>
                                <p v-if="!showCard" class="fs_license_form_description">
                                    {{ $t("Please Provide a license key of FluentSupport") }}
                                </p>
                            </div>
                            <div class="fs_license_input_wrapper">
                                <el-input
                                    v-model="licenseKey"
                                    :placeholder="$t('License key')"
                                    :class="{ 'fs_license_input_error': showLicenseKeyError }"
                                    class="fs_license_input"
                                    @input="showLicenseKeyError = false"
                                    @keyup.enter="verifyLicense()"
                                >
                                    <template #append>
                                        <el-button
                                            @click="verifyLicense()"
                                            class="fs_license_verify_btn"
                                        >
                                            <svg width="17" height="9" viewBox="0 0 17 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8.93775 5.25411C8.7467 6.36417 8.14642 7.36226 7.25544 8.05136C6.36445 8.74046 5.24749 9.07049 4.12507 8.9763C3.00264 8.88211 1.9563 8.37055 1.19262 7.5426C0.428933 6.71465 0.00339216 5.63048 1.87515e-06 4.50411C-0.00102824 3.37507 0.422395 2.28689 1.18626 1.45548C1.95013 0.62407 2.99862 0.11018 4.12371 0.0157691C5.2488 -0.0786414 6.36827 0.253328 7.26001 0.945811C8.15176 1.63829 8.75061 2.64069 8.93775 3.75411H16.5V5.25411H15V8.25411H13.5V5.25411H12V8.25411H10.5V5.25411H8.93775ZM4.5 7.50411C5.29565 7.50411 6.05871 7.18804 6.62132 6.62544C7.18393 6.06283 7.5 5.29976 7.5 4.50411C7.5 3.70847 7.18393 2.9454 6.62132 2.38279C6.05871 1.82019 5.29565 1.50411 4.5 1.50411C3.70435 1.50411 2.94129 1.82019 2.37868 2.38279C1.81607 2.9454 1.5 3.70847 1.5 4.50411C1.5 5.29976 1.81607 6.06283 2.37868 6.62544C2.94129 7.18804 3.70435 7.50411 4.5 7.50411V7.50411Z" fill="#525866"/>
                                            </svg>

                                            <span>{{ $t("Verify License") }}</span>
                                        </el-button>
                                    </template>
                                </el-input>
                                <p
                                    v-if="showLicenseKeyError"
                                    class="fs_license_error_message"
                                >
                                    {{ $t("Please enter your license key.") }}
                                </p>
                                <p v-else class="fs_license_hint_text">
                                    {{ $t("Please enter your license key.") }}
                                </p>
                            </div>
                        </div>
                        <p v-if="!showCard" class="fs_license_purchase_text">
                            {{ $t("Don't have a license key?") }}
                            <a
                                class="fs_doc_link fs_license_purchase_link"
                                target="_blank"
                                :href="licenseData.purchase_url"
                            >{{ $t("Purchase one here") }}</a>
                        </p>
                    </div>

                    <p v-if="showCard && !showForm" class="fs_license_switch">
                        {{ $t('Have a new license Key?') }}
                        <a href="#" class="fs_license_purchase_link" @click.prevent="showNewLicenseInput = true">{{ $t('Click here') }}</a>
                    </p>

                </div>
            </div>

            <div class="fs_box_body fs_skeleton_loader" v-else>
                <el-skeleton :rows="4" animated />
            </div>
        </div>

        <el-dialog
            v-model="deactivateDialogVisible"
            :title="$t('Deactivate this license?')"
            width="400"
            class="fs_dialog"
            :append-to-body="true"
        >
            <div class="fs_dialog_warning_content">
                <i class="el-icon el-message-box__status el-message-box-icon--warning"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896 448 448 0 0 1 0-896m0 192a58.432 58.432 0 0 0-58.24 63.744l23.36 256.384a35.072 35.072 0 0 0 69.76 0l23.296-256.384A58.432 58.432 0 0 0 512 256m0 512a51.2 51.2 0 1 0 0-102.4 51.2 51.2 0 0 0 0 102.4"></path></svg></i>
                <p>{{ $t('This site will stop receiving plugin updates until a license is activated again.') }}</p>
            </div>
            <template #footer>
                <div class="fs_popconfirm_actions">
                    <el-button
                        class="fs_outline_btn"
                        size="small"
                        @click="deactivateDialogVisible = false"
                    >{{ $t('Cancel') }}</el-button>
                    <el-button
                        class="fs_filled_btn"
                        size="small"
                        :loading="verifying"
                        @click="confirmDeactivate()"
                    >{{ $t('Yes, Deactivate') }}</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
export default {
    name: "LicenseManagement",
    data() {
        return {
            fetching: false,
            licenseData: {},
            verifying: false,
            licenseKey: "",
            showNewLicenseInput: false,
            showLicenseKeyError: false,
            deactivateDialogVisible: false,
        };
    },
    computed: {
        status() {
            return this.licenseData.status || "unregistered";
        },
        isExpired() {
            if (this.status === "expired" || this.licenseData.is_expired) {
                return true;
            }

            // Trust the date over the status: a license whose expiry has passed
            // and whose grace window has run out is expired, whatever the store
            // still calls it.
            const days = this.licenseData.days_remaining;

            return (
                !this.licenseData.is_lifetime &&
                !this.inGracePeriod &&
                days !== null &&
                days !== undefined &&
                days < 0
            );
        },
        // The store keeps a license valid for a while past its expiry date, so
        // "Active" alone would hide the fact that the clock is already running.
        inGracePeriod() {
            return !!this.licenseData.in_grace_period;
        },
        // Only these states describe a license we actually hold, so only these
        // are worth rendering as a card.
        showCard() {
            return ["valid", "expired", "disabled"].indexOf(this.status) !== -1;
        },
        showForm() {
            return !this.showCard || this.showNewLicenseInput;
        },
        renewUrl() {
            return this.licenseData.renew_url || this.licenseData.purchase_url;
        },
        subscriptionActive() {
            return (
                ["active", "trialing", "trial"].indexOf(
                    this.licenseData.subscription_status
                ) !== -1
            );
        },
        // An active subscription renews itself, so there is nothing to renew by hand.
        renewAction() {
            if (this.subscriptionActive) {
                return null;
            }

            return { url: this.renewUrl, label: this.$t("Renew License") };
        },
        supportAction() {
            if (!this.licenseData.support_url) {
                return null;
            }

            return {
                url: this.licenseData.support_url,
                label: this.$t("Contact Support"),
            };
        },
        expiringSoon() {
            const days = this.licenseData.days_remaining;
            return days !== null && days !== undefined && days >= 0 && days <= 30;
        },
        subscriptionNeedsAttention() {
            const status = this.licenseData.subscription_status;
            if (!status) {
                return false;
            }
            return (
                [
                    "cancelled",
                    "canceled",
                    "paused",
                    "on_hold",
                    "pending",
                    "failing",
                    "expired",
                ].indexOf(status) !== -1
            );
        },
        statusMeta() {
            if (this.inGracePeriod) {
                return { label: this.$t("Grace Period"), type: "warning" };
            }

            if (this.isExpired) {
                return { label: this.$t("Expired"), type: "danger" };
            }

            const map = {
                valid: { label: this.$t("Active"), type: "success" },
                expired: { label: this.$t("Expired"), type: "danger" },
                disabled: { label: this.$t("Disabled"), type: "danger" },
                invalid: { label: this.$t("Invalid"), type: "danger" },
                error: { label: this.$t("Error"), type: "danger" },
                unregistered: { label: this.$t("Not Activated"), type: "neutral" },
            };

            return (
                map[this.status] || {
                    label: this.humanize(this.status),
                    type: "neutral",
                }
            );
        },
        subscriptionMeta() {
            const status = this.licenseData.subscription_status;
            if (!status) {
                return null;
            }

            const types = {
                active: "success",
                trialing: "success",
                trial: "success",
                paused: "warning",
                on_hold: "warning",
                pending: "warning",
                failing: "danger",
                cancelled: "danger",
                canceled: "danger",
                expired: "danger",
            };

            return {
                label: this.humanize(status),
                type: types[status] || "neutral",
            };
        },
        expiryRow() {
            if (this.licenseData.is_lifetime) {
                return {
                    label: this.$t("Expires"),
                    value: this.$t("Lifetime"),
                    badge: "success",
                };
            }

            if (!this.licenseData.expires_human) {
                return null;
            }

            const days = this.licenseData.days_remaining;
            let hint = "";

            if (days !== null && days !== undefined) {
                if (days === -1) {
                    hint = this.$t("expired 1 day ago");
                } else if (days < 0) {
                    hint = this.transWith("expired %d days ago", Math.abs(days));
                } else if (days === 0) {
                    hint = this.$t("expires today");
                } else if (days === 1) {
                    hint = this.$t("in 1 day");
                } else {
                    hint = this.transWith("in %d days", days);
                }
            }

            return {
                label: this.$t("Expires"),
                value: this.licenseData.expires_human,
                badge:
                    days !== null && days !== undefined && days < 0 ? "danger" : "",
                hint: hint,
            };
        },
        // The grace window is worth naming in the card, but not dating: its
        // length is our assumption about the store, not something the store
        // reports, so promising a specific end date would be guessing out loud.
        graceRow() {
            if (!this.inGracePeriod) {
                return null;
            }

            return {
                label: this.$t("Updates & Support"),
                value: this.$t("Ending soon"),
                badge: "warning",
            };
        },
        licenseRows() {
            const rows = [];

            if (this.expiryRow) {
                rows.push(this.expiryRow);
            }

            if (this.graceRow) {
                rows.push(this.graceRow);
            }

            if (this.subscriptionMeta) {
                rows.push({
                    label: this.$t("Subscription"),
                    value: this.subscriptionMeta.label,
                    badge: this.subscriptionMeta.type,
                });
            }

            if (this.licenseData.license_key_masked) {
                rows.push({
                    label: this.$t("License Key"),
                    value: this.licenseData.license_key_masked,
                });
            }

            if (
                this.licenseData.customer_name ||
                this.licenseData.customer_email_masked
            ) {
                rows.push({
                    label: this.$t("Licensed To"),
                    value:
                        this.licenseData.customer_name ||
                        this.licenseData.customer_email_masked,
                    hint: this.licenseData.customer_name
                        ? this.licenseData.customer_email_masked
                        : "",
                });
            }

            return rows;
        },
        alert() {
            const remoteMessage =
                this.licenseData.error_message || this.licenseData.message;

            // Inside the grace window the license still works, so this is a
            // warning rather than an error - but it has to say when it stops.
            if (this.inGracePeriod) {
                return {
                    type: "warning",
                    title: this.licenseData.expires_human
                        ? this.transWith(
                              "Your license expired on %s",
                              this.licenseData.expires_human
                          )
                        : this.$t("Your license has expired."),
                    description: this.$t(
                        "You are in a grace period, so everything keeps working for now. Updates and support will end soon - renew your license to keep them."
                    ),
                    action: this.renewAction,
                };
            }

            if (this.isExpired) {
                return {
                    type: "danger",
                    title: this.licenseData.expires_human
                        ? this.transWith(
                              "Your license expired on %s",
                              this.licenseData.expires_human
                          )
                        : this.$t("Looks like your license has expired."),
                    description: this.$t(
                        "Renew your license to keep receiving plugin updates, new features and support."
                    ),
                    action: this.renewAction,
                };
            }

            if (this.status === "disabled") {
                return {
                    type: "danger",
                    title: this.$t("This license has been disabled."),
                    description:
                        remoteMessage ||
                        this.$t("Please contact our support team for assistance."),
                    action: this.supportAction,
                };
            }

            if (this.status === "invalid" || this.status === "error") {
                return {
                    type: "danger",
                    title: this.$t("We could not verify this license."),
                    description: remoteMessage || "",
                    action: this.supportAction,
                };
            }

            if (this.status === "valid" && this.subscriptionNeedsAttention) {
                return {
                    type: "warning",
                    title: this.transWith(
                        "Your subscription is not active (%s)",
                        this.subscriptionMeta.label
                    ),
                    description: this.licenseData.expires_human
                        ? this.transWith(
                              "Your license will not renew on its own. It expires on %s.",
                              this.licenseData.expires_human
                          )
                        : "",
                    action: this.renewAction,
                };
            }

            // A license that is about to expire on an active subscription renews
            // itself, so it is not worth warning about.
            if (
                this.status === "valid" &&
                this.expiringSoon &&
                !this.subscriptionActive
            ) {
                return {
                    type: "warning",
                    title:
                        this.licenseData.days_remaining === 0
                            ? this.$t("Your license expires today.")
                            : this.transWith(
                                  "Your license expires in %d days",
                                  this.licenseData.days_remaining
                              ),
                    action: this.renewAction,
                };
            }

            return null;
        },
    },
    methods: {
        // Translate a string that carries %s / %d placeholders, so translators
        // get a full sentence instead of concatenated fragments.
        transWith(key, ...args) {
            let text = this.$t(key);

            args.forEach((arg) => {
                text = text.replace(/%[ds]/, arg);
            });

            return text;
        },
        humanize(value) {
            if (!value) {
                return "";
            }
            const text = String(value).replace(/_/g, " ");
            return text.charAt(0).toUpperCase() + text.slice(1);
        },
        async fetchLicense() {
            this.showLicenseKeyError = false;
            this.fetching = true;
            await this.$get("pro/license", { verify: true })
                .then((response) => {
                    this.licenseData = response;
                })
                .catch((errors) => {
                    // $handleError unwraps the jqXHR's responseJSON itself; do not
                    // pre-flatten it here or the raw response ends up on screen.
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        async verifyLicense() {
            if (!this.licenseKey) {
                this.showLicenseKeyError = true;
                this.$notify({
                    type: "error",
                    message: this.$t("Please provide a license key"),
                    position: "bottom-right",
                });
                return;
            }

            this.showLicenseKeyError = false;
            this.verifying = true;

            await this.$post("pro/license", {
                license_key: this.licenseKey,
            })
                .then((response) => {
                    this.licenseData = response.license_data;
                    this.licenseKey = "";
                    this.showNewLicenseInput = false;
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });
                })
                .catch((errorResponse) => {
                    this.$handleError(errorResponse);
                })
                .always(() => {
                    this.verifying = false;
                });
        },
        deactivateLicense() {
            this.deactivateDialogVisible = true;
        },
        confirmDeactivate() {
            this.verifying = true;

            // POST rather than DELETE on purpose: this route exists on every
            // Fluent Support Pro version, the DELETE one does not.
            this.$post("pro/remove-license")
                .then((response) => {
                    this.licenseData = response.license_data || {};
                    this.showNewLicenseInput = false;
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
                    this.verifying = false;
                    this.deactivateDialogVisible = false;
                });
        },
    },
    mounted() {
        this.fetchLicense();
        this.$setTitle(this.$t("License Management"));
    },
};
</script>
