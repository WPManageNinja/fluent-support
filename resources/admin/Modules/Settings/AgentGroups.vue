<template>
    <div>
        <div v-if="!fetching" class="fs_table_container" style="width: 100%; margin: 0;">
            <div class="fs_table_header">
                <div class="fs_box_actions">
                    <div class="fs_ticket_orders fs_table_search_field">
                        <el-input
                            @keyup.enter="getGroups"
                            clearable
                            @clear="getGroups"
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
                        @click="openCreateModal()"
                        class="fs_filled_btn"
                        icon="Plus"
                    >
                        {{ $t("Add Group") }}
                    </el-button>
                </div>
            </div>
            <div style="overflow: hidden; width: 100%;">
            <div class="fs_table_wrapper fs_agent_groups_table" style="overflow-x: auto;">
                <el-table
                    :data="groups"
                    row-class-name="fs_table_row"
                    header-row-class-name="fs_table_header_row"
                    cell-class-name="fs_table_cell"
                    header-cell-class-name="fs_table_header_cell"
                    table-layout="fixed"
                >
                    <template #empty>
                        <div class="fs_table_empty_state">
                            <el-empty
                                :description="$t('No Agent Groups Found')"
                                :image-size="130"
                            >
                                <template #image>
                                    <TableEmptyStateImage />
                                </template>
                            </el-empty>
                        </div>
                    </template>
                    <el-table-column
                        prop="title"
                        :label="$t('Title')"
                        width="150"
                    />
                    <el-table-column
                        :label="$t('Members')"
                        width="120"
                    >
                        <template #default="scope">
                            <span>{{ scope.row.agents_count }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column
                        :label="$t('Default')"
                        width="120"
                    >
                        <template #default="scope">
                            <el-tag
                                v-if="scope.row.settings && scope.row.settings.is_default"
                                type="success"
                                size="small"
                            >
                                {{ $t("Default") }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column align="center" width="200" :label="$t('Actions')">
                        <template #default="scope">
                            <div class="fs-table-action-cell">
                                <div class="fs_action_button_wrapper">
                                    <el-button
                                        class="fs_action_button"
                                        @click="openEditModal(scope.row)"
                                        text
                                        icon="EditPen"
                                    />
                                </div>
                                <div class="fs_action_button_wrapper">
                                    <el-button
                                        class="fs_action_button"
                                        @click="openDeleteModal(scope.row)"
                                        text
                                        icon="Delete"
                                    />
                                </div>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            </div>
            <div class="fs_pagination_wrapper" v-if="groups.length">
                <span class="fs_pagination_left">
                    <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                    <pagination
                        @fetch="getGroups()"
                        :pagination="pagination"
                        layout="sizes"
                    />
                </span>
                <span class="fs_pagination_right">
                    <pagination
                        @fetch="getGroups()"
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

        <el-dialog
            v-if="editing_group"
            :append-to-body="true"
            :title="editing_group.id ? $t('Edit Group') : $t('Create Agent Group')"
            v-model="group_modal"
            width="60%"
            class="fs_dialog"
        >
            <el-form label-position="top" :data="editing_group">
                <el-form-item :label="$t('Title')" class="fs_form_item" required>
                    <el-input
                        class="fs_text_input fs_text_input_40"
                        type="text"
                        :placeholder="$t('Group Title')"
                        v-model="editing_group.title"
                    />
                </el-form-item>
                <el-form-item :label="$t('Description')" class="fs_form_item">
                    <el-input
                        class="fs_textarea_input fs_text_input_40"
                        type="textarea"
                        rows="3"
                        :placeholder="$t('Group Description')"
                        v-model="editing_group.description"
                    />
                </el-form-item>
                <el-form-item :label="$t('Agents')" class="fs_form_item" required>
                    <el-select
                        v-model="editing_group.agent_ids"
                        multiple
                        filterable
                        :reserve-keyword="false"
                        :placeholder="$t('Select Agents')"
                        class="fs_select_field"
                        style="width: 100%"
                    >
                        <el-option
                            v-for="agent in availableAgents"
                            :key="agent.id"
                            :label="agent.first_name + ' ' + agent.last_name"
                            :value="agent.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="fs_form_item">
                    <el-checkbox v-model="editing_group.is_default">
                        {{ $t("Set as Default Group") }}
                    </el-checkbox>
                    <span class="fs_agent_group_default_description">
                        {{ $t("When new agents are created without being assigned to any group, they will automatically be added to the default group.") }}
                    </span>
                </el-form-item>
            </el-form>

            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="group_modal = false"
                    >
                        {{ $t("Cancel") }}
                    </el-button>
                    <el-button
                        v-loading="saving"
                        :disabled="saving || !hasChanges"
                        @click="createOrUpdateGroup()"
                        class="fs_filled_btn"
                    >
                        {{ editing_group.id ? $t("Update Group") : $t("Add Group") }}
                    </el-button>
                </span>
            </template>
        </el-dialog>

        <el-dialog
            v-if="deleting_group"
            :append-to-body="true"
            :title="$t('Delete Agent Group')"
            v-model="delete_modal"
            width="450"
            class="fs_dialog"
        >
            <div class="fs_dialog_warning_content">
                <p>{{ $t('All agents in this group will be moved to the selected fallback group.') }}</p>
            </div>
            <el-form label-position="top" style="margin-top: 15px;">
                <el-form-item :label="$t('Move agents to')" class="fs_form_item">
                    <el-select
                        v-model="fallback_group_id"
                        :placeholder="$t('Select a fallback group')"
                        class="fs_select_field"
                        style="width: 100%"
                    >
                        <el-option
                            v-for="group in fallbackGroups"
                            :key="group.id"
                            :label="group.title"
                            :value="group.id"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <div class="fs_popconfirm_actions">
                    <el-button class="fs_outline_btn" @click="delete_modal = false">
                        {{ $t('Cancel') }}
                    </el-button>
                    <el-button
                        class="fs_filled_btn"
                        v-loading="deleting"
                        :disabled="!fallback_group_id || deleting"
                        @click="confirmDeleteGroup"
                    >
                        {{ $t('Confirm Delete') }}
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>
<script type="text/babel">
import Pagination from "../../Pieces/Pagination";
import TableEmptyStateImage from "@/admin/Components/TableEmptyStateImage.vue";

export default {
    name: "agent-groups",
    components: {
        Pagination,
        TableEmptyStateImage
    },
    data() {
        return {
            groups: [],
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0,
            },
            fetching: true,
            saving: false,
            group_modal: false,
            editing_group: false,
            original_group: null,
            search: "",
            freshAgents: [],
            delete_modal: false,
            deleting_group: null,
            fallback_group_id: null,
            deleting: false,
        };
    },
    computed: {
        availableAgents() {
            return this.freshAgents.length ? this.freshAgents : (this.appVars.support_agents || []);
        },
        fallbackGroups() {
            if (!this.deleting_group) return [];
            return this.groups.filter(g => g.id !== this.deleting_group.id);
        },
        hasChanges() {
            if (!this.editing_group || !this.editing_group.id) return true;
            if (!this.original_group) return true;
            return JSON.stringify(this.editing_group) !== JSON.stringify(this.original_group);
        }
    },
    methods: {
        getGroups() {
            this.fetching = true;
            this.$get("agent-groups", {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search,
            })
            .then((response) => {
                this.groups = response.groups.data;
                this.pagination.total = response.groups.total;
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.fetching = false;
            });
        },

        openCreateModal() {
            this.editing_group = {
                title: "",
                description: "",
                agent_ids: [],
                is_default: false,
            };
            this.group_modal = true;
        },

        openEditModal(group) {
            this.editing_group = null;
            this.$get(`agent-groups/${group.id}`)
                .then((response) => {
                    this.freshAgents = response.agents || [];
                    const agentGroup = response.group;
                    this.editing_group = {
                        id: agentGroup.id,
                        title: agentGroup.title,
                        description: agentGroup.description || "",
                        agent_ids: (agentGroup.agent_ids || []).map(id => Number(id)),
                        is_default: !!(agentGroup.settings && agentGroup.settings.is_default),
                    };
                    this.original_group = JSON.parse(JSON.stringify(this.editing_group));
                    this.group_modal = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },

        createOrUpdateGroup() {
            if (!this.editing_group.title) {
                this.$notify({
                    type: 'warning',
                    message: this.$t('Title is required'),
                    position: 'bottom-right',
                });
                return;
            }

            if (!this.editing_group.agent_ids || !this.editing_group.agent_ids.length) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Please add at least one group member.'),
                    position: 'bottom-right',
                });
                return;
            }

            this.saving = true;
            let method = this.$post;
            let route = "agent-groups";
            if (this.editing_group.id) {
                method = this.$put;
                route = `agent-groups/${this.editing_group.id}`;
            }

            method(route, { ...this.editing_group })
            .then((response) => {
                this.$notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });
                this.getGroups();
                this.group_modal = false;
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.saving = false;
            });
        },

        openDeleteModal(group) {
            this.deleting_group = group;
            this.fallback_group_id = null;
            this.delete_modal = true;
        },

        confirmDeleteGroup() {
            this.deleting = true;
            this.$del(`agent-groups/${this.deleting_group.id}`, {
                fallback_group_id: this.fallback_group_id,
            })
            .then((response) => {
                this.$notify({
                    message: response.message,
                    type: "success",
                    position: "bottom-right",
                });
                this.delete_modal = false;
                this.deleting_group = null;
                this.getGroups();
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.deleting = false;
            });
        }
    },

    mounted() {
        this.getGroups();
    }
};
</script>
