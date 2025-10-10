<template>
    <div class="fs_tk_filters">
        <div class="fs_tk_filter">
            <label>{{$t('Status')}}</label>
            <el-radio-group @change="fetchTickets()" v-model="filters.status_type">
                <el-radio-button v-for="(status, statusKey) in ticket_statuses_group" :value=statusKey>
                    {{translate(ucFirst(statusKey))}}
                </el-radio-button>
            </el-radio-group>
        </div>
        <div v-if="appVars.mailboxes.length" class="fs_tk_filter">
            <label>{{$t('Inbox')}}</label>
            <el-select clearable filterable  @change="fetchTickets()" v-model="filters.mailbox_id"
                       :placeholder="$t('All Inbox')">
                <el-option v-for="mailbox in appVars.mailboxes" :key="mailbox.id" :value="mailbox.id"
                           :label="mailbox.name"></el-option>
            </el-select>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Product')}}</label>
            <el-select 
                @change="fetchTickets()" 
                v-model="filters.product_id"
                :placeholder="$t('All Products')"
                multiple
                clearable
                :popper-append-to-body=true
                collapse-tags>
                <el-option v-for="product in appVars.support_products" :key="product.id" :value="product.id" :label="product.title"></el-option>
            </el-select>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Support Staff')}}</label>
            <el-select clearable filterable @change="fetchTickets()" v-model="filters.agent_id"
                       :placeholder="$t('All Support Staff')">
                <el-option value="unassigned" :label="$t('Un-Assigned')"></el-option>
                <el-option v-for="agent in appVars.support_agents" :key="agent.id" :value="agent.id"
                           :label="agent.full_name"></el-option>
            </el-select>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Priority (Admin)')}}</label>
            <el-select clearable @change="fetchTickets()" v-model="filters.priority"
                       :placeholder="$t('All')">
                <el-option v-for="(priority, priorityKey) in appVars.admin_priorities" :key="priorityKey"
                           :value="priorityKey" :label="priority"></el-option>
            </el-select>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Priority (Customer)')}}</label>
            <el-select clearable @change="fetchTickets()" v-model="filters.client_priority"
                       :placeholder="$t('All')">
                <el-option v-for="(priority, priorityKey) in appVars.client_priorities" :key="priorityKey"
                           :value="priorityKey" :label="priority"></el-option>
            </el-select>
        </div>
        <div v-if="appVars.ticket_tags.length" class="fs_tk_filter">
            <label>{{$t('Tags')}}</label>
            <el-select
                @change="fetchTickets()"
                v-model="filters.ticket_tags"
                :placeholder="$t('Filter By Tags')"
                multiple
                clearable
                :popper-append-to-body=true
                collapse-tags
            >
                <el-option v-for="tag in appVars.ticket_tags"
                           :key="tag.id"
                           :label="tag.title"
                           :value="tag.id"></el-option>
            </el-select>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Search')}}</label>
            <el-input @keyup.enter="fetchTickets()" clearable @clear="fetchTickets()"
                      :placeholder="$t('Please input')" v-model="searchInput">
                <template #append>
                    <el-button @click="fetchTickets()" icon="Search"></el-button>
                </template>
            </el-input>
        </div>
        <div class="fs_tk_filter">
            <label>{{$t('Waiting for Reply')}}</label>
            <el-switch @change="maybeChangeWaitingReply()" active-value="yes" :inactive-value="false"
                       v-model="filters.waiting_for_reply"></el-switch>
        </div>
        <div class="fs_tk_filter">
            <el-button :type="(has_active_filter) ? 'danger' : 'default'" @click="resetFilters()">
                {{$t('Reset Filters')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import {useFluentHelper, useNotify} from "@/admin/Composable/FluentFrameworkHelper";
import {computed, onMounted, onUnmounted, reactive, toRefs, watch} from "vue";

export default {
    name: 'TicketFilters',
    emits: ['fetchTickets', 'searchChange', 'resetFilters'],
    props: ['filters', 'resetFilters', 'search'],
    setup(props, context) {
        const emit = context.emit;
        const { appVars, translate } = useFluentHelper();
        const {notify} = useNotify();
        const state = reactive({
            filterColumns: {
                id: translate('Ticket ID'),
                product_id: translate('Product ID'),
                priority: translate('Priority'),
                client_priority: translate('Client Priority'),
                title: translate('Title'),
                last_agent_response: translate('Last Agent Response'),
                last_customer_response: translate('Last Customer Response'),
                waiting_since: translate('Waiting Time'),
                response_count: translate('Response Count'),
                created_at: translate('Created At')
            },
            searchInput: computed({
                get: () => props.search,
                set: (value) => emit('searchChange', value)
            }),
            ticket_statuses_group: appVars.ticket_statuses_group,
            statusOrder: Object.keys(appVars.ticket_statuses_group)
        });

        const fetchTickets = () => {
            emit('fetchTickets');
        };

        const maybeChangeWaitingReply = () => {
            if (props.filters.waiting_for_reply == 'yes') {
                if (props.filters.status_type == 'new' || props.filters.status_type == 'active') {
                    props.filters.status_type =  props.filters.status_type;
                }
                else{
                    props.filters.status_type = 'open';
                }
            }
            fetchTickets();
        }

        const has_active_filter = computed(() => {
            const f = props.filters;
            return f.status_type !== 'open' || f.product_id || f.mailbox_id || f.agent_id || f.priority || f.client_priority || f.waiting_for_reply || state.searchInput || f.ticket_tags?.length || f.filter_type;
        });

        const handleKeydown = (event) => {
            const { metaKey, shiftKey, altKey, code } = event;
            if (!metaKey) return;

            const shiftActions = {
                ArrowRight: () => shiftStatus("right"),
                ArrowLeft: () => shiftStatus("left"),
            };

            const altActions = {
                KeyR: () => props.resetFilters(),
                KeyW: () => {
                    props.filters.waiting_for_reply = props.filters.waiting_for_reply === 'yes' ? 'no' : 'yes';
                    maybeChangeWaitingReply();
                },
            };

            if (shiftKey && shiftActions[code]) {
                event.preventDefault();
                shiftActions[code]();
            }

            if (altKey && altActions[code]) {
                event.preventDefault();
                altActions[code]();
            }
        };

        const shiftStatus = (direction) => {
            const currentIndex = state.statusOrder.indexOf(props.filters.status_type);
            const length = state.statusOrder.length;
            const newIndex = direction === "right"
                ? (currentIndex + 1) % length
                : (currentIndex - 1 + length) % length;

            props.filters.status_type = state.statusOrder[newIndex];
            fetchTickets();
        };


        onMounted(() => {
            if(appVars.keyboard_shortcuts === 'yes') {
                window.addEventListener("keydown", handleKeydown);
            }
        });

        onUnmounted(() => {
            if(appVars.keyboard_shortcuts === 'yes') {
                window.removeEventListener("keydown", handleKeydown);
            }
        });

        return {
            appVars,
            translate,
            notify,
            fetchTickets,
            maybeChangeWaitingReply,
            has_active_filter,
            shiftStatus,
            ...toRefs(state),
        };
    }
}
</script>
