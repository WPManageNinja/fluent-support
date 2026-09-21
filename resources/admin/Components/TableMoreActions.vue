<template>

    <el-dropdown trigger="click" :hide-on-click="false">
        <span class="fs_table_more_actions_dropdown">
            <div class="fs_action_button_wrapper">
                <el-button
                    class="fs_action_button fs_more_action"
                    v-loading="fetching"
                    text
                    icon="More">
                </el-button>
            </div>
        </span>
        <template #dropdown>
            <el-dropdown-menu class="fs_global_dropdown fs_table_more_actions_dropdown_menu">
                <el-dropdown-item
                    v-if="hasAction('duplicate')"
                    @click="duplicateItem(scope)"
                >
                    <div class="fs_dropdown_item_content">
                        <el-icon class="fs_dropdown_icon">
                            <DocumentCopy />
                        </el-icon>
                        {{ $t('Duplicate') }}
                    </div>
                </el-dropdown-item>
                <el-dropdown-item
                    v-if="hasAction('delete')"
                    class="fs_delete_action"
                    @click="deleteDialogVisible = true"
                >
                    <div class="fs_dropdown_item_content">
                        <el-icon class="fs_dropdown_icon">
                            <Delete />
                        </el-icon>
                        {{ $t('Delete') }}
                    </div>
                </el-dropdown-item>
            </el-dropdown-menu>
        </template>
    </el-dropdown>

    <el-dialog
        v-model="deleteDialogVisible"
        :title="$t('Confirm Delete')"
        width="400"
        class="fs_dialog"
        :append-to-body="true"
    >
        <div class="fs_dialog_warning_content">
            <i class="el-icon el-message-box__status el-message-box-icon--warning"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896 448 448 0 0 1 0-896m0 192a58.432 58.432 0 0 0-58.24 63.744l23.36 256.384a35.072 35.072 0 0 0 69.76 0l23.296-256.384A58.432 58.432 0 0 0 512 256m0 512a51.2 51.2 0 1 0 0-102.4 51.2 51.2 0 0 0 0 102.4"></path></svg></i>
            <p>{{ deleteWarning }}</p>
        </div>
        <template #footer>
            <div class="fs_popconfirm_actions">
                <el-button class="fs_outline_btn" size="small" @click="deleteDialogVisible = false">{{ $t('Cancel') }}</el-button>
                <el-button
                    class="fs_filled_btn"
                    size="small"
                    @click="confirmDelete"
                >
                    {{ $t('Delete') }}
                </el-button>
            </div>
        </template>
    </el-dialog>

</template>

<script type="text/babel">
import { Delete, DocumentCopy } from '@element-plus/icons-vue';

export default {
    name: "TableMoreActions",
    components: { Delete, DocumentCopy },
    emits: ['delete', 'duplicate'],
    props: {
        scope: {
            type: Object,
            required: true
        },
        fetching: {
            type: Boolean,
            default: false
        },
        deleteWarning: {
            type: String,
            default: 'Are you sure you want to delete this?'
        },
        actions: {
            type: Array,
            default: () => ['delete']
        }
    },
    data() {
        return {
            deleteDialogVisible: false
        };
    },
    methods: {
        hasAction(action) {
            return this.actions.includes(action);
        },
        deleteTag(row) {
            this.$emit('delete', row);
        },
        confirmDelete() {
            this.deleteDialogVisible = false;
            this.$emit('delete', this.scope);
        },
        duplicateItem(row) {
            this.$emit('duplicate', row);
        }
    },
};
</script>
