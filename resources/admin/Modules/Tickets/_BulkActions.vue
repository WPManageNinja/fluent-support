<template>
    <div v-loading="working" class="fs_bulk_actions_wrap">
        <div v-if="ticket_selections.length" class="fs_bulk_actions_bar">
            <div class="fs_bulk_action_item" @click="add_response_modal = true">
                <IconPack :icon-key="'reply'" />
                <span>{{ $t('Reply') }}</span>
            </div>

            <el-popover
                v-if="canAssignAgents"
                placement="bottom"
                :width="400"
                trigger="manual"
                class="fs_popover"
                :visible="assignAgentPop"
            >
                <template #reference>
                    <div class="fs_bulk_action_item" :class="{'fs_pop_active': assignAgentPop}" @click="togglePop('assignAgentPop')">

                        <IconPack :icon-key="'agent'" />
                        <span>{{ $t('Agent') }}</span>
                    </div>
                </template>
                <div class="fs_extended_pop">
                    <el-select class="fs_select_field" popper-class="fs_agent_group_popper" filterable v-model="agent_id">
                        <template v-if="agentGroups.length">
                            <el-option-group
                                v-for="group in groupedAgentOptions"
                                :key="group.label"
                                :label="group.label"
                            >
                                <el-option
                                    v-for="agent in group.agents"
                                    :key="agent.id"
                                    :value="agent.id"
                                    :label="agent.full_name"
                                />
                            </el-option-group>
                        </template>
                        <template v-else>
                            <el-option
                                v-for="agent in appVars.support_agents"
                                :key="agent.id"
                                :value="agent.id"
                                :label="agent.full_name"
                            />
                        </template>
                    </el-select>
                    <div class="fs_popover_action_btn">
                        <el-button class="fs_filled_btn" @click="assignAgent()" style="margin-top: 10px;" :disabled="!agent_id" size="small" type="danger">
                            {{$t('Assign Agent')}}
                        </el-button>
                        <el-button class="fs_outline_btn" @click="assignAgentPop = false" size="small" type="default" style="margin-top: 10px;">{{$t('Close')}}</el-button>
                    </div>
                </div>
            </el-popover>

            <el-popover
                v-if="canAssignAgents"
                placement="bottom"
                :width="400"
                trigger="manual"
                class="fs_popover"
                :visible="assignGroupPop"
            >
                <template #reference>
                    <div class="fs_bulk_action_item" :class="{'fs_pop_active': assignGroupPop}" @click="togglePop('assignGroupPop')">
                        <IconPack :icon-key="'agentGroupIcon'" />
                        <span>{{ $t('Group') }}</span>
                    </div>
                </template>
                <div class="fs_extended_pop">
                    <el-select class="fs_select_field" filterable v-model="agent_group_id" :placeholder="$t('Select Agent Group')">
                        <el-option
                            v-for="group in appVars.agent_groups"
                            :key="group.id"
                            :value="group.id"
                            :label="group.title"></el-option>
                    </el-select>
                    <div class="fs_popover_action_btn">
                        <el-button class="fs_filled_btn" @click="assignAgentGroup()" style="margin-top: 10px;" :disabled="!agent_group_id" size="small" type="danger">
                            {{$t('Assign via Group')}}
                        </el-button>
                        <el-button class="fs_outline_btn" @click="assignGroupPop = false" size="small" type="default" style="margin-top: 10px;">{{$t('Close')}}</el-button>
                    </div>
                </div>
            </el-popover>

            <work-flow-selector @reloadTickets="fetchTickets()" :ticket_ids="ticket_selections">
                <div class="fs_bulk_action_item">
                    <IconPack :icon-key="'workflow'" />
                    <span>{{ $t('Workflow') }}</span>
                </div>
            </work-flow-selector>

            <el-popover
                placement="bottom"
                :width="400"
                trigger="manual"
                class="fs_popover"
                :visible="addTagPop"
            >
                <template #reference>
                    <div class="fs_bulk_action_item" :class="{fs_pop_active: addTagPop}" @click="togglePop('addTagPop')">
                        <IconPack :icon-key="'tags'" />
                        <span>{{ $t('Tags') }}</span>
                    </div>
                </template>
                <div class="fs_extended_pop">
                    <el-select class="fs_select_field" :multiple="true" filterable v-model="tag_ids">
                        <el-option
                            v-for="agent in appVars.ticket_tags"
                            :key="agent.id"
                            :value="agent.id"
                            :label="agent.title"></el-option>
                    </el-select>

                    <div class="fs_popover_action_btn">
                        <el-button class="fs_filled_btn" style="margin-top: 10px;" size="small" type="success" @click="assignTags()">
                            {{$t('Apply Tags')}}
                        </el-button>
                        <el-button class="fs_outline_btn" style="margin-top: 10px;" @click="addTagPop = false" size="small" type="default">{{$t('Close')}}</el-button>
                    </div>
                </div>
            </el-popover>

            <el-popover
                placement="bottom"
                :width="400"
                trigger="click"
            >
                <template #reference>
                    <div class="fs_bulk_action_item">
                        <IconPack :icon-key="'closeTicket'" />
                        <span>{{ $t('Close') }}</span>
                    </div>
                </template>
                <p style="margin: 0 0 1em 0;">{{$t('close_selected_ticket_warning')}}</p>
                <el-button class="fs_filled_btn" style="margin-top: 0;" size="small" type="success" @click="closeTickets()">
                    {{$t('Close Selected Tickets')}}
                </el-button>
            </el-popover>

            <el-popover
                placement="bottom"
                :width="400"
                trigger="click"
            >
                <template #reference>
                    <div class="fs_bulk_action_item">
                        <IconPack :icon-key="'delete'" />
                        <span>{{ $t('Delete') }}</span>
                    </div>
                </template>
                <p style="margin: 0 0 1em 0;">{{$t('delete_selected_ticket_warning')}}</p>
                <el-button class="fs_filled_btn" style="margin-top: 0;" size="small" type="danger" @click="deleteTickets()">
                    {{$t('Yes, Delete Selected Tickets')}}
                </el-button>
            </el-popover>
        </div>

        <el-dialog
            :title="$t('Reply To Selected Tickets')"
            v-model="add_response_modal"
            width="60%"
            class="fs_dialog fs_bulk_action_modal">
            <div class="fs_bulk_action_create_response">
                <create-response @created="fetchTickets()" v-if="add_response_modal" :ticket="ticket_selections"/>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import WorkFlowSelector from './parts/_WorkFlowSelector';
