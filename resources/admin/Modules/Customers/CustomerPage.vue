<template>
    <div class="fs_customer_page">
        <div v-if="customer.id" class="fs_box_wrapper">
            <div style="padding: 20px" class="fs_box_header">
                <div class="fs_box_head">
                    <el-breadcrumb separator=">">
                        <el-breadcrumb-item :to="{ name: 'Customers' }">{{$t('Customers')}}</el-breadcrumb-item>
                        <el-breadcrumb-item>{{ customer.first_name }} {{ customer.last_name }}</el-breadcrumb-item>
                    </el-breadcrumb>
                </div>
                <div class="fs_box_actions fs_customer_filters">
                    <div class="fs_cs_status_filter">
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px">
                <el-row :gutter="30">
                    <el-col :sm="12" :md="18" :xs="24">
                        <div style="padding: 20px; margin-top: 2.3em;" class="fs_box_body">
                            <div class="fs_cs_profile_picture" @mouseover="showIcon" @mouseout="hideIcon">
                                <div class="fs_customer_avatar">

                                    <el-dropdown trigger="click" :hide-on-click="false"
                                        placement="bottom-start"
                                        v-if="canOpenProfileUploadPopup">
                                        <el-icon class="fs_customer_avatar_upload"> <Camera /> </el-icon>
                                        <template #dropdown>
                                            <el-dropdown-menu class="fs_global_dropdown fs-cs-avatar-actions">
                                                <el-dropdown-item>
                                                    <el-upload
                                                        class="fs-avatar-uploader"
                                                        :action="upload_url"
                                                        :on-success ="handleAvatarSuccess"
                                                        :on-error="handleAvatarError"
                                                        :headers="requestHeaders"
                                                        :show-file-list="false"
                                                        :before-upload="beforeAvatarUpload"
                                                    >
                                                        Upload a Custom Picture
                                                    </el-upload>

                                                    </el-dropdown-item>
                                                <el-dropdown-item v-if="customer.avatar">
