<template>
    <div v-loading="working" class="fs_bulk_actions_wrap">
        <div v-if="ticket_selections.length" class="fs_bulk_actions_bar">
            <ul v-loading="working" class="fs_tk_actions">
                <li :title="translate('Add Reply')"
                    @click="add_response_modal = true">
                    <el-icon> <ChatLineSquare /> </el-icon>
                </li>
                <li :title="translate('Assign Agent ')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="manual"
                        :visible="assignAgentPop"
                    >
                        <template #reference>
                            <el-icon :class="{'fs_pop_active': assignAgentPop}" @click="togglePop('assignAgentPop')">
                                <User />
                            </el-icon>
                        </template>

                        <div class="fs_extended_pop">
                            <el-select filterable v-model="agent_id">
                                <el-option
                                    v-for="agent in appVars.support_agents"
                                    :key="agent.id"
                                    :value="agent.id"
                                    :label="agent.full_name"></el-option>
                            </el-select>
                            <el-button @click="assignAgent()" style="margin-top: 10px;" :disabled="!agent_id"
                                       size="small"
                                       type="danger">
                                {{translate('Assign Agent')}}
                            </el-button>
                            <el-button @click="assignAgentPop = false" size="small" type="default" style="margin-top: 10px;">{{translate('Close')}}</el-button>
                        </div>
                    </el-popover>
                </li>
                <li :title="translate('Run Workflow')">
                    <work-flow-selector @reloadTickets="fetchTickets()" :ticket_ids="ticket_selections"/>
                </li>
                <li :title="translate('Add Tag(s)')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="manual"
                        :visible="addTagPop"
                    >
                        <template #reference>
                            <el-icon @click="togglePop('addTagPop')" :class="{fs_pop_active: assignAgentPop}">
                                <PriceTag />
                            </el-icon>
                        </template>
                        <div class="fs_extended_pop">
                            <el-select :multiple="true" filterable v-model="tag_ids">
                                <el-option
                                    v-for="agent in appVars.ticket_tags"
                                    :key="agent.id"
                                    :value="agent.id"
                                    :label="agent.title"></el-option>
                            </el-select>
                            <el-button style="margin-top: 10px;" size="small" type="success" @click="assignTags()">
                                {{translate('Apply Tags')}}
                            </el-button>
                            <el-button style="margin-top: 10px;" @click="addTagPop = false" size="small" type="default">{{translate('Close')}}</el-button>
                        </div>
                    </el-popover>
                </li>
                <li :title="translate('Close Tickets')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="click"
                    >
                        <template #reference>
                            <el-icon @click="togglePop()"> <Finished /> </el-icon>
                        </template>
                        <p style="margin: 0 0 1em 0;">{{translate('close_selected_ticket_warning')}}</p>
                        <el-button style="margin-top: 0;" size="small" type="success"
                                   @click="closeTickets()">{{translate('Close Selected Tickets')}}
                        </el-button>
                    </el-popover>
                </li>
                <li :title="translate('Watch Tickets')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="click"
                    >
                        <template #reference>
                            <el-icon @click="togglePop()"> <View /> </el-icon>
                        </template>
                        <p style="margin: 0 0 1em 0;">{{translate('Watch selected tickets to get notifications?')}}</p>
                        <el-button style="margin-top: 0;" size="small" type="primary"
                                   @click="watchTickets()">{{translate('Watch Selected Tickets')}}
                        </el-button>
                    </el-popover>
                </li>
                <li :title="translate('Unwatch Tickets')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="click"
                    >
                        <template #reference>
                            <el-icon @click="togglePop()"> <Hide /> </el-icon>
                        </template>
                        <p style="margin: 0 0 1em 0;">{{translate('Stop watching selected tickets?')}}</p>
                        <el-button style="margin-top: 0;" size="small" type="default"
                                   @click="unwatchTickets()">{{translate('Unwatch Selected Tickets')}}
                        </el-button>
                    </el-popover>
                </li>
                <li :title="translate('Delete Tickets')">
                    <el-popover
                        placement="bottom"
                        :width="400"
                        trigger="click"
                    >
                        <template #reference>
                            <el-icon @click="togglePop()"> <Delete /> </el-icon>
                        </template>
                        <p style="margin: 0 0 1em 0;">{{translate('delete_selected_ticket_warning')}}</p>
                        <el-button style="margin-top: 0;" size="small" type="danger"
                                   @click="deleteTickets()">{{translate('Yes, Delete Selected Tickets')}}
                        </el-button>
                    </el-popover>
                </li>
            </ul>
        </div>
        <el-dialog
            :title="translate('Reply To Selected Tickets')"
            v-model="add_response_modal"
            width="60%"
            class="fs_dialog">
            <create-response @created="fetchTickets()" v-if="add_response_modal" :ticket="ticket_selections"/>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import WorkFlowSelector from './parts/_WorkFlowSelector';
import CreateResponse from "./_CreateResponse";
import { reactive, toRefs } from "vue";
import { useFluentHelper, useNotify } from "@/admin/Composable/FluentFrameworkHelper";
export default {
    name: 'TicketBulkActions',
    props: ['ticket_selections'],
    components: {
        WorkFlowSelector,
        CreateResponse
    },

    setup(props, {emit}) {
        const {
            translate,
            handleError,
            post,
        } = useFluentHelper();
        const { notify } = useNotify();

        const state = reactive({
            agent_id: '',
            tag_ids: [],
            workflow_id: '',
            add_response_modal: false,
            working: false,
            assignAgentPop: false,
            addTagPop: false
        });

        const fetchTickets = () => {
            state.agent_id = '';
            state.tag_ids = '';
            state.workflow_id = '';
            state.addTagPop = false;
            state.assignAgentPop = false;
            state.add_response_modal = false;
            emit('fetchTickets');
        };

        const doBulkActions = (data) => {
            state.working = true;
            data.ticket_ids = props.ticket_selections;
            post('tickets/bulk-actions', data)
                .then(response => {
                    notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    fetchTickets();
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.working = false;
                });
        };

        const assignAgent = () => {
            if (!state.agent_id) {
                notify({
                    type: 'error',
                    message: translate('Please select an agent first'),
                    position: 'bottom-right'
                });
                return false;
            }

            doBulkActions({
                bulk_action: 'assign_agent',
                agent_id: state.agent_id
            });
        };

        const assignTags = () => {
            if (!state.tag_ids.length) {
                notify({
                    type: 'error',
                    message: translate('Please select tag first'),
                    position: 'bottom-right'
                });
                return false;
            }

            doBulkActions({
                bulk_action: 'assign_tags',
                tag_ids: state.tag_ids
            });
        };

        const closeTickets = () => {
            doBulkActions({
                bulk_action: 'close_tickets'
            });
        };

        const deleteTickets = () => {
            doBulkActions({
                bulk_action: 'delete_tickets'
            });
        };

        const watchTickets = () => {
            doBulkActions({
                bulk_action: 'watch_tickets'
            });
        };

        const unwatchTickets = () => {
            doBulkActions({
                bulk_action: 'unwatch_tickets'
            });
        };

        const togglePop = (pop) => {
            if (pop && state[pop]) {
                    state[pop] = false;
                return;
            }
            state.assignAgentPop = false;
            state.addTagPop = false;
            if (pop) {
                state[pop] = true;
            }
        }

        return {
            ...toRefs(state),
            fetchTickets,
            doBulkActions,
            assignAgent,
            assignTags,
            closeTickets,
            deleteTickets,
            watchTickets,
            unwatchTickets,
            togglePop,
            translate,
        }
    }
}
</script>