import CreateResponse from "./_CreateResponse";
import IconPack from "@/admin/Components/IconPack.vue";
import { getGroupedAgentOptions } from '@/admin/Composable/agentGroupHelper';
import { mapState } from 'pinia';
import { useTicketListStore } from '@/admin/stores/ticketList.store';

export default {
    name: 'TicketBulkActions',
    components: {
        WorkFlowSelector,
        CreateResponse,
        IconPack
    },
    computed: {
        ...mapState(useTicketListStore, ['ticket_selections']),
        // Both bulk assign actions write ticket->agent_id, so they follow the same
        // capability pair the server enforces on the bulk-actions route: the
        // assign capability on top of a ticket-management capability.
        canAssignAgents() {
            const permissions = this.appVars.me?.permissions || [];

            return permissions.includes('fst_assign_agents')
                && (permissions.includes('fst_manage_own_tickets')
                    || permissions.includes('fst_manage_unassigned_tickets')
                    || permissions.includes('fst_manage_other_tickets'));
        },
        agentGroups() {
            return this.appVars.agent_groups || [];
        },
        groupedAgentOptions() {
            return getGroupedAgentOptions(this.appVars.support_agents || [], this.agentGroups, this.$t('Other'));
        },
    },
    data() {
        return {
            agent_id: '',
            agent_group_id: '',
            tag_ids: [],
            workflow_id: '',
            add_response_modal: false,
            working: false,
            assignAgentPop: false,
            assignGroupPop: false,
            addTagPop: false
        };
    },

    methods: {
        fetchTickets() {
            this.agent_id = '';
            this.agent_group_id = '';
            this.tag_ids = [];
            this.workflow_id = '';
            this.addTagPop = false;
            this.assignAgentPop = false;
            this.assignGroupPop = false;
            this.add_response_modal = false;
            this.$emit('fetchTickets');
        },
        doBulkActions(data) {
            this.working = true;
            data.ticket_ids = this.ticket_selections;
            this.$post('tickets/bulk-actions', data)
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    this.fetchTickets();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.working = false;
                });
        },
        assignAgent() {
            if (!this.agent_id) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Please select an agent first'),
                    position: 'bottom-right'
                });
                return false;
            }

            this.doBulkActions({
                bulk_action: 'assign_agent',
                agent_id: this.agent_id
            });
        },
        assignAgentGroup() {
            if (!this.agent_group_id) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Please select an agent group first'),
                    position: 'bottom-right'
                });
                return false;
            }

            this.doBulkActions({
                bulk_action: 'assign_agent_group',
                agent_group_id: this.agent_group_id
            });
        },
        assignTags() {
            if (!this.tag_ids.length) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Please select tag first'),
                    position: 'bottom-right'
                });
                return false;
            }

            this.doBulkActions({
                bulk_action: 'assign_tags',
                tag_ids: this.tag_ids
            });
        },
        closeTickets() {
            this.doBulkActions({
                bulk_action: 'close_tickets'
            });
        },
        deleteTickets() {
            this.doBulkActions({
                bulk_action: 'delete_tickets'
            });
        },
        togglePop(pop) {
            if (pop && this[pop]) {
                this[pop] = false;
                return;
            }
            this.assignAgentPop = false;
            this.assignGroupPop = false;
            this.addTagPop = false;
            if (pop) {
                this[pop] = true;
            }
        }
    }
}
</script>