<!--                                                    Reset To Default Gravatar-->
                                                    <el-popconfirm
                                                        confirm-button-text="Yes"
                                                        cancel-button-text="No"
                                                        :title="$t('Reset to gravatar?')"
                                                        @confirm="confirmResetProfile"
                                                    >
                                                        <template #reference>
                                                            Reset To Default Gravatar
                                                        </template>
                                                    </el-popconfirm>
                                                </el-dropdown-item>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                    <img v-if="customer.photo" :src="customer.photo" class="avatar"/>
                                </div>
                            </div>
                            <customer-form v-if="customer.id" :customer="customer" style="margin-top: 4em;"/>
                        </div>
                    </el-col>
                    <el-col :sm="12" :md="6" :xs="24">
                        <div class="fs_tk_sidebar_wrap_new">
                            <collapsible-widget
                                v-if="customer"
                                :title="$t('Customer Informations')"
                                icon="User"
                                :default-expanded="true"
                                class="fs_sidebar_widget fs_sidebar_widget_ci"
                            >
                                <div class="fs_client_info_body">
                                    <!-- Avatar and Name -->
                                    <div class="fs_client_header">
                                        <router-link :to="{name: 'view_customer', params: { customer_id: customer.id }}">
                                            <div class="fs_client_avatar">
                                                <img :src="customer.photo" :alt="customer.full_name"/>
                                            </div>
                                        </router-link>
                                        <div class="fs_client_details">
                                            <div class="fs_client_name_row">
                                                <h4 class="fs_client_name">
                                                    {{ customer.full_name }}
                                                    <span class="fs_blocked_badge" v-if="customer.status == 'inactive'">({{$t('Blocked')}})</span>
                                                </h4>
                                            </div>
                                            <p class="fs_client_email">
                                                <a rel="noopener" target="_blank" v-if="customer.profile_edit_url"
                                                   :href="customer.profile_edit_url">
                                                    {{ customer.email }}
                                                </a>
                                                <span v-else>{{ customer.email }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- FluentCRM Profile -->
                                    <fluent-crm-profile v-if="fluentcrm_profile" :crm_profile="fluentcrm_profile" />
                                </div>
                            </collapsible-widget>

                            <collapsible-widget
                                v-if="widgets"
                                v-for="(widget, widget_key) in widgets"
                                :key="widget_key"
                                :title="widget.title || widget.header"
                                icon="ShoppingCart"
                                :default-expanded="false"
                                :class="`fs_tk_widget_${widget_key}`"
                                class="fs_sidebar_widget fs_tk_extra_card"
                            >
                                <!-- WooCommerce Purchases -->
                                <div v-if="widget_key === 'woo_purchases'" class="fs_widget_purchases">
                                    <ul class="fs_purchase_list">
                                        <li v-for="(order, order_key) in widget.orders" :key="order_key" class="fs_purchase_item">
                                            <div class="fs_purchase_row">
                                                <el-tooltip :content="`Purchase date: `+order.date+`, Amount: `+getCurrencySymbol(order.currency)+order.total" placement="top" :raw-content="true">
                                                    <a @click="getOrderDetails(order, widget.products)" class="fs_order_link">#{{order.id}}</a>
                                                </el-tooltip>
                                                <div class="fs_purchase_divider"></div>
                                                <el-tag size="small" :type="getType(order.status)">{{ order.status }}</el-tag>
                                            </div>
                                        </li>
                                    </ul>
                                    <el-drawer class="fs_wc_order_details" v-model="drawer" size="40%" :append-to-body="true" :with-header="false">
                                        <el-card class="fs_order_box_card">
                                            <template #header>
                                                <div class="fs_order_card_header">
                                                    <h3>{{$t('Order')}} #{{orders.orderInfo.id}}</h3>
                                                    <el-tag class="ml-2" :type="getType(orders.orderInfo.status)">{{orders.orderInfo.status}}</el-tag>
                                                </div>
                                            </template>
                                            <div class="fs_wc_card-body">
                                                <div>
                                                    <div class="fs_wc-order-preview-address">
                                                        <h2>{{$t('Billing details')}}</h2>
                                                        <p v-html="orders.orderInfo.billing_address"></p>
                                                        <p><strong>{{$t('Email')}}: </strong>{{orders.orderInfo.email}}</p>
                                                        <p v-if="orders.orderInfo.phone"><strong>{{$t('Phone')}}: </strong>{{orders.orderInfo.phone}}</p>
                                                        <p v-if="orders.orderInfo.payment_method"><strong>{{$t('Payment Via')}}: </strong>{{orders.orderInfo.payment_method}}</p>
                                                        <p v-if="orders.orderInfo.date"><strong>{{$t('Purchase Date')}}: </strong>{{orders.orderInfo.date}}</p>
                                                    </div>
                                                    <div class="fs_wc-order-preview-address fs_mt_30">
                                                        <h2>{{$t('Shipping details')}}</h2>
                                                        <p v-html="orders.orderInfo.shipping_address"></p>
                                                        <p><strong>{{$t('Shipping method')}} </strong> {{orders.orderInfo.shipping_method}}</p>
                                                    </div>
                                                </div>
                                                <el-table
                                                    :data="orders.products"
                                                    style="width: 100%; margin-top:2%;">
                                                    <el-table-column prop="post_title" :label="$t('Product')" width="60%"/>
                                                    <el-table-column prop="product_qty" :label="$t('Quantity')" width="20%"  align="center"/>
                                                    <el-table-column :label="$t('Total')" width="20%" align="center">
                                                        <template #default="scope">
                                                            <span v-html="orders.orderInfo.currency"></span><span>{{ scope.row.product_gross_revenue }}</span>
                                                        </template>
                                                    </el-table-column>
                                                </el-table>
                                            </div>

                                            <div class="fs_order_card_footer">
                                                <a :href="orders.orderInfo.order_link" target="_blank" class="el-button fs_filled_btn">{{$t('Edit')}}</a>
                                                <el-button @click="drawer = false" class="fs_outline_btn">{{$t('Close')}}</el-button>
                                            </div>
                                        </el-card>
                                    </el-drawer>
                                </div>
                                <!-- Other Widgets -->
                                <div v-else class="fs_widget_custom_body">
                                    <div v-html="widget.body_html"></div>
                                </div>
                            </collapsible-widget>

                            <collapsible-widget
                                v-if="tickets && tickets.length"
                                :title="$t('Support Tickets') + ` (${tickets.length})`"
                                icon="ChatLineSquare"
                                :default-expanded="false"
                                class="fs_sidebar_widget fs_tk_other_tickets_card"
                            >
                                <div class="fs_previous_conversations_body">
                                    <ul class="fs_conversation_list">
                                        <li v-for="other_ticket in tickets" :key="'other_ticket_'+other_ticket.id" class="fs_conversation_item">
                                            <router-link
                                                :to="{
                                                    name: 'view_ticket',
                                                    params: { ticket_id: other_ticket.id },
                                                    query: {prev_ticket: ticket_id}
                                                }"
                                                class="fs_conversation_link"
                                            >
                                                <div class="fs_conversation_content">
                                                    <el-icon class="fs_conversation_icon"><Message /></el-icon>
                                                    <span class="fs_conversation_title">{{ other_ticket.title }}</span>
                                                </div>
                                                <span class="fs_status_badge" :class="other_ticket.status?.toLowerCase()">
                                                {{ other_ticket.status }}
                                            </span>
                                            </router-link>
                                        </li>
                                    </ul>
                                </div>
                            </collapsible-widget>
                        </div>
                    </el-col>
                </el-row>
            </div>
        </div>
        <div class="fs_padded_20" v-else>
            <el-skeleton class="fs_box_wrapper fs_skeleton" :rows="5" animated />
        </div>
    </div>
