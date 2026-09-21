<template>
    <div v-if="currentTickets && !isMobile" class="fs_tickets_tiny_nav" :class="{ 'is-collapsed': isCollapsed }">
        <!-- Ticket List with Collapse Transition -->
        <el-collapse-transition>
            <div v-show="!isCollapsed" class="fs_ticket_nav">
                <router-link
                    v-for="ticket in currentTickets"
                    :key="ticket.id"
                    :to="{ name: 'view_ticket', params: { ticket_id: ticket.id } }"
                    class="fs_ticket_card"
                >
                    <img
                        v-if="ticket.customer"
                        :title="ticket.customer.email + ' - ' + ticket.customer.full_name"
                        :alt="ticket.customer.full_name"
                        class="fs_ticket_avatar"
                        :src="ticket.customer.photo"
                    />
                    <div class="fs_ticket_content">
                        <div class="fs_ticket_title_row">
                            <p class="fs_ticket_title_text">{{ ticket.title }}</p>
                        </div>
                        <div class="fs_ticket_meta_row">
                            <p class="fs_ticket_excerpt">{{ getExcerpt(ticket) }}</p>
                                <p class="fs_ticket_time" :data-ticket-time="ticket.updated_at || ticket.created_at">{{ $timeDiff(ticket.updated_at || ticket.created_at) }}</p>
                        </div>
                    </div>
                </router-link>
            </div>
        </el-collapse-transition>
    </div>
    <div v-else class="fs_tk_inner_sidebar" :class="{ 'is-collapsed': isCollapsed, 'has-view-route': hasViewRoute }">
        <div class="fs_sidebar_header" v-if="!isViewingTicket">
            <h3 class="fs_sidebar_title">{{ $t('Ticket Filters') }}</h3>
            <el-button
                class="fs_tickets_collapse_btn"
                :class="{ 'collapsed': isCollapsed }"
                @click="$emit('toggle-collapse')"
                :title="$t(isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                text
            >
                <img :src="appVars.asset_url + 'images/arrowLeftLine.svg'" alt="">
            </el-button>
        </div>
        <div class="fs_sidebar_items">
            <ul>
            <li v-for="(route, index) in dynamicRoutes" :key="`${route.name}-${index}`">
                    <router-link
                        :class="{router_not_exactly_matched: isCurrentRouteMatched(route.name)}"
                        :to="route.to"
                        @click="route.onClick"
                    >
                        <el-tooltip
                            v-if="isCollapsed"
                            :content="route.label"
                            placement="right"
                            :enterable="true"
                            :popper-class="route.name"
                        >
                            <IconPack
                                :icon-key="route.icon"
                                class="fs_sidebar_icons"
                                :width="20"
                                :height="20"
                                :fill="isRouteActive(route.name) ? 'var(--fs-text-primary)' : 'var(--fs-text-secondary)'"
                            />
                        </el-tooltip>
                        <IconPack
                            v-else
                            :icon-key="route.icon"
                            class="fs_sidebar_icons"
                            :width="20"
                            :height="20"
                            :fill="isRouteActive(route.name) ? 'var(--fs-text-primary)' : 'var(--fs-text-secondary)'"
                        />
                        <span v-if="!isCollapsed" class="fs_sidebar_label">{{ route.label }}</span>
                    </router-link>
            </li>
        </ul>
        </div>
    </div>
</template>

<script type="text/babel">

import IconPack from "@/admin/Components/IconPack.vue";
import { mapState } from 'pinia';
import { useTicketListStore } from '@/admin/stores/ticketList.store';

