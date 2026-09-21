<template>
    <div class="fs_custom_filter_container">
        <el-tooltip effect="dark" :content="$t('Filter')" placement="top" trigger="hover">
            <span>
                <el-popover
                    placement="bottom-start"
                    width="600"
                    v-model:visible="filterPopoverVisible"
                    trigger="manual"
                    popper-class="fs_filter_dropdown"
                >
                    <div class="fs_filter_panels">
                        <!-- Left Panel - Filter Categories (shown when no category selected) -->
                        <div class="fs_filter_left_panel" v-if="!selectedCategory">
                            <div class="fs_filter_categories">
                                <div
                                    v-for="category in filteredCategories"
                                    :key="category.value"
                                    @click="selectCategory(category)"
                                    class="fs_filter_category"
                                >
                                    <IconPack :iconKey="category.icon" :width="20" :height="20" class="fs_category_icon" />
                                    {{ category.label }}
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel - Filter Options (shown when category selected) -->
                        <div class="fs_filter_right_panel" v-if="selectedCategory">
                            <div class="fs_filter_header">
                                <el-button
                                    @click="goBackToCategories"
                                    text
                                    class="fs_back_button"
                                >
                                    <IconPack iconKey="backIcon" :width="18" :height="18" />
                                </el-button>
                                <span class="fs_category_title">{{ selectedCategory.label }}</span>
                            </div>
                            <div class="fs_filter_search fs_text_input" v-if="selectedCategory.options.length > 6">
                                <el-input
                                    v-model="optionSearchQuery"
                                    :placeholder="$t('Search') + '...'"
                                    clearable
                                    size="small"
                                />
                            </div>
                            <div class="fs_filter_options">
                                <!-- Multi-select options -->
                                <template v-if="selectedCategory.isMultiple">
                                    <div
                                        v-for="option in filteredOptions"
                                        :key="option.value"
                                        @click="toggleMultiOption(option)"
                                        class="fs_filter_option"
                                    >
                                        <el-checkbox
                                            :model-value="isOptionSelected(option)"
                                            class="fs_option_checkbox"
                                        />
                                        {{ option.label }}
                                    </div>
                                </template>

                                <!-- Single-select options -->
                                <template v-else>
                                    <div
                                        v-for="option in filteredOptions"
                                        :key="option.value"
                                        @click="selectSingleOption(option)"
                                        class="fs_filter_option"
                                    >
                                        <el-radio
                                            :model-value="isSingleOptionSelected(option)"
                                            class="fs_option_radio"
                                        />
                                        {{ option.label }}
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <template #reference>
                        <el-button
                            @click="filterPopoverVisible = !filterPopoverVisible"
                            class="fs_outline_btn"
                            :disabled="disabled"
                        >
                            <IconPack iconKey="filter" :width="20" :height="20" />
                        </el-button>
                    </template>
                </el-popover>
            </span>
        </el-tooltip>

    </div>
</template>

<script type="text/babel">
import IconPack from "@/admin/Components/IconPack.vue";

