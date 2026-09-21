<template>
    <div class="fs_products">
        <div class="fs_box_wrapper fs_custom_fields_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t("Custom Ticket Fields") }}</h3>
                </div>
            </div>
            <template v-if="has_pro">
                <div v-if="!loading" class="fs_table_container">
                    <div class="fs_custom_table_field_header fs_table_header">
                        <div class="fs_custom_field_actions">
                            <el-button
                                v-if="has_pro"
                                @click="addFieldVisible = true"
                                class="fs_filled_btn"
                                icon="Plus"
                            >
                                {{ $t("Add New Field") }}
                            </el-button>
                        </div>
                    </div>
                    <div class="fs_table_wrapper">
                        <div class="fs_custom_field_table_header_row">
                            <div class="fs_field_label_header">
                                {{ $t("Label") }}
                            </div>
                            <div class="fs_field_slug_header">
                                {{ $t("Slug") }}
                            </div>
                            <div class="fs_field_type_header">
                                {{ $t("Type") }}
                            </div>
                            <div class="fs_field_actions_header">
                                {{ $t("Actions") }}
                            </div>
                        </div>
                        <draggable
                            v-model="fields"
                            ghost-class="ghost"
                            item-key="slug"
                            @end="saveFields"
                            handle=".fs_custom_drag_svg"
                        >
                            <template #item="{ element, index }">
                                <div
                                    :key="element.slug"
                                    class="fs_custom_field_row"
                                >
                                    <span class="fs_custom_drag_svg">
                                        <svg
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M4 0.5H20C21.933 0.5 23.5 2.067 23.5 4V20C23.5 21.933 21.933 23.5 20 23.5H4C2.067 23.5 0.5 21.933 0.5 20V4L0.504883 3.82031C0.595411 2.03035 2.03035 0.595411 3.82031 0.504883L4 0.5Z"
                                                fill="var(--fs-bg-primary, white)"
                                            />
                                            <path
                                                d="M4 0.5H20C21.933 0.5 23.5 2.067 23.5 4V20C23.5 21.933 21.933 23.5 20 23.5H4C2.067 23.5 0.5 21.933 0.5 20V4L0.504883 3.82031C0.595411 2.03035 2.03035 0.595411 3.82031 0.504883L4 0.5Z"
                                                stroke="var(--fs-border-default, #E1E4EA)"
                                            />
                                            <path
                                                d="M9.375 8.25C9.99632 8.25 10.5 7.74632 10.5 7.125C10.5 6.50368 9.99632 6 9.375 6C8.75368 6 8.25 6.50368 8.25 7.125C8.25 7.74632 8.75368 8.25 9.375 8.25ZM9.375 13.125C9.99632 13.125 10.5 12.6213 10.5 12C10.5 11.3787 9.99632 10.875 9.375 10.875C8.75368 10.875 8.25 11.3787 8.25 12C8.25 12.6213 8.75368 13.125 9.375 13.125ZM10.5 16.875C10.5 17.4963 9.99632 18 9.375 18C8.75368 18 8.25 17.4963 8.25 16.875C8.25 16.2537 8.75368 15.75 9.375 15.75C9.99632 15.75 10.5 16.2537 10.5 16.875ZM14.625 8.25C15.2463 8.25 15.75 7.74632 15.75 7.125C15.75 6.50368 15.2463 6 14.625 6C14.0037 6 13.5 6.50368 13.5 7.125C13.5 7.74632 14.0037 8.25 14.625 8.25ZM15.75 12C15.75 12.6213 15.2463 13.125 14.625 13.125C14.0037 13.125 13.5 12.6213 13.5 12C13.5 11.3787 14.0037 10.875 14.625 10.875C15.2463 10.875 15.75 11.3787 15.75 12ZM14.625 18C15.2463 18 15.75 17.4963 15.75 16.875C15.75 16.2537 15.2463 15.75 14.625 15.75C14.0037 15.75 13.5 16.2537 13.5 16.875C13.5 17.4963 14.0037 18 14.625 18Z"
                                                fill="#525866"
                                            />
                                        </svg>
                                    </span>
                                    <div class="fs_custom_field_column">
                                        <div class="fs_field_label">
                                            {{
                                                element.label ||
                                                element.admin_label
                                            }}
                                            <el-icon
                                                :title="$t('Agent Only Field')"
                                                v-if="
                                                    element.admin_only == 'yes'
                                                "
                                                ><Lock
                                            /></el-icon>
                                            <el-icon
                                                :title="$t('Conditional Field')"
                                                v-if="
                                                    element.has_logics == 'yes'
                                                "
                                                ><Connection
                                            /></el-icon>
                                        </div>
                                        <div class="fs_field_slug">
                                            {{ element.slug }}
                                        </div>
                                        <div class="fs_field_type">
                                            {{ element.type }}
                                        </div>
                                        <div class="fs_field_actions">
                                            <div class="fs-table-action-cell">
                                                <div
                                                    class="fs_action_button_wrapper"
                                                >
                                                    <el-button
                                                        class="fs_action_button"
                                                        @click="
                                                            updateFieldModal(
                                                                index
                                                            )
                                                        "
                                                        text
                                                        icon="EditPen"
                                                    >
                                                    </el-button>
                                                </div>
                                                <TableMoreActions
                                                    :scope="{
                                                        row: element,
                                                        $index: index,
                                                    }"
                                                    :fetching="loading"
                                                    @delete="deleteField(index)"
                                                    :delete-warning="
                                                        $t(
                                                            'Delete custom field'
                                                        )
                                                    "
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                    <div class="fs_pagination_wrapper" v-if="fields.length">
                        <span class="fs_pagination_left">
                            <p>
                                Page {{ pagination.current_page }} of
                                {{
                                    Math.ceil(
                                        pagination.total / pagination.per_page
                                    )
                                }}
                            </p>
                            <pagination
                                @fetch="fetchFields()"
                                :pagination="pagination"
                                layout="sizes"
                            />
                        </span>
                        <span class="fs_pagination_right">
                            <pagination
                                @fetch="fetchFields()"
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
                :heading="$t('tk_data_collect_using_customfield')"
                :description="$t('pro_promo')"
                :button-text="$t('Upgrade To Pro')"
                utm-content="feature_lock_custom_fields"
            />
        </div>
        <el-dialog
            :title="$t('Add New Custom Field')"
            v-model="addFieldVisible"
            :append-to-body="true"
            width="60%"
            class="fs_dialog"
        >
            <custom-field-form
                form_type="new"
                :field_types="field_types"
                :item="new_item"
                :fields="fields"
            ></custom-field-form>
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="() => addFieldVisible = false">
                        {{ $t("Cancel") }}
                    </el-button>
                    <el-button class="fs_filled_btn" @click="createField()" type="success">{{
                        $t("Add")
                    }}</el-button>
                </span>
            </template>
        </el-dialog>

        <el-dialog
            :title="$t('Update Custom Field')"
            v-model="updateFieldVisible"
            :append-to-body="true"
            width="60%"
            class="fs_dialog"
        >
            <custom-field-form
                v-if="updateFieldVisible"
                :fields="fields"
                form_type="update"
                :field_types="field_types"
                :item="update_field"
            ></custom-field-form>
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="() => updateFieldVisible = false">
                        {{ $t("Cancel") }}
                    </el-button>
                    <el-button class="fs_filled_btn" @click="updateField()" type="success">{{
                        $t("Update Field")
                    }}</el-button>
                </span>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import CustomFieldForm from "./_CustomFieldForm";
