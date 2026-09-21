<template>
    <div class="fs_filter_container">
        <div class="fs_filter_left">
            <div class="fs_status_filter">
                <div class="fs_button_groups">
                    <button
                        v-for="status in statusOptions"
                        :key="status.value"
                        :class="['status-btn', { active: localStatusFilter === status.value }]"
                        @click="updateStatusFilter(status.value)"
                        :disabled="appVars.customer_status === 'inactive'"
                    >
                        {{ status.label }}
                    </button>
                </div>
            </div>
            <div class="fs_product_filter_head">
                <div v-if="appVars.support_products && appVars.support_products.length" class="fs_product_filter">
                    <el-select
                        v-model="filters.product_id"
                        :placeholder="$t('All Products')"
                        clearable
                        filterable
                        @change="fetchTickets"
                        style="width: 180px"
                        :disabled="appVars.customer_status === 'inactive'"
                    >
                        <el-option
                            v-for="product in appVars.support_products"
                            :key="product.id"
                            :value="product.id"
                            :label="product.title"
                        />
                    </el-select>
                </div>
                <div class="fs_sorting">
                    <el-popover
                        trigger="click"
                        placement="bottom-start"
                        width="240"
                        popper-class="fs_popover"
                        :disabled="appVars.customer_status === 'inactive'"
                    >
                        <div class="fs_popover_item">
                            <label class="fs_popover_title">{{ $t('Sort By') }}</label>
                            <div class="fs_radio_blocks">
                                <el-radio-group
                                    @change="fetchTickets"
                                    v-model="sorting.sort_by"
                                    :disabled="appVars.customer_status === 'inactive'"
                                >
                                    <el-radio v-for="column in sortingColumns" :key="column.value"
                                              :value="column.value">
                                        {{ column.label }}
                                    </el-radio>
                                </el-radio-group>
                            </div>
                            <hr/>
                            <el-radio-group
                                class="fs_switch_button"
                                size="small"
                                v-model="sorting.sort_type"
                                :disabled="appVars.customer_status === 'inactive'"
                            >
                                <el-radio-button value="asc">{{ $t('Ascending') }}</el-radio-button>
                                <el-radio-button value="desc">{{ $t('Descending') }}</el-radio-button>
                            </el-radio-group>
                            <div class="fs_sorting_button">
                                <el-button @click="fetchTickets()" type="primary"
                                           :disabled="appVars.customer_status === 'inactive'">
                                    {{ $t('Apply') }}
                                </el-button>
                            </div>
                        </div>
                        <template #reference>
                            <span>
                                <el-tooltip effect="dark" :content="$t('Sort')" placement="top">
                                    <el-button :disabled="appVars.customer_status === 'inactive'">
                                        <svg width="20" height="20" viewBox="0 0 20 20"
                                             fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 4V13H18.25L15.25 16.75L12.25 13H14.5V4H16ZM10 14.5V16H3.25V14.5H10ZM11.5 9.25V10.75H3.25V9.25H11.5ZM11.5 4V5.5H3.25V4H11.5Z"
                                                fill="#99A0AE"
                                            />
                                        </svg>
                                    </el-button>
                                </el-tooltip>
                            </span>
                        </template>
                    </el-popover>
                </div>
            </div>
        </div>

        <div class="fs_filter_right">
            <div class="fs_search_filter">
                <el-input
                    v-model="localSearchQuery"
                    :placeholder="$t('Search') + '...'"
                    clearable
                    @keyup.enter="fetchTickets"
                    @input="onSearchInput"
                    class="search-input"
                    :disabled="appVars.customer_status === 'inactive'"
                >
                    <template #prefix>
                        <svg class="search-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                stroke="currentColor"
                                stroke-width="2"
                                fill="none"
                            />
                        </svg>
                    </template>
                </el-input>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'TicketFilters',
    props: {
        filters: {type: Object, required: true},
        sorting: {type: Object, required: true},
        sortingColumns: {type: Array, required: true},
        search: {type: String, default: ''},
        statusFilter: {type: String, default: 'all'},
        appVars: {type: Object, required: true}
    },
    data() {
        return {
            localSearchQuery: this.search,
            localStatusFilter: this.statusFilter
        };
    },
    computed: {
        statusOptions() {
            return [
                {label: this.$t('All'), value: 'all'},
                {label: this.$t('Open'), value: 'open'},
                {label: this.$t('Closed'), value: 'closed'}
            ];
        }
    },
    methods: {
        updateStatusFilter(status) {
            this.localStatusFilter = status;
            this.$emit('update:statusFilter', status);
            this.fetchTickets();
        },
        fetchTickets() {
            this.$emit('fetch-tickets', {
                search: this.localSearchQuery,
                statusFilter: this.localStatusFilter
            });
        },
        onSearchInput() {
            if (!this.localSearchQuery) {
                this.fetchTickets();
            }
        }
    },
    watch: {
        localSearchQuery(newVal) {
            this.$emit('update:search', newVal);
        }
    }
};
</script>
