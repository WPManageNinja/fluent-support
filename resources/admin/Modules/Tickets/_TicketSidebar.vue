<template>
    <div class="fs_tk_sidebar_wrap_new">
        <!-- Client Information Widget -->
        <collapsible-widget
            v-if="ticket && ticket.customer"
            :title="$t('Customer Informations')"
            icon="User"
            :default-expanded="true"
            class="fs_sidebar_widget fs_sidebar_widget_ci"
        >
            <div class="fs_client_info_body">
                <!-- Avatar and Name -->
                <div class="fs_client_header">
                    <router-link :to="{name: 'view_customer', params: { customer_id: ticket.customer.id }}">
                        <div class="fs_client_avatar">
                            <img :src="ticket.customer.photo" :alt="ticket.customer.full_name"/>
                        </div>
                    </router-link>
                    <div class="fs_client_details">
                        <div class="fs_client_name_row">
                            <h4 class="fs_client_name">
                                {{ ticket.customer.full_name }}
                                <span class="fs_blocked_badge" v-if="ticket.customer.status == 'inactive'">({{$t('Blocked')}})</span>
                            </h4>
                        </div>
                        <p class="fs_client_email">
                            <a rel="noopener" target="_blank" v-if="ticket.customer.profile_edit_url"
                               :href="ticket.customer.profile_edit_url">
                                {{ ticket.customer.email }}
                            </a>
                            <span v-else>{{ ticket.customer.email }}</span>
                        </p>
                    </div>
                    <el-icon v-if="appVars.me.permissions.includes('fst_sensitive_data')"
                             class="fs_client_more_icon"
                             @click="customerManagement">
                        <More />
                    </el-icon>
                </div>

                <!-- Customer Address -->
                <p class="fs_customer_address">
                    {{ getCustomerAddress(ticket.customer) }}
                </p>

                <!-- Customer Note -->
                <div v-if="ticket.customer.note" class="fs_customer_note">
                    {{ ticket.customer.note }}
                </div>

                <!-- FluentCRM Profile -->
                <fluent-crm-profile v-if="fluentcrm_profile" :crm_profile="fluentcrm_profile" />
            </div>
        </collapsible-widget>

        <!-- Time Logging Widget -->
        <collapsible-widget
            v-if="appVars.agent_time_tracking === 'yes' && has_pro"
            :title="$t('Time Logging')"
            icon="Timer"
            :default-expanded="false"
            class="fs_sidebar_widget"
        >
            <TaskTimer :ticket_id="ticket_id" :customer_id="ticket.customer_id" />
        </collapsible-widget>

        <!-- Loading State -->
        <div class="fs_sidebar_loading" v-if="loading">
            <el-skeleton :rows="1" animated/>
        </div>

        <fluent-booking-sidebar-widget
            v-if="ticket && appVars.fluent_booking?.active && can_manage_tickets"
            :ticket-id="ticket_id"
            :refresh-key="fetch_other_tickets"
        />

        <!-- Watchers/Bookmarks Widget -->
        <collapsible-widget
            v-if="has_pro && watchers.length"
            :title="$t('Watchers') + (watchers.length ? ` (${watchers.length})` : '')"
            icon="View"
            :default-expanded="false"
            class="fs_sidebar_widget"
        >
            <div class="fs_watchers_body">
                <div v-if="watchers.length" class="fs_watchers_list">
                    <el-tag
                        v-for="(watcher,watcher_key) in watchers"
                        :key="watcher_key"
                        class="fs_watcher_tag"
                        size="small"
                        :closable="can_manage_tickets"
                        :disable-transitions="false"
                        @close="handleClose(watcher.id)"
                    >
                        {{ watcher.full_name}}
                    </el-tag>
                </div>

                <el-popover
                    v-if="can_manage_tickets"
                    placement="bottom"
                    :width="300"
                    :visible="add_watcher"
                    trigger="manual"
                    popper-class="fs_popover"
                >
                    <template #reference>
                        <el-button
                            @click="add_watcher = !add_watcher"
                            size="small"
                            class="fs_outline_btn"
                        >
                            <el-icon><Plus /></el-icon>
                            {{ $t('Add Watcher') }}
                        </el-button>
                    </template>

                    <h4 class="fs_popover_title">{{$t('Add Bookmark')}}</h4>

                    <el-select
                        multiple
                        filterable
                        v-model="watcherIds"
                        size="small"
                        class="fs_watcher_select fs_select_field"
                    >
                        <el-option
                            v-for="(agent,agent_key) in agents"
                            :key="agent_key"
                            :value="agent.id"
                            :label="agent.full_name"
                        ></el-option>
                    </el-select>

                    <el-button
                        @click="updateWatcher()"
                        type="primary"
                        size="small"
                        class="fs_filled_btn"
                    >
                        {{$t('Update')}}
                    </el-button>
                </el-popover>
            </div>
        </collapsible-widget>

        <!-- Extra Widgets (WooCommerce, FluentCart, etc.) -->
        <collapsible-widget
            v-if="extra_widgets"
            v-for="(widget, widget_key) in extra_widgets"
            :key="widget_key"
            :title="widget.title || widget.header"
            icon="ShoppingCart"
            :default-expanded="true"
            :class="`fs_tk_widget_${widget_key}`"
            class="fs_sidebar_widget fs_tk_extra_card"
        >
            <!-- WooCommerce Purchases -->
            <div v-if="widget_key === 'woo_purchases'" class="fs_widget_purchases">
                <ul class="fs_purchase_list">
                    <li v-for="(order, order_key) in widget.orders" :key="order_key" class="fs_purchase_item">
                        <div class="fs_purchase_row">
                            <el-tooltip
                                :content="getOrderTooltip(widget_key, order)"
                                placement="top"
                                :raw-content="true"
                            >
                                <a
                                    @click="openDrawer(widget_key, order, widget.products)"
                                    class="fs_order_link"
                                >#{{ order.id }}</a>
                            </el-tooltip>
                            <div class="fs_purchase_divider"></div>
                            <el-tag size="small" :type="getType(order.status)">{{ order.status }}</el-tag>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- FluentCart Purchases -->
            <div v-else-if="widget_key === 'fct_purchases'" class="fs_widget_fct_purchases">
                <div v-if="widget.summary" class="fs_summary_stats">
                    <ul class="fs_full_listed">
                        <li>
                            <span class="fs_list_sub">{{ $t('Lifetime Value') }}:</span>
                            <span class="fs_list_value">
                                <a
                                    :href="`${origin}/wp-admin/admin.php?page=fluent-cart#/customers/${widget.fluent_cart_customer_id}/view`"
                                    target="_blank"
                                    class="fs_list_value fs_ltv_link"
                                    v-html="widget.summary.lifetime_value"
                                >
                                </a>
                            </span>
                        </li>
                        <li>
                            <span class="fs_list_sub">{{ $t('Purchases') }}:</span>
                            <span class="fs_list_value">{{ widget.summary.total_purchases }}</span>
                        </li>
                        <li v-if="widget.summary.first_purchase">
                            <span class="fs_list_sub">{{ $t('First Purchased') }}:</span>
                            <span class="fs_list_value">{{ widget.summary.first_purchase }}</span>
                        </li>
                        <li v-if="widget.summary.last_purchase">
                            <span class="fs_list_sub">{{ $t('Last Purchased') }}:</span>
                            <span class="fs_list_value">{{ widget.summary.last_purchase }}</span>
                        </li>
                    </ul>
                </div>

                <div v-for="(product, product_key) in widget.products" :key="product_key" class="fct_product_item">
                    <div class="fct_product_content">
                        <el-tooltip
                            :content="`Purchased on: ${dateTimeFormat(product.order.date, 'MMM DD, YYYY')}`"
                            placement="top"
                        >
                            <h3 class="fct_product_title">{{ product.product_name }}</h3>
                        </el-tooltip>
                        <div class="fct_license_type" v-if="product.license_type && product.license_type !== 'Subscription'">{{ product.license_type }}</div>
                        <div class="fct_product_price_row">
                            <span class="fct_product_price" v-html="product.formatted_price"></span>
                            <span class="fct_product_type_icon">
                                <el-icon v-if="product.license_type && product.license_type.includes('Subscription')"><Refresh /></el-icon>
                                <el-icon v-else><ShoppingBag /></el-icon>
                                {{ (product.license_type && product.license_type.includes('Subscription')) ? $t('Subscription') : $t('Product') }}
                            </span>
                        </div>
                        <div class="fct_product_sites" v-if="product.status === 'expired'">
                            <span class="fct_expired_badge">
                                <el-icon><Timer /></el-icon>
                                {{ $t('Expired') }}
                            </span>
                        </div>
                        <div class="fct_product_status_row">
                            <span class="fct_status_indicator" :class="'fct_status_' + product.status.toLowerCase()">
                                <span class="fct_status_dot"></span>
                                {{ product.status }}
                            </span>
                            <div class="fct_order_links">
                                <a
                                    :href="`${origin}/wp-admin/admin.php?page=fluent-cart#/orders/${product.order.id}/view`"
                                    target="_blank"
                                    class="fct_order_link"
                                    :title="`View Order #${product.order.invoice_no || 'WPMN-' + product.order.id}`"
                                >
                                    #{{ product.order.id }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Widgets -->
            <div v-else class="fs_widget_custom_body">
                <div v-html="widget.body_html"></div>
            </div>
        </collapsible-widget>

        <el-drawer
            class="fs_wc_order_details"
            v-model="drawer"
            :append-to-body="true"
            size="40%"
            :with-header="false"
        >
            <el-card v-if="drawerType === 'woo_purchases'" class="fs_order_box_card">
                <template #header>
                    <div class="fs_order_card_header">
                        <h3>{{ $t('Order') }} #{{ orders.orderInfo.id }}</h3>
                        <el-tag class="ml-2" :type="getType(orders.orderInfo.status)">
                            {{ orders.orderInfo.status }}
                        </el-tag>
                    </div>
                </template>
                <div class="fs_wc_card-body">
                    <div>
                        <div class="fs_wc-order-preview-address">
                            <h2>{{ $t('Billing details') }}</h2>
                            <span v-if="orders.orderInfo.billing_address"><strong>{{ $t('Address') }}: </strong> <p v-html="orders.orderInfo.billing_address"></p></span>
                            <p><strong>{{ $t('Email') }}: </strong>{{ orders.orderInfo.email }}</p>
                            <p v-if="orders.orderInfo.phone"><strong>{{ $t('Phone') }}: </strong>{{ orders.orderInfo.phone }}</p>
                            <p v-if="orders.orderInfo.payment_method"><strong>{{ $t('Payment Via') }}: </strong>{{ orders.orderInfo.payment_method }}</p>
                            <p v-if="orders.orderInfo.date"><strong>{{ $t('Purchase Date') }}: </strong>{{ orders.orderInfo.date }}</p>
                        </div>
                        <div class="fs_wc-order-preview-address fs_mt_30">
                            <h2>{{ $t('Shipping details') }}</h2>
                            <span v-if="orders.orderInfo.shipping_address"><strong>{{ $t('Address') }}: </strong> <p v-html="orders.orderInfo.shipping_address"></p></span>
                            <p><strong>{{ $t('Shipping method') }} </strong> {{ orders.orderInfo.shipping_method }}</p>
                        </div>
                    </div>
                    <el-table :data="orders.products" style="width: 100%; margin-top: 2%;">
                        <el-table-column prop="post_title" :label="$t('Product')" width="60%" />
                        <el-table-column prop="product_qty" :label="$t('Quantity')" width="20%" align="center" />
                        <el-table-column :label="$t('Total')" width="20%" align="center">
                            <template #default="scope">
                                <span v-html="orders.orderInfo.currency"></span><span>{{ scope.row.product_gross_revenue }}</span>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <div class="fs_order_card_footer">
                    <a :href="orders.orderInfo.order_link" target="_blank" class="el-button fs_filled_btn">{{ $t('Edit') }}</a>
                    <el-button @click="cancelClick" class="fs_outline_btn">{{ $t('Close') }}</el-button>
                </div>
            </el-card>

            <el-card v-else-if="drawerType === 'fct_purchases'" class="fs_order_box_card">
                <template #header>
                    <div class="fs_order_card_header">
                        <h3>Order #{{ orders.orderInfo.id }}</h3>
                        <el-tag class="ml-2" :type="getType(orders.orderInfo.status)">
                            {{ orders.orderInfo.status }}
                        </el-tag>
                    </div>
                </template>
                <div class="fs_wc_card-body">
                    <div v-for="(addressType, index) in ['billing_address', 'shipping_address']" :key="index">
                        <div class="fs_wc-order-preview-address">
                            <h2>{{ addressType === 'billing_address' ? $t('Billing Details') : $t('Shipping Details') }}</h2>
                            <div v-if="orders.orderInfo[addressType]">
                                <p><strong>{{ $t('Name') }}: </strong> {{ orders.orderInfo[addressType].full_name }}</p>
                                <p><strong>{{ $t('Email') }}: </strong> {{ orders.orderInfo[addressType].email }}</p>
                                <p><strong>{{ $t('Address') }}: </strong> {{ formatFullAddress(orders.orderInfo[addressType].formatted_address) }} </p>
                                <p v-if="orders.orderInfo.payment_method">
                                    <strong>{{ $t('Payment Via') }}:</strong>
                                    {{ getPaymentMethodName(orders.orderInfo.payment_method) }}
                                </p>
                                <p v-if="orders.orderInfo.created_at">
                                    <strong>{{ $t('Purchase Date') }}: </strong>
                                    {{ dateTimeFormat(orders.orderInfo.created_at, 'DD MMM YYYY, hh:mm A') }}
                                </p>

                            </div>
                            <p v-else>{{ $t('No') }} {{ addressType === 'billing_address' ? $t('billing') : $t('shipping') }} {{ $t('address available') }}</p>
                        </div>
                    </div>
                    <el-table :data="orders.products" style="margin-top: 20px;">
                        <el-table-column prop="post_title" :label="$t('Product')" width="30%" />
                        <el-table-column prop="title" :label="$t('Variant')" width="20%" />
                        <el-table-column prop="quantity" :label="$t('Quantity')" width="20%" align="center" />
                        <el-table-column :label="$t('Total')" width="20%" align="center">
                            <template #default="scope">
                                <span v-html="scope.row.formatted_total"></span>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <div class="fs_order_card_footer">
                    <a
                        :href="`${origin}/wp-admin/admin.php?page=fluent-cart#/orders/${orders.orderInfo.id}/view`"
                        target="_blank"
                        class="el-button el-button--primary">
                        {{ $t('Edit') }}
                    </a>

                    <el-button @click="cancelClick" type="danger">{{ $t('Close') }}</el-button>
                </div>
            </el-card>
        </el-drawer>

        <!-- Dynamic Template Widgets (extensible via PHP/JS hooks) -->
        <DynamicTemplates
            v-if="ticket"
            filter="ticket_sidebar"
            :widgetsQuery="{ ticket_id: ticket.id }"
            :data="{ ticket }"
        />

        <!-- Previous Conversations Widget -->
        <collapsible-widget
            v-if="other_tickets && other_tickets.length"
            :title="$t('Previous Conversations') + (otherTicketsTotal ? ` (${otherTicketsTotal})` : '')"
            icon="ChatLineSquare"
            :default-expanded="true"
            class="fs_sidebar_widget fs_tk_other_tickets_card"
        >
            <div class="fs_previous_conversations_body">
                <ul class="fs_conversation_list">
                    <li v-for="other_ticket in other_tickets" :key="'other_ticket_'+other_ticket.id" class="fs_conversation_item">
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
                <div v-if="otherTicketsMore" class="fs_load_more_wrap">
                    <button class="fs_load_more_btn" :disabled="loadingMoreTickets" @click="fetchMoreOtherTickets">
                        <el-icon v-if="loadingMoreTickets" class="is-loading"><Loading /></el-icon>
                        <icon-pack v-else icon-key="arrow_down" :width="16" :height="16" />
                        <span v-if="!loadingMoreTickets">{{ $t('Load More') }}</span>
                    </button>
                </div>
            </div>
        </collapsible-widget>

        <!-- Additional Details Widget -->
        <collapsible-widget
            :title="$t('Additional Details')"
            icon="Setting"
            :default-expanded="true"
            class="fs_sidebar_widget"
        >
            <div class="fs_additional_details_body">
                <!-- Client Priority -->
                <div class="fs_detail_field">
                    <label class="fs_detail_label">{{ $t('Client Priority') }}</label>
                    <el-select
                        v-model="ticket.client_priority"
                        @change="updateTicketPriority('client_priority')"
                        size="small"
                        class="fs_select_field"
                        :disabled="!can_manage_tickets"
                    >
                        <el-option
                            v-for="(priorityLabel, priority) in client_priorities"
                            :key="priority"
                            :value="priority"
                            :label="priorityLabel"
                        ></el-option>
                    </el-select>
                </div>

                <!-- Agent Priority -->
                <div class="fs_detail_field">
                    <label class="fs_detail_label">{{ $t('Agent Priority') }}</label>
                    <el-select
                        v-model="ticket.priority"
                        @change="updateTicketPriority('priority')"
                        size="small"
                        class="fs_select_field"
                        :disabled="!can_manage_tickets"
                    >
                        <el-option
                            v-for="(priorityLabel, priority) in admin_priorities"
                            :key="priority"
                            :value="priority"
                            :label="priorityLabel"
                        ></el-option>
                    </el-select>
                </div>

                <!-- Add Tags -->
                <div v-if="can_manage_tickets" class="fs_detail_field">
                    <label class="fs_detail_label">{{ $t('Add Tags') }}</label>
                    <div class="fs_tags_wrapper">
                        <ticket-tags :creatable="true" :ticket_id="ticket.id" :tags.sync="ticket.tags"/>
                    </div>
                </div>
            </div>
        </collapsible-widget>

        <!-- Customer Management Dialog -->
        <el-dialog v-model="customerManagementModal" :append-to-body="true" :title="$t('Customer Management')" class="fs_dialog">
            <el-tabs v-model="activeTabName" @tab-click="handleClick" class="fs_customer_management_tabs">
                <el-tab-pane :label="$t('Update Customer')" name="update_customer_data">
                    <customer-form @updated="closeModal" source="ticket" :customer="ticket.customer"/>
                </el-tab-pane>

                <el-tab-pane :label="$t('Change Customer')" name="change_customer">
                    <el-form :data="ticket" label-position="top">
                        <el-form-item :label="$t('Select Customer')">
                            <remote-selector
                                v-model="customerID"
                                response_key="customers"
                                api_path="customers"
                                value_selector="id"
                                label_joiner=" - "
                                :label_selectors="['full_name','email']"
                                clearable
                            />
                        </el-form-item>
                    </el-form>

                    <el-form-item>
                        <el-button
                            @click="changeCustomer(customerID)"
                            :disabled="changing || !customerID"
                            v-loading="changing"
                            class="fs_filled_btn"
                        >
                            {{ $t('Change Customer') }}
                        </el-button>
                    </el-form-item>
                </el-tab-pane>
            </el-tabs>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import CustomerForm from "../Customers/_CustomerForm";