import TableMoreActions from "@/admin/Components/TableMoreActions.vue";
import Pagination from "../../Pieces/Pagination";
import TableEmptyStateImage from "@/admin/Components/TableEmptyStateImage.vue";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";
import draggable from "vuedraggable";

export default {
    name: "CustomFields",
    components: {
        CustomFieldForm,
        TableMoreActions,
        Pagination,
        TableEmptyStateImage,
        NarrowPromo,
        draggable,
    },
    data() {
        return {
            fields: [],
            loading: false,
            new_item: {},
            field_types: {},
            addFieldVisible: false,
            updateFieldVisible: false,
            update_field: {},
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0,
            },
        };
    },
    created() {
        this.$setTitle(this.$t("Custom Ticket Fields"));
        if (this.has_pro) {
            this.fetchFields();
        }
    },
    methods: {
        fetchFields() {
            this.loading = true;
            this.$get("ticket-custom-fields", {
                with: ["field_types"],
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
            })
                .then((response) => {
                    this.fields = response.fields;
                    this.field_types = response.field_types;
                    if (response.pagination) {
                        this.pagination = response.pagination;
                    }
                    this.loading = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                    this.loading = false;
                });
        },
        saveFields() {
            this.loading = true;
            this.$post("ticket-custom-fields", {
                fields: JSON.stringify(this.fields),
            })
                .then((response) => {
                    this.fields = response.fields;
                    this.$notify({
                        type: "success",
                        message: response.message || this.$t("Fields order updated successfully"),
                        position: "bottom-right",
                    });
                    this.loading = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                    this.loading = false;
                });
        },
        createField() {
            if (!this.validateField(this.new_item)) {
                return false;
            }
            this.fields.push(this.new_item);
            this.addFieldVisible = false;
            this.new_item = {};
            this.saveFields();
        },
        validateField(item) {
            if (!item.label) {
                this.$notify({
                    type: "error",
                    message: this.$t("Please Provide label"),
                    position: "bottom-right",
                });
                return false;
            }
            if (!("options" in item)) {
                return true;
            }
            if (!Array.isArray(item.options) || item.options.length === 0 || item.options.some((option) => option.trim() === "")) {
                this.$notify({
                    type: "error",
                    message: this.$t("Please provide valid field option values"),
                    position: "bottom-right",
                });
                return false;
            }
            return true;
        },
        updateFieldModal(fieldIndex) {
            this.update_field = this.fields[fieldIndex];
            this.updateFieldVisible = true;
        },
        updateField() {
            if (!this.validateField(this.update_field)) {
                return false;
            }
            this.updateFieldVisible = false;
            this.update_field = {};
            this.saveFields();
        },
        deleteField(fieldIndex) {
            this.fields.splice(fieldIndex, 1);
            this.saveFields();
        },
        movePosition(fromIndex, type) {
            let toIndex = fromIndex - 1;
            if (type === "down") {
                toIndex = fromIndex + 1;
            }
            // Create a new array to trigger reactivity
            const newFields = [...this.fields];
            const element = newFields[fromIndex];
            newFields.splice(fromIndex, 1);
            newFields.splice(toIndex, 0, element);
            this.fields = newFields;
            this.saveFields();
        },
    },
};
</script>
