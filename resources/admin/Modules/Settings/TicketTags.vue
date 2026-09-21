<template>
    <div class="fs_products">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <div class="fs_box_head">
                        <h3>{{ $t("Ticket Tags") }}</h3>
                    </div>
                </div>
            </div>
            <template v-if="has_pro">
                <div v-if="!fetching" class="fs_table_container">
                    <div class="fs_table_header">
                        <div class="fs_box_actions">
                            <div class="fs_ticket_orders fs_table_search_field">
                                <el-input
                                    @keyup.enter="fetchTags"
                                    clearable
                                    @clear="fetchTags"
                                    :disabled="!has_pro"
                                    :placeholder="$t('Search...')"
                                    v-model="search"
                                    class="fs_text_input fs_table_search_input"
                                >
                                    <template #prefix>
                                        <el-icon class="el-input__icon"><search /></el-icon>
                                    </template>
                                </el-input>
                            </div>
                            <el-button
                                v-if="has_pro"
                                @click="createTagModal()"
                                class="fs_filled_btn"
                                icon="Plus"
                            >
                                {{ $t("Add New") }}
                            </el-button>
                        </div>
                    </div>
                    <div class="fs_table_wrapper">
                        <el-table
                            :data="tags"
                            row-class-name="fs_table_row"
                            header-row-class-name="fs_table_header_row"
                            cell-class-name="fs_table_cell"
                            header-cell-class-name="fs_table_header_cell"
                            table-layout="fixed"
                        >
                            <template #empty>
                                <div class="fs_table_empty_state">
                                    <el-empty
                                        :description="$t('No Tags Found')"
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
                                :label="$t('Name')"
                            ></el-table-column>
                            <el-table-column
                                prop="description"
                                width="250"
                                :label="$t('Description')"
                            ></el-table-column>
                            <el-table-column
                                :label="$t('Tickets')"
                                width="80"
                            >
                                <template #default="scope">
                                    <router-link
                                        :to="{
                                            name: 'tickets',
                                            query: { tags: [scope.row.id] },
                                        }"
                                    >
                                        {{ scope.row.count }}
                                    </router-link>
                                </template>
                            </el-table-column>
                            <el-table-column align="center" width="150" :label="$t('Action')">
                                <template #default="scope">
                                    <div class="fs-table-action-cell">
                                        <div class="fs_action_button_wrapper">
                                            <el-button
                                                class="fs_action_button"
                                                @click="editTagModal(scope.row)"
                                                text
                                                icon="EditPen">
                                            </el-button>
                                        </div>

                                        <TableMoreActions
                                            :scope="scope"
                                            :fetching="fetching"
                                            @delete="deleteTag(scope.row)"
                                            :delete-warning="$t('tag_delete_warning')"
                                        />
                                    </div>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <div class="fs_pagination_wrapper" v-if="tags.length">
                        <span class="fs_pagination_left">
                            <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                            <pagination
                                @fetch="fetchTags()"
                                :pagination="pagination"
                                layout="sizes"
                            />
                        </span>
                        <span class="fs_pagination_right">
                            <pagination
                                @fetch="fetchTags()"
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
            </template>
            <NarrowPromo
                v-else
                :heading="$t('segment_ticket_by_tags')"
                :description="$t('pro_promo')"
                :button-text="$t('Upgrade To Pro')"
                utm-content="feature_lock_ticket_tags"
            />
        </div>

        <el-dialog
            :append-to-body="true"
            :title="
            editing_tag && editing_tag.id
                ? $t('Edit Tag')
                : $t('Create New Ticket Tag')
            "
            v-model="tag_modal"
            v-if="editing_tag"
            width="60%"
            class="fs_dialog"
        >
            <el-form label-position="top" :data="editing_tag">
            <el-form-item :label="$t('Tag Name')" class="fs_form_item">
                <el-input
                    class="fs_text_input fs_text_input_40"
                    type="text"
                    :placeholder="$t('Tag Title')"
                    v-model="editing_tag.title"
                />
            </el-form-item>
            <el-form-item :label="$t('Description')" class="fs_form_item">
                <el-input
                    class="fs_textarea_input fs_text_input_40"
                    type="textarea"
                    rows="4"
                    :placeholder="$t('Tag Description')"
                    v-model="editing_tag.description"
                />
            </el-form-item>
            </el-form>

            <template #footer>
            <span class="fs_dialog_footer">

                <el-button
                    class="fs_outline_btn"
                    @click="() => tag_modal = false">
                    {{ $t("Cancel") }}
                </el-button>


                <el-button
                    v-loading="saving"
                    :disabled="saving"
                    @click="createOrUpdateTag()"
                    class="fs_filled_btn"
                    >
                    {{
                        (editing_tag && editing_tag.id)
                            ? $t("Update Ticket Tag")
                            : $t("Add Ticket Tag") }}
                </el-button
                >
            </span>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Pagination from "../../Pieces/Pagination";
import TableMoreActions from "@/admin/Components/TableMoreActions.vue";
import TableEmptyStateImage from "@/admin/Components/TableEmptyStateImage.vue";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: "TicketTags",
    components: {
        Pagination,
        TableMoreActions,
        TableEmptyStateImage,
        NarrowPromo
     },

    data() {
        return {
            tags: [],
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0,
            },
            fetching: true,
            saving: false,
            tag_modal: false,
            editing_tag: false,
            search: "",
        };
    },
    methods: {
        fetchTags() {
            this.fetching = true;
            this.$get("ticket-tags", {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search,
            })
                .then((response) => {
                    this.tags = response.tags.data;
                    this.pagination.total = response.tags.total;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },

        createOrUpdateTag() {
            this.saving = true;
            let method = this.$post;
            let route = "ticket-tags";
            if (this.editing_tag.id) {
                method = this.$put;
                route = `ticket-tags/${this.editing_tag.id}`;
            }

            method(route, {
                ...this.editing_tag,
            })
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.fetchTags();
                    this.tag_modal = false;
                    this.renewOptions("ticket-tags/options");
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        editTagModal(tag) {
            this.editing_tag = JSON.parse(JSON.stringify(tag));
            this.tag_modal = true;
        },

        createTagModal() {
            this.editing_tag = {
                title: "",
                description: "",
            };
            this.tag_modal = true;
        },

        deleteTag(tag) {

            this.$del(`ticket-tags/${tag.id}`).then((response) => {
                this.$notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });
                this.fetchTags();
                this.renewOptions("ticket-tags/options");
            });
        }
    },

    mounted() {
        if (this.has_pro) {
            this.fetchTags();
        }
        this.$setTitle(this.$t("Ticket Tags"));
    }
};
</script>