import RemoteSelector from "../../Pieces/RemoteSelector";
import FluentCrmProfile from "./parts/_CrmProfile";
import TaskTimer from "./parts/_TaskTimer";
import CollapsibleWidget from "./parts/_CollapsibleWidget.vue";
import TicketTags from "./parts/_Tags.vue";
import DynamicTemplates from "../../Bits/DynamicTemplates/DynamicTemplates.vue";
import FluentBookingSidebarWidget from "./_FluentBookingSidebarWidget.vue";
import IconPack from "@/admin/Components/IconPack.vue";
import each from "lodash/each";

export default {
    name: "TicketSidebar",
    components: {
        CustomerForm,
        RemoteSelector,
        FluentCrmProfile,
        TaskTimer,
        CollapsibleWidget,
        TicketTags,
        DynamicTemplates,
        FluentBookingSidebarWidget,
        IconPack,
    },
    props: ["ticket_id", "ticket", "fluentcrm_profile", "watchers", "watcher_ids", "fetch_other_tickets", "can_manage_tickets"],
    data() {
        return {
            drawer: false,
            drawerType: null, // 'woo_purchases' | 'fct_purchases'
            loading: true,
            extra_widgets: false,
            other_tickets: [],
            watcherIds: [],
            customerManagementModal: false,
            changing: false,
            activeTabName: "update_customer_data",
            add_watcher: false,
            ticketSummary: "",
            ticketTone: "",
            agents: this.appVars.support_agents,
            client_priorities: this.appVars.client_priorities,
            admin_priorities: this.appVars.admin_priorities,
            orders: null,
            customerID: null,
            origin: window.location.origin,
            otherTicketsPage: 1,
            otherTicketsTotal: 0,
            otherTicketsMore: false,
            loadingMoreTickets: false,
            loadMoreRequest: null,
            widgetsRequest: null,
        };
    },
    watch: {
        watcher_ids(newIds) {
            this.watcherIds = newIds;
        },
        ticket_id() {
            if (this.widgetsRequest) {
                this.widgetsRequest.abort();
                this.widgetsRequest = null;
            }
            if (this.loadMoreRequest) {
                this.loadMoreRequest.abort();
                this.loadMoreRequest = null;
            }
            this.other_tickets = [];
            this.extra_widgets = null;
            this.otherTicketsPage = 1;
            this.otherTicketsMore = false;
            this.fetchWidgets();
        },
        fetch_other_tickets() {
            this.otherTicketsPage = 1;
            this.otherTicketsMore = false;
            this.fetchWidgets();
        },
    },
    mounted() {
        this.fetchWidgets();
        this.watcherIds = this.watcher_ids;
        if (this.has_pro && this.ticket.watchers && this.ticket.watchers.length > 0) {
            this.watcherIds = this.ticket.watchers.map((watcher) => watcher.tag_id.toString());
        }
    },
    methods: {
        fetchMoreOtherTickets() {
            if (this.loadingMoreTickets || !this.otherTicketsMore) return;
            this.loadingMoreTickets = true;
            const nextPage = this.otherTicketsPage + 1;
            this.loadMoreRequest = this.$get(`tickets/${this.ticket_id}/widgets`, { page: nextPage })
                .then((response) => {
                    this.otherTicketsPage = nextPage;
                    this.other_tickets = this.other_tickets.concat(response.other_tickets);
                    this.otherTicketsTotal = response.other_tickets_total;
                    this.otherTicketsMore = response.other_tickets_more;
                })
                .catch((errors) => this.$handleError(errors))
                .always(() => {
                    this.loadingMoreTickets = false;
                    this.loadMoreRequest = null;
                });
        },
        fetchWidgets() {
            this.loading = true;
            this.widgetsRequest = this.$get(`tickets/${this.ticket_id}/widgets`, { with: ['other_tickets', 'extra_widgets'] })
                .then((response) => {
                    this.other_tickets = response.other_tickets;
                    this.otherTicketsTotal = response.other_tickets_total;
                    this.otherTicketsMore = response.other_tickets_more;
                    this.otherTicketsPage = 1;
                    this.extra_widgets = response.extra_widgets;
                })
                .catch((errors) => this.$handleError(errors))
                .always(() => {
                    this.loading = false;
                    this.widgetsRequest = null;
                });

            },
        customerManagement() {
            this.customerManagementModal = !this.customerManagementModal;
        },
        changeCustomer(customer_id) {
            this.$put(`tickets/${this.ticket_id}/change-customer`, { customer: customer_id })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right"
                    });
                    this.closeModal();
                })
                .catch((errors) => this.$handleError(errors))
                .always(() => {
                    this.loading = false;
                });
        },
        handleClick(tab) {
            this.activeTabName = tab.props.name;
        },
        closeModal() {
            this.customerManagementModal = false;
            window.location.reload();
        },
        getCustomerAddress(customer) {
            if (!customer.custom_field_keys) return "";
            return customer.custom_field_keys
                .map((key) => customer[key])
                .filter(Boolean)
                .join(", ");
        },
        getPaymentMethodName(method) {
            const map = { offline_payment: this.$t("Offline Payment"), stripe: "Stripe", paypal: "PayPal" };
            return map[method] || method;
        },
        handleClose(watcherId) {
            this.$confirm({
                message: this.$t("watcher_remove_warning"),
                title: this.$t("Warning"),
                options: {
                    confirmButtonText: this.$t("Yes"),
                    cancelButtonText: this.$t("No"),
                    type: "warning",
                    cancelButtonClass: 'el-button--default fs_outline_btn',
                    confirmButtonClass: 'el-button--danger fs_filled_btn',
                },
            })
                .then(() => {
                    const index = this.watcherIds.indexOf(watcherId.toString());
                    if (index > -1) {
                        this.watcherIds.splice(index, 1);
                    }
                    this.saving = true;
                    this.updateWatcher();
                })
                .catch((errors) => this.$handleError(errors));
        },
        updateWatcher() {
            this.saving = true;
            this.$post(`tickets/${this.ticket.id}/sync-watchers`, { watchers: this.watcherIds })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right"
                    });
                    this.$emit("refresh");
                    this.add_watcher = false;
                    this.saving = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.saving = false;
                });
        },
        openDrawer(type, order, products = null) {
            this.drawerType = type;
            this.orders = type === "woo_purchases"
                ? { orderInfo: order, products: products?.[order.id] || [] }
                : { orderInfo: order, products: order.order_items || [] };
            this.drawer = true;
        },
        getOrderDetails(current_order, products) {
            this.openDrawer('woo_purchases', current_order, products);
        },
        getOrderDetailsForCart(order) {
            this.openDrawer('fct_purchases', order);
        },
        getDisplayCurrency(currency) {
            return currency || '';
        },
        getLicenseType(status) {
            switch (status?.toString().toLowerCase()) {
                case 'active':
                    return 'success';
                case 'expired':
                    return 'danger';
                case 'disabled':
                    return 'warning';
                case 'inactive':
                    return 'info';
                default:
                    return 'info';
            }
        },
        getProductStatusType(status) {
            switch (status?.toString().toLowerCase()) {
                case 'active':
                case 'completed':
                    return 'success';
                case 'processing':
                    return 'primary';
                case 'on-hold':
                    return 'warning';
                case 'expired':
                case 'canceled':
                case 'failed':
                    return 'danger';
                default:
                    return 'info';
            }
        },
        cancelClick() {
            this.drawer = false;
            this.drawerType = null;
            this.orders = null;
        },
        getOrderTooltip(type, order) {
            const formattedDate = this.dateTimeFormat(order.date, "DD MMM YYYY, hh:mm A");
            return `Purchase date: ${formattedDate}, Amount: ${order.currency} ${order.total}`;
        },
        formatFullAddress(address) {
            if (!address) return "";
            return [
                address.address_1,
                address.address_2,
                address.city,
                address.state,
                address.postcode,
                address.country].filter(Boolean).join(", ");
        },
        getType(status) {
            switch (status?.toString().toLowerCase()) {
                case "on-hold":
                    return "warning";
                case "processing":
                    return "primary";
                case "completed":
                    return "success";
                default:
                    return "info";
            }
        },
        updateTicketPriority(propName, propValue = null) {
            // If propValue is provided, use it; otherwise read from props.ticket
            const value = propValue !== null ? propValue : this.ticket[propName];
            this.$put(`tickets/${this.ticket_id}/property`, { prop_name: propName, prop_value: value })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right"
                    });
                    each(response.update_data, (data, key) => {
                        this.ticket[key] = data;
                    });
                    this.$emit("refresh");
                })
                .catch((errors) => this.$handleError(errors));
        },
    },
};
</script>

