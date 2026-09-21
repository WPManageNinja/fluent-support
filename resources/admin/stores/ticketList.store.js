import { defineStore } from 'pinia';
import ticketListService from '@/admin/services/ticketListService';

// Cache duration: 5 minutes (in-memory only)
const CACHE_DURATION = 5 * 60 * 1000;

/**
 * Generate a stable hash from filter state for cache key comparison
 */
function hashFilters(data) {
    try {
        return JSON.stringify({
            filters: data.filter_type !== 'advanced' ? data.filters : null,
            search: data.filter_type !== 'advanced' ? data.search : null,
            filter_type: data.filter_type,
            advanced_filters: data.filter_type === 'advanced' ? data.advanced_filters : null,
            order_by: data.order_by,
            order_type: data.order_type,
            page: data.pagination?.current_page || 1,
            per_page: data.pagination?.per_page || 10,
        });
    } catch (e) {
        return '';
    }
}

export const useTicketListStore = defineStore('ticketList', {
    state: () => ({
        // Entity data
        tickets: [],

        // Pagination
        pagination: {
            current_page: 1,
            total: 0,
            per_page: 10,
        },

        // Status groups
        ticket_statuses_group: {},

        // Filters
        filters: {
            status_type: 'open',
            product_id: [],
            agent_id: [],
            agent_group: [],
            priority: [],
            client_priority: [],
            waiting_for_reply: '',
            ticket_tags: [],
            mailbox_id: [],
            watcher: '',
        },

        // Search
        search: '',
        searchActive: false,

        // Sorting
        order_by: 'last_customer_response',
        order_type: 'ASC',

        // Filter mode
        filter_type: 'simple',
        advanced_filters: [[]],

        // Saved searches
        label_search_id: '',
        label_search_name: '',
        labelSearchList: [],

        // Selections
        ticket_selections: [],

        // Loading flags
        loading: false,
        first_time_loading: true,
        app_ready: false,
        appReady: false,
        doing_bulk: false,

        // Display settings
        fieldVisibility: {
            title: true,
            author: true,
            tags: true,
            source: true,
            description: true,
            response_count: true,
            product: true,
            mailbox: true,
            agent: true,
            status: true,
            client_priority: true,
            waiting_time: true,
        },

        // Grid view column visibility — default shows
        gridFieldVisibility: {
            title: true,
            author: true,
            tags: true,
            source: true,
            description: true,
            response_count: true,
            product: true,
            mailbox: false,
            agent: true,
            status: false,
            client_priority: true,
            waiting_time: true,
        },
        ticketLayout: 'default',
        viewMode: 'list',

        // In-memory cache metadata
        cache_metadata: {
            last_fetch_time: null,
            cache_key_hash: null,
            is_stale: false,
        },
        _fetchId: 0,

    }),

    getters: {
        activeFilters(state) {
            if (!state.filters) return {};

            const active = {};
            if (state.filters.product_id && state.filters.product_id.length > 0) {
                active.product_id = state.filters.product_id;
            }
            if (state.filters.agent_id && state.filters.agent_id.length > 0) {
                active.agent_id = state.filters.agent_id;
            }
            if (state.filters.priority && state.filters.priority.length > 0) {
                active.priority = state.filters.priority;
            }
            if (state.filters.client_priority && state.filters.client_priority.length > 0) {
                active.client_priority = state.filters.client_priority;
            }
            if (state.filters.ticket_tags && state.filters.ticket_tags.length > 0) {
                active.ticket_tags = state.filters.ticket_tags;
            }
            if (state.filters.mailbox_id && state.filters.mailbox_id.length > 0) {
                active.mailbox_id = state.filters.mailbox_id;
            }
            if (state.filters.agent_group && state.filters.agent_group.length > 0) {
                active.agent_group = state.filters.agent_group;
            }
            return active;
        },

        selectAllChecked(state) {
            return state.tickets.length > 0 && state.ticket_selections.length === state.tickets.length;
        },

        isIndeterminate(state) {
            return state.ticket_selections.length > 0 && state.ticket_selections.length < state.tickets.length;
        },

        isCacheValid(state) {
            if (!state.cache_metadata.last_fetch_time || state.cache_metadata.is_stale) {
                return false;
            }
            return (Date.now() - state.cache_metadata.last_fetch_time) < CACHE_DURATION;
        },

        filterHash(state) {
            return hashFilters({
                filters: state.filters,
                search: state.search,
                filter_type: state.filter_type,
                advanced_filters: state.advanced_filters,
                order_by: state.order_by,
                order_type: state.order_type,
                pagination: state.pagination,
            });
        },

        hasCachedDataForCurrentFilters(state) {
            if (!state.cache_metadata.cache_key_hash) return false;
            return state.cache_metadata.cache_key_hash === hashFilters({
                filters: state.filters,
                search: state.search,
                filter_type: state.filter_type,
                advanced_filters: state.advanced_filters,
                order_by: state.order_by,
                order_type: state.order_type,
                pagination: state.pagination,
            });
        },

        shouldShowCache() {
            return this.isCacheValid
                && this.hasCachedDataForCurrentFilters
                && this.tickets.length > 0;
        },
    },

    actions: {
        /**
         * Fetch tickets from API
         * @param {boolean} forceRefresh - Bypass cache check
         * @param {boolean} silent - No loading spinner (background refresh)
         */
        async fetchTickets(forceRefresh = false, silent = false) {
            if (!forceRefresh && this.shouldShowCache) {
                return;
            }

            const fetchId = ++this._fetchId;
            this.ticket_selections = [];

            if (!silent) {
                this.loading = true;
            }

            try {
                const query = this._buildQuery();
                const response = await ticketListService.fetchList(query);

                // Race condition guard: discard stale responses
                if (fetchId !== this._fetchId) return;

                // Page correction: current page is empty but results exist
                if (
                    response.tickets.total &&
                    !response.tickets.from &&
                    this.pagination.current_page > 1
                ) {
                    this.pagination.current_page = 1;
                    return this.fetchTickets(true);
                }

                this.tickets = response.tickets.data;
                this.pagination.total = response.tickets.total;

                // Update cache metadata
                this.cache_metadata.last_fetch_time = Date.now();
                this.cache_metadata.cache_key_hash = this.filterHash;
                this.cache_metadata.is_stale = false;
            } catch (error) {
                throw error;
            } finally {
                if (fetchId === this._fetchId) {
                    this.loading = false;
                    this.first_time_loading = false;
                }
            }
        },

        /**
         * Build query parameters from current store state
         */
        _buildQuery() {
            const query = {
                page: this.pagination.current_page,
                per_page: this.pagination.per_page,
                order_by: this.order_by,
                order_type: this.order_type,
                filter_type: this.filter_type,
            };

            const hasPro = window.fluentSupportAdmin?.has_pro;

            if (this.filter_type === 'advanced' && hasPro) {
                query.advanced_filters = JSON.stringify(this.advanced_filters);
            } else {
                query.filters = this.filters;
                query.search = this.search;

                if (!hasPro) {
                    query.filter_type = 'simple';
                    this.filter_type = 'simple';
                }
            }

            return query;
        },

        /**
         * Mark in-memory cache as stale. Call after any mutation.
         */
        invalidateCache() {
            this.cache_metadata.is_stale = true;
            this.cache_metadata.last_fetch_time = null;
            this.cache_metadata.cache_key_hash = null;
        },

        setStatus(statusKey) {
            this.filters.status_type = statusKey;
        },

        resetFilters(routeQuery) {
            this.filters = {
                status_type: 'open',
                product_id: [],
                agent_id: [],
                agent_group: [],
                priority: [],
                client_priority: [],
                waiting_for_reply: '',
                ticket_tags: [],
                mailbox_id: [],
                watcher: '',
            };
            this.search = '';
            this.order_type = 'ASC';
            this.order_by = 'last_customer_response';
            this.pagination.current_page = 1;

            if (routeQuery && routeQuery.agent_id) {
                this.filters.agent_id = routeQuery.agent_id;
            }
            if (routeQuery && routeQuery.watcher) {
                this.filters.watcher = routeQuery.watcher;
            }
            if (routeQuery && routeQuery.product_id) {
                this.filters.product_id = Array.isArray(routeQuery.product_id) ? routeQuery.product_id : [routeQuery.product_id];
            }
        },

        resetWithOutFetch() {
            this.filters = {
                status_type: 'open',
                product_id: [],
                agent_id: [],
                agent_group: [],
                priority: [],
                client_priority: [],
                waiting_for_reply: '',
                ticket_tags: [],
                mailbox_id: [],
                watcher: '',
            };
            this.search = '';
            this.pagination.current_page = 1;
        },

        clearAllFilters() {
            this.filters = {
                ...this.filters,
                status_type: 'open',
                mailbox_id: [],
                product_id: [],
                agent_id: [],
                agent_group: [],
                priority: [],
                client_priority: [],
                ticket_tags: [],
                waiting_for_reply: '',
                watcher: '',
            };
        },

        resetAdvancedFilters() {
            this.advanced_filters = [[]];
        },

        handleCustomFilter(filterType, filterValue) {
            switch (filterType) {
                case 'status':
                    this.filters.status_type = filterValue;
                    break;
                case 'priority':
                    this.filters.priority = Array.isArray(filterValue) ? filterValue : [filterValue];
                    break;
                case 'client_priority':
                    this.filters.client_priority = Array.isArray(filterValue) ? filterValue : [filterValue];
                    break;
                case 'agent_id':
                    this.filters.agent_id = Array.isArray(filterValue) ? filterValue : [filterValue];
                    break;
                case 'product_id':
                    this.filters.product_id = Array.isArray(filterValue) ? filterValue : [filterValue];
                    break;
                case 'mailbox_id':
                    this.filters.mailbox_id = Array.isArray(filterValue) ? filterValue : [filterValue];
                    break;
                case 'ticket_tags':
                    this.filters.ticket_tags = Array.isArray(filterValue) ? filterValue.map(v => parseInt(v)) : [parseInt(filterValue)];
                    break;
                case 'agent_group':
                    this.filters.agent_group = Array.isArray(filterValue) ? filterValue.map(v => parseInt(v)) : [parseInt(filterValue)];
                    break;
            }
        },

        handleSelectAll(checked) {
            if (checked) {
                this.ticket_selections = this.tickets.map(ticket => ticket.id);
            } else {
                this.ticket_selections = [];
            }
        },

        handleTicketSelection(ticketId) {
            const index = this.ticket_selections.indexOf(ticketId);
            if (index > -1) {
                this.ticket_selections.splice(index, 1);
            } else {
                this.ticket_selections.push(ticketId);
            }
        },

        deleteSelected() {
            if (!this.ticket_selections.length) return;
            this.doing_bulk = true;
            return ticketListService.bulkDelete({
                ticket_ids: this.ticket_selections,
            }).then(() => {
                this.invalidateCache();
            }).always(() => {
                this.doing_bulk = false;
            });
        },

        closeSelected() {
            this.doing_bulk = true;
            return ticketListService.bulkClose({
                ticket_ids: this.ticket_selections,
                bulk_action: 'close_tickets',
            }).then(() => {
                this.invalidateCache();
            }).always(() => {
                this.doing_bulk = false;
            });
        },

        handleSaveSearch(query) {
            return ticketListService.saveLabelSearch({ query });
        },

        fetchLabelSearches() {
            return ticketListService.fetchLabelSearches()
                .then((response) => {
                    this.labelSearchList = response;
                });
        },

        handleLabelSearchDelete(id) {
            return ticketListService.deleteLabelSearch(id)
                .then((response) => {
                    if (response) {
                        if (this.label_search_id === id) {
                            this.label_search_id = null;
                        }
                        this.labelSearchList = this.labelSearchList.filter(item => item.id !== id);
                    }
                    return response;
                });
        },

        handleAdvanceSearch(item) {
            this.advanced_filters = JSON.parse(item.advanced_filters);
            this.filter_type = item.filter_type;
            this.label_search_name = '';
            this.label_search_id = '';
        },

        handleLabelSearchEdit(item) {
            this.advanced_filters = JSON.parse(item.advanced_filters);
            this.filter_type = item.filter_type;
            this.label_search_name = item.label_search_name;
            this.label_search_id = item.id;
        },

    },
});
