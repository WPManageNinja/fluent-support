<template>
    <div class="fs_view_ticket">
        <template v-if="ticket && ticket.id">
            <div class="fs_ticket_body">
                <!-- New Secondary Top Bar Design -->
                <!-- Action Buttons Teleported to Top Bar -->
                <Teleport to="#fs_top_bar_actions_slot">
                    <div class="fs_action_buttons">
                        <!-- Refresh Button -->
                        <el-tooltip effect="dark" :content="$t('Refresh')" placement="top">
                            <el-button
                                class="fs_refresh_btn fs_outline_btn"
                                :title="$t('Refresh')"
                                @click="fetchTicket()"
                                :disabled="loading"
                                v-loading="loading"
                            >
                                <img :src="appVars.asset_url + 'images/refresh.svg'" alt="">
                            </el-button>
                        </el-tooltip>

                        <!-- Bookmark/Watcher Button -->
                        <el-tooltip v-if="!watchers.length && has_pro" effect="dark" :content="$t('Add Bookmark')" placement="top">
                            <el-button
                                class="fs_outline_btn"
                                :title="$t('Add Bookmark')"
                                @click="(show_watcher_modal=true)"
                            >
                                <img :src="appVars.asset_url + 'images/bookMark.svg'" alt="">
                            </el-button>
                        </el-tooltip>

                        <!-- Support Agent Button -->
                        <el-tooltip v-if="canAssignAgents" effect="dark" :content="$t('Assign Agent')" placement="top" trigger="hover">
                            <span class="fs_agent_selector_tooltip">
                                <el-popover
                                    placement="bottom"
                                    :width="300"
                                    trigger="click"
                                    popper-class="fs_popover"
                                    @show="onAgentPopoverShow"
                                >
                                    <template #reference>

                                            <el-button
                                                class="fs_outline_btn"
                                                :class="ticket.agent?.photo ? 'fs_agent_selector_btn' : ''"
                                            >
                                                <div v-if="ticket.agent?.photo" class="fs_agent_selector_display">
                                                    <img
                                                        :src="ticket.agent.photo"
                                                        :alt="ticket.agent.full_name"
                                                        class="fs_agent_avatar_img"
                                                    />
                                                    <span class="fs_agent_name_display">
                                                        {{ ticket.agent?.full_name || $t('Unassigned') }}
                                                        <span v-if="assignedAgentGroupName" class="fs_agent_group_label">({{ assignedAgentGroupName }})</span>
                                                    </span>
                                                    <el-icon class="fs_agent_dropdown_arrow"><ArrowDown /></el-icon>
                                                </div>
                                                <img
                                                    v-else
                                                    :src="appVars.asset_url + 'images/agenIconFill.svg'"
                                                    alt=""
                                                    class="fs_agent_icon_placeholder"
                                                />
                                            </el-button>
                                    </template>
                                    <div class="fs_agent_selector">
                                        <el-select
                                            ref="agentSelect"
                                            v-model="ticket.agent_id"
                                            @change="updateTicketAttr('agent_id')"
                                            filterable
                                            class="fs_select_field fs_agent_selector_dropdown"
                                            popper-class="fs_agent_group_popper"
                                            :placeholder="$t('Select Agent')"
                                        >
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
                                                    v-for="agent in agents"
                                                    :key="agent.id"
                                                    :value="agent.id"
                                                    :label="agent.full_name"
                                                />
                                            </template>
                                        </el-select>
                                    </div>
                                </el-popover>
                            </span>
                        </el-tooltip>
                        <!-- Workflow Button -->
                        <el-tooltip v-if="appVars.manual_workflows && appVars.manual_workflows.length" effect="dark" :content="$t('Run Workflow')" placement="top" trigger="hover">
                            <work-flow-selector
                                @reloadTickets="fetchTicket()"
                                :ticket_ids="[ticket_id]">
                                <el-button
                                    class="fs_outline_btn"
                                    :title="$t('Run Workflow')"
                                >
                                    <img :src="appVars.asset_url + 'images/workFlow.svg'" alt="">
                                </el-button>
                            </work-flow-selector>
                        </el-tooltip>

                        <!-- Close/Reopen Ticket Button -->
                        <el-tooltip v-if="canManageTickets" effect="dark" :content="$t('Close Ticket')" placement="top" trigger="hover">
                            <el-button
                                class="fs_outline_btn fs_action_btn_with_text fs_close_ticket_btn"
                                @click="ticket.status === 'closed' ? reOpen() : closeTicket()"
                                :disabled="updating"
                                v-loading="updating"
                            >
                                <el-icon v-if="ticket.status !== 'closed'"><Check /></el-icon>
                                <el-icon v-if="ticket.status === 'closed'"><RefreshRight /></el-icon>
                                <span>{{ ticket.status === 'closed' ? $t('Re-open') : $t('Close Ticket') }}</span>
                            </el-button>
                        </el-tooltip>

                        <!-- More Actions Button -->
                        <el-tooltip v-if="canManageTickets" effect="dark" :content="$t('More Actions')" placement="top" trigger="hover">
                            <el-dropdown class="fs_more_action" trigger="click" @command="handleTopBarCommand">
                                <el-button class="fs_outline_btn" :title="$t('More Actions')">
                                    <el-icon style="transform: rotate(90deg);"><More /></el-icon>
                                </el-button>
                                <template #dropdown>
                                    <el-dropdown-menu class="fs_global_dropdown fs_table_more_actions_dropdown_menu">
                                        <div v-if="has_pro">
                                            <el-dropdown-item command="merge">
                                                <svg width="14" style="margin-right: 4px;" height="14" viewBox="0 0 14 14"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M4.14447 5.12748C4.24566 5.4978 4.46577 5.8246 4.77092 6.05754C5.07606 6.29048 5.44932 6.41666 5.83322 6.41665H8.16655C8.85374 6.4167 9.51884 6.65933 10.0446 7.10178C10.5704 7.54423 10.9232 8.15808 11.0406 8.83515C11.4365 8.96431 11.7734 9.23039 11.9908 9.5856C12.2082 9.9408 12.2918 10.3619 12.2267 10.7732C12.1616 11.1845 11.9519 11.5591 11.6355 11.8298C11.319 12.1005 10.9163 12.2495 10.4999 12.25C10.0924 12.2503 9.69767 12.1084 9.38362 11.8489C9.06957 11.5893 8.85594 11.2283 8.77956 10.828C8.70318 10.4278 8.76884 10.0135 8.96522 9.65651C9.1616 9.29952 9.47639 9.02224 9.85531 8.87248C9.75412 8.50216 9.534 8.17537 9.22886 7.94242C8.92371 7.70948 8.55045 7.5833 8.16655 7.58331H5.83322C5.20199 7.58424 4.58765 7.37946 4.08322 6.99998V8.84915C4.47259 8.98676 4.80078 9.2576 5.00976 9.61378C5.21875 9.96997 5.29509 10.3886 5.22528 10.7956C5.15547 11.2026 4.94401 11.5719 4.62827 11.8381C4.31254 12.1043 3.91286 12.2503 3.49989 12.2503C3.08691 12.2503 2.68724 12.1043 2.3715 11.8381C2.05577 11.5719 1.84431 11.2026 1.7745 10.7956C1.70469 10.3886 1.78102 9.96997 1.99001 9.61378C2.199 9.2576 2.52718 8.98676 2.91655 8.84915V5.15081C2.52979 5.01426 2.20325 4.74624 1.99393 4.39351C1.7846 4.04079 1.70577 3.62576 1.7712 3.22085C1.83663 2.81594 2.04216 2.44686 2.35192 2.17801C2.66169 1.90916 3.05602 1.75762 3.46611 1.74983C3.8762 1.74204 4.276 1.87849 4.59576 2.13537C4.91551 2.39226 5.13491 2.75326 5.21568 3.15539C5.29644 3.55753 5.23344 3.97525 5.03766 4.33567C4.84189 4.6961 4.52577 4.97633 4.14447 5.12748V5.12748ZM3.49989 4.08331C3.6546 4.08331 3.80297 4.02186 3.91237 3.91246C4.02176 3.80306 4.08322 3.65469 4.08322 3.49998C4.08322 3.34527 4.02176 3.1969 3.91237 3.0875C3.80297 2.97811 3.6546 2.91665 3.49989 2.91665C3.34518 2.91665 3.1968 2.97811 3.08741 3.0875C2.97801 3.1969 2.91655 3.34527 2.91655 3.49998C2.91655 3.65469 2.97801 3.80306 3.08741 3.91246C3.1968 4.02186 3.34518 4.08331 3.49989 4.08331ZM3.49989 11.0833C3.6546 11.0833 3.80297 11.0219 3.91237 10.9125C4.02176 10.8031 4.08322 10.6547 4.08322 10.5C4.08322 10.3453 4.02176 10.1969 3.91237 10.0875C3.80297 9.9781 3.6546 9.91665 3.49989 9.91665C3.34518 9.91665 3.1968 9.9781 3.08741 10.0875C2.97801 10.1969 2.91655 10.3453 2.91655 10.5C2.91655 10.6547 2.97801 10.8031 3.08741 10.9125C3.1968 11.0219 3.34518 11.0833 3.49989 11.0833ZM10.4999 11.0833C10.6546 11.0833 10.803 11.0219 10.9124 10.9125C11.0218 10.8031 11.0832 10.6547 11.0832 10.5C11.0832 10.3453 11.0218 10.1969 10.9124 10.0875C10.803 9.9781 10.6546 9.91665 10.4999 9.91665C10.3452 9.91665 10.1968 9.9781 10.0874 10.0875C9.97801 10.1969 9.91655 10.3453 9.91655 10.5C9.91655 10.6547 9.97801 10.8031 10.0874 10.9125C10.1968 11.0219 10.3452 11.0833 10.4999 11.0833Z"
                                                        fill="currentColor"/>
                                                </svg>
                                                {{ $t('Merge Ticket') }}
                                            </el-dropdown-item>
                                            <el-dropdown-item command="close_silently">
                                                <el-icon>
                                                    <MuteNotification/>
                                                </el-icon>
                                                {{ $t('Close Ticket Silently') }}
                                            </el-dropdown-item>
                                        </div>
                                        <el-dropdown-item v-if="appVars.fluent_boards" command="fluent_boards">
                                            <img :src="appVars.asset_url + '/images/addTask.svg'" class="fs_add_task_logo">
                                            {{ $t('Add Task to Fluent Boards') }}
                                        </el-dropdown-item>
                                        <el-dropdown-item command="delete">
                                            <el-icon><Delete /></el-icon>
                                            {{ $t('Delete Ticket') }}
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </el-tooltip>
                    </div>
                </Teleport>

                <!-- New Title Container Design -->
                <div class="fs_title_container">
                    <div class="fs_title_content">
                        <!-- Editable Title -->
                        <div class="fs_title_row">
                            <h2 class="fs_ticket_title">
                                <el-popover
                                    v-if="canManageTickets"
                                    placement="bottom-start"
                                    :width="popoverWidth"
                                    trigger="click"
                                >
                                    <template #reference>
                                        <span :title="$t('Click to Edit Subject')">{{ ticket?.title }}</span>
                                    </template>
                                    <el-input @keyup.enter="updateTicketAttr('title')"
                                              v-model="ticket.title" class="fs_text_input"></el-input>
                                    <p style="margin-top: 8px; font-size: 12px; color: #525866;">{{ $t('Press enter to save') }}</p>
                                </el-popover>
                                <span v-else>{{ ticket?.title }}</span>
                            </h2>
