<template>
    <div class="fs_ticket_wrapper">
        <div class="fs_tickets_container">
            <div class="fs_tickets_header">
                <label> {{ $t('All Tickets') }}</label>

                <div class="fs_tickets_actions">
                    <el-button
                        :disabled="appVars.customer_status === 'inactive'"
                        class="fs_create_ticket_btn"
                        @click="$router.push({ name: 'create_ticket' })"
                    >
                        {{ '+ ' + $t('create_ticket_cta') }}
                    </el-button>

                    <el-dropdown v-if="appVars.show_logout" class="fs_actions_dropdown" trigger="click">
                        <el-button class="fs_actions_dropdown_btn">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M10 3.25C9.38125 3.25 8.875 3.75625 8.875 4.375C8.875 4.99375 9.38125 5.5 10 5.5C10.6187 5.5 11.125 4.99375 11.125 4.375C11.125 3.75625 10.6187 3.25 10 3.25ZM10 14.5C9.38125 14.5 8.875 15.0063 8.875 15.625C8.875 16.2437 9.38125 16.75 10 16.75C10.6187 16.75 11.125 16.2437 11.125 15.625C11.125 15.0063 10.6187 14.5 10 14.5ZM10 8.875C9.38125 8.875 8.875 9.38125 8.875 10C8.875 10.6188 9.38125 11.125 10 11.125C10.6187 11.125 11.125 10.6188 11.125 10C11.125 9.38125 10.6187 8.875 10 8.875Z"
                                    fill="#525866"/>
                            </svg>

                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_dropdown_menu">
                                <el-dropdown-item class="fs_dropdown_item" v-if="appVars.show_logout" @click="logout">
                                    <div class="fs_logout_item">
                                        <svg class="fs_logout_icon" width="20" height="20" viewBox="0 0 20 20"
                                             fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M4.75 17.5C4.55109 17.5 4.36032 17.421 4.21967 17.2803C4.07902 17.1397 4 16.9489 4 16.75V3.25C4 3.05109 4.07902 2.86032 4.21967 2.71967C4.36032 2.57902 4.55109 2.5 4.75 2.5H15.25C15.4489 2.5 15.6397 2.57902 15.7803 2.71967C15.921 2.86032 16 3.05109 16 3.25V5.5H14.5V4H5.5V16H14.5V14.5H16V16.75C16 16.9489 15.921 17.1397 15.7803 17.2803C15.6397 17.421 15.4489 17.5 15.25 17.5H4.75ZM14.5 13V10.75H9.25V9.25H14.5V7L18.25 10L14.5 13Z"
                                                fill="#525866"/>
                                        </svg>
                                        <span>{{ $t('Log Out') }}</span>
                                    </div>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </div>

            <div class="fs_filters_section">
                <TicketFilters
                    :filters="filters"
                    :sorting="sorting"
                    :sortingColumns="sortingColumns"
                    @update:search.sync="search = $event"
                    @update:statusFilter="filterType = $event"
                    :appVars="appVars"
                    @fetch-tickets="fetchTickets"
                />
            </div>

            <div v-if="appVars.customer_status === 'inactive'" class="fs_inactive_message_container">
                <div class="fs_inactive_message">
                    <div class="fs_inactive">
                        <svg width="148" height="148" viewBox="0 0 148 148" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <rect width="148" height="148" rx="74" fill="#F2F5F8"/>
                            <path
                                d="M113.01 64C115.459 64 117.444 65.9887 117.444 68.442V102.07C117.444 104.523 115.459 106.512 113.01 106.512H108.127C107.31 106.512 106.648 107.175 106.648 107.993V115.767C106.648 117.086 105.056 117.747 104.125 116.814L94.2745 106.946C93.9973 106.668 93.6213 106.512 93.2293 106.512H59.4342C56.9853 106.512 55 104.523 55 102.07V68.442C55 65.9887 56.9852 64 59.4342 64H113.01Z"
                                fill="#CACFD8" stroke="#99A0AE" stroke-linecap="round" stroke-linejoin="round"/>
                            <path
                                d="M34.4342 46C31.9853 46 30 47.9887 30 50.442V90.4078C30 92.861 31.9853 94.8497 34.4342 94.8497H40.9276C41.7439 94.8497 42.4056 95.5127 42.4056 96.3304V106.016C42.4056 107.335 43.9977 107.996 44.9289 107.063L56.688 95.2834C56.9652 95.0057 57.3411 94.8497 57.7332 94.8497H97.3194C99.7684 94.8497 101.754 92.861 101.754 90.4077V50.442C101.754 47.9887 99.7684 46 97.3194 46H34.4342Z"
                                fill="white" stroke="#99A0AE" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M45.1162 57.9929L51.8613 64.7505L45.1162 71.5075" stroke="#99A0AE"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M86.6377 57.9929L79.8926 64.7505L86.6377 71.5075" stroke="#99A0AE"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M77.2182 82.3687H54.5342" stroke="#99A0AE" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="fs_inactive_text">
                        {{ $t('customer_inactive_message') }}
                    </div>
                </div>
            </div>

            <div v-else class="fs_tickets_table">
                <el-table
                    :data="tickets"
                    v-loading="loading"
                    style="width: 100%"
                    :row-class-name="'fs_ticket_row'"
                    :empty-text="$t('No tickets found')"
                >
                    <el-table-column prop="title" :label="$t('Conversation')" class-name="conversation-cell"
                                     min-width="50%">
                        <template #default="{ row }">
                            <router-link class="fs_ticket_conversation"
                                         :to="{ name: 'view_ticket', params: { ticket_id: row.serial_number || row.id }}">
                                <div class="fs_ticket_title">
                                    <strong>{{ row.title }}</strong>
                                    <span v-show="parseInt(row?.response_count) > 0" class="fs_response_count">
                                      {{ row.response_count }}
                                    </span>
                                </div>
                                <p class="fs_ticket_preview">{{ getExcerpt(row) }}</p>
                            </router-link>
                        </template>
                    </el-table-column>
                    <el-table-column prop="human_date" :label="$t('Date')" class-name="date-cell" min-width="25%"/>
                    <el-table-column prop="status" :label="$t('Status')" class-name="fs_status_cell" min-width="25%">
                        <template #default="{ row }">
                            <span :class="['fs_status_badge', getStatusClass(row.status)]">
                                <span class="fs_status_dot"></span>
                                {{ appVars.ticket_statuses[row.status] || row.status }}
                            </span>
                        </template>
                    </el-table-column>
                </el-table>
            </div>

            <div
                style="padding-bottom: 20px"
                class="fs_pagination_section"
                v-if="tickets.length"
            >
                <Pagination
                    @fetch="fetchTickets()"
                    :pagination="pagination"
                />
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import debounce from 'lodash/debounce'
import TicketFilters from "./_TicketFilter.vue";
import Pagination from "./pieces/Pagination";

