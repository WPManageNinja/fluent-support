<template>
    <div class="fs_create_ticket_form fs_global_form_builder">
        <!-- Header -->
        <div class="fs_ticket_header">
            <div class="fs_ticket_header_text">
                <h3 class="fs_ticket_title">{{ $t('Create a Ticket') }}</h3>
                <p class="fs_ticket_subtitle">{{ $t('Enter your query to create a support request.') }}</p>
            </div>
            <button class="el-dialog__headerbtn" @click="$emit('close')" type="button">
                <el-icon class="el-dialog__close"><Close /></el-icon>
            </button>
        </div>

        <!-- Body -->
        <div class="fs_dialog_body">
        <el-form :data="ticket" label-position="top">

                <!-- Step 1: Search/Create Contact -->
                <div v-if="step == 'search'" class="fs_step_content">
                    <el-form-item :label="$t('Search Existing contact or provide email address')">
                        <el-input
                            @keypress.enter.native.prevent="searchCustomers()"
                            v-model="search_customer"
                            :placeholder="$t('Search or provide email address')">
                            <template #suffix>
                                <el-button
                                    @click="searchCustomers()"
                                    :disabled="!search_customer || searching"
                                    :loading="searching"
                                    link
                                    type="primary"
                                    size="small">
                                    {{ $t('Search') }}
                                </el-button>
                            </template>
                        </el-input>
                    </el-form-item>

                    <div v-loading="searching"
                         v-if="searching || (search_results && search_results.data && search_results.data.length && ticket.create_customer != 'yes')"
                         class="fs_customer_selector">
                        <h3>{{ $t('Please Select a contact') }} [{{ search_results.provider }}]</h3>
                        <ul class="fs_contact_results">
                            <li v-for="item in search_results.data" :key="item.id"
                                @click="customerSelected(item, search_results.provider)">
                                {{ item.email }} - {{ item.first_name }} {{ item.last_name }}
                            </li>
                        </ul>
                    </div>

                    <template v-if="searched">
                        <div class="fs_create_contact_link">
                            <el-checkbox true-value="yes" false-value="no" v-model="ticket.create_customer">
                                <span class="fs_checkbox_text">
                                    {{ $t('Could not find a contact?') }}
                                    <span class="fs_link_text">{{ $t('Create a new one.') }}</span>
                                </span>
                            </el-checkbox>
                        </div>

                        <div class="fs_tk_create_customer" v-if="ticket.create_customer=='yes'">
                            <el-row :gutter="20">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('First Name')">
                                        <el-input :placeholder="$t('First name')" type="text"
                                                  v-model="new_customer.first_name"></el-input>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Last Name')">
                                        <el-input :placeholder="$t('Last name')" type="text"
                                                  v-model="new_customer.last_name"></el-input>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-form-item :label="$t('Email')" :error="validation_errors.email">
                                <el-input :placeholder="$t('Email')" type="email" v-model="new_customer.email"></el-input>
                            </el-form-item>

                            <el-row :gutter="20" v-if="ticket.create_wp_user == 'yes'">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Username')" :error="validation_errors.username">
                                        <el-input :placeholder="$t('Username')" type="text"
                                                  v-model="new_customer.username"></el-input>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Password')" :error="validation_errors.password">
                                        <el-input
                                            :placeholder="$t('Password (Leave blank for auto generated)')"
                                            type="password"
                                            show-password
                                            v-model="new_customer.password">
                                        </el-input>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-form-item>
                                <el-checkbox true-value="yes" false-value="no" v-model="ticket.create_wp_user">
                                    {{ $t('Create New User in WordPress') }}
                                </el-checkbox>
                            </el-form-item>
                        </div>
                    </template>
                </div>

                <!-- Step 2: Ticket Details -->
                <div v-else-if="step == 'ticket'" class="fs_step_content fs_step_content_ticket_form">
                    <!-- Contact Details Card -->
                    <div class="fs_contact_details_card">
                        <h4 class="fs_card_label">{{ $t('Contact Details') }}</h4>
                        <div class="fs_contact_info">
                            <p class="fs_contact_info_item">
                                <span class="fs_info_label">{{ $t('Name:') }}</span>
                                {{ new_customer.first_name }} {{ new_customer.last_name }}
                            </p>
                            <p class="fs_contact_info_item">
                                <span class="fs_info_label">{{ $t('Customer Email:') }}</span>
                                {{ new_customer.email }}
                            </p>
                        </div>
                    </div>

                    <el-form-item v-if="mailboxes && mailboxes.length > 1" :label="$t('Select Business Inbox')">
                        <el-select
                            v-model="ticket.mailbox_id"
                            :placeholder="$t('Select Business Inbox')"
                            clearable
                            filterable
                            popper-class="fs_select_dropdown">
                            <el-option
                                v-for="mailbox in mailboxes"
                                :key="mailbox.id"
                                :value="mailbox.id"
                                :label="mailbox.name">
                            </el-option>
                        </el-select>
                        <error :error="errors.get('mailbox_id')"/>
                    </el-form-item>

                    <el-form-item :label="$t('Subject')">
                        <el-input
                            :placeholder="$t('What\'s this support ticket about?')"
                            type="text"
                            v-model="ticket.title">
                        </el-input>
                        <error :error="errors.get('title')"/>
                    </el-form-item>

                    <el-form-item :label="$t('Ticket Details')" class="fs_reply_editor">
                        <wp-editor
                            :height="150"
                            :autofocus="true"
                            :media-buttons="false"
                            :is_direct_paste="true"
                            v-model="ticket.content"
                            v-if="editor_ready"
                            :show-saved-replies="true"/>
                        <error :error="errors.get('content')"/>
                    </el-form-item>

                    <attachment-form
                        :ticket="ticket"
                        :attachments="attachments"
                        :errors="errors"
                        @upload-state-change="updateAttachmentUploadState"
                    ></attachment-form>

                    <el-row :gutter="20">
                        <el-col v-if="products && products.length" :md="12" :xs="24">
                            <el-form-item :label="$t('Related Product/Service')">
                                <el-select
                                    v-model="ticket.product_id"
                                    :placeholder="$t('Select related Product/Service')"
                                    clearable
                                    filterable
                                    popper-class="fs_select_dropdown">
                                    <el-option
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id"
                                        :label="product.title">
                                    </el-option>
                                </el-select>
                                <error :error="errors.get('product_id')"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('Priority (Customer)')">
                                <el-select
                                    v-model="ticket.client_priority"
                                    :placeholder="$t('Normal')"
                                    clearable
                                    filterable
                                    popper-class="fs_select_dropdown">
                                    <el-option
                                        v-for="(priority,priorityKey) in priorities"
                                        :key="priorityKey"
                                        :value="priorityKey"
                                        :label="priority">
                                    </el-option>
                                </el-select>
                                <error :error="errors.get('client_priority')"/>
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <custom-field-form :custom_data="ticket.custom_fields" :ticket="ticket" type="create_ticket" v-if="has_pro"/>

                    <el-form-item>
                        <el-checkbox true-value="yes" false-value="no" v-model="ticket.agent_initiated">
                            {{ $t('Initiated by agent') }}
                        </el-checkbox>
                    </el-form-item>
                </div>

            </el-form>
        </div>
        <!-- Footer -->
        <div class="fs_dialog_footer">
            <!-- Progress Indicator -->
            <div class="fs_progress_indicator">
                <div class="fs_progress_bar">
                    <div class="fs_progress_fill" :style="{ width: step === 'search' ? '50%' : '100%' }"></div>
                </div>
                <p class="fs_progress_text">
                    {{ step === 'search' ? $t('Step 1 of 2') : $t('Step 2 of 2') }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="fs_dialog_actions">
                <el-button
                    v-if="step === 'search'"
                    @click="$emit('close')"
                    class="fs_outline_btn">
                    {{ $t('Cancel') }}
                </el-button>
                <el-button
                    v-if="step === 'ticket'"
                    @click="step = 'search'"
                    class="fs_outline_btn">
                    {{ $t('Back') }}
                </el-button>
                <el-button
                    v-if="step === 'search'"
                    @click="goToTicketStep()"
                    :disabled="!canProceedToTicket"
                    class="fs_filled_btn">
                    {{ $t('Next') }}
                </el-button>
                <el-button
                    v-if="step === 'ticket'"
                    @click="create()"
                    :disabled="creating || attachmentUploadState.uploading"
                    v-loading="creating"
                    class="fs_filled_btn">
                    {{ $t('Create Ticket') }}
                </el-button>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
import RemoteSelector from '../../Pieces/RemoteSelector';
import Error from '../../../admin/Pieces/Error';
import Errors from '../../../admin/Bits/Errors';
import CustomFieldForm from './parts/_CustomFieldForm';
import AttachmentForm from "@/admin/Modules/Tickets/_AttachmentForm.vue";

export default {
    name: 'CreateTicketForm',
    components: {
        AttachmentForm,
        WpEditor,
        RemoteSelector,
        Error,
        CustomFieldForm,
    },
    emits: ['close'],
    data() {
        return {
            step: 'search',
            creating: false,
            editor_ready: true,
            products: [],
            priorities: {},
            mailboxes: [],
            errors: new Errors(),
            ticket: {
                customer_id: '',
                mailbox_id: '',
                title: '',
                content: '',
                product_id: '',
                client_priority: 'normal',
                custom_fields: CustomFieldForm.data(),
                create_customer: 'no',
                create_wp_user: 'no',
                agent_initiated: 'no',
            },
            new_customer: {},
            customer_search_item: {
                provider: '',
                id: ''
            },
            search_customer: '',
            search_results: {
                provider: '',
                data: []
            },
            searching: false,
            searched: false,
            attachments: [],
            validation_errors: {
                email: '',
                username: '',
                password: ''
            },
            has_pro: false,
            searchDebounceTimer: null,
            attachmentUploadState: {
                uploading: false,
                count: 0
            }
        };
    },
    watch: {
        search_customer(val) {
            clearTimeout(this.searchDebounceTimer);
            if (val && val.length >= 3) {
                this.searchDebounceTimer = setTimeout(() => {
                    this.searchCustomers();
                }, 400);
            }
        }
    },
    computed: {
        canProceedToTicket() {
            // Can proceed if a customer is selected from search results
            if (this.new_customer.email && this.ticket.create_customer !== 'yes') {
                return true;
            }
            // Can proceed if creating new customer and email is provided
            if (this.ticket.create_customer === 'yes' && this.new_customer.email) {
                return true;
            }
            return false;
        }
    },
    created() {
        this.products = this.appVars.support_products || [];
        this.priorities = this.appVars.client_priorities || {};
        this.mailboxes = this.appVars.mailboxes || [];
        this.has_pro = this.appVars.has_pro || false;
    },
    methods: {
        updateAttachmentUploadState(state) {
            this.attachmentUploadState = state;
        },

        notifyAttachmentUploadPending() {
            const message = this.attachmentUploadState.count > 1
                ? this.$t('Please wait for all attachments to finish uploading before creating the ticket.')
                : this.$t('Please wait for the attachment to finish uploading before creating the ticket.');

            this.$notify({
                message,
                position: 'bottom-right',
                type: 'warning'
            });
        },

        create() {
            if (this.attachmentUploadState.uploading) {
                this.notifyAttachmentUploadPending();
                return;
            }

            this.errors.clear();
            this.creating = true;
            this.$post('tickets', {
                ticket: this.ticket,
                newCustomer: this.new_customer,
                attachments: this.attachments
            })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        position: 'bottom-right',
                        type: 'success'
                    });
                    this.$router.push({name: 'view_ticket', params: {ticket_id: response.ticket.id}});
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.creating = false;
                });
        },
        labelMaker(name, email) {
            return `${name} - ${email}`;
        },
        insertTemplate(content) {
            this.editor_ready = false;
            this.ticket.content = this.ticket.content + content;
            this.$nextTick(() => {
                this.editor_ready = true;
            });
        },
        searchCustomers() {
            this.searching = true;
            this.searched = false;
            this.$get('tickets/search-contact', {
                search: this.search_customer
            })
                .then(response => {
                    this.search_results = response;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.searching = false;
                    this.searched = true;
                });
        },
        customerSelected(item, provider) {
            this.new_customer = {...item};
            this.new_customer.provider = provider;
            this.step = 'ticket';
        },
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        resetValidationErrors() {
            this.validation_errors.email = '';
            this.validation_errors.username = '';
            this.validation_errors.password = '';
        },
        validateEmail() {
            const email = this.new_customer.email;
            if (!email || !this.isValidEmail(email)) {
                this.validation_errors.email = this.$t('Please provide a valid email address.');
                return false;
            }
            return true;
        },
        validateWpUser() {
            if (this.ticket.create_wp_user !== 'yes') return true;

            let isValid = true;

            if (!this.new_customer.username) {
                this.validation_errors.username = this.$t('Username is required for WordPress user.');
                isValid = false;
            }

            return isValid;
        },
        goToTicketStep() {
            this.resetValidationErrors();

            if (this.ticket.create_customer === 'yes') {
                const isEmailValid = this.validateEmail();
                const isWpUserValid = this.validateWpUser();

                if (!isEmailValid || !isWpUserValid) return;
            }

            this.step = 'ticket';
        }
    }
}
</script>
