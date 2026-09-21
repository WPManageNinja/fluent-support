<template>
    <div class="fs_customers">
        <div v-if="!first_time_loading" class="fs_box_wrapper">
            <div class="fs_component_header">
                <div class="fs_component_head">
                    <h3 class="fs_page_title">{{ $t("All Customers") }}</h3>
                </div>
                <div class="fs_box_actions">
                    <el-button
                        @click="showAddCustomerModal({})"
                        class="fs_filled_btn"
                        icon="Plus"
                    >
                        {{ $t("Add Customer") }}
                    </el-button>
                </div>
            </div>
            <div class="fs_table_container">
                <div class="fs_table_header">
                    <div class="fs_box_actions">
                        <div class="fs_status_tabs">
                            <div class="fs_segmented_control">
                                <button
                                    v-for="(statusKey, statusName) in allStatusFilters"
                                    :key="statusName"
                                    @click="setStatus(statusName)"
                                    :class="['fs_segment_button', { 'fs_segment_active': status === statusName }]"
                                >
                                    {{ statusKey }}
                                </button>
                            </div>
                        </div>
                        <div class="fs_customers_table_actions">
                            <div v-if="canDeleteCustomers" class="fs_bulk_actions_section">
                                <BulkAction
                                    :selected-items="selectedCustomers"
                                    :actions="bulkActions"
                                    v-model:selected-action="selectedBulkAction"
                                    @apply-action="handleBulkAction"
                                />
                            </div>
                            <div class="fs_ticket_orders fs_table_search_field">
                                <el-input
                                    @keyup.enter="fetchCustomers()"
                                    clearable
                                    @clear="fetchCustomers()"
                                    :placeholder="$t('Search Customers...')"
                                    v-model="search"
                                    class="fs_text_input fs_table_search_input"
                                >
                                    <template #prefix>
                                        <el-icon class="el-input__icon"><search /></el-icon>
                                    </template>
                                </el-input>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fs_table_wrapper">
                    <el-table
                        :data="customers"
                        row-class-name="fs_table_row"
                        header-row-class-name="fs_table_header_row"
                        cell-class-name="fs_table_cell"
                        header-cell-class-name="fs_table_header_cell"
                        table-layout="fixed"
                        v-loading="loading"
                        @selection-change="handleSelectionChange"
                    >
                        <el-table-column
                            v-if="canDeleteCustomers"
                            type="selection"
                            width="50"
                            align="center"
                        ></el-table-column>
                        <el-table-column
                            width="50"
                            prop="id"
                            :label="$t('ID')"
                        ></el-table-column>
                        <el-table-column
                            prop="full_name"
                            width="250"
                            :label="$t('Name')"
                        >
                            <template #default="scope">
                                <div class="fs_customer_name_cell">
                                    <div class="fs_customer_avatar">
                                        <img
                                            :src="scope.row.photo"
                                            :alt="scope.row.full_name || $t('Customer')"
                                            class="fs_avatar_img"
                                            @error="handleImageError"
                                        />
                                    </div>
                                    <div class="fs_customer_info">
                                        <router-link
                                            :to="{
                                                name: 'view_customer',
                                                params: { customer_id: scope.row.id },
                                            }"
                                            class="fs_customer_name_link"
                                        >
                                            {{ scope.row.full_name ? scope.row.full_name: 'N/A' }}
                                        </router-link>
                                        <div class="fs_customer_email">
                                            {{ scope.row.email }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column
                            width="120"
                            :label="$t('Status')"
                        >
                            <template #default="scope">
                                <div class="fs_status_badge fs_customer_status_badge" :class="`fs_status_${scope.row.status}`">
                                    <span v-if="scope.row.status == 'inactive'">{{
                                        $t("Blocked")
                                    }}</span>
                                    <span v-else>{{
                                        ucFirst(scope.row.status)
                                    }}</span>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column
                            width="180"
                            :label="$t('Last Activity')"
                        >
                            <template #default="scope">
                                {{ $timeDiff(scope.row.last_response_at) || '--' }}
                            </template>
                        </el-table-column>
                        <el-table-column
                            width="150"
                            :label="$t('Stats')"
                        >
                            <template #default="scope">
                                <router-link
                                    :to="{
                                        name: 'tickets',
                                        query: {
                                            search:
                                                'customer_id:' + scope.row.id,
                                        },
                                    }"
                                    class="fs_stats_link"
                                >
                                    <el-button
                                        size="small"
                                        text
                                        class="fs_stats_button"
                                    >
                                        <template #icon>
                                            <img :src="appVars.asset_url + 'images/ticketCount.svg'"/>
                                        </template>
                                        {{ scope.row.total_tickets }}
                                    </el-button>
                                </router-link>
                            </template>
                        </el-table-column>
                        <el-table-column
                            align="center"
                            width="150"
                            :label="$t('Action')"
                        >
                            <template #default="scope">
                                <div class="fs-table-action-cell">
                                    <div class="fs_action_button_wrapper">
                                        <el-button
                                            class="fs_action_button"
                                            @click="showAddCustomerModal(scope.row)"
                                            text
                                            icon="EditPen">
                                        </el-button>
                                    </div>

                                    <TableMoreActions
                                        v-if="canDeleteCustomers"
                                        :scope="scope"
                                        :fetching="loading"
                                        @delete="deleteCustomer(scope.row.id)"
                                        :delete-warning="$t('Are you sure to delete this customer? It will delete all associated data with this customer')"
                                    />
                                </div>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <div class="fs_pagination_wrapper" v-if="customers.length">
                    <span class="fs_pagination_left">
                        <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                        <pagination
                            @fetch="fetchCustomers()"
                            :pagination="pagination"
                            layout="sizes"
                        />
                    </span>
                    <span class="fs_pagination_right">
                        <pagination
                            @fetch="fetchCustomers()"
                            :pagination="pagination"
                            :background="true"
                            layout="prev, pager, next"
                        />
                    </span>
                </div>
            </div>
        </div>
        <div class="fs_table_container fs_skeleton_loader" v-else>
            <el-skeleton class="fs_box_wrapper fs_skeleton" :rows="5" animated />
        </div>
        <el-dialog
            :append-to-body="true"
            :title="
                editing_customer.id
                    ? $t('Update customer')
                    : $t('Add new customer')
            "
            v-model="showEditModal"
            v-if="editing_customer"
            width="60%"
            class="fs_dialog"
        >
            <customer-form
                v-if="editing_customer"
                @updated="closeModal()"
                :customer="editing_customer"
                :key="componentKey"
            />
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="() => showEditModal = false">
                        {{ $t("Cancel") }}
                    </el-button>

                    <el-button
                        v-loading="saving"
                        :disabled="saving"
                        @click="createOrUpdateCustomer()"
                        class="fs_filled_btn"
                        >
                        {{
                            (editing_customer && editing_customer.id)
                                ? $t("Update Customer")
                                : $t("Create Customer") }}
                    </el-button>
                </span>
            </template>
        </el-dialog>

        <el-dialog
            v-model="showBulkDeleteDialog"
            :title="$t('Confirm Delete')"
            width="400"
            class="fs_dialog"
            :append-to-body="true"
        >
            <div class="fs_dialog_warning_content">
                <i class="el-icon el-message-box__status el-message-box-icon--warning">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">
                        <path
                            fill="currentColor"
                            d="M512 64a448 448 0 1 1 0 896 448 448 0 0 1 0-896m0 192a58.432 58.432 0 0 0-58.24 63.744l23.36 256.384a35.072 35.072 0 0 0 69.76 0l23.296-256.384A58.432 58.432 0 0 0 512 256m0 512a51.2 51.2 0 1 0 0-102.4 51.2 51.2 0 0 0 0 102.4"
                        />
                    </svg>
                </i>
                <p>{{ bulkDeleteWarning }}</p>
            </div>
            <template #footer>
                <div class="fs_popconfirm_actions">
                    <el-button
                        class="fs_outline_btn"
                        size="small"
                        @click="cancelBulkDelete"
                    >
                        {{ $t('Cancel') }}
                    </el-button>
                    <el-button
                        class="fs_filled_btn"
                        size="small"
                        :loading="deleting"
                        @click="confirmBulkDelete"
                    >
                        {{ $t('Delete') }}
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Pagination from "../../Pieces/Pagination";
import CustomerForm from "./_CustomerForm";
import TableMoreActions from "@/admin/Components/TableMoreActions.vue";
import BulkAction from "@/admin/Components/BulkAction.vue";