export default {
    name: 'TicketsMenu',
    components: {IconPack},
    props: {
        isCollapsed: {
            type: Boolean,
            default: false
        }
    },
    emits: ['toggle-collapse'],
    data() {
        return {
            isMobile: false,
            dynamicRoutes: [
                {
                    name: 'allTickets',
                    label: this.$t('All Tickets'),
                    to: {name: 'tickets'},
                    icon: 'Tickets',
                    onClick: () => this.saveRoutes(),
                },
                {
                    name: 'myTickets',
                    label: this.$t('My Tickets'),
                    to: {name: 'tickets', query: {agent_id: this.appVars.me.id}},
                    icon: 'User',
                    onClick: () => this.saveRoutes({agent_id: this.appVars.me.id}),
                },
                {
                    name: 'unassignedTickets',
                    label: this.$t('Unassigned'),
                    to: {name: 'tickets', query: {agent_id: 'unassigned'}},
                    icon: 'View',
                    onClick: () => this.saveRoutes({agent_id: 'unassigned'}),
                },
                ...(this.has_pro ? [{
                    name: 'mentionedTickets',
                    label: this.$t('Bookmarks'),
                    to: {name: 'tickets', query: {watcher: 'watcher', agent_id: this.appVars.me.id}},
                    icon: 'CollectionTag',
                    onClick: () => this.saveRoutes({watcher: 'watcher', agent_id: this.appVars.me.id}),
                }] : []),
            ],
        }
    },
    computed: {
        ...mapState(useTicketListStore, { storeTickets: 'tickets' }),
        currentTickets() {
            if (this.$route.name !== 'view_ticket' || !(this.storeTickets && this.storeTickets.length)) {
                return null;
            }

            return this.storeTickets;
        },
        isViewingTicket() {
            return this.$route.name === 'view_ticket';
        },
        hasViewRoute() {
            return this.$route.name && this.$route.name.includes('view');
        },
        isAll() {
            return this.$route.query.agent_id;
        },
        isMine() {
            return !(this.$route.query.watcher !== 'watcher' && this.appVars.me.id === parseInt(this.$route.query.agent_id));
        },
        isUnassigned() {
            return !(this.$route.query.agent_id === 'unassigned' && !this.$route.query.watcher);
        },
        isMentioned() {
            return !(this.$route.query.watcher === 'watcher' && this.appVars.me.id === parseInt(this.$route.query.agent_id));
        }
    },
    methods: {
        getExcerpt(ticket) {
            let text = (ticket.preview_response) ? ticket.preview_response.content : ticket.content;

            if (!text) {
                return '';
            }

            text = text.replace(/<\/?("[^"]*"|'[^']*'|[^>])*(>|$)/g, "");

            return text.substring(0, 100);
        },

        isCurrentRouteMatched(routeName) {
            const routeContacts = {
                allTickets: this.isAll,
                myTickets: this.isMine,
                unassignedTickets: this.isUnassigned,
                mentionedTickets: this.isMentioned,
            };

            if (routeName in routeContacts) {
                return routeContacts[routeName];
            }

            return false;
        },

        isRouteActive(routeName) {
            // Returns true when the route is active (opposite of isCurrentRouteMatched)
            return !this.isCurrentRouteMatched(routeName);
        },

        saveRoutes(currentRouteQuery = {}) {
            this.$saveData("routesData", JSON.stringify(currentRouteQuery));
        },

        handleKeydown(event) {
            const { metaKey, shiftKey, code } = event;

            if (!metaKey || !shiftKey) return;

            const keyActions = {
                KeyA: () => this.$router.push(this.dynamicRoutes[0].to),  // Shortcut for 'All Tickets'
                KeyM: () => this.$router.push(this.dynamicRoutes[1].to),  // Shortcut for 'My Tickets'
                KeyU: () => this.$router.push(this.dynamicRoutes[2].to),  // Shortcut for 'Unassigned'
                KeyB: () => this.$router.push(this.dynamicRoutes[3]?.to),  // Shortcut for 'Bookmarks' (if has_pro)
            };

            const action = keyActions[code];
            if (action) {
                event.preventDefault();
                action();
            }
        },
    },
    mounted() {
        this.isMobile = window.innerWidth < 768;
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.addEventListener('keydown', this.handleKeydown);
        }
    },
    unmounted() {
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.removeEventListener('keydown', this.handleKeydown);
        }
    }
};
</script>
