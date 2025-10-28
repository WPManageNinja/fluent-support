<template>
    <div class="fs_view_ticket">
        <template v-if="ticket && ticket.id">
            <div class="fs_ticket_body">
                <div class="fs_ticket_actions">
                    <ul class="fs_tk_actions">
                        <li :title="translate('Add Reply')"
                            v-if="ticket.status != 'closed'"
                            class="fs_add_reply"
                            :class="(show_response_box == 'response') ? 'fs_action_active' : ''"
                            @click="draftReplyPermission ? show_response_box = 'draft_response' : show_response_box = (show_response_box === 'response' ? '' : 'response')">
                            <el-icon style="vertical-align: middle;">
                                <chat-line-square/>
                            </el-icon>
                        </li>
                        <li :title="translate('Add Internal Note')"
                            class="fs_add_note"
                            :class="(show_response_box == 'note') ? 'fs_action_active' : ''"
                            @click="show_response_box = (show_response_box === 'note' ? '' : 'note')">
                            <el-icon style="vertical-align: middle;">
                                <notebook/>
                            </el-icon>
                        </li>
                        <li :title="translate('Run Workflow')" class="fs_add_workflow"
                            v-if="appVars.manual_workflows && appVars.manual_workflows.length">
                            <work-flow-selector @reloadTickets="fetchTicket()" :ticket_ids="[ticket_id]"/>
                        </li>

                        <li :title="translate('Assigned Agent ') + ticket.agent?.full_name">
                            <el-popover
                                placement="bottom"
                                :width="400"
                                trigger="click"
                            >
                                <template #reference>
                                <span class="fs_agent_photo_icon" v-if="ticket.agent">
                                    <img :alt="ticket.agent?.full_name" :src="ticket.agent?.photo"/>
                                </span>
                                    <el-icon style="vertical-align: middle;" v-else>
                                        <user/>
                                    </el-icon>
                                </template>

                                <el-select filterable @change="updateTicketAttr('agent_id')" v-model="ticket.agent_id">
                                    <el-option
                                        v-for="agent in agents"
                                        :key="agent.id"
                                        :value="agent.id"
                                        :label="agent.full_name"></el-option>
                                </el-select>
                            </el-popover>
                        </li>

                        <li :title="translate('AI Assistant ')" class="fs_ai_assistant" v-if="openAIIntegration">
                            <el-dropdown trigger="click">
                                <el-button class="fs_ai_intelligent_button">
                                    <img :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                                </el-button>

                                <template #dropdown>
                                    <el-dropdown-menu>
                                        <el-dropdown-item @click="getTicketSummary">Ticket Summary</el-dropdown-item>
                                        <el-dropdown-item @click="getCustomerSentiment">Customer Sentiment</el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </li>

                    </ul>
                    <div class="fs_product">
                        <el-button v-loading="loading" @click="fetchTicket()"
                                   icon="Refresh"
                                   class="fs_refresh_tk_page"
                                   size="small"></el-button>

                        <el-button v-loading="updating" :disabled="updating" @click="closeTicket()"
                                   v-if="ticket.status != 'closed'" class="fs_close_btn" type="info" size="small">
                            {{ translate('Close') }}
                        </el-button>

                        <el-popover
                            placement="bottom"
                            :width="400"
                            trigger="click"
                            v-if="products.length"
                        >
                            <template #reference>
                                <span style="margin: 10px;"><el-icon style="vertical-align: middle;"><goods/></el-icon> {{
                                        ticket.product?.title
                                    }}</span>
                            </template>

                            <el-select @change="updateTicketAttr('product_id')" v-model="ticket.product_id">
                                <el-option
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="product.id"
                                    :label="product.title"></el-option>
                            </el-select>

                        </el-popover>

                        <el-popover
                            placement="bottom"
                            :width="400"
                            trigger="click"
                            v-if="appVars.me.permissions.includes('fst_manage_settings')"
                        >
                            <template #reference>
                                <span class="fs_business_name" style="margin-right: 10px;">
                                    <el-icon style="vertical-align: middle;"><office-building/></el-icon> {{ ticket.mailbox?.name }}
                                </span>
                            </template>
                            <el-select @change="changeMailbox" v-model="ticket.mailbox.name">
                                <el-option
                                    v-for="mailbox in mailboxes"
                                    :key="mailbox.id"
                                    :value="mailbox.id"
                                    :label="mailbox.name"></el-option>
                            </el-select>
                        </el-popover>

                        <span v-else class="fs_business_name" style="margin-right: 10px;">
                            <el-icon style="vertical-align: middle;"><office-building/></el-icon> {{ ticket.mailbox?.name }}
                        </span>

                        <el-dropdown  style="vertical-align: middle;" trigger="click">
                            <span class="el-dropdown-link">
                              <el-icon style="transform: rotate(90deg)"><More/></el-icon>
                            </span>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <div v-if="has_pro">
                                        <el-dropdown-item  @click='(show_merge_modal=true) && (customerTickets())'>
                                            <svg width="14" style="margin-right: 4px;" height="14" viewBox="0 0 14 14"
                                                 fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M4.14447 5.12748C4.24566 5.4978 4.46577 5.8246 4.77092 6.05754C5.07606 6.29048 5.44932 6.41666 5.83322 6.41665H8.16655C8.85374 6.4167 9.51884 6.65933 10.0446 7.10178C10.5704 7.54423 10.9232 8.15808 11.0406 8.83515C11.4365 8.96431 11.7734 9.23039 11.9908 9.5856C12.2082 9.9408 12.2918 10.3619 12.2267 10.7732C12.1616 11.1845 11.9519 11.5591 11.6355 11.8298C11.319 12.1005 10.9163 12.2495 10.4999 12.25C10.0924 12.2503 9.69767 12.1084 9.38362 11.8489C9.06957 11.5893 8.85594 11.2283 8.77956 10.828C8.70318 10.4278 8.76884 10.0135 8.96522 9.65651C9.1616 9.29952 9.47639 9.02224 9.85531 8.87248C9.75412 8.50216 9.534 8.17537 9.22886 7.94242C8.92371 7.70948 8.55045 7.5833 8.16655 7.58331H5.83322C5.20199 7.58424 4.58765 7.37946 4.08322 6.99998V8.84915C4.47259 8.98676 4.80078 9.2576 5.00976 9.61378C5.21875 9.96997 5.29509 10.3886 5.22528 10.7956C5.15547 11.2026 4.94401 11.5719 4.62827 11.8381C4.31254 12.1043 3.91286 12.2503 3.49989 12.2503C3.08691 12.2503 2.68724 12.1043 2.3715 11.8381C2.05577 11.5719 1.84431 11.2026 1.7745 10.7956C1.70469 10.3886 1.78102 9.96997 1.99001 9.61378C2.199 9.2576 2.52718 8.98676 2.91655 8.84915V5.15081C2.52979 5.01426 2.20325 4.74624 1.99393 4.39351C1.7846 4.04079 1.70577 3.62576 1.7712 3.22085C1.83663 2.81594 2.04216 2.44686 2.35192 2.17801C2.66169 1.90916 3.05602 1.75762 3.46611 1.74983C3.8762 1.74204 4.276 1.87849 4.59576 2.13537C4.91551 2.39226 5.13491 2.75326 5.21568 3.15539C5.29644 3.55753 5.23344 3.97525 5.03766 4.33567C4.84189 4.6961 4.52577 4.97633 4.14447 5.12748V5.12748ZM3.49989 4.08331C3.6546 4.08331 3.80297 4.02186 3.91237 3.91246C4.02176 3.80306 4.08322 3.65469 4.08322 3.49998C4.08322 3.34527 4.02176 3.1969 3.91237 3.0875C3.80297 2.97811 3.6546 2.91665 3.49989 2.91665C3.34518 2.91665 3.1968 2.97811 3.08741 3.0875C2.97801 3.1969 2.91655 3.34527 2.91655 3.49998C2.91655 3.65469 2.97801 3.80306 3.08741 3.91246C3.1968 4.02186 3.34518 4.08331 3.49989 4.08331ZM3.49989 11.0833C3.6546 11.0833 3.80297 11.0219 3.91237 10.9125C4.02176 10.8031 4.08322 10.6547 4.08322 10.5C4.08322 10.3453 4.02176 10.1969 3.91237 10.0875C3.80297 9.9781 3.6546 9.91665 3.49989 9.91665C3.34518 9.91665 3.1968 9.9781 3.08741 10.0875C2.97801 10.1969 2.91655 10.3453 2.91655 10.5C2.91655 10.6547 2.97801 10.8031 3.08741 10.9125C3.1968 11.0219 3.34518 11.0833 3.49989 11.0833ZM10.4999 11.0833C10.6546 11.0833 10.803 11.0219 10.9124 10.9125C11.0218 10.8031 11.0832 10.6547 11.0832 10.5C11.0832 10.3453 11.0218 10.1969 10.9124 10.0875C10.803 9.9781 10.6546 9.91665 10.4999 9.91665C10.3452 9.91665 10.1968 9.9781 10.0874 10.0875C9.97801 10.1969 9.91655 10.3453 9.91655 10.5C9.91655 10.6547 9.97801 10.8031 10.0874 10.9125C10.1968 11.0219 10.3452 11.0833 10.4999 11.0833Z"
                                                    fill="currentColor"/>
                                            </svg>
                                            {{ translate('Merge Tickets') }}
                                        </el-dropdown-item>

                                        <el-dropdown-item v-if="appVars.fluent_boards" @click="show_fbs_add_task_modal = true">
                                            <img :src="appVars.asset_url + '/images/addTask.svg'" class="fs_add_task_logo">
                                            {{ translate('Add Task to Fluent Boards') }}
                                        </el-dropdown-item>

                                        <el-dropdown-item  @click='(show_watcher_modal=true)' v-if="!watchers.length">
                                            <svg width="14" style="margin-right: 4px;" height="14" viewBox="0 0 14 14"
                                                 fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M10.5 1.16634C9.21115 1.16634 8.16634 2.21115 8.16634 3.49999C8.16634 4.78882 9.21115 5.83362 10.5 5.83362C10.982 5.83362 11.4262 5.69291 11.796 5.44517C11.9298 5.35553 12.111 5.39134 12.2006 5.52517C12.2903 5.659 12.2545 5.84015 12.1206 5.92981C11.6548 6.24184 11.0978 6.41695 10.5 6.41695C8.88897 6.41695 7.58301 5.11099 7.58301 3.49999C7.58301 1.88899 8.88897 0.583008 10.5 0.583008C12.111 0.583008 13.417 1.88899 13.417 3.49999L13.4169 3.50571V3.93748C13.4169 4.50127 12.9599 4.95832 12.3961 4.95832C12.0449 4.95832 11.7351 4.78094 11.5514 4.51087C11.2861 4.78669 10.9132 4.95833 10.5003 4.95833C9.69485 4.95833 9.04192 4.30541 9.04192 3.49999C9.04192 2.69458 9.69485 2.04166 10.5003 2.04166C10.8286 2.04166 11.1315 2.15014 11.3753 2.33322C11.3753 2.17219 11.5059 2.04166 11.6669 2.04166C11.828 2.04166 11.9586 2.17225 11.9586 2.33333V3.93748C11.9586 4.17911 12.1545 4.37498 12.3961 4.37498C12.6377 4.37498 12.8336 4.17911 12.8336 3.93748V3.49687L12.8336 3.49191C12.8293 2.20679 11.7862 1.16634 10.5 1.16634ZM9.62526 3.49999C9.62526 3.98325 10.017 4.37499 10.5003 4.37499C10.9835 4.37499 11.3753 3.98325 11.3753 3.49999C11.3753 3.01674 10.9835 2.62499 10.5003 2.62499C10.017 2.62499 9.62526 3.01674 9.62526 3.49999Z"
                                                    fill="currentColor"/>
                                                <path
                                                    d="M12.8334 8.60417V6.10878C12.5737 6.34124 12.2791 6.53543 11.9584 6.68267V8.60417C11.9584 9.16796 11.5014 9.625 10.9376 9.625H7.29962L4.375 11.8128L4.37438 9.625H3.06258C2.49879 9.625 2.04175 9.16796 2.04175 8.60417V3.64583C2.04175 3.08204 2.49879 2.625 3.06258 2.625H7.11033C7.19025 2.31457 7.31164 2.02082 7.46832 1.75H3.06258C2.01554 1.75 1.16675 2.59879 1.16675 3.64583V8.60417C1.16675 9.65119 2.01554 10.5 3.06258 10.5H3.49962L3.50008 12.1041C3.50008 12.2614 3.55104 12.4146 3.64534 12.5407C3.88654 12.8632 4.34349 12.9291 4.66598 12.6879L7.59071 10.5H10.9376C11.9846 10.5 12.8334 9.65119 12.8334 8.60417Z"
                                                    fill="currentColor"/>
                                            </svg>
                                            {{ translate('Add Bookmark') }}
                                        </el-dropdown-item>

                                        <el-dropdown-item @click="toggleWatch()" v-loading="watching_loading" v-if="!currentAgentHasReplied">
                                            <el-icon>
                                                <View v-if="!is_watching" />
                                                <Hide v-if="is_watching" />
                                            </el-icon>
                                            {{ is_watching ? translate('Unwatch Ticket') : translate('Watch Ticket') }}
                                        </el-dropdown-item>

                                        <el-dropdown-item  @click='(close_ticket_silently="yes") && closeTicket()'>
                                            <el-icon>
                                                <MuteNotification/>
                                            </el-icon>
                                            {{ translate('Close Ticket Silently') }}
                                        </el-dropdown-item>
                                    </div>
                                    <el-dropdown-item v-if="deleteTicketPermission" @click="deleteTicket()" v-loading="deleting">
                                        <el-icon>
                                            <Delete />
                                        </el-icon>
                                        {{ translate('Delete') }}
                                    </el-dropdown-item>

                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>

                        <el-dialog
                            v-model="show_fbs_add_task_modal"
                            v-if="has_pro && show_fbs_add_task_modal"
                            :title="translate('Add Task to Fluent Boards')"
                            class="fs_dialog"
                        >
                            <FluentBoardsIntegration @created="afterCreatedTask" :ticket=ticket :fluentcrm_profile="fluentcrm_profile" />
                        </el-dialog>

                        <el-dialog
                            v-model="show_merge_modal"
                            v-if="has_pro && show_merge_modal"
                            :title="translate('Merge Tickets')"
                            class="fs_dialog"
                        >
                            <div class="fs_box_body" v-if="customer_tickets.length && app_ready">
                                <el-table
                                    :data="customer_tickets"
                                    style="width: 100%"
                                    @selection-change="handleMergeSelectionChange"
                                >
                                    <el-table-column type="selection" width="55" />
                                    <el-table-column prop="id" label="ID" width="130"></el-table-column>
                                    <el-table-column prop="title" label="Title" width="250"></el-table-column>
                                    <el-table-column prop="status" label="Status" width="130"></el-table-column>
                                </el-table>
                                <div class="fs_tk_merge_actions">
                                    <div style="padding-bottom: 20px;" class="fframe_pagination_wrapper" v-if="customer_tickets.length">
                                        <pagination @fetch="customerTickets" :pagination="pagination"/>
                                    </div>
                                    <el-button size="default" type="primary"
                                               @click="mergeTickets()">
                                        {{ translate('Merge') }}
                                    </el-button>
                                </div>
                            </div>
                            <div class="fs_box_body" v-else-if="!customer_tickets.length && app_ready">
                                <h3>{{ translate('customer_has_one_tk') }}</h3>
                            </div>
                            <div style="padding: 20px; background: white;" class="fs_box_body" v-else>
                                <el-skeleton :rows="5" animated/>
                            </div>
                        </el-dialog>

                        <el-dialog
                            v-model="show_watcher_modal"
                            v-if="has_pro && show_watcher_modal"
                            :title="translate('Add Bookmark')"
                            class="fs_dialog"
                        >
                            <div class="fs_box_body">
                                <el-select :multiple="true" v-model="watcherIds">
                                    <el-option
                                        v-for="(agent,agent_key) in agents"
                                        :key="agent_key" :value="agent.id"
                                        :label="agent.full_name"></el-option>
                                </el-select>

                                <el-button type="primary" size="small"
                                           style="margin-top: 20px" @click="addWatchers">{{ translate('Add') }}
                                </el-button>
                            </div>
                        </el-dialog>
                    </div>
                </div>
                <div class="fs_th_header">
                    <div class="fs_header_group">
                        <div class="fs_tk_subject">
                            <h2 :title="translate('Click to Edit Subject')">
                                <el-popover
                                    placement="bottom"
                                    :width="400"
                                    trigger="click"
                                >
                                    <template #reference>
                                        <span> {{ ticket?.title }}</span>
                                    </template>

                                    <el-input @keyup.enter="updateTicketAttr('title')"
                                              v-model="ticket.title"></el-input>
                                    <p>{{ translate('Press enter to save') }}</p>
                                </el-popover>
                            </h2>
                            <ticket-tags :creatable="true" :ticket_id="ticket.id" :tags.sync="ticket.tags"/>
                        </div>
                        <div class="fs_tk_badges">
                            <span class="fs_ticket_id">#{{ ticket.id }} </span>

                            <el-popover
                                placement="bottom"
                                :width="400"
                                trigger="click"
                            >
                                <template #reference>
                                    <span :title="translate('Client Priority: ') + translate(ticket.client_priority) "
                                          :class="'fs_badge_priority_'+ticket.client_priority" class="fs_badge">
                                        <el-icon style="vertical-align: middle;"><user/></el-icon> {{
                                            translate(ticket.client_priority)
                                        }}</span>
                                </template>

                                <el-select @change="updateTicketAttr('client_priority')"
                                           v-model="ticket.client_priority"
                                           size="small">
                                    <el-option

                                        v-for="(priorityLabel, priority) in client_priorities"
                                        :key="priority" :value="priority"
                                        :label="priorityLabel"></el-option>
                                </el-select>
                            </el-popover>

                            <el-popover
                                placement="bottom"
                                :width="400"
                                trigger="click"
                            >
                                <template #reference>
                                    <span :title="translate('Admin Priority:') + translate(ticket.priority) "
                                          :class="'fs_badge_priority_'+ticket.priority" class="fs_badge">
                                        <el-icon style="vertical-align: middle;"><service/></el-icon> {{
                                            translate(ticket.priority)
                                        }}</span>
                                </template>

                                <el-select @change="updateTicketAttr('priority')" v-model="ticket.priority"
                                           size="small">
                                    <el-option

                                        v-for="(priorityLabel, priority) in admin_priorities"
                                        :key="priority" :value="priority"
                                        :label="priorityLabel"></el-option>
                                </el-select>
                            </el-popover>

                            <el-popover
                                placement="bottom"
                                :width="400"
                                trigger="click"
                                v-if="has_pro && !isEmpty(ticket_statuses)"
                            >
                                <template #reference>
                                    <span class="fs_badge" :class="'fs_badge_' + ticket.status">{{
                                            translate(getStatusDisplayName(ticket.status))
                                        }}</span>
                                </template>

                                <el-select @change="updateTicketAttr('status')" v-model="ticket.status">
                                    <el-option
                                        v-for="(status, statusKey) in getTicketStatus"
                                        :key="statusKey"
                                        :value="status"
                                        :label="statusKey">{{ statusKey }}
                                    </el-option>
                                </el-select>

                            </el-popover>
                            <span v-else class="fs_badge" :class="'fs_badge_' + ticket.status">{{
                                    translate(getStatusDisplayName(ticket.status))
                                }}</span>
                        </div>
                    </div>
                </div>
                <div class="fs_intelligence" v-loading="ResponseLoader" v-if="!ResponseLoader">
                    <div class="fs_intelligence_card__result" v-if="ticketSummary">
                        <div class="fs_generated_summary">
                            <div>
                                <strong>Ticket Summary:</strong>
                                <p>{{ ticketSummary}}</p>
                            </div>
                            <div class="fs_ai_response_actions">
                                <div class="fs_ai_regenerate">
                                    <el-button class="fs_ai_regenerate_button" @click="getTicketSummary">
                                        <img :src="appVars.asset_url + 'images/regenerate.svg'" alt="">
                                    </el-button>
                                </div>
                                <div class="fs_ai_response_close">
                                    <el-button class="fs_ai_response_close_button" @click="closeAIResponse">
                                        <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                                    </el-button>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="fs_intelligence_card__result" v-if="customerSentiment">
                        <div class="fs_customer_sentiment">
                            <div>
                                <div> {{customerSentiment }}</div>
                            </div>
                            <div class="fs_ai_response_actions">
                                <div class="fs_ai_regenerate">
                                    <el-button class="fs_ai_regenerate_button" @click="getCustomerSentiment">
                                        <img :src="appVars.asset_url + 'images/regenerate.svg'" alt="">
                                    </el-button>
                                </div>
                                <div class="fs_ai_response_close" >
                                    <el-button class="fs_ai_response_close_button" @click="closeAIResponse">
                                        <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                                    </el-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fs_ai_response_loading" v-if="ResponseLoader">
                    <el-skeleton :rows="4" animated />
                </div>
                <create-response
                    v-if="show_response_box"
                    @created="recordNewResponse"
                    :ticket="ticket"
                    :is_agent="'yes'"
                    @close="show_response_box = ''"
                    :type="show_response_box"
                    :draft="draftData"
                    :openAIIntegration="openAIIntegration"
                    :fluentBotIntegration="fluentBotIntegration"
                    @discardDraft="discardDraft"
                />
                <div class="fs_create_response text-align-center" v-if="ticket.status == 'closed'">
                    <p>{{ translate('ticket_closed') }} {{ ticket.resolved_at }}
                        <span v-if="ticket.closed_by_person">
                            by <b>{{ getHumanName(ticket.closed_by_person) }}</b>
                        </span></p>
                    <el-button v-loading="updating" :disabled="updating" @click="reOpen()" type="info" size="small">
                        {{ translate('Reopen This ticket') }}
                    </el-button>
                </div>

                <div v-if="ticket && ticket.id" class="fs_threads_container">

                    <div v-if="appVars.enable_draft_mode === 'yes'">
                        <article v-if ="showDraft && !show_response_box"
                                 class="fs_saved_draft"
                        >
                            <span class="draft_title"> {{translate('Saved Draft')}} </span>
                            <div class="fs_thread_content">
                            <span class="fs_draft_header">
                            </span>
                                <section class="fs_thread_wrap">
                                    <section class="fs_thread_message">
                                        <div class="fs_thread_head">
                                            <div class="fs_thread_title">
                                                <strong>{{translate('You created a draft')}}</strong>
                                            </div>
                                        </div>
                                        <div class="fs_thread_body">
                                            <div v-html="santizeContent(draftResponse)"
                                                 class="fs_thread_body"></div>
                                        </div>
                                        <div>
                                            <el-button-group>
                                                <el-button @click="show_response_box = draftReplyPermission ? 'draft_response' : 'response'">
                                                    {{ translate('Edit') }}
                                                </el-button>
                                                <el-button @click="discardDraft(draftData.id)">
                                                    {{ translate('Discard') }}
                                                </el-button>
                                            </el-button-group>
                                        </div>
                                    </section>
                                </section>
                            </div>
                        </article>
                    </div>

                    <article v-for="conversation in conversations"
                             :key="conversation.id"
                             :ref="el => setRef(conversation.id, el)"
                             :class="getTicketClasses(conversation, ticket) ">
                        <span :class="getRibbonClass(conversation, ticket)" >{{getTextByPerson(conversation, ticket)}}</span>
                        <div class="fs_thread_content">
                            <section class="fs_avatar">
                                <img v-if="conversation.person" :src="conversation.person?.photo"
                                     :alt="conversation.person.full_name"/>
                            </section>
                            <section class="fs_thread_wrap">
                                <section class="fs_thread_message">
                                    <div class="fs_thread_head">
                                        <div class="fs_thread_title">
                                            <strong v-if="conversation.person">{{
                                                    getHumanName(conversation.person)
                                                }}</strong>&nbsp;
                                            <span v-if="conversation.conversation_type == 'response'"> {{
                                                    translate('replied')
                                                }}</span>
                                            <span v-else-if="conversation.conversation_type == 'note'"> {{
                                                    translate('added a note')
                                                }}</span>

                                            <div class="carrier_info" v-if="ticket.source == 'email'">
                                                <div class="from_info" v-if="conversation.person.person_type == 'customer'">
                                                    <span><strong>From: </strong>{{ conversation.person?.full_name }}&lt;{{ conversation.person?.email }}&gt;</span>
                                                </div>
                                                <div class="cc_info" v-if="conversation.cc_info?.length">
                                                    <span><strong>Cc: </strong>{{ getArrToString(conversation.cc_info) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fs_thread_actions">
                                            <div class="fs_agent_feedback" v-if="has_pro && appVars.agent_feedback_rating === 'yes' && conversation.agent_feedback" >
                                                <img :src="`${appVars.asset_url}images/icons/${conversation.agent_feedback === 'like' ? 'likeButtonFill' : 'dislikeButtonFill'}.svg`" />
                                            </div>
                                            <span v-if="conversation.source" :title="'Source: ' + conversation.source"
                                                  :class="'fc_source_icon fc_source_icon_'+conversation.source"><span>{{ conversation.source }}</span></span>
                                            <span :title="translate('Conversation created at ') + conversation.created_at">
                                                {{ $timeDiff(conversation.created_at) }}
                                            </span>
                                            <el-dropdown @command="handleResponseActionCommand" trigger="click">
                                                <span class="el-dropdown-link">
                                                    <el-icon><arrow-down/></el-icon>
                                                </span>
                                                <template #dropdown>
                                                    <el-dropdown-menu>
                                                        <el-dropdown-item
                                                            v-if="!draftReplyPermission || conversation.conversation_type === 'draft_response'"
                                                            :command="{ type: 'edit', conversation: conversation }"
                                                            icon="EditPen"> {{ translate('Edit') }}
                                                        </el-dropdown-item>
                                                        <el-dropdown-item v-if="has_pro && conversation.conversation_type !== 'draft_response'"
                                                                          :command="{ type: 'split_ticket', conversation: conversation }"
                                                                          icon="TopLeft">
                                                            {{ translate('Split Ticket') }}
                                                        </el-dropdown-item>
                                                        <el-dropdown-item
                                                            :command="{ type: 'delete', conversation: conversation }"
                                                            icon="Delete"> {{ translate('Delete') }}
                                                        </el-dropdown-item>
                                                    </el-dropdown-menu>
                                                </template>
                                            </el-dropdown>
                                        </div>
                                    </div>
                                    <div v-html="santizeContent(conversation.content)" class="fs_thread_body"></div>

                                    <div class="fst_file_lists" v-if="conversation.attachments?.length">
                                        <hr/>
                                        <ul>
                                            <li
                                                v-for="attachment in conversation.attachments"
                                                :key="attachment.file_hash"
                                            >
                                                <el-icon style="vertical-align: middle;">
                                                    <paperclip/>
                                                </el-icon>
                                                <a target="_blank" rel="noopener"
                                                   :href="attachment.secureUrl">{{
                                                        attachment.title
                                                    }}</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-if="conversation.conversation_type == 'draft_response' && draftReplyApprovePermission" class="fs_draft_response_actions">
                                        <el-button size="small" type="primary" @click="approveDraftResponse(conversation)">{{translate('Approve')}}</el-button>
                                    </div>
                                </section>
                            </section>
                        </div>
                    </article>
                    <article class="fs_thread conversion_starter">
                        <div class="fs_thread_content">
                            <section class="fs_avatar">
                                <img :src="ticket.customer?.photo" :alt="ticket.customer?.full_name"/>
                            </section>
                            <section class="fs_thread_wrap">
                                <section class="fs_thread_message">
                                    <div class="fs_thread_head">
                                        <div class="fs_thread_title">
                                            <strong>{{ ticket.customer?.full_name }}</strong>
                                            {{ translate('started the conversation') }}
                                            <div class="carrier_info" v-if="ticket.source == 'email'">
                                                <div class="from_info">
                                                    <span><strong>From: </strong>{{
                                                            ticket.customer?.full_name
                                                        }}&lt;{{ ticket.customer?.email }}&gt;</span>
                                                </div>
                                                <div class="cc_info" v-if="ticket.carbon_copy">
                                                    <span><strong>Cc: </strong>{{ ticket.carbon_copy }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fs_thread_actions">
                                            <span v-if="ticket.source" :title="'Source: ' + ticket.source"
                                                  :class="'fc_source_icon fc_source_icon_'+ticket.source"><span>{{ ticket.source }}</span></span>
                                            <span :title="translate('Ticket created at ') + ticket.created_at">{{ $timeDiff(ticket.created_at) }}</span>
                                        </div>
                                    </div>
                                    <div v-html="santizeContent(ticket.content)" class="fs_thread_body"></div>

                                    <div class="fst_file_lists" v-if="ticket.attachments && ticket.attachments.length">
                                        <ul>
                                            <li v-if="ticket.attachments.length"
                                                v-for="attachment in ticket.attachments"
                                                :key="attachment.file_hash"
                                            >
                                                <el-icon style="vertical-align: middle;">
                                                    <paperclip/>
                                                </el-icon>
                                                <a target="_blank" rel="noopener"
                                                   :href="attachment.secureUrl">{{
                                                        attachment.title
                                                    }}</a>
                                            </li>
                                        </ul>
                                    </div>
                                </section>
                            </section>
                            <div v-if="has_pro && !isEmpty(appVars.custom_fields)" class="fc_custom_data_wrap">
                                <h3>{{ translate('Additional Data') }}
                                    <el-button @click="showCustomDataEditForm = !showCustomDataEditForm" :text="true"
                                               icon="EditPen" size="small"></el-button>
                                </h3>
                                <ul v-if="!isEmpty(ticket.custom_fields)">
                                    <li v-for="(fieldValue, fieldName) in ticket.custom_fields" :key="fieldName">
                                        <b>{{ appVars.custom_fields[fieldName].label }}</b> :
                                        <span v-if="isArray(fieldValue)">
                                            <span class="fs_custom_check_value" v-for="value in fieldValue"
                                                  :key="value">{{ value }}</span>
                                        </span>
                                        <span v-else v-html="santizeContent(fieldValue)"></span>
                                    </li>
                                </ul>
                                <p v-else>{{ translate('No additional data found') }}</p>
                            </div>
                        </div>
                        <el-dialog
                            :title="translate('Updating Custom Field Data')"
                            v-model="showCustomDataEditForm"
                            v-if="showCustomDataEditForm"
                            width="60%"
                            class="fs_dialog">
                            <custom-field-form @syncData="syncCustomData" :ticket_id="ticket_id" type="update_ticket"
                                               :custom_data="ticket.custom_fields"/>
                        </el-dialog>

                    </article>
                </div>
            </div>
            <div class="fs_ticket_sidebar">
                <ticket-sidebar :fluentcrm_profile="fluentcrm_profile" :ticket_id="ticket_id" :ticket="ticket"
                                :watchers="watchers" :watcher_ids="watcherIds" @refresh="fetchTicket" :fetch_other_tickets="fetch_other_tickets"/>
            </div>
        </template>
        <template v-else-if="ticketNotFound.length">
            <div class="fs_ticket_error_message">
                <div class="error-container">
                    <img
                        :src="
                            appVars.asset_url +
                            'images/ticketNotFound.svg'
                        "
                        class="fs_ticket_not_found_svg"
                    />
                    <h1>{{ ticketNotFound }}</h1>
                    <p>Go back to <a @click="$router.push({ name: 'tickets' })" style="cursor: pointer;">All Tickets</a> or contact support for assistance.</p>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="fs_ticket_body">
                <div style="padding: 15px;">
                    <el-skeleton :rows="10" animated/>
                </div>
            </div>
            <div style="margin-left: 20px;" class="fs_ticket_sidebar">
                <el-skeleton style="background: white;padding: 20px; margin-bottom: 20px; box-sizing: border-box;"
                             :rows="3" animated/>
                <el-skeleton style="background: white;padding: 20px; margin-bottom: 20px; box-sizing: border-box;"
                             :rows="3" animated/>
                <el-skeleton style="background: white;padding: 20px; margin-bottom: 20px; box-sizing: border-box;"
                             :rows="3" animated/>
            </div>
        </template>

        <modal :show="edit_response_modal" @close="edit_response_modal=false" :title="translate('Edit Response')">
            <template #body>
                <edit-response @updated="edit_response_modal = false; editing_response = false" v-if="editing_response"
                               :response="editing_response"/>
            </template>
        </modal>

        <modal :show="split_ticket_modal" @close="split_ticket_modal=false" :title="translate('Split Ticket')">
            <template #body>
                <split-ticket :ticket_data="split_ticket" @split-ticket="splitToNewTicket" :spliting="loading"/>
            </template>
        </modal>
        <active-agents :ticket="ticket" v-if="ticket && ticket.id"/>
    </div>
</template>

<script type="text/babel">
import CreateResponse from './_CreateResponse';
import EditResponse from './_EditResponse';
import TicketSidebar from './_TicketSidebar';
import FluentBoardsIntegration from './_FluentBoardsIntegration';
import each from 'lodash/each';
import isEmpty from 'lodash/isEmpty';
import isArray from 'lodash/isArray';
import ActiveAgents from './_active_agents';
import TicketTags from './parts/_Tags';
import CustomFieldForm from './parts/_CustomFieldForm';
import WorkFlowSelector from './parts/_WorkFlowSelector';
import Pagination from "../../Pieces/Pagination";
import Modal from "../../Pieces/Modal";
import SplitTicket from "./_SplitTicket"
import {useFluentHelper, useNotify, useConfirm, wpHooks, useScrollToRef} from "@/admin/Composable/FluentFrameworkHelper";
import {computed, nextTick, onMounted, onBeforeUnmount, reactive, toRefs, watch, ref, unref} from "vue";
import {useRoute, useRouter} from "vue-router";

export default {
    name: 'ViewTicket',
    props: ['ticket_id'],
    components: {
        Modal,
        Pagination,
        CreateResponse,
        TicketSidebar,
        EditResponse,
        ActiveAgents,
        TicketTags,
        CustomFieldForm,
        WorkFlowSelector,
        SplitTicket,
        FluentBoardsIntegration
    },

    setup(props, _) {
        const { appVars, get, post, put, del, setTitle, has_pro, handleError, translate, } = useFluentHelper();
        const {notify} = useNotify();
        const {confirm} = useConfirm();
        const {doAction} = wpHooks();
        const router = useRouter();
        const route = useRoute();
        const state = reactive({
            loading: false,
            ticket: {},
            conversations: [],
            show_response_box: '',
            products: appVars.support_products,
            agents: appVars.support_agents,
            admin_priorities: appVars.admin_priorities,
            client_priorities: appVars.client_priorities,
            updating: false,
            deleting: false,
            active_agents: [],
            edit_response_modal: false,
            editing_response: false,
            showCustomDataEditForm: false,
            fluentcrm_profile: false,
            mailboxes: appVars.mailboxes,
            customer_tickets: [],
            show_merge_modal: false,
            show_watcher_modal: false,
            watchers: [],
            pagination: {
                current_page: 1,
                total: 0,
                per_page: 10
            },
            conversation_type: '',
            filteredWatchersIds: [],
            split_ticket_modal: false,
            split_ticket: {},
            ticket_statuses: appVars.changeable_ticket_statuses,
            close_ticket_silently: "no",
            app_ready: false,
            fetch_other_tickets: false,
            draftReplyPermission: false,
            draftReplyApprovePermission: false,
            draftResponse: {},
            showDraft: false,
            draftData: {},
            show_response_draft: false,
            tickets_to_merge:[],
            filteredMergeSelectedTickets:[],
            ticketNotFound: "",
            show_fbs_add_task_modal: false,
            watcherIds: [],
            ticketSummary: '',
            customerSentiment: '',
            ResponseLoader: false,
            apiKey: '',
            openAIIntegration: !!appVars.open_ai_integration,
            fluentBotIntegration: !!appVars.fluent_bot_integration,
            deleteTicketPermission: false,
            is_watching: false,
            watching_loading: false
        });

        watch(() => route.params.ticket_id, (ticketId) => {
            if (ticketId) {
                doAction('ticket_view_exit', state.ticket.id);
                state.ticket = {};
                nextTick(() => {
                    doAction('ticket_view_entered', ticketId);
                    fetchTicket();
                });
            }
        });

        const fetchTicket = async () => {
            state.loading = true;
            await get(`tickets/${props.ticket_id}`, {
                customer_id: state.ticket.customer_id,
                with_data: ['fluentcrm_profile']
            }).then(response => {
                if (appVars.enable_draft_mode === 'yes') {
                    fetchDraft();
                }
                state.loading = false;
                state.ticket = response.ticket;
                state.ticket.product_id = (state.ticket.product_id && state.ticket.product_id !== '0')
                    ? Number(state.ticket.product_id)
                    : null;
                setTitle(response.ticket.title);
                state.conversations = response.responses;
                state.draftReplyPermission = appVars.me.permissions.includes('fst_draft_reply');
                state.draftReplyApprovePermission = appVars.me.permissions.includes('fst_approve_draft_reply');
                state.deleteTicketPermission = appVars.me.permissions.includes('fst_delete_tickets');

                // sent the response to body event
                document.body.dispatchEvent(new CustomEvent('fs_ticket_viewed', {
                    detail: {
                        response: response
                    }
                }));

                if (appVars.fluentcrm_config) {
                    state.fluentcrm_profile = response.fluentcrm_profile;
                }

                if (has_pro) {
                    state.watchers = response.watchers;
                    state.filteredWatchersIds = response.watchers.map((watcher, key) => {
                        return watcher.tag_id;
                    });
                }

                // Check if current user is watching this ticket
                checkWatchStatus();

            }).catch(error => {
                state.loading = false;
                if (error.responseJSON) {
                    state.ticketNotFound = translate("No matching ticket was found for the provided ID.");
                }
            })
                .always(() => {
                    state.loading = false;
                });
        };

        const fetchDraft = async () => {
            try {
                const response = await get(`tickets/${props.ticket_id}/draft`);
                state.draftData = response.draft;

                if (state.draftData && state.draftData.value.content) {
                    state.draftResponse = state.draftData.value.content;
                    state.showDraft = true;
                } else {
                    state.draftResponse = null;
                    state.draft = null;
                }
            } catch (error) {
                handleError(error);
            }
        };

        const getTextByPerson = (conversation, ticket) => {
            if (conversation?.conversation_type === 'draft_response') {
                return translate('Draft Response');
            }

            if (conversation?.person.person_type === 'agent') {
                return (conversation.person.title ? conversation.person.title : translate('Support Staff'));
            }else{
                if (ticket.customer_id == conversation.person_id) {
                    return translate('Thread Starter')
                } else {
                    return translate('Thread Follower')
                }
            }
        }

        const getRibbonClass = (conversation, ticket) => {
            const classes = [
                'fs_thread_ribbon'
            ];

            if (conversation.person) {
                if (conversation.person.person_type === 'agent') {
                    classes.push('fs_thread_ribbon_agent');
                } else {
                    if (ticket.customer_id == conversation.person_id) {
                        classes.push('fs_thread_ribbon_customer');
                    } else {
                        classes.push('fs_thread_ribbon_customer_cc');
                    }
                }
            }

            if (conversation.conversation_type === 'draft_response') {
                classes.push('fs_thread_ribbon_draft_response');
            }

            return classes;
        }

        const getTicketClasses = (conversation, ticket) => {
            const classes = [
                'fs_thread'
            ];

            if (conversation.person) {
                if (conversation.person.person_type === 'agent') {
                    classes.push('fs_person_agent');
                    classes.push('fs_agent');
                }
                else {
                    if (ticket.customer_id == conversation.person_id) {
                        classes.push('fs_person_customer');
                        classes.push('fs_customer');
                    } else {
                        classes.push('fs_person_customer_cc');
                        classes.push('fs_cc_customer');
                    }
                }
            }

            classes.push('fs_conv_type_' + conversation.conversation_type);
            return classes;
        }

        const recordNewResponse = (response, data) => {
            state.conversations.unshift(response);
            state.ticket.status = data.ticket.status;
            state.show_response_box = '';

            each(data.update_data, (data, key) => {
                state.ticket[key] = data;
            });

            if (appVars.pref.go_back_after_reply === 'yes') {
                if (window.history.state.back) {
                 //   router.go(-1);
                }
            }
        }

        const updateTicketAttr = (propName) => {
            put(`tickets/${state.ticket.id}/property`, {
                prop_name: propName,
                prop_value: state.ticket[propName]
            })
                .then(response => {
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });

                    each(response.update_data, (data, key) => {
                        state.ticket[key] = data;
                    });
                    fetchTicket();
                })
                .catch((errors) => {
                    handleError(errors);
                });
        }

        const getHumanName = (person) => {
            if (parseInt(appVars.me?.id) === parseInt(person.id)) {
                return translate('You');
            }

            return person.full_name;
        }

        const closeTicket = () => {
            state.updating = true;
            post(`tickets/${state.ticket.id}/close`, {close_ticket_silently: state.close_ticket_silently})
                .then(response => {
                    state.ticket.status = response.ticket.status;
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    if (window.history.state.back) {
                        router.go(-1);
                    }
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.updating = false;
                });
        }

        const deleteTicket = () => {
            confirm({
                message: translate('single_ticket_delete_warning'),
                title: 'Warning',
                options: {
                    confirmButtonText: translate('Delete Ticket'),
                    cancelButtonText: translate('Cancel'),
                    type: 'warning',
                    confirmButtonClass: 'el-button--danger',
                }
            }).then(() => {
                state.deleting = true;
                del(`tickets/${state.ticket.id}/delete`)
                    .then(response => {
                        notify({
                            message: response.message,
                            type: "success",
                            position: "bottom-right",
                        });
                        router.push({name: 'tickets'});
                    })
            });
        }

        const approveDraftResponse = (conversation) => {

            put(`tickets/${conversation.ticket_id}/approve_draft_response/${conversation.id}`, {
                content: conversation.content
            })
                .then(response => {
                    notify({
                        message: response.message,
                        type: 'success'
                    });
                    fetchTicket();
                })
                .catch((errors) => {
                    handleError(errors);
                })

        }

        const reOpen = () => {
            state.updating = true;
            post(`tickets/${state.ticket.id}/re-open`)
                .then(response => {
                    state.ticket.status = response.ticket.status;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.updating = false;
                });
        }

        const onActivityChange = (items) => {
            const personIds = [];
            items.forEach((item) => {
                personIds.push(item.val());
            });
            state.active_agents = personIds;
        }

        const onTicketDataChange = (item) => {
            let data = item.val();
            state.ticket[data.type] = data.data;
        }

        const handleResponseActionCommand = (data) => {
            const actionType = data.type;
            const conversation = data.conversation;

            if (actionType == 'delete') {
                confirm({
                    message: translate('response_delete_warning'),
                    title: 'Warning',
                    options: {
                        confirmButtonText: translate('Delete Response'),
                        cancelButtonText: translate('Cancel'),
                        type: 'warning'
                    }
                }).then(() => {
                    del(`tickets/${state.ticket.id}/responses/${conversation.id}`)
                        .then(response => {
                            notify({
                                message: response.message,
                                type: "success",
                                position: "bottom-right",
                            });
                            fetchTicket();
                        })
                        .catch((error) => {
                            handleError(error);
                        });
                });
            } else if (actionType === 'edit') {
                state.editing_response = conversation;
                state.edit_response_modal = true;
                state.conversation_type = conversation.conversation_type;
            } else if (actionType === 'split_ticket') {
                state.split_ticket = {
                    conversation_id: conversation.id,
                    content: conversation.content,
                    customer_id: parseInt(state.ticket.customer_id),
                    mailbox_id: parseInt(state.ticket.mailbox_id),
                    product_id: state.ticket.product?.id,
                    client_priority: state.ticket.priority,
                    create_wp_user: 'no',
                    create_customer: 'no',
                };
                state.split_ticket_modal = true;
            }
        }

        const afterCreatedTask = () => {
            state.show_fbs_add_task_modal = false;
            fetchTicket();
        }

        const changeMailbox = (mailbox) => {
            state.loading = !state.loading;
            put(`mailboxes/${state.ticket.mailbox_id}/move_tickets`, {
                new_box_id: mailbox,
                ticket_ids: [props.ticket_id],
                move_type: 'Custom',
            })
                .then(response => {
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    fetchTicket();
                })
                .catch((error) => {
                    handleError(error);
                })
                .always(() => {
                    state.loading = false;
                });
        }

        const customerTickets = () => {
            get('tickets/customer_tickets/' + state.ticket.customer_id, {
                exclude_ticket_id: props.ticket_id,
                page: state.pagination.current_page,
                per_page: state.pagination.per_page,
            })
                .then(response => {
                    state.app_ready = true;
                    state.customer_tickets = response.tickets.data;
                    state.pagination.total = response.tickets.total;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.loading = false;
                });
        }
        //ticket merge

        const handleMergeSelectionChange = (selected) => {
            state.tickets_to_merge = selected;
        }
        // Get ids from 'tickets_to_merge' array when it changes and store in 'selectedTickets' array
        watch(() => state.tickets_to_merge, (selected) => {
            state.filteredMergeSelectedTickets = selected.map((ticket) => {
                return parseInt(ticket.id);
            });
        });

        const mergeTickets = () => {
            confirm({
                message: translate('Are you sure you want to merge these tickets?'),
                title: translate('Merge Tickets'),
                type: 'Warning',
                options: {
                    confirmButtonText: translate('Merge'),
                    cancelButtonText: translate('Cancel'),
                    type: 'warning'
                }
            }).then(() => {
                state.loading = true;
                post('tickets/' + props.ticket_id + '/merge_tickets', {
                    ticket_to_merge: state.filteredMergeSelectedTickets,
                })
                    .then(response => {
                        notify({
                            message: response.message,
                            type: "success",
                            position: "bottom-right",
                        });
                        customerTickets();
                        fetchTicket();
                        state.fetch_other_tickets = !state.fetch_other_tickets;
                    })
                    .catch((error) => {
                        handleError(error);
                    })
                    .always(() => {
                        state.loading = false;
                    });
            });
        }

        const santizeContent = (content) => {
            if (!content) {
                return content;
            }

            content = content.replace(/\n\s*\n/g, '\n').replace(/\n\s*\n/g, '\n');
            // check if this is type of string
            if (typeof content !== 'string') {
                return content;
            }

            const tagRegex = /<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>|<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
            const config = {
                ADD_ATTR: ['target'],
            };

            return window.DOMPurify.sanitize(content, config);
        }

        const syncCustomData = (data) => {
            state.ticket.custom_fields = data;
            state.showCustomDataEditForm = false;
        }

        const addWatchers = () => {
            state.saving = true;
            post(`tickets/${state.ticket.id}/sync-watchers`, {
                watchers: state.watcherIds
            })
                .then(response => {
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    fetchTicket()
                    state.show_watcher_modal = false;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.saving = false;
                });
        }

        const checkWatchStatus = () => {
            get(`tickets/${state.ticket.id}/watch/status`)
                .then(response => {
                    state.is_watching = response.is_watching || false;
                })
                .catch(() => {
                    state.is_watching = false;
                });
        }

        const toggleWatch = () => {
            state.watching_loading = true;

            if (state.is_watching) {
                // Unwatch
                del(`tickets/${state.ticket.id}/watch/${appVars.me.id}`)
                    .then(response => {
                        state.is_watching = false;
                        notify({
                            message: translate('You are no longer watching this ticket'),
                            type: "success",
                            position: "bottom-right",
                        });
                    })
                    .catch((errors) => {
                        handleError(errors);
                    })
                    .always(() => {
                        state.watching_loading = false;
                    });
            } else {
                // Watch
                post(`tickets/${state.ticket.id}/watch`, {
                    watchers: [appVars.me.id]
                })
                    .then(response => {
                        state.is_watching = true;
                        notify({
                            message: translate('You are now watching this ticket'),
                            type: "success",
                            position: "bottom-right",
                        });
                    })
                    .catch((errors) => {
                        handleError(errors);
                    })
                    .always(() => {
                        state.watching_loading = false;
                    });
            }
        }

        const splitToNewTicket = () => {
            state.loading = true;
            post('tickets/' + state.ticket.id + '/split_ticket', {
                split_ticket: state.split_ticket,
            })
                .then(response => {
                    notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    customerTickets();
                    fetchTicket();
                    state.split_ticket_modal = false;
                })
                .catch((error) => {
                    handleError(error);
                })
                .always(() => {
                    state.loading = false;
                });
        }

        const discardDraft = (draftID) => {
            del('tickets/' + draftID + '/draft')
                .then(response => {
                    state.draftData = null;
                    state.draftResponse = null;
                    state.showDraft = false;
                })
                .catch((error) => {
                    handleError(error);
                })
        }

        const getArrToString = (arr) => {
            if (!arr) {
                return '';
            }
            return arr.join(',');
        }

        const getStatusDisplayName = (status) => {
            if (isEmpty(state.ticket_statuses)) {
                return status;
            }

            for (const key in state.ticket_statuses) {
                if (state.ticket_statuses[key].includes(status)) {
                    return key;
                }
            }

            return status;
        };

        const getTicketStatus = computed(() => {
            const status = {};

            for (let key in state.ticket_statuses) {
                if(state.ticket_statuses[key].length){
                    status[key] = state.ticket_statuses[key][0];
                }
            }

            return status;
        });

        const closeAIResponse = () => {
            state.ticketSummary = '';
            state.customerSentiment = '';
        }

        const getTicketSummary = () => {
            state.ResponseLoader = true;
            post(`openai/${props.ticket_id}/get-ticket-summary`, {
                type: 'ticketSummary'
            })
                .then(response => {
                    state.ticketSummary = response;
                    state.customerSentiment = '';
                    state.ResponseLoader = false;
                })
                .catch((errors) => {
                    state.ResponseLoader = false;
                    handleError(errors);
                })
        }

        const getCustomerSentiment = () => {
            state.ResponseLoader = true;
            post(`openai/${props.ticket_id}/get-ticket-tone`, {
                type: 'ticketTone'
            })
                .then(response => {
                    let sentimentWithEmoji = response;
                    if (response.includes('Positive')) {
                        sentimentWithEmoji = '😀 Positive';
                    } else if (response.includes('Neutral')) {
                        sentimentWithEmoji = '😐 Neutral';
                    } else if (response.includes('Negative')) {
                        sentimentWithEmoji = '😡 Negative ';
                    }

                    state.customerSentiment = sentimentWithEmoji;
                    state.ticketSummary= '';
                    state.ResponseLoader = false;
                })
                .catch((errors) => {
                    handleError(errors);
                })
        }

        const conversationRefs = ref({});

        const setRef = (id, el) => {
            if (el) {
                conversationRefs.value[id] = el;
            }
        };

        // Check if current agent has replied to this ticket
        const currentAgentHasReplied = computed(() => {
            if (!state.conversations || !state.conversations.length || !appVars.me) {
                return false;
            }

            // Check if current agent has any response in conversations
            return state.conversations.some(conversation => {
                return conversation.person &&
                       conversation.person.person_type === 'agent' &&
                       conversation.person.id === appVars.me.id &&
                       conversation.conversation_type === 'response';
            });
        });

        const handleKeydown = (event) => {
            const { metaKey, altKey, code } = event;

            if (metaKey && altKey && code === 'KeyI') {
                return;
            }

            if (metaKey && altKey) {
                event.preventDefault();

                const keyActions = {
                    KeyR: () => {
                        let responseType = state.draftReplyPermission ? 'draft_response' : 'response';
                        state.show_response_box = (state.show_response_box === responseType) ? '' : responseType;
                    },
                    KeyN: () => {
                        state.show_response_box = (state.show_response_box === 'note') ? '' : 'note';
                    },
                    KeyQ: () => fetchTicket(),
                    KeyM: () => {
                        if (has_pro) {
                            customerTickets();
                            state.show_merge_modal = !state.show_merge_modal;
                            state.app_ready = true;
                        }

                    },
                    KeyB: () => {
                        if (has_pro) {
                            state.show_watcher_modal = !state.show_watcher_modal;
                        }
                    },
                };

                const action = keyActions[code];
                if (action) {
                    action();
                }
            }
        };

        const scrollToHash = async () => {
            const fullHash = window.location.hash;
            const hash = fullHash.split('#').pop();


            if (hash && conversationRefs.value[hash]) {
                const ref = conversationRefs.value[hash];
                return useScrollToRef(ref);
            }
        };

        onMounted(async () => {
            await fetchTicket();
            doAction('ticket_view_entered', props.ticket_id);

            await scrollToHash();
            window.addEventListener("hashchange", scrollToHash);
            if(appVars.keyboard_shortcuts === 'yes') {
                window.addEventListener("keydown", handleKeydown);
            }
        });

        onBeforeUnmount(() => {
            doAction('ticket_view_exit', props.ticket_id);
            document.body.dispatchEvent(new CustomEvent('fs_ticket_view_exit', {
                detail: {
                    ticket_id: props.ticket_id
                }
            }));
            if(appVars.keyboard_shortcuts === 'yes') {
                window.removeEventListener("keydown", handleKeydown);
            }
        });

        return {
            ...toRefs(state),
            setTitle,
            has_pro,
            appVars,
            handleError,
            fetchTicket,
            getTicketClasses,
            isEmpty,
            isArray,
            recordNewResponse,
            updateTicketAttr,
            getHumanName,
            closeTicket,
            reOpen,
            onActivityChange,
            onTicketDataChange,
            handleResponseActionCommand,
            changeMailbox,
            customerTickets,
            mergeTickets,
            santizeContent,
            syncCustomData,
            addWatchers,
            checkWatchStatus,
            toggleWatch,
            splitToNewTicket,
            fetchDraft,
            discardDraft,
            getTicketStatus,
            translate,
            getArrToString,
            handleMergeSelectionChange,
            deleteTicket,
            getRibbonClass,
            getTextByPerson,
            approveDraftResponse,
            getStatusDisplayName,
            afterCreatedTask,
            getTicketSummary,
            getCustomerSentiment,
            closeAIResponse,
            setRef,
            currentAgentHasReplied
        }
    }
}
</script>

<style>
.agent_title {
    content: '';
    position: relative;
    left: 0;
    top: 0;
    background: #1785EB;
    color: #fff;
    padding: 5px 10px;
    font-size: 11px;
}
.fs_agent {
    border-left: 4px solid #1785EB;
}
.fs_customer{
    border-left: 4px solid #15BE7C;
}
.fs_cc_customer {
    border-left: 4px solid #EC5c03;
}
.fs_conv_type_note {
    border-left: 0px solid #e6a23c;
}
i.dashicons.dashicons-randomize {
    transform: rotate(90deg);
}
.carrier_info {
    padding: 5px 0;
    font-size: 12px;
    color: #a5b3be;
    font-weight: 400;
    line-height: 1.4;
}
.fs_view_ticket .fs_ticket_body .fs_thread_wrap .fs_thread_title .carrier_info strong {
    font-size: 13px;
    color: #6f7b87;
}
.fs_conv_type_draft_response {
    background: #fef4e6 ;
    border-left: 4px solid #F58E07;
}
.fs_thread_ribbon_draft_response {
    background: #F58E07 !important;
}
.fs_ticket_error_message {
    font-size: 13px;
    color: #6f7b87;
    width: 100%
}
.fs_saved_draft {
    border-top: 1px solid #e5e9ec;
    border-left: 4px solid #6e5d519e;
}
.draft_title {
    content: '';
    position: relative;
    left: 0;
    top: 0;
    background: #6e5d519e;
    color: #fff;
    padding: 5px 10px;
    font-size: 11px;
}
.fs_tk_merge_actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 25px;
}
.fs_thread_ribbon{
    position: relative;
    left: 0;
    top: 0;
    color: #fff;
    font-size: 10px;
    padding: 5px 10px;
}
.fs_thread_ribbon_agent{
    background: #1785EB;
}
.fs_thread_ribbon_customer{
    background: #15BE7C;
}
.fs_thread_ribbon_customer_cc{
    background: #EC5c03;
}
.error-container {
    text-align: center;
    background-color: #ffffff;
    padding: 20px;

}
.error-container h1{
    color: #616669;
}
.fs_ticket_not_found_svg {
    margin-left: 100px;
}

.fs_ai_response_loading {
    border: 1px solid #E1E4EA;
    border-radius: 12px;
    padding: 20px;
    margin: 20px;
}

.fs_ai_intelligent_button {
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    background-color: #0e121b;
    display: flex;
    max-width: 36px;
    padding: 4px 6px;
    height: 33px;
}

.fs_ai_intelligent_button:hover {
    background-color: #353a43;
}

.fs_ai_intelligent_button:focus {
    background-color: #171d26;
}

.fs_ai_intelligent_button img {
    width: 20px;
    height: 20px;
    object-fit: cover;
}

</style>