<!--                            <ticket-tags :creatable="true" :ticket_id="ticket.id" :tags.sync="ticket.tags"/>-->
                        </div>

                        <!-- Info Container: Timer + Status Badge -->
                        <div class="fs_info_container">
                            <!-- Timer Info -->
                            <el-tooltip v-if="ticket.waiting_since" effect="dark" :content="$t('Waiting Time') + ': ' + ticket.waiting_since" placement="top" trigger="hover">
                            <div class="fs_timer_info">
                                <el-icon class="fs_timer_icon"><Timer /></el-icon>
                                <span class="fs_timer_text">{{ $t('Waiting') }}: {{ $timeDiff(ticket.waiting_since) }}</span>
                            </div>
                            </el-tooltip>
                            <!-- Separator Dot -->
                            <span v-if="ticket.waiting_since" class="fs_info_separator">∙</span>

                            <!-- Status Badge -->
                            <el-popover
                                placement="bottom"
                                :width="300"
                                trigger="click"
                                v-if="canManageTickets && has_pro && !isEmpty(ticket_statuses)"
                            >
                                <template #reference>
                                    <div class="fs_ticket_status">
                                        <span class="fs_status_badge" :class="`fs_status_${ticket.status?.toLowerCase()}`">
                                            {{ appVars.ticket_statuses[ticket.status] || ticket.status }}
                                        </span>
                                    </div>
                                </template>
                                <el-select class="fs_select_field" :teleported="false" :disabled="updating" @change="handleStatusChange" :model-value="ticket.status" size="default">
                                    <el-option
                                        v-for="(status, statusKey) in getTicketStatus"
                                        :key="statusKey"
                                        :value="statusKey"
                                        :label="appVars.ticket_statuses[statusKey] || statusKey">{{ appVars.ticket_statuses[statusKey] || statusKey }}
                                    </el-option>
                                </el-select>
                            </el-popover>
                            <div v-else class="fs_ticket_status">
                                <span class="fs_status_badge" :class="`fs_status_${ticket.status?.toLowerCase()}`">
                                    {{ appVars.ticket_statuses[ticket.status] || ticket.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <el-dialog
                    v-model="show_merge_modal"
                    v-if="has_pro && show_merge_modal"
                    :title="$t('Merge Tickets')"
                    :append-to-body="true"
                    class="fs_dialog fs_dialog_merge_ticket"
                >
                    <div class="fs_box_body" v-if="customer_tickets.length && app_ready">
                        <el-table
                            :data="customer_tickets"
                            style="width: 100%"
                            @selection-change="handleMergeSelectionChange"
                        >
                            <el-table-column type="selection" width="55" />
                            <el-table-column prop="id" :label="$t('ID')" width="130"></el-table-column>
                            <el-table-column prop="title" :label="$t('Title')" width="250"></el-table-column>
                            <el-table-column prop="status" :label="$t('Status')" width="130"></el-table-column>
                        </el-table>
                        <div class="fs_tk_merge_actions">
                            <div style="padding-bottom: 20px;" class="fs_pagination_wrapper" v-if="customer_tickets.length">
                                <pagination @fetch="customerTickets" :pagination="pagination"/>
                            </div>
                            <el-button class="fs_filled_btn" size="default" type="primary"
                                       @click="mergeTickets()">
                                {{ $t('Merge') }}
                            </el-button>
                        </div>
                    </div>
                    <div class="fs_box_body" v-else-if="!customer_tickets.length && app_ready">
                        <h3>{{ $t('customer_has_one_tk') }}</h3>
                    </div>
                    <div class="fs_box_body fs_skeleton_loader" v-else>
                        <el-skeleton :rows="5" animated/>
                    </div>
                </el-dialog>

                <el-dialog
                    v-model="show_watcher_modal"
                    v-if="has_pro && show_watcher_modal"
                    :title="$t('Add Bookmark')"
                    :append-to-body="true"
                    class="fs_dialog"
                >
                    <div class="fs_box_body">
                        <el-select class="fs_select_field" filterable :clearable="true" :multiple="true" v-model="watcherIds">
                            <el-option
                                v-for="(agent,agent_key) in agents"
                                :key="agent_key" :value="agent.id"
                                :label="agent.full_name"></el-option>
                        </el-select>

                        <el-button class="fs_filled_btn fs_bookmark_action_btn"
                                    @click="addWatchers">{{ $t('Add') }}
                        </el-button>
                    </div>
                </el-dialog>

                <!-- New Ticket Header Design -->
                <div class="fs_ticket_header">
                    <div class="fs_header_container">
                        <!-- Left: Action Group (Reply & Notes Buttons) -->
                        <div class="fs_action_group">
                            <el-button
                                v-if="ticket.status != 'closed' && (canManageTickets || draftReplyPermission)"
                                class="fs_outline_btn"
                                :class="{ 'fs_header_btn_active': isReplyResponseBoxOpen }"
                                @click="toggleReplyResponseBox"
                            >
                                <IconPack :iconKey="isReplyResponseBoxOpen ? 'cancel' : 'total_replies'" :width="20" :height="20" />

                                {{ isReplyResponseBoxOpen ? $t('Cancel Reply') : $t('Reply') }}
                            </el-button>
                            <el-button
                                v-if="canManageTickets"
                                class="fs_outline_btn"
                                :class="{ 'fs_header_btn_active': isNoteResponseBoxOpen }"
                                @click="toggleNoteResponseBox"
                            >
                                <IconPack v-if="isNoteResponseBoxOpen" iconKey="cancel" :width="20" :height="20" />
                                <img v-else class="fs_header_icon" :src="appVars.asset_url + 'images/draftLineIcon.svg'" alt="">

                                {{ isNoteResponseBoxOpen ? $t('Cancel Notes') : $t('Notes') }}
                            </el-button>
                        </div>

                        <!-- Right: Business Inbox, Product, Workflow -->
                        <div class="fs_header_right_group">
                            <!-- Business Inbox Dropdown -->
                            <el-tooltip effect="dark" :content="$t('Business Inbox')" placement="top" trigger="hover">
                                <span >
                                    <el-dropdown v-if="appVars.me.permissions.includes('fst_manage_settings') && mailboxes && mailboxes.length > 1" trigger="click" @command="updateTicketAttr('mailbox_id', $event)">
                                                <el-button class="fs_outline_btn fs_header_dropdown">
                                                    <el-icon class="fs_header_icon"><OfficeBuilding /></el-icon>
                                                    <span class="fs_header_dropdown_text">{{ getMailboxName(ticket.mailbox_id) }}</span>
                                                    <el-icon class="fs_header_icon"><ArrowDown /></el-icon>
                                                </el-button>
                                        <template #dropdown>
                                            <el-dropdown-menu class="fs_global_dropdown">
                                                <el-dropdown-item
                                                    v-for="mailbox in mailboxes"
                                                    :key="mailbox.id"
                                                    :command="mailbox.id"
                                                    :disabled="ticket.mailbox_id == mailbox.id"
                                                >
                                                    {{ mailbox.name }}
                                                </el-dropdown-item>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                </span>
                            </el-tooltip>

                            <!-- Product Button -->
                            <el-tooltip effect="dark" :content="$t('Product')" placement="top" trigger="hover">
                                <span>
                                    <el-dropdown v-if="products && products.length" trigger="click" @command="updateTicketAttr('product_id', $event)" @visible-change="onProductDropdownToggle">
                                        <el-button class="fs_outline_btn" :class="{ 'fs_header_dropdown': ticket.product_id }">
                                            <img class="fs_header_icon" :src="appVars.asset_url + 'images/productIcon.svg'" alt="">
                                            <span v-if="ticket.product_id" class="fs_header_dropdown_text">{{ getProductName(ticket.product_id) }}</span>
                                            <el-icon v-if="ticket.product_id" class="fs_header_icon"><ArrowDown /></el-icon>
                                        </el-button>
                                        <template #dropdown>
                                            <el-dropdown-menu class="fs_global_dropdown">
                                                <div v-if="products.length > 6" class="fs_dropdown_search" @click.stop>
                                                    <el-input
                                                        v-model="productSearchQuery"
                                                        :placeholder="$t('Search') + '...'"
                                                        clearable
                                                        size="small"
                                                        @click.stop
                                                    />
                                                </div>
                                                <el-dropdown-item
                                                    v-for="product in filteredHeaderProducts"
                                                    :key="product.id"
                                                    :command="product.id"
                                                    :disabled="ticket.product_id == product.id"
                                                >
                                                    {{ product.title }}
                                                </el-dropdown-item>
                                                <div v-if="!filteredHeaderProducts.length" class="fs_dropdown_no_results">
                                                    {{ $t('No products found') }}
                                                </div>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                </span>
                            </el-tooltip>


                            <el-tooltip effect="dark" :content="$t('AI Intelligence')" placement="top" trigger="hover">
                                <span>
                                    <el-dropdown v-if="openAIIntegration" trigger="click">
                                        <el-button class="fs_filled_btn fs_ai_btn">
                                            <img class="fs_header_icon" :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                                        </el-button>
                                        <template #dropdown>
                                            <el-dropdown-menu class="fs_global_dropdown">
                                                <el-dropdown-item @click="getTicketSummary">{{ $t('Ticket Summary') }}</el-dropdown-item>
                                                <el-dropdown-item @click="getCustomerSentiment">{{ $t('Customer Sentiment') }}</el-dropdown-item>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                </span>
                            </el-tooltip>
                        </div>
                    </div>
                </div>

                <div class="fs_intelligence" v-loading="ResponseLoader" v-if="!ResponseLoader">
                    <div class="fs_intelligence_card__result" v-if="ticketSummary">
                        <div class="fs_generated_summary">
                            <div class="fs_ai_response_actions">
                                <div class="fs_ai_regenerate">
                                    <el-button class="fs_outline_btn" @click="getTicketSummary">
                                        <img :src="appVars.asset_url + 'images/refresh.svg'" alt="">
                                        {{ $t('Regenerate') }}
                                    </el-button>
                                </div>
                                <div class="fs_ai_response_close">
                                    <el-button class="fs_ai_response_close_button" @click="closeAIResponse">
                                        <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                                    </el-button>
                                </div>
                            </div>
                            <div>
                                <strong>{{ $t('Ticket Summary') }}:</strong>
                                <p>{{ ticketSummary}}</p>
                            </div>
                        </div>

                    </div>
                    <div class="fs_intelligence_card__result" v-if="customerSentiment">
                        <div class="fs_customer_sentiment">
                            <div class="fs_ai_response_actions">
                                <div class="fs_ai_regenerate">
                                    <el-button class="fs_outline_btn" @click="getCustomerSentiment">
                                        <img :src="appVars.asset_url + 'images/refresh.svg'" alt="">
                                        {{ $t('Regenerate') }}
                                    </el-button>
                                </div>
                                <div class="fs_ai_response_close" >
                                    <el-button class="fs_ai_response_close_button" @click="closeAIResponse">
                                        <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                                    </el-button>
                                </div>
                            </div>
                            <div>
                                <div class="fs_customer_sentiment_status">
                                    <div class="fs_sentiment_emoji">{{ customerSentiment.emoji }}</div>
                                    <div class="fs_sentiment_label">{{ $t('Tone of customer') }}</div>
                                    <div class="fs_sentiment_text">{{ customerSentiment.text }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fs_ai_response_loading" v-if="ResponseLoader">
                    <el-skeleton :rows="4" animated />
                </div>

                <transition name="fs-response-box">
                    <div v-if="show_response_box" class="fs_response_box_transition">
                        <div class="fs_response_box_transition_inner">
                            <create-response
                                @created="recordNewResponse"
                                :ticket="ticket"
                                :is_agent="'yes'"
                                @close="show_response_box = ''"
                                :type="show_response_box"
                                :draft="draftData"
                                :openAIIntegration="openAIIntegration"
                                :fluentBotIntegration="fluentBotIntegration"
                                @discardDraft="discardDraft"
                                @toggleFluentBot="showFluentBotPanel = !showFluentBotPanel"
                            />
                        </div>
                    </div>
                </transition>
                <div class="fs_create_response text-align-center" v-if="ticket.status == 'closed'" :class="'fs_create_response_'+ticket.status">
                    <span>{{$t('Closed Ticket')}}</span>
                    <p>{{ $t('ticket_closed') }} {{ ticket.resolved_at }}
                        <span v-if="ticket.closed_by_person">
                            by <b>{{ getHumanName(ticket.closed_by_person) }}</b>
                        </span></p>
                    <el-button v-if="canManageTickets" v-loading="updating" :disabled="updating" @click="reOpen()" class="fs_outline_btn">
                        <img :src="appVars.asset_url + 'images/refresh.svg'" alt="">
                        {{ $t('Reopen This ticket') }}
                    </el-button>
                </div>

                <div v-if="ticket && ticket.id" class="fs_threads_container">

                    <article v-if="(appVars.enable_draft_mode === 'yes') && (showDraft && !show_response_box)" class="fs_saved_draft fs_conversation_message fs_msg_draft">
                        <div class="fs_message_container">
                            <!-- Avatar -->
                            <div class="fs_message_avatar">

                            </div>

                            <div class="fs_message_content">
                                <div class="fs_message_header">
                                    <div class="fs_message_info">
                                        <div class="fs_message_name_row">
                                            <div class="fs_badge_draft_response">
                                                {{ $t('Saved Draft') }}
                                            </div>
                                        </div>

                                        <div>
                                            <span class="fs_message_timestamp" v-if="draftData?.created_at" :title="$t('Draft created at ') + draftData.created_at">
                                                {{ $timeDiff(draftData.created_at) }}
                                            </span>
                                            <span class="fs_message_timestamp" v-else-if="draftData?.updated_at" :title="$t('Draft updated at ') + draftData.updated_at">
                                                {{ $timeDiff(draftData.updated_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div v-html="santizeContent(draftResponse)" class="fs_message_body"></div>

                                <div class="fs_draft_actions">
                                    <el-button class="fs_outline_btn" size="default" @click="show_response_box = draftReplyPermission ? 'draft_response' : 'response'">
                                        <el-icon><EditPen /></el-icon>
                                        {{ $t('Edit') }}
                                    </el-button>
                                    <el-button class="fs_outline_btn" size="default" @click="discardDraft(draftData.id)" text>
                                        {{ $t('Discard') }}
                                    </el-button>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article v-for="conversation in conversations"
                             :key="conversation.id"
                             :ref="el => setRef(conversation.id, el)"
                             :class="['fs_conversation_message', getConversationClass(conversation)]">

                        <!-- Message Container -->
                        <div class="fs_message_container">
                            <!-- Avatar -->
                            <div class="fs_message_avatar">
                                <img v-if="conversation.person" :src="conversation.person?.photo"
                                     :alt="conversation.person.full_name"/>
                            </div>

                            <!-- Message Content -->
                            <div class="fs_message_content">
                                <!-- Header: Name, Role, Badge, Timestamp, More Button -->
                                <div class="fs_message_header">
                                    <div class="fs_message_info">
                                        <!-- Name and Role -->
                                        <div class="fs_message_name_row">
                                            <span v-if="conversation.person" class="fs_message_name">{{
                                                getHumanName(conversation.person)
                                            }}</span>
                                            <el-tooltip v-if="conversation.person?.person_type === 'customer'" effect="dark" :content="$t('Customer')" placement="top">
                                                <span class="fs_role_badge fs_role_badge_customer">
                                                    <IconPack iconKey="user_icon" :width="16" :height="16" />
                                                </span>
                                            </el-tooltip>
                                            <el-tooltip v-else-if="conversation.person?.person_type === 'agent'" effect="dark" :content="$t('Agent')" placement="top">
                                                <span class="fs_role_badge fs_role_badge_agent">
                                                    <IconPack iconKey="agent_icon" :width="16" :height="16" />
                                                </span>
                                            </el-tooltip>

                                            <!-- Role Badge for Notes -->
                                            <span v-if="conversation.conversation_type === 'note'" class="fs_message_label fs_label_internal_info">
                                                {{ $t('Added Note') }}
                                            </span>

                                            <span v-if="conversation.conversation_type === 'internal_info'" class="fs_message_label fs_label_internal_info">
                                                {{ $t('Added Internal Info') }}
                                            </span>


                                            <!-- Badge for Draft Response or Reply for Review -->
                                            <div v-if="conversation.conversation_type === 'draft_response'" class="fs_badge_draft_response">
                                                {{ $t('DRAFT RESPONSE') }}
                                            </div>
                                        </div>

                                        <!-- Timestamp -->
                                        <div>
                                            <span class="fs_message_timestamp" :title="$t('Conversation created at ') + conversation.created_at">
                                                {{ $timeDiff(conversation.created_at) }}
                                            </span>

                                            <!-- Carbon Copy Info -->
                                            <div v-if="ticket.carbon_copy" class="fs_carbon_copy_info">
                                                <span><strong>Cc: </strong>{{ ticket.carbon_copy }}</span>
                                            </div>
                                        </div>

                                        <div class="carrier_info" v-if="conversation.person?.person_type == 'customer' || conversation.cc_info?.length">
                                            <div class="from_info" v-if="conversation.person.person_type == 'customer'">
                                                <span><strong>From: </strong>{{ conversation.person?.full_name }}&lt;{{ conversation.person?.email }}&gt;</span>
                                            </div>
                                            <div class="fs_cc_info" v-if="conversation.cc_info?.length">
                                                <span><strong>Cc: </strong>{{ getArrToString(conversation.cc_info) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- More Actions Button -->

                                    <div class="fs_conversation_right_side">
                                        <div>
                                            <span v-if="conversation.source" class="fs_ticket_source">
                                                <img :src="appVars.asset_url + 'images/source.svg'" alt="">
                                                {{ $t('Source:') }} {{ ucFirst(conversation.source) }}
                                            </span>
                                        </div>
                                        <div v-if="canManageTickets || draftReplyPermission" class="fs_message_actions">
                                            <el-dropdown @command="handleResponseActionCommand" trigger="click">
                                                <el-button class="fs_more_btn">
                                                    <el-icon><MoreFilled /></el-icon>
                                                </el-button>
                                                <template #dropdown>
                                                    <el-dropdown-menu class="fs_global_dropdown fs_table_more_actions_dropdown_menu">
                                                        <el-dropdown-item
                                                            v-if="!draftReplyPermission || conversation.conversation_type === 'draft_response'"
                                                            :command="{ type: 'edit', conversation: conversation }"
                                                            icon="EditPen"> {{ $t('Edit') }}
                                                        </el-dropdown-item>
                                                        <el-dropdown-item v-if="has_pro && conversation.conversation_type !== 'draft_response'"
                                                                          :command="{ type: 'split_ticket', conversation: conversation }"
                                                                          icon="TopLeft">
                                                            {{ $t('Split Ticket') }}
                                                        </el-dropdown-item>
                                                        <el-dropdown-item
                                                            v-if="fluentBotIntegration && conversation.conversation_type === 'response'"
                                                            :command="{ type: 'add_to_fluentbot', conversation: conversation }">
                                                            <img :src="appVars.asset_url + 'images/fluentBotDark.svg'" alt="" class="fs_fluent_bot_menu_icon" /> {{ $t('Add to FluentBot') }}
                                                        </el-dropdown-item>
                                                        <el-dropdown-item
                                                            v-if="canManageTickets"
                                                            :command="{ type: 'delete', conversation: conversation }"
                                                            icon="Delete"> {{ $t('Delete') }}
                                                        </el-dropdown-item>
                                                    </el-dropdown-menu>
                                                </template>
                                            </el-dropdown>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Body -->
                                <div v-html="santizeContent(conversation.content)" class="fs_message_body"></div>

                                <!-- Attachments -->
                                <div class="fs_message_attachments" v-if="conversation.attachments?.length">
                                    <ul>
                                        <li v-for="attachment in conversation.attachments" :key="attachment.file_hash">
                                            <el-icon style="vertical-align: middle;">
                                                <paperclip/>
                                            </el-icon>
                                            <a target="_blank" rel="noopener" :href="attachment.secureUrl">{{
                                                attachment.title
                                            }}</a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Liked Indicator -->
                                <div v-if="has_pro && appVars.agent_feedback_rating === 'yes' && conversation.agent_feedback" class="fs_message_liked">
                                    <img :src="`${appVars.asset_url}images/icons/${conversation.agent_feedback === 'like' ? 'likeButtonFill' : 'dislikeButtonFill'}.svg`" />
                                    <span>{{ conversation.agent_feedback === 'like' ? $t('Liked') : $t('Disliked') }}</span>
                                </div>

                                <!-- Draft Response Actions -->
                                <div v-if="conversation.conversation_type === 'draft_response'" class="fs_draft_actions">
                                    <el-button class="fs_outline_btn" v-if="draftReplyApprovePermission" size="default" @click="approveDraftResponse(conversation)">
                                        <img :src="appVars.asset_url +'images/checkDoubleLine.svg'" alt="">
                                        {{ $t('Approve') }}
                                    </el-button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="fs_conversation_message fs_thread_starter">
                        <!-- Message Container -->
                        <div class="fs_message_container">
                            <!-- Avatar -->
                            <div class="fs_message_avatar">
                                <img :src="ticket.customer?.photo" :alt="ticket.customer?.full_name"/>
                            </div>

                            <!-- Message Content -->
                            <div class="fs_message_content">
                                <!-- Header: Name, Role, Badge, Timestamp -->
                                <div class="fs_message_header">
                                    <div class="fs_message_info">
                                        <!-- Name and Role -->
                                        <div class="fs_message_name_row">
                                            <span class="fs_message_name">{{ ticket.customer?.full_name }}</span>
                                            <el-tooltip effect="dark" :content="$t('Customer')" placement="top">
                                                <span class="fs_role_badge fs_role_badge_customer">
                                                    <IconPack iconKey="user_icon" :width="16" :height="16" />
                                                </span>
                                            </el-tooltip>

                                            <!-- Thread Starter Badge -->
                                            <div class="fs_message_badge fs_badge_thread_starter">
                                                {{ $t('THREAD STARTER') }}
                                            </div>
                                            <span v-if="ticket.created_by_agent && !ticket.created_by_agent.agent_initiated" class="fs_created_by_agent">
                                                {{ $t('Created By') }} <strong>{{ ticket.created_by_agent.full_name }}</strong>
                                            </span>
                                        </div>

                                        <!-- Timestamp and Email Row -->
                                        <div class="fs_message_time_row">
                                            <span class="fs_message_timestamp" :title="$t('Ticket created at ') + ticket.created_at">
                                                {{ $timeDiff(ticket.created_at) }}
                                            </span>
                                            <span class="fs_message_separator"> • </span>
                                            <span class="fs_message_email">{{ ticket.customer?.email }}</span>

                                            <div class="carrier_info" v-if="ticket.source == 'email'">
                                                <div class="fs_cc_info" v-if="ticket.carbon_copy">
                                                    <span><strong>Cc: </strong>{{ ticket.carbon_copy }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Source on the right -->
                                    <div class="fs_conversation_right_side">
                                        <span v-if="ticket.source" class="fs_ticket_source">
                                            <img :src="appVars.asset_url + 'images/source.svg'" alt="">
                                            {{ $t('Source:') }} {{ ucFirst(ticket.source) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Message Body -->
                                <div v-html="santizeContent(ticket.content)" class="fs_message_body"></div>

                                <!-- Attachments -->
                                <div class="fs_message_attachments" v-if="ticket.attachments && ticket.attachments.length">
                                    <ul>
                                        <li v-for="attachment in ticket.attachments" :key="attachment.file_hash">
                                            <el-icon style="vertical-align: middle;">
                                                <paperclip/>
                                            </el-icon>
                                            <a target="_blank" rel="noopener" :href="attachment.secureUrl">{{
                                                attachment.title
                                            }}</a>
                                        </li>
                                    </ul>
                                </div>


                                <!-- Custom Field Edit Dialog -->
                                <el-dialog
                                    :title="$t('Updating Custom Field Data')"
                                    v-model="showCustomDataEditForm"
                                    v-if="showCustomDataEditForm"
                                    width="60%"
                                    :append-to-body="true"
                                    class="fs_dialog">
                                    <custom-field-form @syncData="syncCustomData" :ticket_id="ticket_id" type="update_ticket"
                                                       :custom_data="ticket.custom_fields"/>
                                </el-dialog>
                            </div>
                        </div>
                    </article>

                    <article class="fs_conversation_message fs_additional_info" v-if="has_pro && !isEmpty(appVars.custom_fields)">
                        <!-- Custom Fields Section -->
                        <div  class="fs_custom_data_card">
                            <div class="fs_custom_data_header">
                                <h3>{{ $t('Additional Info') }}</h3>
                                <el-button @click="showCustomDataEditForm = !showCustomDataEditForm" :text="true"
                                            size="small" class="fs_edit_custom_data_btn">
                                    <img :src="appVars.asset_url + 'images/edit.svg'" alt="">
                                </el-button>
                            </div>
                            <div class="fs_custom_data_body">
                                <ul v-if="!isEmpty(ticket.custom_fields)" class="fs_custom_data_list">
                                    <li v-for="(fieldValue, fieldName) in ticket.custom_fields" :key="fieldName" class="fs_custom_data_item">
                                        <span dir="auto" class="fs_custom_data_label">{{ appVars.custom_fields[fieldName].label }}</span>
                                        <div dir="auto" class="fs_custom_data_value">
                                                    <span v-if="appVars.custom_fields[fieldName].type === 'date-range' && isArray(fieldValue)">
                                                        {{ fieldValue.join(' - ') }}
                                                    </span>
                                            <span v-else-if="isArray(fieldValue)">
                                                        <span dir="auto" class="fs_custom_check_value" v-for="value in fieldValue"
                                                              :key="value">{{ value }}</span>
                                                    </span>
                                            <span v-else v-html="santizeContent(fieldValue)"></span>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="fs_no_data">{{ $t('No additional data found') }}</p>
                            </div>
                        </div>
                    </article>

                </div>
            </div>

            <div class="fs_ticket_sidebar">
                <ticket-sidebar :fluentcrm_profile="fluentcrm_profile" :ticket_id="ticket_id" :ticket="ticket"
                                :watchers="watchers" :watcher_ids="watcherIds" @refresh="fetchTicket" :fetch_other_tickets="fetch_other_tickets" :can_manage_tickets="canManageTickets"/>
            </div>

            <Teleport to="body">
                <transition name="fs_panel_slide">
                    <!-- Lazy mount on first open (fluentBotBootstrapped sticks true), then stay
                         mounted so reopening restores session without replaying bootstrap fetches.
                         Users who never open the panel incur zero FluentBot cost. -->
                    <div
                        v-if="fluentBotBootstrapped"
                        v-show="showFluentBotPanel"
                        class="fs_fluent_bot_panel"
                        ref="fluentBotPanel"
                        role="dialog"
                        tabindex="-1"
                        :aria-label="$t('FluentBot assistant panel')"
                        :aria-hidden="showFluentBotPanel ? 'false' : 'true'"
                    >
                        <FluentBotAIResponseGenerator
                            ref="fluentBotComponent"
                            type="createResponse"
                            :ticketId="ticket.id"
                            :productID="ticket.product_id"
                            :is_agent="'yes'"
                            :ticketResponses="conversations"
                            :addContextId="pendingFluentBotContextId"
                            @contextAdded="pendingFluentBotContextId = null"
                            @close="showFluentBotPanel = false"
                            @insert="insertFluentBotResponse"
                        />
                    </div>
                </transition>
            </Teleport>
        </template>

        <template v-else-if="ticketNotFound.length">
            <div class="fs_ticket_error_message">
                <div class="fs_error_container">
                    <img
                        :src="appVars.asset_url + 'images/ticketNotFound.svg'"
                        :alt="$t('Ticket not found')"
                        class="fs_error_icon"
                    />
                    <h2 class="fs_error_title">{{ ticketNotFound }}</h2>
                    <p class="fs_error_description">
                        {{ $t('Go back to') }}
                        <a class="fs_error_link" @click="$router.push({ name: 'tickets' })">{{ $t('All Tickets') }}</a>
                        {{ $t('or contact support for assistance.') }}
                    </p>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="fs_ticket_body">
                <div class="fs_skeleton_loader">
                    <el-skeleton :rows="10" animated/>
                </div>
            </div>
            <div class="fs_ticket_sidebar">
                <el-skeleton class="fs_sidebar_skeleton"
                             :rows="3" animated/>
                <el-skeleton class="fs_sidebar_skeleton"
                             :rows="3" animated/>
                <el-skeleton class="fs_sidebar_skeleton"
                             :rows="3" animated/>
            </div>
        </template>

        <el-dialog
            v-model="edit_response_modal"
            :title="$t('Edit Response')"
            :append-to-body="true"
            class="fs_dialog"
        >
            <edit-response
                v-if="editing_response"
                @updated="edit_response_modal = false; editing_response = false"
                :response="editing_response"
                :ticket-id="ticket.id"
            />
        </el-dialog>

        <el-dialog
            v-model="show_fbs_add_task_modal"
            v-if="show_fbs_add_task_modal"
            :title="$t('Add Task to Fluent Boards')"
            :append-to-body="true"
            class="fs_dialog"
        >
            <FluentBoardsIntegration @created="afterCreatedTask" :ticket=ticket :fluentcrm_profile="fluentcrm_profile" />
        </el-dialog>

        <el-dialog
            v-model="split_ticket_modal"
            :title="$t('Split Ticket')"
            :append-to-body="true"
            class="fs_dialog"
        >
            <split-ticket
                :ticket_data="split_ticket"
                @split-ticket="splitToNewTicket"
                :spliting="loading"
            />
        </el-dialog>
        <active-agents :ticket="ticket" v-if="ticket && ticket.id"/>
    </div>
</template>

<script type="text/babel">
import { stripReservedElementPlusClasses } from '@/common/domPurifyGuards';
import CreateResponse from './_CreateResponse';
import EditResponse from './_EditResponse';
import TicketSidebar from './_TicketSidebar';
import FluentBoardsIntegration from './_FluentBoardsIntegration';
import each from 'lodash/each';
import isArray from 'lodash/isArray';
import isEmpty from 'lodash/isEmpty';
import ActiveAgents from './_active_agents';
import TicketTags from './parts/_Tags';
import CustomFieldForm from './parts/_CustomFieldForm';
import WorkFlowSelector from './parts/_WorkFlowSelector';
import Pagination from "../../Pieces/Pagination";
import SplitTicket from "./_SplitTicket"
import { getGroupedAgentOptions } from '@/admin/Composable/agentGroupHelper';
import IconPack from "@/admin/Components/IconPack.vue";
import FluentBotAIResponseGenerator from './_FluentBotAIResponseGenerator.vue';

export default {
    name: 'ViewTicket',
    props: ['ticket_id'],
    components: {
        Pagination,
        CreateResponse,
        TicketSidebar,
        EditResponse,
        ActiveAgents,
        TicketTags,
        CustomFieldForm,
        WorkFlowSelector,
        SplitTicket,
        FluentBoardsIntegration,
        IconPack,
        FluentBotAIResponseGenerator,
    },
    data() {
        return {
            loading: false,
            ticket: {},
            conversations: [],
            show_response_box: '',
            products: this.appVars.support_products,
            productSearchQuery: '',
            agents: this.appVars.support_agents,
            admin_priorities: this.appVars.admin_priorities,
            client_priorities: this.appVars.client_priorities,
            updating: false,
            deleting: false,
            active_agents: [],
            edit_response_modal: false,
            editing_response: false,
            showCustomDataEditForm: false,
            fluentcrm_profile: false,
            mailboxes: this.appVars.mailboxes,
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
            ticket_statuses: this.appVars.changeable_ticket_statuses,
            close_ticket_silently: "no",
            app_ready: false,
            fetch_other_tickets: false,
            canManageTickets: false,
            canAssignAgents: false,
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
            fluentBotIntegration: !!this.appVars.fluent_bot_integration,
            showFluentBotPanel: false,
            // Sticks true on first open so the panel stays mounted for session continuity
            // (no remount on close/reopen) while paying zero cost until the user opens it.
            fluentBotBootstrapped: false,
            pendingFluentBotContextId: null,
            deleteTicketPermission: false,
            conversationRefs: {}
        };
    },
    computed: {

        openAIIntegration() {
            return !!this.appVars.open_ai_integration;
        },

        filteredHeaderProducts() {
            const products = this.products || [];
            const query = this.productSearchQuery.trim().toLowerCase();
            if (!query) return products;
            return products.filter(product => product.title.toLowerCase().includes(query));
        },

        getTicketStatus() {
            const status = {};
            for (let key in this.ticket_statuses) {
                if(this.ticket_statuses[key].length){
                    status[key] = this.ticket_statuses[key][0];
                }
            }
            return status;
        },

        popoverWidth() {
            if (typeof window !== 'undefined') {
                return Math.min(600, window.innerWidth - 40);
            }
            return 600;
        },

        agentGroups() {
            return this.appVars.agent_groups || [];
        },

        groupedAgentOptions() {
            return getGroupedAgentOptions(this.agents || [], this.agentGroups, this.$t('Other'));
        },

        assignedAgentGroupName() {
            if (!this.ticket.agent_id || !this.agentGroups.length) return '';
            const agentId = Number(this.ticket.agent_id);
            for (const group of this.agentGroups) {
                const groupAgentIds = (group.agent_ids || []).map(Number);
                if (groupAgentIds.includes(agentId)) {
                    return group.title;
                }
            }
            return '';
        },

        isReplyResponseBoxOpen() {
            return ['response', 'draft_response'].includes(this.show_response_box);
        },

        isNoteResponseBoxOpen() {
            return this.show_response_box === 'note';
        }
    },
    watch: {
        showFluentBotPanel(open) {
            if (open) {
                // Mark bootstrapped on first open so v-if mounts the component and keeps it.
                this.fluentBotBootstrapped = true;
                // Capture prior focus target so we can restore it when the panel closes.
                this._priorFocusEl = document.activeElement;
                this._escHandler = (e) => {
                    if (e.key === 'Escape') {
                        this.handleFluentBotEscape(e);
                    }
                };
                document.addEventListener('keydown', this._escHandler);

                // Move focus into the dialog for keyboard/AT users.
                this.$nextTick(() => {
                    const panel = this.$refs.fluentBotPanel;
                    if (panel && typeof panel.focus === 'function') {
                        panel.focus();
                    }
                });
            } else {
                if (this._escHandler) {
                    document.removeEventListener('keydown', this._escHandler);
                    this._escHandler = null;
                }
                // Restore focus to the element that was focused before the panel opened.
                // preventScroll: if the agent scrolled the ticket while the panel was open,
                // that element is now off-screen and a plain focus() would yank the page
                // back up to it.
                if (this._priorFocusEl && typeof this._priorFocusEl.focus === 'function') {
                    try {
                        this._priorFocusEl.focus({ preventScroll: true });
                    } catch (e) {
                        // element may have been unmounted; safe to ignore
                    }
                }
                this._priorFocusEl = null;
            }
        },
        '$route.params.ticket_id'(ticketId) {
            if (ticketId) {
                this.doAction('ticket_view_exit', this.ticket.id);
                this.ticket = {};
                this.$nextTick(() => {
                    this.doAction('ticket_view_entered', ticketId);
                    this.fetchTicket();
                });
            }
        },
        tickets_to_merge(selected) {
            this.filteredMergeSelectedTickets = selected.map((ticket) => {
                return parseInt(ticket.id);
            });
        }
    },

    methods: {
        handleFluentBotEscape(e) {
            // Let open Element Plus dialogs handle escape first
            const openOverlay = document.querySelector('.el-overlay:not([style*="display: none"])');
            if (openOverlay && openOverlay.style.display !== 'none') {
                return;
            }

            e.stopPropagation();

            // Close inner menus first, then the panel
            const fluentBotRef = this.$refs.fluentBotComponent;
            if (fluentBotRef && fluentBotRef.hasOpenMenu()) {
                fluentBotRef.closeMenus();
                return;
            }

            this.showFluentBotPanel = false;
        },

        insertFluentBotResponse(content) {
            if (window.tinymce && window.tinymce.activeEditor) {
                window.tinymce.activeEditor.insertContent(content);
                this.showFluentBotPanel = false;
                return;
            }

            // Reply editor isn't open — keep panel visible so content isn't lost silently.
            this.$notify({
                message: this.$t('Open the reply editor first to insert FluentBot content.'),
                type: 'warning',
                position: 'bottom-right'
            });
        },
        onAgentPopoverShow() {
            this.$nextTick(() => {
                if (this.$refs.agentSelect) {
                    this.$refs.agentSelect.focus();
                    this.$refs.agentSelect.toggleMenu();
                }
            });
        },

        toggleReplyResponseBox() {
            const responseType = this.draftReplyPermission ? 'draft_response' : 'response';
            this.show_response_box = (this.show_response_box === responseType) ? '' : responseType;
        },

        toggleNoteResponseBox() {
            this.show_response_box = this.isNoteResponseBoxOpen ? '' : 'note';
        },

        async fetchTicket() {
            this.loading = true;
            await this.$get(`tickets/${this.ticket_id}`, {
                customer_id: this.ticket.customer_id,
                with_data: ['fluentcrm_profile']
            }).then(response => {
                if (this.appVars.enable_draft_mode === 'yes') {
                    this.fetchDraft();
                }
                this.loading = false;
                this.ticket = response.ticket;
                this.ticket.product_id = (this.ticket.product_id && this.ticket.product_id !== '0')
                    ? Number(this.ticket.product_id)
                    : null;
                this.$setTitle(response.ticket.title);
                this.conversations = response.responses;
                const permissions = this.appVars.me.permissions;
                this.canManageTickets = permissions.includes('fst_manage_own_tickets')
                    || permissions.includes('fst_manage_unassigned_tickets')
                    || permissions.includes('fst_manage_other_tickets');
                // Reassigning is its own capability, checked server-side on the
                // agent_id property branch — managing a ticket is not enough.
                this.canAssignAgents = this.canManageTickets
                    && permissions.includes('fst_assign_agents');
                this.draftReplyPermission = permissions.includes('fst_draft_reply');
                this.draftReplyApprovePermission = permissions.includes('fst_approve_draft_reply');
                this.deleteTicketPermission = permissions.includes('fst_delete_tickets');

                // sent the response to body event
                document.body.dispatchEvent(new CustomEvent('fs_ticket_viewed', {
                    detail: {
                        response: response
                    }
                }));

                if (this.appVars.fluentcrm_config) {
                    this.fluentcrm_profile = response.fluentcrm_profile;
                }

                if (this.has_pro) {
                    this.watchers = response.watchers;
                    this.filteredWatchersIds = response.watchers.map((watcher, key) => {
                        return watcher.tag_id;
                    });
                }


            }).catch(error => {
                this.loading = false;
                this.$handleError(error);
            })
            .always(() => {
                this.loading = false;
            });
        },

        async fetchDraft() {
            try {
                const response = await this.$get(`tickets/${this.ticket_id}/draft`);
                this.draftData = response.draft;

                if (this.draftData && this.draftData.value.content) {
                    this.draftResponse = this.draftData.value.content;
                    this.showDraft = true;
                } else {
                    this.draftResponse = null;
                    this.draft = null;
                }
            } catch (error) {
                this.$handleError(error);
            }
        },

        getTextByPerson(conversation, ticket) {
            if (conversation?.conversation_type === 'draft_response') {
                return this.$t('Draft Response');
            }

            if (conversation?.person.person_type === 'agent') {
                return (conversation.person.title ? conversation.person.title : this.$t('Support Staff'));
            }else{
                if (ticket.customer_id == conversation.person_id) {
                    return this.$t('Thread Starter')
                } else {
                    return this.$t('Thread Follower')
                }
            }
        },

        getRibbonClass(conversation, ticket) {
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
        },

        getTicketClasses(conversation, ticket) {
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
        },

        getConversationClass(conversation) {
            // Determine background color based on conversation type and person type
            if (conversation.conversation_type === 'note') {
                return 'fs_msg_note'; // Yellow background
            } else if (conversation.conversation_type === 'draft_response') {
                return 'fs_msg_draft'; // Light gray background
            } else if (conversation.conversation_type === 'internal_info') {
                return 'fs_internal_info'; // Light gray background
            } else if (conversation.person?.person_type === 'customer') {
                return 'fs_msg_customer'; // White background
            } else if (conversation.person?.person_type === 'agent') {
                return 'fs_msg_agent'; // White background
            }
            return '';
        },

        recordNewResponse(response, data) {
            this.conversations.unshift(response);
            this.ticket.status = data.ticket.status;
            this.show_response_box = '';

            if (response?.conversation_type === 'response' && response?.content?.includes('fs_ticket_id=')) {
                window.dispatchEvent(new CustomEvent('fluent-support/fluent-booking-link-sent', {
                    detail: {
                        ticket_id: this.ticket_id,
                        response_id: response.id
                    }
                }));
            }

            each(data.update_data, (data, key) => {
                this.ticket[key] = data;
            });

            if (this.appVars.pref.go_back_after_reply === 'yes') {
                if (window.history.state?.back) {
                    //   this.$router.go(-1);
                }
            }
        },

        updateTicketAttr(propName, propValue = null) {
            // If propValue is provided, use it; otherwise read from this.ticket
            const value = propValue !== null ? propValue : this.ticket[propName];

            return this.$put(`tickets/${this.ticket.id}/property`, {
                prop_name: propName,
                prop_value: value
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });

                    each(response.update_data, (data, key) => {
                        this.ticket[key] = data;
                    });
                    this.fetchTicket();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },

        handleStatusChange(newStatus) {
            // Prevent overlapping status requests from the same dropdown while a
            // status transition (close/re-open/property update) is still in flight.
            if (this.updating) {
                return;
            }

            const prevStatus = this.ticket.status;

            if (newStatus === prevStatus) {
                return;
            }

            // Closing from the status control should behave exactly like the
            // Close Ticket button (runs the dedicated /close flow + internal note).
            if (newStatus === 'closed') {
                this.closeTicket();
                return;
            }

            // Reopening a closed ticket should go through the dedicated re-open
            // flow so it logs the same internal note as the Re-open button.
            if (prevStatus === 'closed') {
                this.reOpen();
                return;
            }

            // active <-> new transitions: property update logs an internal note backend-side.
            this.updating = true;
            this.updateTicketAttr('status', newStatus)
                .always(() => {
                    this.updating = false;
                });
        },

        getHumanName(person) {
            if (parseInt(this.appVars.me?.id) === parseInt(person.id)) {
                return this.$t('You');
            }

            return person.full_name;
        },

        closeTicket() {
            this.updating = true;
            this.$post(`tickets/${this.ticket.id}/close`, {close_ticket_silently: this.close_ticket_silently})
                .then(response => {
                    this.ticket.status = response.ticket.status;
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    if (window.history.state?.back) {
                        this.$router.go(-1);
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.updating = false;
                });
        },

        deleteTicket() {
            this.$confirm({
                message: this.$t('Are you sure to delete this Ticket?'),
                title: this.$t('Warning'),
                options: {
                    confirmButtonText: this.$t('Delete Ticket'),
                    cancelButtonText: this.$t('Cancel'),
                    type: 'warning',
                    cancelButtonClass: 'el-button--default fs_outline_btn',
                    confirmButtonClass: 'el-button--danger fs_filled_btn',
                }
            }).then(() => {
                this.deleting = true;
                this.$del(`tickets/${this.ticket.id}/delete`)
                    .then(response => {
                        this.$notify({
                            message: response.message,
                            type: "success",
                            position: "bottom-right",
                        });
                        this.$router.push({name: 'tickets'});
                    })
            });
        },

        approveDraftResponse(conversation) {
            this.$put(`tickets/${conversation.ticket_id}/approve_draft_response/${conversation.id}`, {
                content: conversation.content
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: 'success'
                    });
                    this.fetchTicket();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
        },

        reOpen() {
            this.updating = true;
            this.$post(`tickets/${this.ticket.id}/re-open`)
                .then(response => {
                    this.ticket.status = response.ticket.status;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.updating = false;
                });
        },

        onActivityChange(items) {
            const personIds = [];
            items.forEach((item) => {
                personIds.push(item.val());
            });
            this.active_agents = personIds;
        },

        onTicketDataChange(item) {
            let data = item.val();
            this.ticket[data.type] = data.data;
        },

        handleResponseActionCommand(data) {
            const actionType = data.type;
            const conversation = data.conversation;

            if (actionType == 'delete') {
                this.$confirm({
                    message: this.$t('response_delete_warning'),
                    title: this.$t('Warning'),
                    options: {
                        confirmButtonText: this.$t('Delete Response'),
                        cancelButtonText: this.$t('Cancel'),
                        type: 'warning',
                        cancelButtonClass: 'el-button--default fs_outline_btn',
                        confirmButtonClass: 'el-button--danger fs_filled_btn',
                    }
                }).then(() => {
                    this.$del(`tickets/${this.ticket.id}/responses/${conversation.id}`)
                        .then(response => {
                            this.$notify({
                                message: response.message,
                                type: "success",
                                position: "bottom-right",
                            });
                            this.fetchTicket();
                        })
                        .catch((error) => {
                            this.$handleError(error);
                        });
                });
            } else if (actionType === 'edit') {
                this.editing_response = conversation;
                this.edit_response_modal = true;
                this.conversation_type = conversation.conversation_type;
            } else if (actionType === 'discard') {
                this.$confirm({
                    message: this.$t('Are you sure you want to discard this draft response?'),
                    title: this.$t('Warning'),
                    options: {
                        confirmButtonText: this.$t('Discard'),
                        cancelButtonText: this.$t('Cancel'),
                        type: 'warning',
                        cancelButtonClass: 'el-button--default fs_outline_btn',
                        confirmButtonClass: 'el-button--danger fs_filled_btn',
                    }
                }).then(() => {
                    this.discardDraft(conversation.id)
                        .then(() => {
                            this.$notify({
                                message: this.$t('Draft response discarded successfully'),
                                type: "success",
                                position: "bottom-right",
                            });
                            this.fetchTicket();
                        })
                        .catch((error) => {
                            this.$handleError(error);
                        });
                });
            } else if (actionType === 'split_ticket') {
                this.split_ticket = {
                    conversation_id: conversation.id,
                    content: conversation.content,
                    customer_id: parseInt(this.ticket.customer_id),
                    mailbox_id: parseInt(this.ticket.mailbox_id),
                    product_id: this.ticket.product?.id,
                    client_priority: this.ticket.priority,
                    create_wp_user: 'no',
                    create_customer: 'no',
                };
                this.split_ticket_modal = true;
            } else if (actionType === 'add_to_fluentbot') {
                this.showFluentBotPanel = true;
                this.pendingFluentBotContextId = conversation.id;
            }
        },

        afterCreatedTask() {
            this.show_fbs_add_task_modal = false;
            this.fetchTicket();
        },

        changeMailbox(mailbox) {
            this.loading = !this.loading;
            this.$put(`mailboxes/${this.ticket.mailbox_id}/move_tickets`, {
                new_box_id: mailbox,
                ticket_ids: [this.ticket_id],
                move_type: 'Custom',
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.fetchTicket();
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        customerTickets() {
            this.$get('tickets/customer_tickets/' + this.ticket.customer_id, {
                exclude_ticket_id: this.ticket_id,
                page: this.pagination.current_page,
                per_page: this.pagination.per_page,
            })
                .then(response => {
                    this.app_ready = true;
                    this.customer_tickets = response.tickets.data;
                    this.pagination.total = response.tickets.total;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        handleMergeSelectionChange(selected) {
            this.tickets_to_merge = selected;
        },

        mergeTickets() {
            this.$confirm({
                message: this.$t('Are you sure you want to merge these tickets?'),
                title: this.$t('Merge Tickets'),
                type: 'Warning',
                options: {
                    confirmButtonText: this.$t('Merge'),
                    cancelButtonText: this.$t('Cancel'),
                    type: 'warning',
                    cancelButtonClass: 'el-button--default fs_outline_btn',
                    confirmButtonClass: 'el-button--danger fs_filled_btn',
                }
            }).then(() => {
                this.loading = true;
                this.$post('tickets/' + this.ticket_id + '/merge_tickets', {
                    ticket_to_merge: this.filteredMergeSelectedTickets,
                })
                    .then(response => {
                        this.$notify({
                            message: response.message,
                            type: "success",
                            position: "bottom-right",
                        });
                        this.customerTickets();
                        this.fetchTicket();
                        this.fetch_other_tickets = !this.fetch_other_tickets;
                    })
                    .catch((error) => {
                        this.$handleError(error);
                    })
                    .always(() => {
                        this.loading = false;
                    });
            });
        },

        santizeContent(content) {
            if (!content || typeof content !== 'string') {
                return content;
            }

            content = content.replace(/\n\s*\n/g, '\n');

            // Convert plain-text editor tokens @[id:Name] to styled mention chips.
            if (content.includes('@[')) {
                content = content.replace(
                    /@\[(\d+):([^\]]+)\]/g,
                    (match, id, name) => `<span class="fs_agent_mention">@${name}</span>`
                );
            }

            stripReservedElementPlusClasses(window.DOMPurify);

            return window.DOMPurify.sanitize(content, {
                ADD_ATTR:    ['target'],
                FORBID_TAGS: ['style'],
            });
        },

        syncCustomData(data) {
            this.ticket.custom_fields = data;
            this.showCustomDataEditForm = false;
        },

        addWatchers() {
            this.saving = true;
            this.$post(`tickets/${this.ticket.id}/add_watchers`, {
                watchers: this.watcherIds
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.fetchTicket()
                    this.show_watcher_modal = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },

        splitToNewTicket() {
            this.loading = true;
            this.$post('tickets/' + this.ticket.id + '/split_ticket', {
                split_ticket: this.split_ticket,
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.customerTickets();
                    this.fetchTicket();
                    this.split_ticket_modal = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        discardDraft(draftID) {
            return this.$del('tickets/' + draftID + '/draft')
                .then(response => {
                    this.draftData = null;
                    this.draftResponse = null;
                    this.showDraft = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                })
        },

        getArrToString(arr) {
            if (!arr) {
                return '';
            }
            return arr.join(',');
        },

        isEmpty(value) {
            return isEmpty(value);
        },
        isArray(error) {
            return isArray(error);
        },

        getMailboxName(mailboxId) {
            if (!this.mailboxes || !this.mailboxes.length) {
                return '';
            }
            const mailbox = this.mailboxes.find(m => m.id == mailboxId);
            return mailbox ? mailbox.name : '';
        },

        getProductName(productId) {
            if (!this.products || !this.products.length || !productId) {
                return '';
            }
            const product = this.products.find(p => p.id == productId);
            return product ? product.title : '';
        },

        onProductDropdownToggle(visible) {
            if (!visible) {
                this.productSearchQuery = '';
            }
        },

        closeAIResponse() {
            this.ticketSummary = '';
            this.customerSentiment = '';
        },

        getTicketSummary() {
            this.ResponseLoader = true;
            // ai/ prefix routes are registered by the Pro plugin
            this.$post(`ai/${this.ticket_id}/get-ticket-summary`, {
                type: 'ticketSummary'
            })
                .then(response => {
                    this.ticketSummary = response;
                    this.customerSentiment = '';
                    this.ResponseLoader = false;
                })
                .catch((errors) => {
                    this.ResponseLoader = false;
                    this.$handleError(errors);
                })
        },

        getCustomerSentiment() {
            this.ResponseLoader = true;
            // ai/ prefix routes are registered by the Pro plugin
            this.$post(`ai/${this.ticket_id}/get-ticket-tone`, {
                type: 'ticketTone'
            })
                .then(response => {
                    let sentimentData = {
                        emoji: '',
                        text: response
                    };

                    if (response.includes('Positive')) {
                        sentimentData.emoji = '😀';
                        sentimentData.text = 'Positive';
                    } else if (response.includes('Neutral')) {
                        sentimentData.emoji = '😐';
                        sentimentData.text = 'Neutral';
                    } else if (response.includes('Negative')) {
                        sentimentData.emoji = '😡';
                        sentimentData.text = 'Negative';
                    }

                    this.customerSentiment = sentimentData;
                    this.ticketSummary= '';
                    this.ResponseLoader = false;
                })
                .catch((errors) => {
                    this.ResponseLoader = false;
                    this.$handleError(errors);
                })
        },

        setRef(id, el) {
            if (el) {
                this.conversationRefs[id] = el;
            }
        },

        handleKeydown(event) {
            const { metaKey, altKey, code } = event;

            if (metaKey && altKey && code === 'KeyI') {
                return;
            }

            if (metaKey && altKey) {
                event.preventDefault();

                const keyActions = {
                    KeyR: () => {
                        if (!this.canManageTickets && !this.draftReplyPermission) return;
                        this.toggleReplyResponseBox();
                    },
                    KeyN: () => {
                        if (!this.canManageTickets) return;
                        this.toggleNoteResponseBox();
                    },
                    KeyQ: () => this.fetchTicket(),
                    KeyM: () => {
                        if (this.has_pro) {
                            this.customerTickets();
                            this.show_merge_modal = !this.show_merge_modal;
                            this.app_ready = false;
                        }

                    },
                    KeyB: () => {
                        if (this.has_pro) {
                            this.show_watcher_modal = !this.show_watcher_modal;
                        }
                    },
                };

                const action = keyActions[code];
                if (action) {
                    action();
                }
            }
        },

        async scrollToHash() {
            const fullHash = window.location.hash;
            const hash = fullHash.split('#').pop();

            if (hash && this.conversationRefs[hash]) {
                const ref = this.conversationRefs[hash];
                return this.$scrollToRef(ref);
            }
        },

        handleTopBarCommand(command) {
            switch (command) {
                case 'merge':
                    this.customerTickets();
                    this.show_merge_modal = !this.show_merge_modal;
                    this.app_ready = false;

                    break;
                case 'close_silently':
                    this.close_ticket_silently = 'yes';
                    this.closeTicket();
                    this.close_ticket_silently = 'no';
                    break;
                case 'fluent_boards':
                    this.show_fbs_add_task_modal = !this.show_fbs_add_task_modal;
                    break;
                case 'delete':
                    this.deleteTicket();
                    break;
            }
        }
    },
    async mounted() {
        await this.fetchTicket();
        this.doAction('ticket_view_entered', this.ticket_id);

        await this.scrollToHash();
        window.addEventListener("hashchange", this.scrollToHash);
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.addEventListener("keydown", this.handleKeydown);
        }
    },
    beforeUnmount() {
        this.doAction('ticket_view_exit', this.ticket_id);
        document.body.dispatchEvent(new CustomEvent('fs_ticket_view_exit', {
            detail: {
                ticket_id: this.ticket_id
            }
        }));
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.removeEventListener("keydown", this.handleKeydown);
        }
        if (this._escHandler) {
            document.removeEventListener('keydown', this._escHandler);
        }
    }
}
</script>
