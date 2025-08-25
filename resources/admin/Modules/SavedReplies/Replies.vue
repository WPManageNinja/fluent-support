<template>
    <div class="fs_saved_replies">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ translate("Saved Replies") }}</h3>
                </div>
                <div class="fs_box_actions" style="display: flex">
                    <el-button
                        :disabled="!has_pro"
                        @click="createModal()"
                        type="primary"
                        icon="Plus"
                    >
                        {{ translate("Create New") }}
                    </el-button>

                    <div class="fs_ticket_orders" style="margin-left: 0.7em">
                        <el-input
                            @keyup.enter="fetch"
                            clearable
                            @clear="fetch"
                            :disabled="!has_pro"
                            :placeholder="translate('Search Replies')"
                            v-model="search"
                        >
                            <template #append>
                                <el-button
                                    @click="fetch"
                                    :disabled="!has_pro"
                                    icon="Search"
                                ></el-button>
                            </template>
                        </el-input>
                    </div>
                </div>
            </div>
            <template v-if="has_pro">
                <div v-if="!loading" class="fs_box_body">
                    <el-table stripe :data="replies">
                        <el-table-column
                            width="80"
                            prop="id"
                            :label="translate('ID')"
                        ></el-table-column>
                        <el-table-column
                            prop="title"
                            :label="translate('Title')"
                        ></el-table-column>
                        <el-table-column width="180" :label="translate('Product')">
                            <template #default="scope">
                                {{ scope.row.product?.title }}
                            </template>
                        </el-table-column>
                        <el-table-column width="180" :label="translate('Created By')">
                            <template #default="scope">
                                {{ scope.row.person?.full_name }}
                            </template>
                        </el-table-column>
                        <el-table-column width="180" :label="translate('Created On')">
                            <template #default="scope">
                                {{ humanDiffTime(scope.row.created_at) }}
                            </template>
                        </el-table-column>
                        <el-table-column width="120" :label="translate('Action')">
                            <template #default="scope">
                                <el-button
                                    @click="editModal(scope.row)"
                                    icon="EditPen"
                                    :text="true"
                                    style="color: var(--el-color-primary);"
                                ></el-button>
                                <el-popconfirm
                                    confirm-button-type="danger"
                                    :confirm-button-text="
                                        translate('Yes, Delete this')
                                    "
                                    :cancel-button-text="translate('No')"
                                    icon="InfoFilled"
                                    icon-color="red"
                                    :title="translate('replies_delete_warning')"
                                    @confirm="deleteReply(scope.row)"
                                >
                                    <template #reference>
                                        <el-button
                                            v-loading="loading"
                                            style="
                                                margin-left: 10px;
                                                color: red;
                                            "
                                            :text="true"
                                            icon="Delete"
                                        ></el-button>
                                    </template>
                                </el-popconfirm>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="fframe_pagination_wrapper" v-if="replies.length">
                        <pagination @fetch="fetch()" :pagination="pagination" />
                    </div>
                </div>
                <div
                    style="padding: 20px; background: white"
                    class="fs_box_body"
                    v-else
                >
                    <el-skeleton :rows="5" animated />
                </div>
            </template>
            <div class="fs_narrow_promo" style="background: white" v-else>
                <h3>{{ translate("create_reply_template") }}</h3>
                <p>{{ translate("pro_promo") }}</p>
                <a
                    target="_blank"
                    rel="noopener"
                    href="https://fluentsupport.com"
                    class="el-button el-button--success"
                    >{{ translate("Upgrade To Pro") }}</a
                >
            </div>
        </div>

        <Teleport to="body">
            <modal
                :show="showModal"
                @close="showModal = false"
                :title="
                    editing_reply && editing_reply.id
                        ? translate('Edit Reply')
                        : translate('Create New Reply')
                "
            >
                <template #body>
                    <el-form label-position="top" :data="editing_reply">
                        <el-form-item :label="translate('Title')">
                            <el-input
                                type="text"
                                :placeholder="translate('Reply Title')"
                                v-model="editing_reply.title"
                            />
                        </el-form-item>
                        <el-form-item :label="translate('Content')">
                            <wp-editor v-model="editing_reply.content" :show-shortcodes="true" :editor_shortcodes="shortcodes"/>
                        </el-form-item>
                        <el-form-item :label="translate('Preferred Product')">
                            <el-select
                                :placeholder="translate('Select Product')"
                                v-model="editing_reply.product_id"
                                clearable
                            >
                                <el-option
                                    v-for="product in products"
                                    :key="product.id"
                                    :label="product.title"
                                    :value="product.id"
                                ></el-option>
                            </el-select>
                        </el-form-item>
                    </el-form>
                </template>
                <template #footer>
                    <el-button
                        v-loading="saving"
                        :disabled="saving"
                        type="success"
                        @click="createOrUpdate()"
                        >{{ translate("Save") }}</el-button
                    >
                </template>
            </modal>
        </Teleport>
    </div>
