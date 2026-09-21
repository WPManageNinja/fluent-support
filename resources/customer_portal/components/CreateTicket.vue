<template>
    <div class="fs_create_ticket_container">
        <!-- Back Button -->
        <back-button/>

        <div class="fs_ticket_form_container">
            <div class="fs_ticket_header">
                <label>{{ $t('submit_heading') }}</label>
            </div>
            <el-form :model="ticket" label-position="top" class="fs_ticket_form">
                <el-form-item :label="$t('subject')" class="fs_input_wrapper">
                    <el-input
                        size="default"
                        v-model="ticket.title"
                        :class="{ 'error': errors.get('title') }"
                        :placeholder="$t('subject_placeholder')"
                    />

                    <div v-if="errors.get('title')" class="error-message">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM7.4 7.4V11H8.6V7.4H7.4ZM7.4 5V6.2H8.6V5H7.4Z"
                                fill="#FB3748"/>
                        </svg>
                        <error :error="errors.get('title')"/>
                    </div>

                    <div v-if="shouldShowSuggestions && ticket.title.length" class="fs_suggestions_popover">
                        <div class="fs_suggestions_header">
                            <label>{{ $t('Suggested Articles') }}</label>
                            <el-button
                                type="text"
                                class="fs_close_button"
                                @click="closeSuggestions"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                     fill="none">
                                    <path
                                        d="M9.99956 8.93949L13.7121 5.22699L14.7726 6.28749L11.0601 9.99999L14.7726 13.7125L13.7121 14.773L9.99956 11.0605L6.28706 14.773L5.22656 13.7125L8.93906 9.99999L5.22656 6.28749L6.28706 5.22699L9.99956 8.93949Z"
                                        fill="#525866"/>
                                </svg>
                            </el-button>
                        </div>
                        <div v-for="(suggestion, index) in suggestions" :key="index" class="fs_suggestions_title">
                            <a :href="suggestion.link" target="_blank" class="fs_article_item">
                                <el-icon class="fs_article_icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16.75 7V16.7448C16.7507 16.8432 16.732 16.9409 16.6949 17.0322C16.6579 17.1234 16.6032 17.2065 16.534 17.2766C16.4649 17.3468 16.3826 17.4026 16.2919 17.4409C16.2011 17.4792 16.1037 17.4993 16.0052 17.5H3.99475C3.79736 17.5 3.60804 17.4216 3.4684 17.2821C3.32875 17.1426 3.2502 16.9534 3.25 16.756V3.244C3.25 2.84125 3.58675 2.5 4.0015 2.5H12.2477L16.75 7ZM15.25 7.75H11.5V4H4.75V16H15.25V7.75ZM7 6.25H9.25V7.75H7V6.25ZM7 9.25H13V10.75H7V9.25ZM7 12.25H13V13.75H7V12.25Z"
                                            fill="#525866"/>
                                    </svg>
                                </el-icon>
                                <p>{{ suggestion.title }}</p>
                            </a>
                        </div>
                    </div>
                </el-form-item>

                <el-form-item class="fs_tk_suggestions">
                    <label class="fs_ticket_details_label"> {{ $t('ticket_details') }}</label>
                    <simple-wp-editor :height="150" :is_direct_paste="true" v-model="ticket.content"/>
                    <p class="fs_tk_help">{{ $t('details_help') }}</p>

                    <div v-if="errors.get('content')" class="error-message">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM7.4 7.4V11H8.6V7.4H7.4ZM7.4 5V6.2H8.6V5H7.4Z"
                                fill="#FB3748"/>
                        </svg>
                        <error :error="errors.get('content')"/>
                    </div>
                </el-form-item>

                <attachment-form v-if="appVars.has_file_upload" :ticket="ticket" :attachments="attachments"/>

                <div v-if="products.length || Object.keys(priorities).length" class="fs_tk_row">
                    <div v-if="products.length" class="fs_tk_col">
                        <el-form-item class="fs_ticket_product fs_input_label" :label="$t('product_services')"
                                      :required="isProductFieldRequired">
                            <el-select clearable filterable v-model="ticket.product_id"
                                       :class="{ 'error': errors.get('product_id') }"
                                       size="large"
                                       :placeholder="$t('service_placeholder')">
                                <el-option v-for="product in products" :key="product.id" :value="product.id"
                                           :label="product.title"></el-option>
                            </el-select>

                            <div v-if="errors.get('product_id')" class="error-message">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM7.4 7.4V11H8.6V7.4H7.4ZM7.4 5V6.2H8.6V5H7.4Z"
                                        fill="#FB3748"/>
                                </svg>
                                <error :error="errors.get('product_id')"/>
                            </div>
                        </el-form-item>
                    </div>
                </div>

                <el-form-item :label="$t('priority')" v-if="Object.keys(priorities).length">
                    <el-select
                        v-model="ticket.client_priority"
                        :placeholder="$t('Normal')"
                        size="large"
                        :class="{ 'error': errors.get('priority') }"
                    >
                        <el-option
                            v-for="(priority, key) in priorities"
                            :key="key"
                            :label="priority"
                            :value="key"
                        />
                    </el-select>

                    <div v-if="errors.get('client_priority')" class="error-message">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM7.4 7.4V11H8.6V7.4H7.4ZM7.4 5V6.2H8.6V5H7.4Z"
                                fill="#FB3748"/>
                        </svg>
                        <error :error="errors.get('client_priority')"/>
                    </div>
                </el-form-item>

                <custom-fields-form :ticket="ticket" :custom_data="custom_data" :exceptions="exceptions"/>

                <el-form-item
                    v-if="recaptchaEnabled && recaptchaVersion === 'recaptcha_v2'"
                    class="fs_recaptcha_container"
                >
                    <div id="fs_ticket_recaptcha"></div>
                    <div v-if="recaptchaLoadFailed" class="error-message fs_recaptcha_retry">
                        {{ $t('reCAPTCHA failed to load.') }}
                        <el-button type="text" @click="retryRecaptchaLoad">{{ $t('Retry') }}</el-button>
                    </div>
                </el-form-item>

                <el-form-item
                    v-if="recaptchaEnabled && recaptchaVersion === 'recaptcha_v3' && recaptchaLoadFailed"
                    class="fs_recaptcha_container"
                >
                    <div class="error-message fs_recaptcha_retry">
                        {{ $t('reCAPTCHA failed to load.') }}
                        <el-button type="text" @click="retryRecaptchaLoad">{{ $t('Retry') }}</el-button>
                    </div>
                </el-form-item>

                <div v-if="generalError" class="error-message fs_create_general_error">{{ generalError }}</div>

                <el-form-item class="fs_submit_button_container">
                    <el-button
                        type="primary"
                        :loading="creating"
                        @click="create"
                        class="fs_create_ticket_button"
                    >
                        {{ $t('btn_text') }}
                    </el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script type="text/babel">
