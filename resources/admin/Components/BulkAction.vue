<template>
    <div class="fs_bulk_action_wrapper">
        <el-select
            :model-value="selectedAction"
            @update:model-value="updateSelectedAction"
            :placeholder="$t('Bulk Action')"
            class="fs_bulk_action_select"
            :disabled="!hasSelectedItems"
        >
            <el-option
                v-for="action in availableActions"
                :key="action.value"
                :label="action.label"
                :value="action.value"
            />
        </el-select>

        <el-button
            @click="applyBulkAction"
            :disabled="!selectedAction || !hasSelectedItems"
            class="fs_bulk_action_apply"
            type="primary"
        >
            {{ $t('Apply') }}
        </el-button>
    </div>
</template>

<script type="text/babel">
export default {
    name: "BulkAction",
    props: {
        selectedItems: {
            type: Array,
            default: () => []
        },
        selectedAction: {
            type: String,
            default: ""
        },
        actions: {
            type: Array,
            default: () => [
                { value: 'delete', label: 'Delete' }
            ]
        }
    },
    emits: ['apply-action', 'update:selectedAction'],
    data() {
        return {
            localSelectedAction: this.selectedAction
        };
    },
    computed: {
        hasSelectedItems() {
            return this.selectedItems && this.selectedItems.length > 0;
        },
        availableActions() {
            return this.actions.map(action => ({
                ...action,
                label: this.$t(action.label)
            }));
        }
    },
    watch: {
        selectedAction(newValue) {
            this.localSelectedAction = newValue;
        },
        localSelectedAction(newValue) {
            this.$emit('update:selectedAction', newValue);
        }
    },
    methods: {
        updateSelectedAction(value) {
            this.$emit('update:selectedAction', value);
        },
        applyBulkAction() {
            if (this.selectedAction && this.hasSelectedItems) {
                this.$emit('apply-action', {
                    action: this.selectedAction,
                    items: this.selectedItems
                });
            }
        }
    }
};
</script>