<style scoped>
.fct_product_item {
    margin-bottom: 0;
    padding: 12px 0;
    border: none;
    background: transparent;
    border-bottom: 1px solid #e5e7eb;
}

.fct_product_item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.fct_product_content {
    padding: 0;
}

.fct_product_title {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: #556575;
    line-height: 1.3;
}

.fct_license_type {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
    font-weight: 400;
}

.fct_product_price_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}

.fct_product_price {
    font-size: 14px;
    font-weight: 600;
    color: #556575;
}

.fct_product_type_icon {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: #6b7280;
}

.fct_product_type_icon .el-icon {
    font-size: 12px;
}

.fct_product_sites {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.fct_expired_badge {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #dc2626;
    font-size: 11px;
}

.fct_expired_badge .el-icon {
    font-size: 11px;
}

.fct_product_status_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
}

.fct_status_indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 500;
}

.fct_status_dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.fct_status_processing .fct_status_dot {
    background-color: #4CAF50; /* Green - Common for processing/ongoing */
}

.fct_status_completed .fct_status_dot {
    background-color: #2196F3; /* Blue - Often used for completed/successful tasks */
}

.fct_status_expired .fct_status_dot {
    background-color: #F44336; /* Red - Commonly used for expired or failed states */
}

.fct_status_on-hold .fct_status_dot {
    background-color: #FF9800; /* Amber/Orange - Often used for hold/waiting states */
}