export default {
    name: 'TicketsList',
    components: {
        Pagination,
        TicketFilters
    },
    data() {
        return {
            inactiveMessage: '',
            tickets: [],
            filterType: 'all',
            loading: true,
            search: '',
            filters: {
                product_id: ''
            },
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0
            },
            sortingColumns: [
                {
                    label: this.$t('Ticket ID'),
                    value: 'id'
                },
                {
                    label: this.$t('Title'),
                    value: 'title'
                },
                {
                    label: this.$t('Created at'),
                    value: 'created_at'
                },
            ],
            sorting: {
                sort_by: 'id',
                sort_type: 'desc'
            }
        }
    },
    computed: {
        totalPages() {
            return Math.ceil(this.pagination.total / this.pagination.per_page)
        }
    },
    methods: {
        fetchTickets() {
            this.loading = true
            this.$get('tickets', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                filter_type: this.filterType,
                search: this.search,
                filters: this.filters,
                sorting: this.sorting
            })
                .then(response => {
                    this.tickets = response.tickets.data
                    this.pagination.total = response.tickets.total;
                })
                .catch(errors => {
                    this.$handleErrors(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getStatusClass(status) {
            return status.toLowerCase()
        },

        getExcerpt(row) {
            let text = (row.preview_response) ? row.preview_response.content : row.content;
            if (!text) {
                return '';
            }
            return text.replace(/<\/?("[^"]*"|'[^']*'|[^>])*(>|$)/g, "");
        },

        handlePageChange(page) {
            this.pagination.current_page = page
            this.fetchTickets()
        },

        updateSearchQuery(search) {
            this.search = search;
        },
        logout() {
            this.$post('logout')
                .then(response => {
                    window.location.reload()
                })
                .catch((errors) => {
                    console.log(errors);
                });
        },
        debouncedSearch: debounce(function () {
            this.pagination.current_page = 1
            this.fetchTickets()
        }, 300)
    },
    watch: {
        filterType: {
            handler() {
                this.pagination.current_page = 1
                this.fetchTickets()
            }
        },
        search: {
            handler() {
                this.debouncedSearch()
            }
        },
        'pagination.per_page': {
            handler() {
                this.fetchTickets()
            }
        }
    },
    mounted() {
        if (this.appVars.customer_status !== 'inactive') {
            this.fetchTickets();
        }
    }
}
</script>