export default {
    name: 'TicketFilters',
    components: { IconPack },
    props: {
        disabled: {
            type: Boolean,
            default: false
        }
    },
    emits: ['apply-filter'],
    data() {
        return {
            filterPopoverVisible: false,
            selectedCategory: null,
            selectedOptions: {},
            optionSearchQuery: ''
        };
    },
    computed: {
        filterCategories() {
            return [
                {
                    value: 'mailbox_id',
                    label: this.$t('Inbox'),
                    icon: 'filtersBusinessBox',
                    isMultiple: true,
                    options: (this.appVars?.mailboxes || []).map(mailbox => ({
                        value: mailbox.id,
                        label: mailbox.name,
                        filterType: 'mailbox_id',
                        filterValue: mailbox.id
                    }))
                },
                {
                    value: 'product_id',
                    label: this.$t('Product'),
                    icon: 'filtersProduct',
                    isMultiple: true,
                    options: (this.appVars?.support_products || []).map(product => ({
                        value: product.id,
                        label: product.title,
                        filterType: 'product_id',
                        filterValue: product.id
                    }))
                },
                {
                    value: 'agent_id',
                    label: this.$t('Support Staff'),
                    icon: 'filtersAgentIcon',
                    isMultiple: true,
                    options: [
                        {
                            value: 'unassigned',
                            label: this.$t('Un-Assigned'),
                            filterType: 'agent_id',
                            filterValue: 'unassigned'
                        },
                        ...(this.appVars?.support_agents || []).map(agent => ({
                            value: agent.id,
                            label: agent.full_name,
                            filterType: 'agent_id',
                            filterValue: agent.id
                        }))
                    ]
                },
                {
                    value: 'agent_group',
                    label: this.$t('Agent Group'),
                    icon: 'agentGroupIcon',
                    isMultiple: true,
                    options: (this.appVars?.agent_groups || []).map(group => ({
                        value: group.id,
                        label: group.title,
                        filterType: 'agent_group',
                        filterValue: group.id
                    }))
                },
                {
                    value: 'priority',
                    label: this.$t('Admin Priority'),
                    icon: 'filtersAdminPriority',
                    isMultiple: true,
                    options: Object.entries(this.appVars?.admin_priorities || {}).map(([key, label]) => ({
                        value: key,
                        label: label,
                        filterType: 'priority',
                        filterValue: key
                    }))
                },
                {
                    value: 'client_priority',
                    label: this.$t('Customer Priority'),
                    icon: 'filtersCustomerPriority',
                    isMultiple: true,
                    options: Object.entries(this.appVars?.client_priorities || {}).map(([key, label]) => ({
                        value: key,
                        label: label,
                        filterType: 'client_priority',
                        filterValue: key
                    }))
                },
                {
                    value: 'ticket_tags',
                    label: this.$t('Tags'),
                    icon: 'filtersTagIcon',
                    isMultiple: true,
                    options: (this.appVars?.ticket_tags || []).map(tag => ({
                        value: tag.id,
                        label: tag.title,
                        filterType: 'ticket_tags',
                        filterValue: tag.id
                    }))
                }
            ];
        },
        filteredCategories() {
            return this.filterCategories;
        },
        filteredOptions() {
            if (!this.selectedCategory) return [];
            const options = this.selectedCategory.options;
            const query = this.optionSearchQuery.trim().toLowerCase();
            if (!query) return options;
            return options.filter(option => option.label.toLowerCase().includes(query));
        }
    },
    methods: {
        selectCategory(category) {
            this.selectedCategory = category;
            this.optionSearchQuery = '';
        },
        goBackToCategories() {
            this.selectedCategory = null;
            this.optionSearchQuery = '';
        },
        toggleMultiOption(option) {
            const key = this.selectedCategory.value;
            if (!this.selectedOptions[key]) {
                this.selectedOptions[key] = [];
            }

            const index = this.selectedOptions[key].findIndex(item => item.value === option.value);
            if (index > -1) {
                this.selectedOptions[key].splice(index, 1);
            } else {
                this.selectedOptions[key].push(option);
            }

            const filterValues = this.selectedOptions[key].map(item => item.filterValue);
            this.$emit('apply-filter', option.filterType, filterValues);
        },
        selectSingleOption(option) {
            const key = this.selectedCategory.value;
            this.selectedOptions[key] = [option];
            this.$emit('apply-filter', option.filterType, option.filterValue);
            this.filterPopoverVisible = false;
            this.selectedCategory = null;
        },
        isOptionSelected(option) {
            const key = this.selectedCategory.value;
            if (!this.selectedOptions[key]) return false;
            return this.selectedOptions[key].some(item => item.value === option.value);
        },
        isSingleOptionSelected(option) {
            const key = this.selectedCategory.value;
            return this.selectedOptions[key]?.[0]?.value === option.value || false;
        },
        clearAllSelections() {
            this.selectedOptions = {};
        },
        clearFilterSelection(filterKey) {
            if (this.selectedOptions[filterKey]) {
                delete this.selectedOptions[filterKey];
            }
        },
        updateFilterSelection(filterKey, filterValues) {
            // Update the selected options based on the filter values
            const category = this.filterCategories.find(cat => cat.value === filterKey);
            if (!category) return;

            if (Array.isArray(filterValues) && filterValues.length > 0) {
                this.selectedOptions[filterKey] = filterValues.map(value => {
                    const option = category.options.find(opt => opt.value == value);
                    return option || { value, label: value, filterType: filterKey, filterValue: value };
                });
            } else {
                delete this.selectedOptions[filterKey];
            }
        }
    }
};
</script>