.fct_status_processing {
    color: #4CAF50; /* Green */
}

.fct_status_completed {
    color: #2196F3; /* Blue */
}

.fct_status_expired {
    color: #F44336; /* Red */
}

.fct_status_on-hold {
    color: #FF9800; /* Amber/Orange */
}

.fct_status_paid {
    color: #6b7280;
    font-weight: 400;
    font-size: 11px;
}

.fct_order_links {
    display: flex;
    align-items: center;
    gap: 6px;
}

.fct_order_link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    font-size: 12px;
    padding: 2px 4px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.fct_order_link:hover {
    color: #1d4ed8;
    background-color: #f0f9ff;
    text-decoration: underline;
}

ul.fs_full_listed {
    border-radius: 4px;
    list-style: none;
    margin: 0;
    padding: 0;
}

ul.fs_full_listed li {
    border-bottom: 1px solid #ebeef4;
    display: block;
    margin: 0;
    padding: 5px 0;
}

ul.fs_full_listed>li span.fs_list_sub {
    font-weight: 500;
}

ul.fs_full_listed>li span.fs_list_value {
    float: right;
}

.fs_ltv_link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.fs_ltv_link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

/* Previous Conversations  Status Badge Colors  */
.fs_status_badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.fs_status_badge.new {
    background: #c0d5ff;
    color: #122368;
}

.fs_status_badge.new::before {
    background: #122368;
}

.fs_status_badge.active {
    background: #afebd2;
    color: #072722;
}

.fs_status_badge.active::before {
    background: #072722;
}

.fs_status_badge.waiting {
    background: #ffd5c0;
    color: #682f12;
}

.fs_status_badge.waiting::before {
    background: #682f12;
}

.fs_status_badge.closed {
    background: #e1e4ea;
    color: #222530;
}

.fs_status_badge.closed::before {
    background: #222530;
}

/* Additional status colors for other states */
.fs_status_badge.open {
    background: #c0d5ff;
    color: #122368;
}

.fs_status_badge.open::before {
    background: #122368;
}

.fs_status_badge.pending {
    background: #ffd5c0;
    color: #682f12;
}

.fs_status_badge.pending::before {
    background: #682f12;
}

.fs_status_badge.resolved {
    background: #afebd2;
    color: #072722;
}

.fs_status_badge.resolved::before {
    background: #072722;
}
</style>