</template>

<script type="text/babel">
import CustomerForm from './_CustomerForm';
import FluentCrmProfile from '@/admin/Modules/Tickets/parts/_CrmProfile';
import CollapsibleWidget from "@/admin/Modules/Tickets/parts/_CollapsibleWidget.vue";

export default {
    name: "CustomerPage",
    props: ['customer_id'],
    components: {
        CollapsibleWidget,
        CustomerForm,
        FluentCrmProfile
    },
    data() {
        return {
            customer: {},
            tickets: [],
            widgets: {},
            drawer: false,
            orders: [],
            loading_sidebar: false,
            fluentcrm_profile: false,
            loading: false,
            upload_url: this.appVars.rest.url + `/customers/profile_image/${this.customer_id}`,
            requestHeaders: {
                'X-WP-Nonce': this.appVars.rest.nonce
            },
            canOpenProfileUploadPopup: true
        }
    },
    mounted() {
        this.fetchCustomer();
    },
    methods: {
        async fetchCustomer() {
            this.loading = true;
            this.$get(`customers/${this.customer_id}`, {
                with: ['widgets', 'tickets', 'fluentcrm_profile']
            })
                .then(response => {
                    this.customer = response.customer;
                    this.tickets = response.tickets;
                    this.widgets = response.widgets;
                    this.fluentcrm_profile = response.fluentcrm_profile;
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        handleAvatarSuccess(response, file) {
            this.customer.photo = response.image;
            this.$notify({
                title: this.$t('Success'),
                message: this.$t(response.message),
                type: 'success'
            });
            this.canOpenProfileUploadPopup = true;
        },

        handleAvatarError(err, file) {
            let errorMessage = JSON.parse(err.message);
            this.$notify({
                title: this.$t('Error'),
                message: this.$t(errorMessage.message),
                type: 'error'
            });
            this.canOpenProfileUploadPopup = true;
        },

        getOrderDetails(current_order, products) {
            this.drawer = true;
            this.orders = {
                orderInfo: current_order,
                products: products[current_order.id],
            }
        },

        getType(status) {
            switch (status.toLocaleString()) {
                case 'on-hold':
                    return 'warning';
                case 'processing':
                    return 'primary';
                case 'completed':
                    return 'success';
                default:
                    return 'info';
            }
        },

        showIcon() {
            document.querySelector('i.fs_customer_avatar_upload').style.display = 'inline-flex';
        },

        hideIcon() {
            document.querySelector('i.fs_customer_avatar_upload').style.display = 'none';
        },

        getDisplayCurrency(currency) {
            // If currency is already a symbol (like from WooCommerce), return as is
            // If it's a currency code (like from FluentCart), convert to symbol
            if (currency && currency.length <= 3 && currency.match(/^[A-Z]{3}$/)) {
                return this.getCurrencySymbol(currency);
            }
            return currency || '';
        },

        async confirmResetProfile() {
            this.loading = true;
            this.$post(`customers/reset_avatar/${this.customer_id}`)
                .then(response => {
                    this.$notify({
                        message: this.$t(response.message),
                        position: 'bottom-right',
                        type: 'success'
                    });
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        beforeAvatarUpload() {
            this.canOpenProfileUploadPopup = false;
        }
    }
}
</script>
