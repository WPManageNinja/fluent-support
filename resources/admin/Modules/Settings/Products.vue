<template>
    <div class="fs_products">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <div class="fs_box_head">
                        <h3>{{ $t("Products") }}</h3>
                    </div>
                </div>
            </div>
            <div v-if="!fetching" class="fs_table_container">
                <div class="fs_table_header">
                    <div class="fs_box_actions">
                        <div class="fs_ticket_orders fs_table_search_field">
                            <el-input
                                @keyup.enter="getProducts"
                                clearable
                                @clear="getProducts"
                                :placeholder="$t('Search...')"
                                v-model="search"
                                class="fs_text_input fs_table_search_input"
                            >
                                <template #prefix>
                                    <el-icon class="el-input__icon"><Search /></el-icon>
                                </template>
                            </el-input>
                        </div>
                        <el-button
                            @click="createTicketModal()"
                            class="fs_filled_btn"
                            icon="Plus"
                        >
                            {{ $t("Add New") }}
                        </el-button>
                    </div>
                </div>
                <div class="fs_table_wrapper">
                    <el-table
                        :data="products"
                        row-class-name="fs_table_row"
                        header-row-class-name="fs_table_header_row"
                        cell-class-name="fs_table_cell"
                        header-cell-class-name="fs_table_header_cell"
                        table-layout="fixed"
                    >
                        <template #empty>
                            <div class="fs_table_empty_state">
                                <el-empty
                                    :description="$t('No Products Found')"
                                    :image-size="130"
                                >
                                    <template #image>
                                        <TableEmptyStateImage />
                                    </template>
                                </el-empty>
                            </div>
                        </template>
                        <el-table-column
                            width="100"
                            prop="id"
                            :label="$t('ID')"
                        ></el-table-column>
                        <el-table-column
                            prop="title"
                            width="200"
                            :label="$t('Title')"
                        ></el-table-column>
                        <el-table-column
                            prop="description"
                            width="250"
                            :label="$t('Description')"
                        ></el-table-column>
                        <el-table-column align="center" width="150" :label="$t('Action')">
                            <template #default="scope">
                                <div class="fs-table-action-cell">
                                    <div class="fs_action_button_wrapper">
                                        <el-button
                                            class="fs_action_button"
                                            @click="editProductModal(scope.row)"
                                            text
                                            icon="EditPen">
                                        </el-button>
                                    </div>

                                    <TableMoreActions
                                        :scope="scope"
                                        :fetching="fetching"
                                        @delete="deleteProduct(scope.row)"
                                        :delete-warning="$t('product_delete_warning')"
                                    />
                                </div>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <div class="fs_pagination_wrapper" v-if="products.length">
                    <span class="fs_pagination_left">
                        <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                        <pagination
                            @fetch="getProducts()"
                            :pagination="pagination"
                            layout="sizes"
                        />
                    </span>
                    <span class="fs_pagination_right">
                        <pagination
                            @fetch="getProducts()"
                            :pagination="pagination"
                            :background="true"
                            layout="prev, pager, next"
                        />
                    </span>
                </div>
            </div>
            <div class="fs_box_body fs_skeleton_loader" v-else>
                <el-skeleton class="fs_box_wrapper" :rows="5" animated />
            </div>
        </div>

        <el-dialog
            v-if="editing_product"
            :append-to-body="true"
            :title="
                editing_product && editing_product.id
                    ? $t('Edit Product')
                    : $t('Create New Supported Product')
            "
            v-model="ticket_modal"
            width="60%"
            class="fs_dialog"
        >
            <el-form label-position="top" :data="editing_product">
                <el-form-item :label="$t('Title')" class="fs_form_item">
                    <el-input
                        class="fs_text_input fs_text_input_40"
                        type="text"
                        :placeholder="$t('Product Title')"
                        v-model="editing_product.title"
                    />
                </el-form-item>
                <el-form-item :label="$t('Description')" class="fs_form_item">
                    <el-input
                        class="fs_textarea_input fs_text_input_40"
                        type="textarea"
                        rows="4"
                        :placeholder="$t('Product Description')"
                        v-model="editing_product.description"
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="() => ticket_modal = false">
                        {{ $t("Cancel") }}
                    </el-button>

                    <el-button
                        v-loading="saving"
                        :disabled="saving"
                        @click="createOrUpdateProduct()"
                        class="fs_filled_btn"
                        >
                        {{
                            (editing_product && editing_product.id)
                                ? $t("Update Product")
                                : $t("Add Product") }}
                    </el-button>
                </span>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Pagination from "../../Pieces/Pagination";
import TableMoreActions from "@/admin/Components/TableMoreActions.vue";
import TableEmptyStateImage from "@/admin/Components/TableEmptyStateImage.vue";
export default {
    name: "SupportProducts",
    components: {
        Pagination,
        TableMoreActions,
        TableEmptyStateImage
     },

    data() {
        return {
            products: [],
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0,
            },
            fetching: true,
            saving: false,
            ticket_modal: false,
            editing_product: false,
            search: "",
        };
    },
    methods: {
        getProducts() {
            this.fetching = true;
            this.$get("products", {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search,
            })
            .then((response) => {
                this.products = response.products.data;
                this.pagination.total = response.products.total;
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.fetching = false;
            });
        },

        createOrUpdateProduct() {
            this.saving = true;
            let method = this.$post;
            let route = "products";
            if (this.editing_product.id) {
                method = this.$put;
                route = `products/${this.editing_product.id}`;
            }

            method(route, {
                ...this.editing_product,
            })
            .then((response) => {
                this.$notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });
                this.getProducts();
                this.ticket_modal = false;
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.saving = false;
            });
        },

        editProductModal(product) {
            this.editing_product = JSON.parse(JSON.stringify(product));
            this.ticket_modal = true;
        },

        createTicketModal() {
            this.editing_product = {
                title: "",
                description: "",
            };
            this.ticket_modal = true;
        },

        deleteProduct(product) {
            this.$del(`products/${product.id}`).then((response) => {
                this.$notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });
                this.getProducts();
            });
        }
    },

    mounted() {
        this.getProducts();
        this.$setTitle(this.$t("Products Settings"));
    }
};
</script>