import SimpleWpEditor from '@/common/SimpleWpEditor.vue'
import Error from '@/common/Error.vue'
import Errors from '@/common/Errors.js'
import CustomFieldsForm from "./_CustomFieldForm";
import AttachmentForm from "./_AttachmentForm";
import BackButton from "./pieces/BackButton";
import debounce from "lodash/debounce";

export default {
    name: 'CreateTicket',
    components: {
        SimpleWpEditor,
        Error,
        CustomFieldsForm,
        AttachmentForm,
        BackButton
    },
    data() {
        return {
            errors: new Errors(),
            generalError: '',
            creating: false,
            editorType: 'paragraph',
            editorMode: 'Visual',
            custom_data: {},
            ticket: {
                title: '',
                content: '',
                product_id: '',
                client_priority: 'normal',
                name: '',
                mailbox_id: this.appVars.mailbox_id ? this.appVars.mailbox_id : null,
            },
            products: this.appVars.support_products,
            priorities: this.appVars.customer_ticket_priorities,
            attachments: [],
            suggestions: [],
            fetchingSuggestions: false,
            shouldShowSuggestions: false,
            recaptchaToken: '',
            recaptchaWidgetId: null,
            recaptchaPollTimeoutId: null,
            recaptchaPollAttempts: 0,
            recaptchaLoadFailed: false,
            isUnmounted: false,
            recaptchaScriptLoadHandler: null,
            recaptchaScriptErrorHandler: null,
            recaptchaV3WaitTimeoutId: null,
            recaptchaBadgePollTimeoutId: null,
            recaptchaBadgePollAttempts: 0,
            recaptchaExecuteTimeoutId: null,
            recaptchaScriptPendingReload: false,
        }
    },
    computed: {
        exceptions() {
            return this.errors.errors;
        },
        recaptchaEnabled() {
            return !!(this.appVars.recaptcha && this.appVars.recaptcha.enabled);
        },
        recaptchaVersion() {
            return this.appVars.recaptcha ? this.appVars.recaptcha.version : '';
        },
        recaptchaSiteKey() {
            return this.appVars.recaptcha ? this.appVars.recaptcha.site_key : '';
        }
    },
    watch: {
        'ticket.title': function (newVal, oldVal) {

            if (!this.appVars.has_doc_integration) {
                return;
            }

            if (this.hasQueryOrSpace(newVal)) {
                this.fetchingSuggestions = true;
                this.debouncedGetSuggestions();
            } else {
                this.fetchingSuggestions = false;
                this.suggestions = [];
            }
        }
    },
    methods: {
        async create() {
            this.errors.clear()
            this.generalError = ''
            this.creating = true

            const recaptchaToken = await this.getRecaptchaToken();

            if (this.isUnmounted) {
                return;
            }

            if (this.recaptchaEnabled && !recaptchaToken) {
                this.generalError = this.$t('Please verify that you are not a robot.');
                this.creating = false;
                return;
            }

            this.$post('tickets', {
                ...this.ticket,
                attachments: this.attachments,
                custom_data: this.custom_data,
                'g-recaptcha-response': recaptchaToken
            })
                .then(response => {
                    this.$router.push({name: 'view_ticket', params: {ticket_id: response.ticket.serial_number || response.ticket.id}});
                })
                .catch((errors) => {
                    this.errors.record(errors)
                    // Portal Rest.js rejects with the parsed JSON body directly (no responseJSON
                    // wrapper). A top-level `message` is a general, non-field error — show it
                    // persistently above the submit button.
                    this.generalError = (errors && errors.message) ? errors.message : ''
                    this.creating = false;
                    this.resetRecaptcha();
                })
                .finally(() => {
                    this.creating = false
                })
        },
        ensureRecaptchaScript() {
            // Loaded lazily here (rather than enqueued for every portal route) since
            // this component is the only consumer of the Google reCAPTCHA script.
            // Dedupe against an already-present tag with this id — whether from an
            // earlier mount of this component, or another plugin's own enqueue of the
            // same handle ('recaptcha' -> 'recaptcha-js'), e.g. AuthHandler's own v2
            // recaptcha enqueue for the login form.
            const existingScript = document.getElementById('recaptcha-js');

            if (existingScript) {
                if (this.isRecaptchaScriptCompatible(existingScript)) {
                    return;
                }

                // The shared tag was loaded for a different version/site key — swap it
                // so v3's execute() isn't called against the wrong render key.
                this.replaceRecaptchaScript(existingScript);
                return;
            }

            this.appendRecaptchaScript();
        },
        buildRecaptchaScriptSrc() {
            let src = 'https://www.google.com/recaptcha/api.js';

            if (this.recaptchaVersion === 'recaptcha_v3') {
                src += '?render=' + this.recaptchaSiteKey;
            }

            return src;
        },
        isRecaptchaScriptCompatible(scriptEl) {
            if (!scriptEl) {
                return false;
            }

            if (this.recaptchaVersion !== 'recaptcha_v3') {
                return true;
            }

            try {
                const scriptUrl = new URL(scriptEl.src, window.location.href);
                return scriptUrl.searchParams.get('render') === this.recaptchaSiteKey;
            } catch (e) {
                return false;
            }
        },
        appendRecaptchaScript() {
            const scriptEl = document.createElement('script');
            scriptEl.id = 'recaptcha-js';
            scriptEl.src = this.buildRecaptchaScriptSrc();
            scriptEl.async = true;
            scriptEl.defer = true;

            this.markRecaptchaScriptPending(scriptEl);
            document.head.appendChild(scriptEl);
        },
        replaceRecaptchaScript(existingScript) {
            const freshScript = document.createElement('script');
            freshScript.id = 'recaptcha-js';
            freshScript.src = this.buildRecaptchaScriptSrc();
            freshScript.async = true;
            freshScript.defer = true;

            this.markRecaptchaScriptPending(freshScript);
            existingScript.replaceWith(freshScript);
        },
        markRecaptchaScriptPending(scriptEl) {
            // window.grecaptcha can still hold a stale instance (e.g. from the script
            // tag we're about to replace) until this specific element finishes loading
            // — gate readiness on it explicitly so waitForRecaptchaScript() can't treat
            // the old global as ready for the newly requested version/site key.
            this.recaptchaScriptPendingReload = true;

            scriptEl.addEventListener('load', () => {
                this.recaptchaScriptPendingReload = false;
            }, {once: true});
        },
        loadRecaptchaWidget() {
            if (!this.recaptchaEnabled || this.recaptchaVersion !== 'recaptcha_v2') {
                return;
            }

            this.recaptchaLoadFailed = false;

            if (this.renderRecaptchaWidget()) {
                return;
            }

            // Primary path: render as soon as the enqueued recaptcha script actually
            // finishes loading, rather than assuming it completes within the poll window.
            this.attachRecaptchaScriptListeners();

            // Fallback path: bounded poll, in case the script tag can't be observed
            // directly or its load event already fired before this component mounted.
            this.pollForRecaptchaWidget();
        },
        renderRecaptchaWidget() {
            if (this.recaptchaWidgetId !== null || this.isUnmounted) {
                return true;
            }

            const container = document.getElementById('fs_ticket_recaptcha');

            if (!(window.grecaptcha && window.grecaptcha.render && container)) {
                return false;
            }

            this.recaptchaWidgetId = window.grecaptcha.render(container, {
                sitekey: this.recaptchaSiteKey,
                callback: (token) => {
                    this.recaptchaToken = token;
                },
                'expired-callback': () => {
                    this.recaptchaToken = '';
                }
            });
            this.recaptchaLoadFailed = false;

            return true;
        },
        attachRecaptchaScriptListeners() {
            // WordPress appends "-js" to the enqueued script handle ('recaptcha',
            // see CustomerPortalHandler::enqueueScripts()) to build the <script> tag's id.
            const scriptEl = document.getElementById('recaptcha-js');

            if (!scriptEl) {
                return;
            }

            this.recaptchaScriptLoadHandler = () => this.pollForRecaptchaWidget();
            this.recaptchaScriptErrorHandler = () => {
                this.recaptchaLoadFailed = true;
            };
            scriptEl.addEventListener('load', this.recaptchaScriptLoadHandler, {once: true});
            scriptEl.addEventListener('error', this.recaptchaScriptErrorHandler, {once: true});
        },
        pollForRecaptchaWidget() {
            const maxAttempts = 50; // ~15s fallback poll, in case the script's load event never fires

            if (this.recaptchaPollTimeoutId) {
                clearTimeout(this.recaptchaPollTimeoutId);
                this.recaptchaPollTimeoutId = null;
            }

            if (this.renderRecaptchaWidget() || this.isUnmounted) {
                return;
            }

            if (this.recaptchaPollAttempts >= maxAttempts) {
                this.recaptchaLoadFailed = true;
                return;
            }

            this.recaptchaPollAttempts++;
            this.recaptchaPollTimeoutId = setTimeout(() => this.pollForRecaptchaWidget(), 300);
        },
        retryRecaptchaLoad() {
            this.recaptchaPollAttempts = 0;
            this.recaptchaLoadFailed = false;

            const scriptEl = document.getElementById('recaptcha-js');

            // Reload when the script never loaded, or when the tag present belongs to
            // a different version/site key (e.g. AuthHandler's own recaptcha enqueue
            // won the shared 'recaptcha-js' id) — window.grecaptcha existing doesn't
            // mean it's usable for this form.
            if (!window.grecaptcha || !this.isRecaptchaScriptCompatible(scriptEl)) {
                this.reloadRecaptchaScript();

                // loadRecaptchaWidget() below is a no-op for v3 (no widget to render),
                // so it never attaches load/error handling to the replacement script.
                // Attach it here instead — otherwise a second failure goes unnoticed
                // until the next submit's bounded 15s wait times out.
                if (this.recaptchaVersion === 'recaptcha_v3') {
                    this.waitForRecaptchaScript();
                }
            }

            this.loadRecaptchaWidget();
        },
        reloadRecaptchaScript() {
            const failedScript = document.getElementById('recaptcha-js');

            if (!failedScript) {
                return;
            }

            if (this.recaptchaScriptLoadHandler) {
                failedScript.removeEventListener('load', this.recaptchaScriptLoadHandler);
                failedScript.removeEventListener('error', this.recaptchaScriptErrorHandler);
            }

            // A <script> tag that already fired 'error' won't be retried by the browser
            // on its own — replace it with a fresh element (cache-busted) so Retry can
            // recover from a genuine script-load failure, not just a slow one. Rebuilt
            // from the current version/site key rather than cache-busting the existing
            // src, since that src may belong to an incompatible shared tag.
            const retryUrl = new URL(this.buildRecaptchaScriptSrc(), window.location.href);
            retryUrl.searchParams.set('fs_retry', Date.now());

            const freshScript = document.createElement('script');
            freshScript.id = 'recaptcha-js';
            freshScript.src = retryUrl.toString();
            freshScript.async = true;

            this.markRecaptchaScriptPending(freshScript);
            failedScript.replaceWith(freshScript);
        },
        resetRecaptcha() {
            if (this.recaptchaVersion !== 'recaptcha_v2') {
                return;
            }

            this.recaptchaToken = '';

            if (window.grecaptcha && window.grecaptcha.reset && this.recaptchaWidgetId !== null) {
                window.grecaptcha.reset(this.recaptchaWidgetId);
            }
        },
        getRecaptchaToken() {
            if (!this.recaptchaEnabled) {
                return Promise.resolve('');
            }

            if (this.recaptchaVersion === 'recaptcha_v3') {
                return this.waitForRecaptchaScript().then((isReady) => {
                    if (!isReady) {
                        return '';
                    }

                    return new Promise((resolve) => {
                        let settled = false;

                        const finish = (token) => {
                            if (settled) {
                                return;
                            }
                            settled = true;
                            clearTimeout(this.recaptchaExecuteTimeoutId);
                            this.recaptchaExecuteTimeoutId = null;
                            resolve(token);
                        };

                        // ready()'s callback firing, and execute() resolving/rejecting, isn't
                        // guaranteed with an invalid/incompatible site key or grecaptcha
                        // instance — bound the wait so create() doesn't hang forever with
                        // creating=true. Tracked on `this` (like recaptchaV3WaitTimeoutId)
                        // so beforeUnmount() can cancel it early.
                        this.recaptchaExecuteTimeoutId = setTimeout(() => finish(''), 15000);

                        try {
                            window.grecaptcha.ready(() => {
                                try {
                                    window.grecaptcha
                                        .execute(this.recaptchaSiteKey, {action: 'create_ticket'})
                                        .then(finish)
                                        .catch(() => finish(''));
                                } catch (e) {
                                    finish('');
                                }

                                // Google may create the badge lazily on this first execute()
                                // call rather than at script-ready time. The mount-time poll
                                // window may already have elapsed by the time the user
                                // actually submits, so re-poll here in case it just appeared.
                                this.recaptchaBadgePollAttempts = 0;
                                this.pollForRecaptchaBadge();
                            });
                        } catch (e) {
                            finish('');
                        }
                    });
                });
            }

            return Promise.resolve(this.recaptchaToken);
        },
        waitForRecaptchaScript() {
            // A stale window.grecaptcha can survive a script replacement/reload until
            // the new element's own 'load' fires — don't trust it as ready until then.
            if (window.grecaptcha && !this.recaptchaScriptPendingReload) {
                this.recaptchaLoadFailed = false;
                return Promise.resolve(true);
            }

            // The v3 flow has no visible widget to retry from, so — unlike the v2
            // poll — a slow-loading script here is handled by waiting out the same
            // bounded window inline, rather than failing the first submit attempt.
            return new Promise((resolve) => {
                const maxWaitMs = 15000;
                let settled = false;

                const finish = (isReady) => {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    clearTimeout(timeoutId);
                    // Surfaces a Retry control (see fs_recaptcha_container v3 block)
                    // instead of leaving the user to hit the same 15s wait again on
                    // every submit with only a generic "not a robot" error.
                    this.recaptchaLoadFailed = !isReady;
                    resolve(isReady);
                };

                const scriptEl = document.getElementById('recaptcha-js');

                if (scriptEl) {
                    scriptEl.addEventListener('load', () => finish(!!window.grecaptcha), {once: true});
                    scriptEl.addEventListener('error', () => finish(false), {once: true});
                }

                const timeoutId = setTimeout(() => finish(!!window.grecaptcha), maxWaitMs);
                this.recaptchaV3WaitTimeoutId = timeoutId;
            });
        },
        pollForRecaptchaBadge() {
            const maxAttempts = 50; // ~15s, mirrors the widget poll's bound

            if (this.recaptchaBadgePollTimeoutId) {
                clearTimeout(this.recaptchaBadgePollTimeoutId);
                this.recaptchaBadgePollTimeoutId = null;
            }

            if (this.isUnmounted) {
                return;
            }

            if (document.querySelector('.grecaptcha-badge')) {
                this.showRecaptchaBadge(true);
                return;
            }

            if (this.recaptchaBadgePollAttempts >= maxAttempts) {
                return;
            }

            this.recaptchaBadgePollAttempts++;
            this.recaptchaBadgePollTimeoutId = setTimeout(() => this.pollForRecaptchaBadge(), 300);
        },
        showRecaptchaBadge(visible) {
            // The badge is portal-wide hidden by default (all_public.scss) so it doesn't
            // float on pages that don't use v3. Un-hide it here while this form is mounted,
            // per Google's reCAPTCHA ToS requirement that the badge stay visible.
            const badge = document.querySelector('.grecaptcha-badge');

            if (badge) {
                badge.style.visibility = visible ? 'visible' : 'hidden';
            }
        },
        closeSuggestions() {
            this.shouldShowSuggestions = false;
        },
        getSuggestions() {
            this.$get('search-doc', {
                search: this.ticket.title
            })
                .then(response => {
                    this.suggestions = response;

                    if (this.suggestions.length) {
                        this.shouldShowSuggestions = true;
                    }
                })
                .catch((errors) => {
                    console.log(errors);
                    // this.errors.record(errors);
                })
                .finally(() => {
                    this.fetchingSuggestions = false;
                });
        },

        hasQueryOrSpace(search) {
            return search && (search.length >= 5 || (search.split(' ').length > 1));
        }
    },
    created() {
        this.debouncedGetSuggestions = debounce(this.getSuggestions, 500);
    },
    mounted() {
        if (this.recaptchaEnabled) {
            this.ensureRecaptchaScript();
        }

        this.loadRecaptchaWidget();

        if (this.recaptchaEnabled && this.recaptchaVersion === 'recaptcha_v3') {
            this.waitForRecaptchaScript().then((isReady) => {
                if (isReady && !this.isUnmounted) {
                    // The badge isn't guaranteed to exist in the DOM as soon as the
                    // script is ready — grecaptcha can create it lazily (e.g. on the
                    // first execute() call) — so poll for it rather than assuming.
                    this.pollForRecaptchaBadge();
                }
            });
        }
    },
    beforeUnmount() {
        this.isUnmounted = true;

        if (this.recaptchaPollTimeoutId) {
            clearTimeout(this.recaptchaPollTimeoutId);
            this.recaptchaPollTimeoutId = null;
        }

        const scriptEl = document.getElementById('recaptcha-js');

        if (scriptEl && this.recaptchaScriptLoadHandler) {
            scriptEl.removeEventListener('load', this.recaptchaScriptLoadHandler);
            scriptEl.removeEventListener('error', this.recaptchaScriptErrorHandler);
        }

        if (this.recaptchaV3WaitTimeoutId) {
            clearTimeout(this.recaptchaV3WaitTimeoutId);
            this.recaptchaV3WaitTimeoutId = null;
        }

        if (this.recaptchaBadgePollTimeoutId) {
            clearTimeout(this.recaptchaBadgePollTimeoutId);
            this.recaptchaBadgePollTimeoutId = null;
        }

        if (this.recaptchaExecuteTimeoutId) {
            clearTimeout(this.recaptchaExecuteTimeoutId);
            this.recaptchaExecuteTimeoutId = null;
        }

        if (this.recaptchaVersion === 'recaptcha_v3') {
            this.showRecaptchaBadge(false);
        }
    }
};
</script>

