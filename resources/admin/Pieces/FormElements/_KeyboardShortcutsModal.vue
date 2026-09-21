<template>
    <el-dialog
        :title="modalTitle"
        :model-value="modelValue"
        width="560px"
        class="fs_dialog fs_keyboard_shortcuts_dialog"
        append-to-body
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div class="fs_keyboard_shortcuts_content">
            <el-tabs v-model="activeTab" class="fs_customer_management_tabs">
                <el-tab-pane :label="macLabel" name="mac">
                    <el-table :data="macShortcuts" size="small" class="fs_shortcuts_table">
                        <el-table-column :label="actionLabel" prop="action" min-width="200" />
                        <el-table-column :label="shortcutLabel" prop="shortcut" width="220" />
                    </el-table>
                </el-tab-pane>
                <el-tab-pane :label="winLabel" name="win">
                    <el-table :data="winShortcuts" size="small" class="fs_shortcuts_table">
                        <el-table-column :label="actionLabel" prop="action" min-width="200" />
                        <el-table-column :label="shortcutLabel" prop="shortcut" width="220" />
                    </el-table>
                </el-tab-pane>
            </el-tabs>
        </div>
    </el-dialog>
</template>

<script type="text/babel">
export default {
    name: 'KeyboardShortcutsModal',
    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        shortcutModalData: {
            type: Object,
            default: null
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            activeTab: 'mac'
        };
    },
    computed: {
        modalTitle() {
            return this.shortcutModalData ? this.shortcutModalData.title : 'Keyboard Shortcuts';
        },
        macLabel() {
            return this.shortcutModalData ? this.shortcutModalData.mac_label : 'macOS';
        },
        winLabel() {
            return this.shortcutModalData ? this.shortcutModalData.win_label : 'Windows';
        },
        actionLabel() {
            return this.shortcutModalData ? this.shortcutModalData.action_label : 'Action';
        },
        shortcutLabel() {
            return this.shortcutModalData ? this.shortcutModalData.shortcut_label : 'Shortcut';
        },
        macShortcuts() {
            return this.shortcutModalData ? this.shortcutModalData.mac_shortcuts : [];
        },
        winShortcuts() {
            return this.shortcutModalData ? this.shortcutModalData.win_shortcuts : [];
        }
    }
};
</script>