</template>

<script title="text/babel">
import Pagination from "../../Pieces/Pagination";
import WpEditor from "../../Pieces/_wp_editor";
import Modal from "../../Pieces/Modal";
import {  onMounted, reactive, toRefs } from "vue";
import { useFluentHelper, useNotify } from "@/admin/Composable/FluentFrameworkHelper";

export default {
    name: "SavedReplies",
    components: {
        WpEditor,
        Pagination,
        Modal,
    },
    setup() {
        const {
            appVars,
            get,
            translate,
            handleError,
            setTitle,
            post,
            put,
            del,
            has_pro,
            humanDiffTime
        } = useFluentHelper();

        const { notify } = useNotify();

        const state = reactive({
            replies: [],
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0,
            },
            loading: false,
            saving: false,
            editing_reply: false,
            products: appVars.support_products,
            search: "",
            shortcodes: {
                "{{customer.first_name}}": "Customer First Name",
                "{{customer.last_name}}": "Customer Last Name",
                "{{customer.full_name}}": "Customer Full Name",
                "{{customer.email}}": "Customer Email",
                "{{customer.title}}": "Customer Title",
                "{{customer.status}}": "Customer Status",
                "{{agent.first_name}}": "Agent First Name",
                "{{agent.last_name}}": "Agent Last Name",
                "{{agent.full_name}}": "Agent Full Name",
                "{{agent.email}}": "Agent Email",
                "{{agent.title}}": "Agent Title",
            },
            showModal: false,
        });

        const fetch = async () => {
            state.loading = true;
            await get("saved-replies", {
                page: state.pagination.current_page,
                per_page: state.pagination.per_page,
                search: state.search,
            })
                .then((response) => {
                    state.replies = response.replies.data;
                    state.pagination.total = response.replies.total;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.loading = false;
                });
        };

        const createOrUpdate = async () => {
            state.saving = true;
            let method = post;
            let route = "saved-replies";
            if (state.editing_reply.id) {
                method = put;
                route = `saved-replies/${state.editing_reply.id}`;
            }

            method(route, {
                ...state.editing_reply,
            })
                .then((response) => {
                    fetch();
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    state.showModal = false;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.saving = false;
                });
        };

        const editModal = (reply) => {
            state.editing_reply = JSON.parse(JSON.stringify(reply));
            state.showModal = true;
        };

        const createModal = () => {
            state.editing_reply = {
                title: "",
                content: "",
                product_id: null,
            };
            state.showModal = true;
        };

        const deleteReply = async (reply) => {
            await del(`saved-replies/${reply.id}`).then((response) => {
                notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });

                fetch();
            });
        };

        onMounted(() => {
            if (has_pro) {
                fetch();
            }
            setTitle("Saved Replies");
        });

        return {
            ...toRefs(state),
            fetch,
            createOrUpdate,
            editModal,
            createModal,
            deleteReply,
            translate,
            humanDiffTime
        }
    },
};
</script>

<style scoped>
.el-button.is-text{
    padding: 5px;
}
</style>