export default {
    name: "Customers",
    components: {
        Pagination,
        CustomerForm,
        TableMoreActions,
        BulkAction,
    },
    data() {
        return {
            customers: [],
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0,
            },
            loading: false,
            deleting: false,
            saving: false,
            first_time_loading: true,
            showEditModal: false,
            editing_customer: {},
            status: "",
            search: "",
            selectedCustomers: [],
            selectedBulkAction: "",
            showBulkDeleteDialog: false,
            bulkActionItems: [],
            statusFilters: {
                active: this.$t("Active"),
                inactive: this.$t("Blocked"),
            },
        };
    },
    computed: {
        componentKey() {
            return this.editing_customer && this.editing_customer.id
                ? this.editing_customer.id.toString()
                : (Math.random() * 1000).toFixed(0).toString();
        },
        allStatusFilters() {
            return {
                '': this.$t('All'),
                ...this.statusFilters
            };
        },
        // Deletion cascades into tickets, conversations and attachments, so the API
        // restricts it to administrators unless the customer_delete_requires_admin filter
        // says otherwise. Mirror that so agents are not shown buttons that 403.
        canDeleteCustomers() {
            const permissions = this.appVars?.me?.permissions || [];

            if (!permissions.includes('fst_sensitive_data')) {
                return false;
            }

            if (!this.appVars?.customer_delete_requires_admin) {
                return true;
            }

            return permissions.includes('administrator');
        },
        bulkActions() {
            return [
                { value: 'delete', label: 'Delete' }
            ];
        },
        bulkDeleteWarning() {
            const count = this.bulkActionItems ? this.bulkActionItems.length : 0;
            if (!count) {
                return this.$t('No customers selected for deletion.');
            }
            return this.$t(
                `Are you sure to delete ${count} customer(s)? It will delete all associated data with these customers`
            );
        }
    },
    mounted() {
        this.$setTitle(this.$t("Customers"));
        this.fetchCustomers();
    },
    methods: {
        async fetchCustomers() {
            this.$get("customers", {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search,
                status: this.status,
            })
                .then((response) => {
                    this.customers = response.customers.data;
                    this.pagination.total = response.customers.total;
                })
                .always(() => {
                    this.loading = false;
                    this.first_time_loading = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                });
        },

        setStatus(statusValue) {
            this.status = statusValue;
            this.fetchCustomers();
        },

        showAddCustomerModal(customer) {
            this.editing_customer = customer;
            this.showEditModal = true;
        },

        closeModal() {
            this.editing_customer = {};
            this.showEditModal = false;
            this.fetchCustomers();
        },

        deleteCustomer(customerId) {
            this.deleting = true;
            this.$del(`customers/${customerId}`)
                .then((response) => {
                    this.fetchCustomers();
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.deleting = false;
                });
        },

        async createOrUpdateCustomer() {
            this.saving = true;
            let method = this.editing_customer.id ? this.$put : this.$post;
            let route = this.editing_customer.id ? `customers/${this.editing_customer.id}` : "customers";

            method(route, {
                ...this.editing_customer,
            })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.fetchCustomers();
                    this.showEditModal = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        tableRowClassName({ row, rowIndex }) {
            return "fs_status_" + row.status;
        },

        handleImageError(event) {
            event.target.style.display = 'none';
        },

        handleSelectionChange(selection) {
            this.selectedCustomers = selection;
        },

        async handleBulkAction({ action, items }) {
            if (action === 'delete') {
                if (!items || !items.length) {
                    return;
                }

                this.bulkActionItems = items;
                this.showBulkDeleteDialog = true;
            }
        },

        async confirmBulkDelete() {
            if (!this.bulkActionItems || !this.bulkActionItems.length) {
                this.showBulkDeleteDialog = false;
                return;
            }

            await this.bulkDeleteCustomers(this.bulkActionItems);
            this.showBulkDeleteDialog = false;
            this.bulkActionItems = [];
        },

        cancelBulkDelete() {
            this.showBulkDeleteDialog = false;
            this.bulkActionItems = [];
        },

        async bulkDeleteCustomers(customers) {
            if (!customers || customers.length === 0) return;

            const customerIds = customers.map(customer => customer.id);

            try {
                this.deleting = true;
                const response = await this.$del('customers/bulk-delete', {
                    customer_ids: customerIds
                });

                this.$notify({
                    message: response.message || this.$t('Customers deleted successfully'),
                    type: "success",
                    position: "bottom-right",
                });

                this.selectedCustomers = [];
                this.selectedBulkAction = "";
                await this.fetchCustomers();

            } catch (error) {
                this.$handleError(error);
            } finally {
                this.deleting = false;
            }
        }
    }
};
</script>
