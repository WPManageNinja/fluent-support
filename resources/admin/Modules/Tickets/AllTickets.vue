<template>
    <div class="fs_all_tickets">
        <div class="fs_box_wrapper">
            <div class="fs_component_header">
                <div class="fs_component_head">
                    <h3 class="fs_page_title">
                        {{ $t("Tickets") }}
                        <span>({{ pagination.total }})</span>
                    </h3>
                </div>

                <div class="fs_box_actions fs_tickets_actions_btn">
                    <el-tooltip effect="dark" :content="$t('Refresh')" placement="top">
                        <el-button
                            @click="refreshTickets()"
                            class="fs_outline_btn fs_refresh_btn"
                        >
                            <IconPack iconKey="refresh" :width="20" :height="20" />
                        </el-button>
                    </el-tooltip>
                    <el-button
                        @click="toggleWaitingForReply()"
                        :class="['fs_outline_btn', 'fs_waiting_reply_btn', { 'fs_waiting_reply_btn_active': filters.waiting_for_reply === 'yes' }]"
                    >
                        <el-checkbox
                            :model-value="filters.waiting_for_reply === 'yes'"
                            @click.prevent.stop
                            class="fs_waiting_reply_checkbox"
                        />
                        {{ $t('Waiting For Reply') }}
                    </el-button>
                    <el-dropdown trigger="click" @command="handleMoreAction">
                        <el-button class="fs_outline_btn fs_more_action_btn">
                            {{ $t("More Action") }}
                            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_global_dropdown fs_global_dropdown">
                                <el-dropdown-item :command="show_bulk_actions ? 'hide_bulk' : 'show_bulk'">
                                    <el-icon v-if="!show_bulk_actions"><View /></el-icon>
                                    <el-icon v-else><Hide /></el-icon>
                                    <span>{{ show_bulk_actions ? $t("Hide Bulk Action") : $t("Show Bulk Action") }}</span>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                    <el-button
                        @click="add_ticket_modal = true"
                        class= "fs_filled_btn"
                        icon="Plus"
                    >{{ $t("Add Ticket") }}
                    </el-button>
                </div>
            </div>
            <div class="fs_box_header">
                <div class="fs_box_head fs_status_tabs_wrapper">
                    <!-- Status Tabs - Hidden when search is active or advanced filter is open -->
                    <div v-if="filter_type !== 'advanced'" class="fs_status_tabs">
                        <div class="fs_segmented_control">
                            <button
                                v-for="(status, statusKey) in ticket_statuses_group"
                                :key="statusKey"
                                @click="setStatus(statusKey)"
                                :class="['fs_segment_button', { 'fs_segment_active': filters.status_type === statusKey }]"
                            >
                                {{ appVars.ticket_statuses[statusKey] || $t(statusKey) }}
                            </button>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="fs_filter_controls">
                        <!-- Left Section: Status bar and Search -->
                        <div class="fs_filter_left_section" :class="{ 'fs_search_active': searchActive }">
                            <!-- Advanced Filter Button - Hidden when search is active -->
                            <div v-if="!searchActive" class="fs_tk_filter fs_advanced_button">
                                <el-switch
                                    @change="toggleAdvancedFilter"
                                    :model-value="filter_type === 'advanced'"
                                    :disabled="!has_pro"
                                    :active-text="$t('Advanced filter')"
                                    inactive-text=""
                                />
                            </div>

                            <!-- Search Button - Disabled when advanced filter is open or search is active -->
                            <div class="fs_tk_filter fs_table_search_field">
                                <el-tooltip effect="dark" :content="$t('Search')" placement="top">
                                    <el-button @click="openSearch()" class="fs_outline_btn fs_search_button" :disabled="filter_type === 'advanced'">
                                        <IconPack iconKey="search" :width="20" :height="20" />
                                    </el-button>
                                </el-tooltip>
                            </div>
                        </div>

                        <!-- Right Section: Filter, Folder, and Sort buttons -->
                        <div class="fs_filter_right_section">
                            <!-- Filter Button with Custom Filter Component - Disabled when advanced filter is open -->
                            <CustomTicketFilters ref="customTicketFiltersRef" @apply-filter="handleCustomFilter" :disabled="filter_type === 'advanced'" />

                            <!-- Add this after the Filter Button -->
                            <el-tooltip effect="dark" :content="$t('Saved Filters')" placement="top">
                                <el-button
                                    @click="fetchLabelSearch()"
                                    class="fs_outline_btn"
                                    v-if="has_pro"
                                >
                                    <IconPack iconKey="labelSearch" :width="20" :height="20" />
                                </el-button>
                            </el-tooltip>

                            <el-popover
                                trigger="click"
                                placement="bottom-start"
                                width="300"
                                popper-class="fs_popover"
                            >
                                <div class="fs_popover_item">
                                    <label class="fs_popover_title">{{ $t('Sort By') }}</label>
                                    <div class="fs_radio_blocks">
                                        <el-radio-group
                                            @change="fetchTickets"
                                            v-model="order_by"
                                        >
                                            <el-radio v-for="(column, columnName) in filterColumns" :key="columnName"
                                                      :value="columnName">
                                                {{ column }}
                                            </el-radio>
                                        </el-radio-group>
                                    </div>
                                    <hr/>
                                    <el-radio-group
                                        class="fs_switch_button"
                                        size="small"
                                        v-model="order_type"
                                        @change="fetchTickets"
                                    >
                                        <el-radio-button value="ASC">{{ $t('Ascending') }}</el-radio-button>
                                        <el-radio-button value="DESC">{{ $t('Descending') }}</el-radio-button>
                                    </el-radio-group>
                                </div>
                                <template #reference>
                                <span>
                                    <el-tooltip effect="dark" :content="$t('Sort')" placement="top">
                                        <el-button class="fs_outline_btn">
                                            <IconPack iconKey="sort" :width="20" :height="20" />
                                        </el-button>
                                    </el-tooltip>
                                </span>
                                </template>
                            </el-popover>

                            <el-popover
                                trigger="click"
                                placement="bottom-end"
                                width="280"
                                popper-class="fs_popover fs_display_settings_popover"
                            >
                                <div class="fs_popover_item">
                                    <div class="fs_popover_header">
                                        <label class="fs_popover_title">{{ $t('Layout Type') }}</label>
                                    </div>
                                    <div class="fs_radio_blocks fs_layout_options">
                                        <el-radio-group v-model="ticketLayout" @change="setTicketLayout">
                                            <el-radio value="default">{{ $t('Default') }} <span class="fs-radio__sublabel">{{$t('(Standard spacing)')}}</span></el-radio>
                                            <el-radio value="compact">{{ $t('Compact') }} <span class="fs-radio__sublabel">{{$t('(Maximum visibility)')}}</span></el-radio>
                                            <el-radio value="grid">{{ $t('Grid View') }} <span class="fs-radio__sublabel">{{$t('(Table columns)')}}</span></el-radio>
                                        </el-radio-group>
                                    </div>
                                    <hr/>
                                    <div class="fs_popover_header">
                                        <label class="fs_popover_title">{{ $t('Display Settings') }}</label>
                                        <el-button @click="resetActiveVisibility()" link size="small">
                                            <IconPack iconKey="refresh" :width="20" :height="20" />
                                        </el-button>

                                    </div>
                                    <div class="fs_checkbox_list">
                                        <el-checkbox
                                            v-for="field in activeVisibilityOptions"
                                            :key="field.key"
                                            v-model="activeVisibility[field.key]"
                                            @change="saveActiveVisibility()"
                                        >
                                            {{ field.label }}
                                        </el-checkbox>
                                    </div>
                                </div>
                                <template #reference>
                                    <span>
                                        <el-tooltip effect="dark" :content="$t('Display Settings')" placement="top">
                                            <el-button class="fs_outline_btn">
                                                <IconPack iconKey="layoutView" :width="20" :height="20" />
                                            </el-button>
                                        </el-tooltip>
                                    </span>
                                </template>
                            </el-popover>
                        </div>
                    </div>
                </div>
                <div v-if="searchActive || search.length > 0" class="fs_search_bar_section">
                    <div class="fs_search_bar_container">
                        <el-input
                            @keyup.enter="fetchTickets()"
                            :placeholder="$t('Search')"
                            v-model="search"
                            class="fs_text_input fs_table_search_input"
                            ref="searchInput"
                            :disabled="filter_type === 'advanced'"
                            clearable
                        >
                            <template #prefix>
                                <el-icon><Search /></el-icon>
                            </template>
                        </el-input>
                         <el-button @click="closeSearch()" class="fs_text_btn" text>
                             {{ $t('Cancel') }}
                         </el-button>
                    </div>
                </div>
            </div>

            <!-- Search Bar Section - Appears below filter line when search is active -->

            <div class="fs_active_filters_section" v-if="activeFilters && Object.keys(activeFilters).length">
                <div class="fs_active_filters_container">
                    <div
                        v-for="(filter, key) in activeFilters"
                        :key="key"
                        class="fs_filter_tag"
                        :class="{ 'fs_filter_tag_with_select': key === 'product_id' }"
                    >
                        <div class="fs_filter_icon_name">
                            <IconPack :iconKey="getFilterIcon(key)" :width="20" :height="20" class="fs_filter_icon" />
                            {{ getFilterLabel(key) }}
                        </div>
                        <div class="fs_filter_value" v-if="key !== 'product_id'">
                            <span>{{ getFilterDisplayValue(key, filter) }}</span>
                        </div>
                        <!-- Product dropdown -->
                        <div class="fs_filter_value" v-else>
                            <el-select
                                v-model="filters.product_id"
                                multiple
                                filterable
                                collapse-tags
                                collapse-tags-tooltip
                                :max-collapse-tags="2"
                                :placeholder="$t('Select products')"
                                class="fs_product_filter_select"
                                @change="handleProductFilterChange"
                            >
                                <el-option
                                    v-for="product in appVars?.support_products || []"
                                    :key="product.id"
                                    :label="product.title"
                                    :value="product.id"
                                />
                            </el-select>
                        </div>
                        <div class="fs_filter_close" @click="removeFilter(key)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M10.0001 8.93906L13.7126 5.22656L14.7731 6.28706L11.0606 9.99956L14.7731 13.7121L13.7126 14.7726L10.0001 11.0601L6.28755 14.7726L5.22705 13.7121L8.93955 9.99956L5.22705 6.28706L6.28755 5.22656L10.0001 8.93906Z" fill="#525866"/>
                            </svg>
                        </div>
                    </div>
            </div>
                <div class="fs_filter_actions">
                    <el-button
                        @click="clearAllFilters()"
                        link
                        class="fs_reset_filter_btn"
                    >
                        {{ $t('Reset Filter') }}
                    </el-button>
                </div>
            </div>
            <!-- Select All Section Bar -->
            <div class="fs_box_body">
                <div v-if="filter_type === 'advanced'">
                    <div v-if="has_pro" class="fs_rich_container">
                        <div v-if="appReady" class="fs_rich_wrap">
                            <div
                                v-for="(
                                    rich_filter, filterIndex
                                ) in advanced_filters"
                                :key="filterIndex"
                            >
                                <div class="fs_rich_filter">
                                    <rich-filter
                                        @maybeRemove="
                                            maybeRemoveGroup(filterIndex)
                                        "
                                        :items="rich_filter"
                                    />
                                </div>
                                <div class="fs_filter_separator" v-if="filterIndex + 1 != advanced_filters.length">
                                    <div class="fs_condition_sign">{{ $t("OR") }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="fs_filter_separator" @click="addConditionGroup()">
                            <div class="fs_condition_sign fs_button">
                                <svg class="fs_add_and_icon" width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.5 4.5V0H6V4.5H10.5V6H6V10.5H4.5V6H0V4.5H4.5Z" fill="#0E121B"/>
                                </svg>
                                {{ $t("OR") }}
                            </div>
                        </div>

                        <div class="fs_advanced_filter_actions">
                            <div class="fs_advanced_filter_actions_left">
                                <el-button
                                    v-if="countAdvancedFilterData(advanced_filters)"
                                    type="primary"
                                    @click="openSaveSearchModal"
                                    class="fs_saved_search_btn"
                                >
                                    <IconPack iconKey="labelSearch" :width="20" :height="20" />
                                    <span class="fs_saved_search_btn_text">{{ label_search_id ? $t("Update Filter") : $t("Save Filter") }}</span>
                                </el-button>
                            </div>
                            <div class="fs_advanced_filter_actions_right">
                                <a
                                    class="fs_filter_clear_btn"
                                    @click="resetAdvancedFilters"
                                >
                                    {{ $t("Reset Filters") }}
                                </a>
                                <el-button class="fs_filled_btn" type="primary" @click="fetchTickets()"
                                    >{{ $t("Apply Filter") }}
                                </el-button>
                            </div>
                        </div>
                    </div>
                    <NarrowPromo
                        v-else
                        :heading="$t('advance_filter_promo')"
                        :description="$t('pro_promo')"
                        :button-text="$t('Upgrade To Pro')"
                        utm-content="feature_lock_advanced_filters"
                    />
                </div>
                <div class="fs_select_all_section" :class="{ 'fs_advance_section_display': filter_type === 'advanced' }" v-if="tickets.length && show_bulk_actions && ticketLayout !== 'grid'">
                    <div class="fs_select_all_container">
                        <div class="fs_select_all">
                            <el-checkbox
                                :indeterminate="isIndeterminate"
                                v-model="selectAllChecked"
                                @change="handleSelectAll"
                            >
                                {{ $t('Select All') }}
                            </el-checkbox>
                            <span class="fs_total_count">
                                {{ ticket_selections.length }} {{ $t('out of') }} {{ pagination.total }} {{ $t('tickets') }}
                            </span>
                        </div>
                    </div>
                </div>
                <template v-if="app_ready && !first_time_loading">
                    <!-- List View -->
                    <div v-if="ticketLayout !== 'grid'" v-loading="loading" element-loading-text="Loading..." class="fs_tickets_list" :class="{ 'fs_tickets_list_compact': ticketLayout === 'compact' }">
                        <div class="fs_ticket_item" v-for="ticket in tickets" :key="ticket.id" @click="gotToTicket(ticket, $event)">
                            <div class="fs_ticket_main_content">
                                <!-- Left Side: Checkbox, Title, Author, Description -->
                                <div class="fs_ticket_left_section">
                                    <div class="fs_ticket_title_row">
                                        <el-checkbox
                                            v-if="show_bulk_actions"
                                            @click.stop
                                            :model-value="ticket_selections.includes(ticket.id)"
                                            @change="handleTicketSelection(ticket.id)"
                                        />
                                        <div class="fs_ticket_title_group">
                                            <template v-if="fieldVisibility.title">
                                                <a :href="$router.resolve({ name: 'view_ticket', params: { ticket_id: ticket.id } }).href"
                                                   @click="goToTicketView(ticket, $event)"
                                                   class="fs_ticket_title"
                                                >
                                                    <span>{{ ticket.title }}</span>
                                                </a>
                                            </template>
                                            <span v-if="fieldVisibility.description && ticketLayout === 'compact'" class="fs_ticket_inline_description">
                                                <span class="fs_inline_separator">–</span>
                                                <span class="fs_inline_desc_text" v-html="getExcerpt(ticket)"></span>
                                            </span>
                                            <span v-if="fieldVisibility.author" class="fs_ticket_author">by {{ ticket.customer?.first_name }} </span>
                                            <el-tooltip
                                                effect="dark"
                                                :content="$t('Internal Ticket ID') + ': #' + ticket.id"
                                                placement="top"
                                                trigger="hover"
                                            >
                                                <span class="fs_tk_number">
                                                    #{{ ticket.display_ticket_number || ticket.serial_number || ticket.id }}
                                                </span>
                                            </el-tooltip>
                                        </div>

                                        <ticket-tags
                                            v-if="fieldVisibility.tags && ticket.tags.length"
                                            @click.stop
                                            :tags="ticket.tags"
                                            :ticket_id="ticket.id"
                                            mode="ticket_list"
                                        ></ticket-tags>

                                        <el-tooltip
                                            v-if="ticket.source && fieldVisibility.source"
                                            effect="dark"
                                            :content="ucFirst(ticket.source)"
                                            placement="top"
                                        >
                                            <span class="fs_ticket_source_wrap">
                                                <img
                                                    v-if="!imageErrors[ticket.id + '_' + ticket.source]"
                                                    :src="$getSourceIcon(ticket.source)"
                                                    :alt="ucFirst(ticket.source)"
                                                    @error="imageErrors[ticket.id + '_' + ticket.source] = true"
                                                    class="fs_ticket_source_img"
                                                />
                                                <span v-else class="fs_source_initial_badge">{{ ticket.source.charAt(0).toUpperCase() }}</span>
                                            </span>
                                        </el-tooltip>
                                        <span v-if="fieldVisibility.status && ticket.status === 'new'" class="fs_status_dot fs_status_dot_new"></span>
                                    </div>
                                    <div v-if="fieldVisibility.description" class="fs_ticket_description">
                                        <p
                                            class="fs_tk_preview_text"
                                            v-html="getExcerpt(ticket)"
                                        ></p>
                                    </div>
                                </div>

                                <!-- Right Side: All Metadata -->
                                <div class="fs_ticket_right_section">
                                    <!-- Response Count & Mailbox -->
                                    <div class="fs_ticket_meta_info">
                                        <el-tooltip v-if="fieldVisibility.response_count && ticket.response_count" effect="dark" :content="$t('Response Count')" placement="top" trigger="hover">
                                            <span class="fs_ticket_conversation_count">
                                                <IconPack iconKey="ticketCount" :width="16" :height="16" />
                                                {{ ticket.response_count }}
                                            </span>
                                        </el-tooltip>
                                        <el-tooltip v-if="fieldVisibility.product && ticket.product && ticket.product.title" effect="dark" :content="$t('Product') + ': ' + (ticket.product?.title || '')" placement="top" trigger="hover">
                                            <span class="fs_ticket_product">
                                                <IconPack iconKey="productIcon" :width="16" :height="16" />
                                                {{ ticket.product.title }}
                                            </span>
                                        </el-tooltip>
                                        <el-tooltip v-if="fieldVisibility.mailbox && ticket.mailbox && ticket.mailbox.name && appVars?.mailboxes?.length > 1" effect="dark" :content="$t('Mailbox') + ': ' + (ticket.mailbox?.name || '')" placement="top" trigger="hover">
                                            <span class="fs_ticket_mailbox">
                                                <IconPack iconKey="businessBoxIcon" :width="16" :height="16" />
                                                {{ ticket.mailbox.name }}
                                            </span>
                                        </el-tooltip>
                                        <span class="fs_ticket_manage" v-if="false">
                                            <img :src="appVars.asset_url + 'images/settings.svg'" alt="">
                                            Manage Ninja
                                        </span>
                                    </div>

                                    <!-- Avatar, Status & Priority -->
                                    <div class="fs_ticket_status_group">
                                        <LiveActivityAvatars :activities="ticket.live_activity" />
                                        <el-tooltip v-if="fieldVisibility.status && ticket.status !== 'new'" effect="dark" :content="$t('Status') + ': ' + (appVars.ticket_statuses[ticket.status] || ticket.status)" placement="top" trigger="hover">
                                            <span class="fs_status_badge" :class="`fs_status_${ticket.status?.toLowerCase()}`">
                                                {{ appVars.ticket_statuses[ticket.status] || ticket.status }}
                                            </span>
                                        </el-tooltip>
                                        <el-tooltip v-if="fieldVisibility.client_priority && ticket.client_priority" effect="dark" :content="$t('Client Priority') + ': ' + (appVars.client_priorities[ticket.client_priority] || ticket.client_priority)" placement="top" trigger="hover">
                                            <span class="fs_client_priority_badge" :class="`fs_client_priority_${ticket.client_priority?.toLowerCase()}`">
                                                <span class="fs_priority_dot" :class="`fs_priority_dot_${ticket.client_priority?.toLowerCase()}`"></span>
                                                {{ appVars.client_priorities[ticket.client_priority] || ticket.client_priority }}
                                            </span>
                                        </el-tooltip>
                                        <el-tooltip v-if="fieldVisibility.waiting_time && ticket.waiting_since" effect="dark" :content="$t('Waiting Time') + ': ' + ticket.waiting_since" placement="top" trigger="hover">
                                            <span class="fs_ticket_waiting_time">
                                                <IconPack iconKey="clock" :width="16" :height="16" />
                                                {{ $timeDiff(ticket.waiting_since)}}
                                            </span>
                                        </el-tooltip>
                                        <el-tooltip
                                            v-if="fieldVisibility.agent && ticket.agent"
                                            effect="dark"
                                            :content="$t('Assigned Agent: ') + (ticket.agent?.full_name || $t('Unassigned'))"
                                            placement="top"
                                            trigger="hover"
                                        >
                                            <span class="fs_ticket_agent_badge">
                                                <IconPack iconKey="agent" :width="14" :height="14" />
                                                <span class="fs_ticket_agent_name">
                                                    {{ ticket.agent.full_name || $t('Unassigned') }}
                                                </span>
                                            </span>
                                        </el-tooltip>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fs_tickets_empty_state" v-if="!tickets.length">
                            <img :src="appVars.asset_url + 'images/empty.svg'" alt="">
                            <p>{{ $t('No Results Found') }}</p>
                        </div>
                    </div>

                    <!-- Grid View -->
                    <div v-else-if="ticketLayout === 'grid'" v-loading="loading" element-loading-text="Loading..." class="fs_tickets_grid_view">
                        <div class="fs_grid_scroll_wrapper">
                            <div class="fs_grid_table" :style="{ '--fs-grid-cols': gridTemplateColumns }">

                                <!-- ── Header Row ─────────────────────────────── -->
                                <div class="fs_grid_header_row">
                                    <!-- Checkbox col: select-all -->
                                    <div class="fs_grid_th fs_grid_th_checkbox">
                                        <el-checkbox
                                            v-if="show_bulk_actions"
                                            :indeterminate="isIndeterminate"
                                            :model-value="selectAllChecked"
                                            @change="handleSelectAll"
                                            @click.stop
                                        />
                                    </div>

                                    <!-- Title -->
                                    <div class="fs_grid_th fs_grid_th_title">
                                        <span>{{ $t('Title') }}</span>
                                    </div>

                                    <!-- Agent -->
                                    <div v-if="gridFieldVisibility.agent" class="fs_grid_th fs_grid_th_agent">
                                        <span>{{ $t('Agent') }}</span>
                                    </div>

                                    <!-- Product -->
                                    <div v-if="gridFieldVisibility.product" class="fs_grid_th fs_grid_th_product">{{ $t('Product') }}</div>

                                    <!-- Responses -->
                                    <div v-if="gridFieldVisibility.response_count" class="fs_grid_th fs_grid_th_responses">{{ $t('Responses') }}</div>

                                    <!-- Priority -->
                                    <div v-if="gridFieldVisibility.client_priority" class="fs_grid_th fs_grid_th_priority">{{ $t('Priority') }}</div>

                                    <!-- Date -->
                                    <div v-if="gridFieldVisibility.waiting_time" class="fs_grid_th fs_grid_th_date">{{ $t('Date') }}</div>

                                    <!-- Tags -->
                                    <div v-if="gridFieldVisibility.tags" class="fs_grid_th fs_grid_th_tags">
                                        <span>{{ $t('Tags') }}</span>
                                    </div>

                                    <!-- Status -->
                                    <div v-if="gridFieldVisibility.status" class="fs_grid_th fs_grid_th_status">{{ $t('Status') }}</div>

                                    <!-- Mailbox -->
                                    <div v-if="gridFieldVisibility.mailbox" class="fs_grid_th fs_grid_th_mailbox">{{ $t('Mailbox') }}</div>
                                </div>

                                <!-- ── Data Rows ───────────────────────────────── -->
                                <div
                                    class="fs_grid_row"
                                    v-for="ticket in tickets"
                                    :key="ticket.id"
                                    :class="{ 'fs_grid_row_selected': ticket_selections.includes(ticket.id) }"
                                    @click="gotToTicket(ticket, $event)"
                                >
                                    <!-- Checkbox cell (dedicated column) -->
                                    <div class="fs_grid_td fs_grid_td_checkbox">
                                        <el-checkbox
                                            v-if="show_bulk_actions"
                                            @click.stop
                                            :model-value="ticket_selections.includes(ticket.id)"
                                            @change="handleTicketSelection(ticket.id)"
                                        />
                                    </div>

                                    <!-- Title Cell -->
                                    <div class="fs_grid_td fs_grid_td_title">
                                        <div class="fs_grid_avatar">
                                            <img v-if="ticket.customer?.photo" :src="ticket.customer.photo" :alt="ticket.customer.first_name" class="fs_grid_avatar_img" />
                                            <span v-else class="fs_grid_avatar_initials">{{ getInitials(ticket.customer) }}</span>
                                        </div>
                                        <div class="fs_grid_title_content">
                                            <div class="fs_grid_title_row">
                                                <a
                                                    :href="$router.resolve({ name: 'view_ticket', params: { ticket_id: ticket.id } }).href"
                                                    @click="goToTicketView(ticket, $event)"
                                                    class="fs_grid_title_text"
                                                >{{ ticket.title }}</a>
                                                <span v-if="gridFieldVisibility.author" class="fs_grid_author">
                                                    {{ $t('by') }} {{ ticket.customer?.first_name }}
                                                    <el-tooltip effect="dark" :content="$t('Internal Ticket ID') + ': #' + ticket.id" placement="top" trigger="hover">
                                                        <em>#{{ ticket.display_ticket_number || ticket.serial_number || ticket.id }}</em>
                                                    </el-tooltip>
                                                </span>
                                                <span v-if="ticket.source && gridFieldVisibility.source" class="fs_grid_source_icon" @click.stop>
                                                    <el-tooltip effect="dark" :content="ucFirst(ticket.source)" placement="top">
                                                        <span class="fs_ticket_source_wrap">
                                                            <img
                                                                v-if="!imageErrors[ticket.id + '_' + ticket.source]"
                                                                :src="$getSourceIcon(ticket.source)"
                                                                :alt="ucFirst(ticket.source)"
                                                                @error="imageErrors[ticket.id + '_' + ticket.source] = true"
                                                                class="fs_grid_source_img"
                                                            />
                                                            <span v-else class="fs_source_initial_badge">{{ ticket.source.charAt(0).toUpperCase() }}</span>
                                                        </span>
                                                    </el-tooltip>
                                                </span>
                                                <span v-if="ticket.status === 'new'" class="fs_status_dot fs_status_dot_new"></span>
                                                <LiveActivityAvatars :activities="ticket.live_activity" @click.stop />
                                            </div>
                                            <p v-if="gridFieldVisibility.description" class="fs_grid_description" v-html="getExcerpt(ticket)"></p>
                                        </div>
                                    </div>

                                    <!-- Agent Cell -->
                                    <div v-if="gridFieldVisibility.agent" class="fs_grid_td fs_grid_td_agent">
                                        <el-tooltip v-if="ticket.agent" effect="dark" :content="ticket.agent.full_name" placement="top">
                                            <span class="fs_grid_cell_text">{{ ticket.agent.first_name }}</span>
                                        </el-tooltip>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Product Cell -->
                                    <div v-if="gridFieldVisibility.product" class="fs_grid_td fs_grid_td_product">
                                        <el-tooltip v-if="ticket.product?.title" effect="dark" :content="ticket.product.title" placement="top">
                                            <span class="fs_grid_cell_text">{{ ticket.product.title }}</span>
                                        </el-tooltip>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Responses Cell -->
                                    <div v-if="gridFieldVisibility.response_count" class="fs_grid_td fs_grid_td_responses">
                                        <span v-if="ticket.response_count" class="fs_grid_response_count">
                                            <IconPack iconKey="ticketCount" :width="16" :height="16" />
                                            {{ ticket.response_count }}
                                        </span>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Priority Cell -->
                                    <div v-if="gridFieldVisibility.client_priority" class="fs_grid_td fs_grid_td_priority">
                                        <span
                                            v-if="ticket.client_priority"
                                            class="fs_client_priority_badge"
                                            :class="`fs_client_priority_${ticket.client_priority?.toLowerCase()}`"
                                        >
                                            <span class="fs_priority_dot" :class="`fs_priority_dot_${ticket.client_priority?.toLowerCase()}`"></span>
                                            {{ appVars.client_priorities[ticket.client_priority] || ticket.client_priority }}
                                        </span>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Date Cell -->
                                    <div v-if="gridFieldVisibility.waiting_time" class="fs_grid_td fs_grid_td_date">
                                        <div class="fs_grid_date_stack">
                                            <el-tooltip v-if="ticket.created_at" effect="dark" :content="$t('Ticket Created')" placement="top">
                                                <span class="fs_grid_date fs_grid_date_muted">
                                                    <IconPack iconKey="clock" :width="14" :height="14" />
                                                    {{ $timeDiff(ticket.created_at) }}
                                                </span>
                                            </el-tooltip>
                                            <el-tooltip v-if="ticket.waiting_since" effect="dark" :content="$t('Waiting Since')" placement="top">
                                                <span class="fs_grid_date">
                                                    <IconPack iconKey="clock" :width="14" :height="14" />
                                                    {{ $timeDiff(ticket.waiting_since) }}
                                                </span>
                                            </el-tooltip>
                                        </div>
                                    </div>

                                    <!-- Tags Cell -->
                                    <div v-if="gridFieldVisibility.tags" class="fs_grid_td fs_grid_td_tags">
                                        <ticket-tags
                                            v-if="ticket.tags && ticket.tags.length"
                                            @click.stop
                                            :tags="ticket.tags"
                                            :ticket_id="ticket.id"
                                            mode="ticket_list"
                                        ></ticket-tags>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Status Cell -->
                                    <div v-if="gridFieldVisibility.status" class="fs_grid_td fs_grid_td_status">
                                        <span
                                            v-if="ticket.status"
                                            class="fs_status_badge"
                                            :class="`fs_status_${ticket.status?.toLowerCase()}`"
                                        >{{ appVars.ticket_statuses[ticket.status] || ticket.status }}</span>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                    <!-- Mailbox Cell -->
                                    <div v-if="gridFieldVisibility.mailbox" class="fs_grid_td fs_grid_td_mailbox">
                                        <span v-if="ticket.mailbox?.name" class="fs_grid_cell_text">{{ ticket.mailbox.name }}</span>
                                        <span v-else class="fs_grid_cell_muted">—</span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="fs_tickets_empty_state" v-if="!tickets.length">
                            <img :src="appVars.asset_url + 'images/empty.svg'" alt="">
                            <p>{{ $t('No Results Found') }}</p>
                        </div>
                    </div>
                </template>

                <div v-else class="fs_skeleton_loader">
                    <el-skeleton :rows="10" animated />
                </div>
                <div class="fs_pagination_wrapper" v-if="tickets.length">
                    <span class="fs_pagination_left">
                        <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                        <pagination
                            @fetch="fetchTickets()"
                            :pagination="pagination"
                            layout="sizes"
                        />
                    </span>
                    <span class="fs_pagination_right">
                        <pagination
                            @fetch="fetchTickets()"
                            :pagination="pagination"
                            :background="true"
                            layout="prev, pager, next"
                        />
                    </span>
                </div>
            </div>
        </div>

        <!-- Create Ticket Modal -->
        <el-dialog
            :append-to-body="true"
            v-model="add_ticket_modal"
            width="50%"
            class="fs_dialog fs_tk_dialog">
            <add-ticket @close="add_ticket_modal = false"></add-ticket>
        </el-dialog>

        <el-dialog
            :title="$t('Save search')"
            v-model="isLabelSearchOpen"
            @close="isLabelSearchOpen = false"
            class="fs_dialog"
        >
            <el-form label-position="top">
                <el-form-item :label="$t('Search Name')">
                    <el-input
                        type="text"
                        :placeholder="$t('Search Name')"
                        v-model="label_search_name"
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button
                        class="fs_outline_btn"
                        @click="isLabelSearchOpen = false"
                        >{{ $t("Cancel") }}</el-button
                    >
                    <el-button
                        v-loading="saving"
                        :disabled="saving"
                        class="fs_filled_btn"
                        type="success"
                        @click="handleSaveSearch"
                        >{{ $t("Save") }}</el-button
                    >
                </span>
            </template>
        </el-dialog>

        <el-drawer
            :with-header="false"
            :append-to-body="true"
            v-model="openLabelSearchDrawer"
            class="fs-el-drawer"
        >
            <label-search-drawer
                :labelSearchList="labelSearchList"
                :filtersValue="advanced_filters"
                @close="closeSavedSearchListModal"
                @getSavedSearch="handleAdvanceSearch"
                @setLabelSearchItem="handleLabelSearchEdit"
                @delete="handleLabelSearchDelete"
            />
            <template #footer>
                <div class="fs_text_right">
                    <el-button class="fs_outline_btn" @click="closeSavedSearchListModal">{{
                        $t("Close")
                    }}</el-button>
                </div>
            </template>
        </el-drawer>

        <ticket-bulk-actions
            v-if="appReady && show_bulk_actions"
            @fetchTickets="refreshTickets()"
        />
    </div>
</template>

<script type="text/babel">
import Pagination from "../../Pieces/Pagination";
import each from "lodash/each";
import AddTicket from "./_AddTicket";
import TicketTags from "./parts/_Tags";
import LabelSearchDrawer from "./parts/_LabelSearchDrawer";
import TicketBulkActions from "./_BulkActions";
import RichFilter from "./parts/RichFilters/RichFilter";
import Modal from "../../Pieces/Modal";
import CustomTicketFilters from "./parts/_TicketFilters";
import { h } from 'vue';
import IconPack from "@/admin/Components/IconPack.vue";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";
import LiveActivityAvatars from "@/admin/Components/LiveActivityAvatars.vue";
import { mapState, mapActions, mapWritableState } from 'pinia';
import { useTicketListStore } from '@/admin/stores/ticketList.store';

import isEmpty from "lodash/isEmpty";
import isArray from "lodash/isArray";

const GRID_COLUMNS = [
    { width: '52px'  },                                        // checkbox  (always visible)
    { width: '1fr'   },                                        // title     (takes remaining space)
    { key: 'agent',           width: '110px' },
    { key: 'product',         width: '130px' },
    { key: 'response_count',  width: '100px' },
    { key: 'client_priority', width: '110px' },
    { key: 'waiting_time',    width: '120px' },
    { key: 'tags',            width: '140px' },
    { key: 'status',          width: '90px'  },
    { key: 'mailbox',         width: '130px' },
];

export default {
    name: "AllTickets",
    components: {
        IconPack,
        Modal,
        Pagination,
        AddTicket,
        TicketTags,
        TicketBulkActions,
        RichFilter,
        LabelSearchDrawer,
        CustomTicketFilters,
        NarrowPromo,
        LiveActivityAvatars,
    },
    data() {
        return {
            add_ticket_modal: false,
            add_response_modal: false,
            show_filters: true,
            openTicketInNewTab: false,
            filterPopoverVisible: false,
            selectedFilterPath: [],
            show_bulk_actions: true,
            imageErrors: {},
            openLabelSearchDrawer: false,
            isLabelSearchOpen: false,
            saving: false,
        };
    },
    computed: {
        ...mapState(useTicketListStore, [
            'tickets',
            'pagination',
            'ticket_statuses_group',
            'loading',
            'first_time_loading',
            'app_ready',
            'appReady',
            'doing_bulk',
            'labelSearchList',
            // Getters
            'activeFilters',
            'selectAllChecked',
            'isIndeterminate',
        ]),
        ...mapWritableState(useTicketListStore, [
            'filters',
            'search',
            'order_by',
            'order_type',
            'filter_type',
            'advanced_filters',
            'ticket_selections',
            'searchActive',
            'fieldVisibility',
            'gridFieldVisibility',
            'ticketLayout',
            'viewMode',
            'label_search_id',
            'label_search_name',
        ]),
        filterColumns() {
            return {
                id: this.$t("Ticket ID"),
                product_id: this.$t("Product ID"),
                priority: this.$t("Priority"),
                client_priority: this.$t("Client Priority"),
                title: this.$t("Title"),
                last_agent_response: this.$t("Last Agent Response"),
                last_customer_response: this.$t("Last Customer Response"),
                waiting_since: this.$t("Waiting Time"),
                response_count: this.$t("Response Count"),
                created_at: this.$t("Created At"),
            };
        },
        fieldVisibilityOptions() {
            return [
                { key: 'title', label: this.$t('Ticket Title') },
                { key: 'author', label: this.$t('Author Name') },
                { key: 'tags', label: this.$t('Tags') },
                { key: 'source', label: this.$t('Source') },
                { key: 'description', label: this.$t('Description') },
                { key: 'response_count', label: this.$t('Response Count') },
                { key: 'product', label: this.$t('Product') },
                { key: 'mailbox', label: this.$t('Mailbox') },
                { key: 'agent', label: this.$t('Assigned Agent') },
                { key: 'status', label: this.$t('Status') },
                { key: 'client_priority', label: this.$t('Client Priority') },
                { key: 'waiting_time', label: this.$t('Waiting Time') },
            ];
        },
        gridVisibilityOptions() {
            return [
                { key: 'agent', label: this.$t('Assigned Agent') },
                { key: 'tags', label: this.$t('Tags') },
                { key: 'status', label: this.$t('Status') },
                { key: 'response_count', label: this.$t('Response Count') },
                { key: 'product', label: this.$t('Product') },
                { key: 'mailbox', label: this.$t('Mailbox') },
                { key: 'client_priority', label: this.$t('Client Priority') },
                { key: 'waiting_time', label: this.$t('Waiting Time') },
                { key: 'author', label: this.$t('Author Name') },
                { key: 'description', label: this.$t('Description') },
                { key: 'source', label: this.$t('Source') },
            ];
        },
        activeVisibility() {
            return this.ticketLayout === 'grid' ? this.gridFieldVisibility : this.fieldVisibility;
        },
        activeVisibilityOptions() {
            return this.ticketLayout === 'grid' ? this.gridVisibilityOptions : this.fieldVisibilityOptions;
        },
        gridTemplateColumns() {
            const V = this.gridFieldVisibility;
            return GRID_COLUMNS
                .filter(col => !col.key || V[col.key])
                .map(col => col.width)
                .join(' ');
        },
    },
    watch: {
        '$route.query.agent_id'(newAgentId, oldAgentId) {
            const store = useTicketListStore();
            if (store.app_ready && this.$route.name !== "view_ticket") {
                if (newAgentId !== oldAgentId) {
                    store.filters.agent_id = newAgentId || [];
                    this.fetchTickets();
                }
            }
        },
        '$route.query.watcher'(newWatcher, oldWatcher) {
            const store = useTicketListStore();
            if (store.app_ready && this.$route.name !== "view_ticket") {
                if (newWatcher !== oldWatcher) {
                    store.filters.watcher = newWatcher || '';
                    this.fetchTickets();
                }
            }
        },
        '$route.query.product_id'(newProductId, oldProductId) {
            const store = useTicketListStore();
            if (store.app_ready && this.$route.name !== "view_ticket") {
                if (newProductId !== oldProductId) {
                    store.filters.product_id = newProductId ? (Array.isArray(newProductId) ? newProductId : [newProductId]) : [];
                    this.fetchTickets();
                }
            }
        },
    },
    methods: {
        ...mapActions(useTicketListStore, {
            storeSetStatus: 'setStatus',
            storeResetFilters: 'resetFilters',
            storeResetWithOutFetch: 'resetWithOutFetch',
            storeClearAllFilters: 'clearAllFilters',
            storeResetAdvancedFilters: 'resetAdvancedFilters',
            storeHandleCustomFilter: 'handleCustomFilter',
            storeHandleSelectAll: 'handleSelectAll',
            storeHandleTicketSelection: 'handleTicketSelection',
            storeDeleteSelected: 'deleteSelected',
            storeCloseSelected: 'closeSelected',
            storeHandleSaveSearch: 'handleSaveSearch',
            storeFetchLabelSearches: 'fetchLabelSearches',
            storeHandleLabelSearchDelete: 'handleLabelSearchDelete',
            storeHandleAdvanceSearch: 'handleAdvanceSearch',
            storeHandleLabelSearchEdit: 'handleLabelSearchEdit',
        }),

        closeSavedSearchListModal() {
            this.openLabelSearchDrawer = false;
        },

        async fetchTickets() {
            const store = useTicketListStore();
            if (!store.app_ready) {
                return false;
            }

            try {
                await store.fetchTickets();
                this.saveFilters();
            } catch (e) {
                this.$handleError(e);
            }
        },

        refreshTickets() {
            const store = useTicketListStore();
            store.invalidateCache();
            this.fetchTickets();
        },

        addConditionGroup() {
            this.advanced_filters.push([]);
        },

        maybeRemoveGroup(index) {
            if (this.advanced_filters.length > 1) {
                this.advanced_filters.splice(index, 1);
            }
        },

        gotToTicket(row, event) {
            if (event.target && event.target.tagName.toLowerCase() === 'a') {
                return;
            }

            this.$router.push({
                name: "view_ticket",
                params: { ticket_id: row.id },
            });
        },

        goToTicketView(ticket, event) {
            if (event.ctrlKey || event.metaKey) {
                const routeData = this.$router.resolve({
                    name: "view_ticket",
                    params: { ticket_id: ticket.id },
                });
                window.open(routeData.href, "_blank");
                event.preventDefault();
                event.stopPropagation();
            }
        },

        handleImageError(event) {
            event.target.style.display = 'none';
        },

        setFromSaveFilters() {
            const store = useTicketListStore();
            store.filter_type = this.$getData("tickets_filter_type", "simple");
            store.ticket_statuses_group = this.appVars.ticket_statuses_group || {};

            const filters = this.$getData("tickets_filter", {});
            each(filters, (filter, filterKey) => {
                store.filters[filterKey] = filter;
            });

            const ticketPref = this.$getData("tickets_pref", false);
            if (ticketPref) {
                store.order_by = ticketPref.order_by;
                store.order_type = ticketPref.order_type;
                store.pagination.per_page = ticketPref.per_page;
                store.pagination.current_page = ticketPref.current_page;
                store.search = ticketPref.search;
            }

            const advancedFilters = this.$getData("tickets_advanced_filters", [[]]);
            if (advancedFilters) {
                store.advanced_filters = advancedFilters;
            }

            const savedBulkActionsState = this.$getData("tickets_show_bulk_actions", 'yes');
            this.show_bulk_actions = savedBulkActionsState !== 'no';

            const savedFieldVisibility = this.$getData('tickets_field_visibility', null);
            if (savedFieldVisibility) {
                store.fieldVisibility = { ...store.fieldVisibility, ...savedFieldVisibility };
            }

            const savedGridFieldVisibility = this.$getData('tickets_grid_field_visibility', null);
            if (savedGridFieldVisibility) {
                store.gridFieldVisibility = { ...store.gridFieldVisibility, ...savedGridFieldVisibility };
            }

            const savedLayout = this.$getData('tickets_layout', 'default');
            store.ticketLayout = savedLayout;

            this.openTicketInNewTab = this.appVars.open_ticket_in_new_tab === "yes" ? true : false;
            store.appReady = true;
        },

        saveFilters() {
            const store = useTicketListStore();
            this.$saveData("tickets_pref", {
                order_by: store.order_by,
                order_type: store.order_type,
                per_page: store.pagination.per_page,
                search: store.search,
                current_page: store.pagination.current_page,
            });

            this.$saveData("tickets_filter_type", store.filter_type);

            if (store.filter_type === "advanced") {
                this.$saveData("tickets_advanced_filters", store.advanced_filters);
            } else {
                this.$saveData("tickets_filter", store.filters);
            }
        },

        changeOrderType() {
            if (this.order_type === "DESC") {
                this.order_type = "ASC";
            } else {
                this.order_type = "DESC";
            }
            this.fetchTickets();
        },

        getWaitingStatus(ticket) {
            if (ticket.status == "closed") {
                return "";
            }
            if (ticket.status === "new") {
                return "new";
            }
            if (!ticket.last_agent_response) {
                return "waiting";
            } else if (!ticket.last_customer_response) {
                return "";
            }
            if (
                this.$dayjs(ticket.last_agent_response).isAfter(
                    ticket.last_customer_response,
                    "seconds"
                )
            ) {
                return "";
            }

            return "waiting";
        },

        getLastResponse(ticket) {
            if (
                this.$dayjs(ticket.last_agent_response).isAfter(
                    ticket.last_customer_response,
                    "seconds"
                )
            ) {
                return this.$timeDiff(ticket.last_agent_response);
            } else {
                return this.$timeDiff(ticket.last_customer_response);
            }
        },

        handleSelectionChange(selections) {
            const selectionIds = [];
            each(selections, (selection) => {
                selectionIds.push(selection.id);
            });
            this.ticket_selections = selectionIds;
        },

        deleteSelected() {
            this.storeDeleteSelected()
                .then((response) => {
                    if (response && response.success) {
                        this.fetchTickets();
                    }
                })
                .catch((error) => {
                    this.$handleError(error);
                });
        },

        closeSelected() {
            this.storeCloseSelected()
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.fetchTickets();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },

        getExcerpt(row) {
            let text = row.preview_response
                ? row.preview_response.content
                : row.content;

            if (!text) {
                return "";
            }
            return window.DOMPurify.sanitize(text, { ALLOWED_TAGS: [] });
        },

        resetFilters() {
            this.storeResetFilters(this.$route.query);
            this.fetchTickets();
        },

        resetWithOutFetch() {
            this.storeResetWithOutFetch();
        },

        getExcerptBox(text) {
            if (!text || typeof text !== "string") {
                return "";
            }
            return text.substring(0, 3).padEnd(5, ".");
        },

        countAdvancedFilterData(filters) {
            return filters.reduce((total, innerArray) => {
                if (Array.isArray(innerArray)) {
                    const validEntries = innerArray.filter((item) => {
                        if (typeof item.value === 'string') {
                            return item.value.trim() !== "";
                        }

                        if (Array.isArray(item.value)) {
                            return item.value.some(val => typeof val === 'string' && val.trim() !== "");
                        }

                        return false;
                    });
                    return total + validEntries.length;
                }
                return total;
            }, 0);
        },

        handleSaveSearch() {
            const store = useTicketListStore();
            if (!store.label_search_name || (store.filter_type === "advanced" && !store.advanced_filters.length)) {
                this.$notify({
                    message: this.$t("Please provide a valid name or filter criteria."),
                    type: "warning",
                    position: "bottom-right",
                });
                return;
            }
            this.isLabelSearchOpen = false;
            store.ticket_selections = [];
            store.loading = true;

            let query = {
                filter_type: store.filter_type,
                label_search_name: store.label_search_name,
            };

            if (store.filter_type === "advanced" && this.has_pro) {
                query.advanced_filters = JSON.stringify(store.advanced_filters);
            }

            if (store.label_search_id) {
                query.id = store.label_search_id;
            }

            this.storeHandleSaveSearch(query)
                .then((response) => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    store.label_search_id = null;
                    store.loading = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {});
        },

        fetchLabelSearch() {
            this.openLabelSearchDrawer = true;
            this.storeFetchLabelSearches()
                .always(() => {
                    const store = useTicketListStore();
                    store.loading = false;
                })
                .catch((error) => {
                    this.$handleError(error);
                });
        },

        handleAdvanceSearch(item) {
            this.storeHandleAdvanceSearch(item);
            this.openLabelSearchDrawer = false;
            this.fetchTickets();
        },

        handleLabelSearchEdit(item) {
            this.storeHandleLabelSearchEdit(item);
            this.openLabelSearchDrawer = false;
        },

        openSaveSearchModal() {
            this.isLabelSearchOpen = true;
            this.label_search_name = this.label_search_id ? this.label_search_name : '';
        },

        handleLabelSearchDelete(id) {
            this.storeHandleLabelSearchDelete(id)
                .then((response) => {
                    if (response) {
                        this.$notify({
                            message: response.message,
                            type: "success",
                            position: "bottom-right",
                        });
                    }
                })
                .catch((error) => {
                    this.$handleError(error);
                });
        },

        handleKeydown(event) {
            const { metaKey, altKey, shiftKey, code } = event;

            if (metaKey && altKey && code === "KeyI") {
                return;
            }

            if (metaKey && shiftKey && (code === "ArrowRight" || code === "ArrowLeft")) {
                event.preventDefault();
                const statusKeys = Object.keys(this.ticket_statuses_group || {});
                if (statusKeys.length === 0) return;

                const currentIndex = statusKeys.indexOf(this.filters.status_type);
                let newIndex;

                if (code === "ArrowRight") {
                    newIndex = (currentIndex + 1) % statusKeys.length;
                } else {
                    newIndex = (currentIndex - 1 + statusKeys.length) % statusKeys.length;
                }

                this.setStatus(statusKeys[newIndex]);
                return;
            }

            if (metaKey && altKey) {
                event.preventDefault();

                const keyActions = {
                    KeyN: () => {
                        this.add_ticket_modal = true;
                    },
                    KeyQ: () => {
                        this.refreshTickets();
                    },
                    KeyF: () => {
                        this.toggleAdvancedFilter(this.filter_type !== "advanced");
                    },
                    KeyR: () => {
                        this.resetAdvancedFilters();
                    },
                    KeyW: () => {
                        this.filters.waiting_for_reply = this.filters.waiting_for_reply === "yes" ? false : "yes";
                        this.maybeChangeWaitingReply();
                    },
                };

                const action = keyActions[code];
                if (action) {
                    action();
                }
            }
        },

        handleTicketSelection(ticketId) {
            this.storeHandleTicketSelection(ticketId);
        },

        maybeChangeWaitingReply() {
            this.fetchTickets();
        },

        toggleWaitingForReply() {
            // Mirrors the old el-switch: active-value="yes" :inactive-value="false"
            this.filters.waiting_for_reply = this.filters.waiting_for_reply === 'yes' ? false : 'yes';
            this.maybeChangeWaitingReply();
        },

        openSearch() {
            this.searchActive = true;
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },

        closeSearch() {
            this.searchActive = false;
            this.search = "";
            this.fetchTickets();
        },

        setStatus(statusKey) {
            this.storeSetStatus(statusKey);
            this.fetchTickets();
        },

        toggleAdvancedFilter(value) {
            if (!this.has_pro) return;

            if (value) {
                this.search = "";
                this.searchActive = false;
            }

            this.filter_type = value ? "advanced" : "simple";
            this.saveFilters();

            if (!value) {
                this.fetchTickets();
            }
        },

        handleFilterSelect(filterType) {
            this.showFilterSelection(filterType);
        },

        showFilterSelection(filterType) {
            switch(filterType) {
                case 'status':
                    this.showStatusFilter();
                    break;
                case 'priority':
                    this.showPriorityFilter();
                    break;
                case 'client_priority':
                    this.showClientPriorityFilter();
                    break;
                case 'agent_id':
                    this.showAgentFilter();
                    break;
                case 'product_id':
                    this.showProductFilter();
                    break;
                case 'mailbox_id':
                    this.showMailboxFilter();
                    break;
                case 'ticket_tags':
                    this.showTagsFilter();
                    break;
            }
        },

        showFilterDialog(title, options, callback) {
            let selectedValue = '';

            ElMessageBox({
                title: this.$t('title'),
                message: h('div', [
                    h('p', { style: 'margin-bottom: 10px;' }, this.$t('Select an option:')),
                    h(ElSelect, {
                        modelValue: selectedValue,
                        placeholder: this.$t('Please select'),
                        style: 'width: 100%',
                        'onUpdate:modelValue': (value) => {
                            selectedValue = value;
                        }
                    }, {
                        default: () => Object.entries(options).map(([value, label]) =>
                            h(ElOption, {
                                key: value,
                                label: label,
                                value: value
                            })
                        )
                    })
                ]),
                showCancelButton: true,
                confirmButtonText: this.$t('Apply'),
                cancelButtonText: this.$t('Cancel'),
                beforeClose: (action, instance, done) => {
                    if (action === 'confirm') {
                        if (selectedValue) {
                            callback(selectedValue);
                            done();
                        } else {
                            ElMessage.warning(this.$t('Please select an option'));
                        }
                    } else {
                        done();
                    }
                }
            }).catch(() => {});
        },

        showStatusFilter() {
            const statuses = this.appVars.ticket_statuses || {};
            const options = Object.keys(this.ticket_statuses_group || {}).reduce((acc, key) => {
                acc[key] = statuses[key] || this.$t(key);
                return acc;
            }, {});

            this.showFilterDialog('Select Status', options, (value) => {
                this.filters.status_type = value;
                this.fetchTickets();
            });
        },

        showPriorityFilter() {
            const priorities = this.appVars?.admin_priorities || {
                'low': 'Low',
                'normal': 'Normal',
                'medium': 'Medium',
                'high': 'High',
                'critical': 'Critical'
            };

            this.showFilterDialog('Select Priority', priorities, (value) => {
                this.filters.priority = value;
                this.fetchTickets();
            });
        },

        showClientPriorityFilter() {
            const priorities = this.appVars?.client_priorities || {
                'low': 'Low',
                'normal': 'Normal',
                'medium': 'Medium',
                'high': 'High',
                'critical': 'Critical'
            };

            this.showFilterDialog('Select Client Priority', priorities, (value) => {
                this.filters.client_priority = value;
                this.fetchTickets();
            });
        },

        showAgentFilter() {
            const agents = this.appVars?.support_agents || [];
            const options = {
                'unassigned': this.$t('Un-Assigned'),
                ...agents.reduce((acc, agent) => {
                    acc[agent.id] = agent.full_name;
                    return acc;
                }, {})
            };

            this.showFilterDialog('Select Agent', options, (value) => {
                this.filters.agent_id = value;
                this.fetchTickets();
            });
        },

        showProductFilter() {
            const products = this.appVars?.support_products || [];
            const options = products.reduce((acc, product) => {
                acc[product.id] = product.title;
                return acc;
            }, {});

            this.showFilterDialog('Select Product', options, (value) => {
                this.filters.product_id = [value];
                this.fetchTickets();
            });
        },

        showMailboxFilter() {
            const mailboxes = this.appVars?.mailboxes || [];
            const options = mailboxes.reduce((acc, mailbox) => {
                acc[mailbox.id] = mailbox.name;
                return acc;
            }, {});

            this.showFilterDialog('Select Mailbox', options, (value) => {
                this.filters.mailbox_id = value;
                this.fetchTickets();
            });
        },

        showTagsFilter() {
            const tags = this.appVars?.ticket_tags || [];
            const options = tags.reduce((acc, tag) => {
                acc[tag.id] = tag.title;
                return acc;
            }, {});

            this.showFilterDialog('Select Tags', options, (value) => {
                this.filters.ticket_tags = [parseInt(value)];
                this.fetchTickets();
            });
        },

        removeFilter(filterKey) {
            if (filterKey === 'status_type') {
                this.filters.status_type = 'open';
            } else if (Array.isArray(this.filters[filterKey])) {
                this.filters[filterKey] = [];
            } else {
                delete this.filters[filterKey];
            }
            if (this.$refs.customTicketFiltersRef && this.$refs.customTicketFiltersRef.clearFilterSelection) {
                this.$refs.customTicketFiltersRef.clearFilterSelection(filterKey);
            }

            if (filterKey === 'agent_id' && this.clearRouteFilters(['agent_id', 'watcher'])) {
                return;
            }

            if (filterKey === 'product_id' && this.clearRouteFilters(['product_id'])) {
                return;
            }

            this.fetchTickets();
        },

        /**
         * agent_id and watcher are mirrored in the URL, and the sidebar highlight is
         * derived from the URL alone. Clearing them from the store without updating the
         * route leaves the sidebar pointing at "My Tickets" over unfiltered data, so drop
         * them from the route instead and let the $route watchers sync the store + refetch.
         *
         * Returns true when a navigation was pushed, meaning the caller must not refetch.
         */
        clearRouteFilters(routeKeys) {
            const query = {...this.$route.query};
            const hasRouteFilter = routeKeys.some(key => key in query);

            // _TicketsMenu persists the sidebar query here and TicketsView replays it into
            // the URL on the next navigation, which would silently re-apply the filter.
            this.$removeData('routesData');

            if (!hasRouteFilter) {
                return false;
            }

            routeKeys.forEach(key => delete query[key]);
            this.$router.push({name: 'tickets', query});

            return true;
        },

        getFilterLabel(key) {
            const labels = {
                status_type: this.$t('Status'),
                priority: this.$t('Priority'),
                client_priority: this.$t('Client Priority'),
                agent_id: this.$t('Agent'),
                product_id: this.$t('Product'),
                mailbox_id: this.$t('Mailbox'),
                ticket_tags: this.$t('Tags'),
                agent_group: this.$t('Agent Group'),
                waiting_for_reply: this.$t('Waiting For Reply')
            };
            return labels[key] || key;
        },

        getFilterDisplayValue(key, value) {
            if (!value) return '';

            if (key === 'status_type') {
                return (this.appVars.ticket_statuses || {})[value] || this.$t(value);
            }
            if (key === 'priority') {
                if (Array.isArray(value)) {
                    return value.map(v => this.appVars?.admin_priorities?.[v] || v).join(', ');
                }
                return this.appVars?.admin_priorities?.[value] || value;
            }
            if (key === 'client_priority') {
                if (Array.isArray(value)) {
                    return value.map(v => this.appVars?.client_priorities?.[v] || v).join(', ');
                }
                return this.appVars?.client_priorities?.[value] || value;
            }
            if (key === 'agent_id') {
                if (Array.isArray(value)) {
                    return value.map(v => {
                        if (v === 'unassigned') return this.$t('Un-Assigned');
                        const agent = this.appVars?.support_agents?.find(a => a.id == v);
                        return agent ? agent.full_name : v;
                    }).join(', ');
                }
                if (value === 'unassigned') return this.$t('Un-Assigned');
                const agent = this.appVars?.support_agents?.find(a => a.id == value);
                return agent ? agent.full_name : value;
            }
            if (key === 'product_id' && Array.isArray(value)) {
                return value.map(id => {
                    const product = this.appVars?.support_products?.find(p => p.id == id);
                    return product ? product.title : id;
                }).join(', ');
            }
            if (key === 'mailbox_id') {
                if (Array.isArray(value)) {
                    return value.map(v => {
                        const mailbox = this.appVars?.mailboxes?.find(m => m.id == v);
                        return mailbox ? mailbox.name : v;
                    }).join(', ');
                }
                const mailbox = this.appVars?.mailboxes?.find(m => m.id == value);
                return mailbox ? mailbox.name : value;
            }
            if (key === 'ticket_tags' && Array.isArray(value)) {
                return value.map(id => {
                    const tag = this.appVars?.ticket_tags?.find(t => t.id == id);
                    return tag ? tag.title : id;
                }).join(', ');
            }
            if (key === 'agent_group' && Array.isArray(value)) {
                return value.map(id => {
                    const group = this.appVars?.agent_groups?.find(g => g.id == id);
                    return group ? group.title : id;
                }).join(', ');
            }
            if (key === 'waiting_for_reply') {
                return value === 'yes' ? this.$t('Yes') : this.$t('No');
            }
            return value;
        },

        filterCascaderOptions() {
            return [
                {
                    value: 'status',
                    label: this.$t('Status'),
                    children: Object.keys(this.ticket_statuses_group || {}).map(key => ({
                        value: key,
                        label: (this.appVars.ticket_statuses || {})[key] || this.$t(key),
                        filterType: 'status',
                        filterValue: key
                    }))
                },
                {
                    value: 'priority',
                    label: this.$t('Priority'),
                    children: Object.entries(this.appVars?.admin_priorities || {}).map(([key, label]) => ({
                        value: key,
                        label: label,
                        filterType: 'priority',
                        filterValue: key
                    }))
                },
                {
                    value: 'client_priority',
                    label: this.$t('Client Priority'),
                    children: Object.entries(this.appVars?.client_priorities || {}).map(([key, label]) => ({
                        value: key,
                        label: label,
                        filterType: 'client_priority',
                        filterValue: key
                    }))
                },
                {
                    value: 'agent_id',
                    label: this.$t('Agent'),
                    children: [
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
                    value: 'product_id',
                    label: this.$t('Product'),
                    children: (this.appVars?.support_products || []).map(product => ({
                        value: product.id,
                        label: product.title,
                        filterType: 'product_id',
                        filterValue: product.id
                    }))
                },
                {
                    value: 'mailbox_id',
                    label: this.$t('Mailbox'),
                    children: (this.appVars?.mailboxes || []).map(mailbox => ({
                        value: mailbox.id,
                        label: mailbox.name,
                        filterType: 'mailbox_id',
                        filterValue: mailbox.id
                    }))
                },
                {
                    value: 'ticket_tags',
                    label: this.$t('Tags'),
                    children: (this.appVars?.ticket_tags || []).map(tag => ({
                        value: tag.id,
                        label: tag.title,
                        filterType: 'ticket_tags',
                        filterValue: tag.id
                    }))
                }
            ];
        },
        handleFilterCascaderChange() {
            if (this.selectedFilterPath.length === 2) {
                const [categoryValue, optionValue] = this.selectedFilterPath;

                const category = this.filterCascaderOptions.find(cat => cat.value === categoryValue);
                if (category) {
                    const option = category.children.find(child => child.value === optionValue);
                    if (option) {
                        const { filterType, filterValue } = option;

                        switch (filterType) {
                            case 'status':
                                this.filters.status_type = filterValue;
                                break;
                            case 'priority':
                                this.filters.priority = filterValue;
                                break;
                            case 'client_priority':
                                this.filters.client_priority = filterValue;
                                break;
                            case 'agent_id':
                                this.filters.agent_id = filterValue;
                                break;
                            case 'product_id':
                                this.filters.product_id = [filterValue];
                                break;
                            case 'mailbox_id':
                                this.filters.mailbox_id = filterValue;
                                break;
                            case 'ticket_tags':
                                this.filters.ticket_tags = [parseInt(filterValue)];
                                break;
                        }

                        this.fetchTickets();
                        this.filterPopoverVisible = false;
                        this.selectedFilterPath = [];
                    }
                }
            }
        },

        getFilterIcon(filterKey) {
            const iconMap = {
                'mailbox_id': 'filtersBusinessBox',
                'product_id': 'filtersProduct',
                'agent_id': 'filtersAgentIcon',
                'agent_group': 'agentGroupIcon',
                'priority': 'filtersAdminPriority',
                'client_priority': 'filtersCustomerPriority',
                'ticket_tags': 'filtersTagIcon',
                'status_type': 'circleCheck'
            };
            return iconMap[filterKey] || 'filter';
        },

        removeData(key) {
            let existingData = this.$getData('__fluentsupport_data');
            if (!existingData) {
                return;
            }
            existingData = JSON.parse(existingData);
            delete existingData[key];
            this.$saveData('__fluentsupport_data', JSON.stringify(existingData));
        },

        clearAllFilters() {
            this.storeClearAllFilters();
            if (this.$refs.customTicketFiltersRef) {
                this.$refs.customTicketFiltersRef.clearAllSelections();
            }
            this.removeData('tickets_filter');
            this.removeData('tickets_pref');

            if (this.clearRouteFilters(['agent_id', 'watcher', 'product_id'])) {
                return;
            }

            this.fetchTickets();
        },

        resetAdvancedFilters() {
            this.storeResetAdvancedFilters();
            this.removeData('tickets_advanced_filters');
            this.removeData('tickets_filter_type');
            this.removeData('tickets_pref');
            this.fetchTickets();
        },

        handleProductFilterChange() {
            if (this.$refs.customTicketFiltersRef && this.$refs.customTicketFiltersRef.updateFilterSelection) {
                this.$refs.customTicketFiltersRef.updateFilterSelection('product_id', this.filters.product_id);
            }
            this.fetchTickets();
        },

        handleCustomFilter(filterType, filterValue) {
            this.storeHandleCustomFilter(filterType, filterValue);
            this.fetchTickets();
        },

        handleSelectAll(checked) {
            this.storeHandleSelectAll(checked);
        },

        handleBulkActionsToggle(value) {
            this.$saveData("tickets_show_bulk_actions", value ? 'yes' : 'no');
            if (!value) {
                this.ticket_selections = [];
            }
        },

        handleMoreAction(command) {
            if (command === 'show_bulk') {
                this.show_bulk_actions = true;
                this.handleBulkActionsToggle(true);
            } else if (command === 'hide_bulk') {
                this.show_bulk_actions = false;
                this.handleBulkActionsToggle(false);
            }
        },

        setViewMode(mode) {
            this.viewMode = mode;
            this.$saveData('tickets_view_mode', mode);
        },

        getInitials(customer) {
            if (!customer) return '?';
            const first = (customer.first_name || '')[0] || '';
            const last = (customer.last_name || '')[0] || '';
            return (first + last).toUpperCase() || '?';
        },

        saveFieldVisibility() {
            this.$saveData('tickets_field_visibility', this.fieldVisibility);
        },

        saveGridFieldVisibility() {
            this.$saveData('tickets_grid_field_visibility', this.gridFieldVisibility);
        },

        saveActiveVisibility() {
            if (this.ticketLayout === 'grid') {
                this.saveGridFieldVisibility();
            } else {
                this.saveFieldVisibility();
            }
        },

        resetFieldVisibility() {
            const store = useTicketListStore();
            store.fieldVisibility = {
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
                waiting_time: true
            };
            this.saveFieldVisibility();
        },

        resetGridFieldVisibility() {
            const store = useTicketListStore();
            store.gridFieldVisibility = {
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
            };
            this.saveGridFieldVisibility();
        },

        resetActiveVisibility() {
            if (this.ticketLayout === 'grid') {
                this.resetGridFieldVisibility();
            } else {
                this.resetFieldVisibility();
            }
        },

        setTicketLayout(layout) {
            this.$saveData('tickets_layout', layout);
        },
    },
    mounted() {
        const store = useTicketListStore();
        store.app_ready = true;
        this.setFromSaveFilters();

        if (this.$route.query.search) {
            this.storeResetWithOutFetch();
            store.filter_type = "simple";
            store.advanced_filters = [[]];
        }

        if (this.$route.query.agent_id) {
            store.filters.agent_id = this.$route.query.agent_id;
            store.filters.watcher = this.$route.query.watcher;
        }
        if (this.$route.query.waiting_for_reply) {
            store.filters.waiting_for_reply = this.$route.query.waiting_for_reply;
        }
        // Checked by key presence, not truthiness: an empty string means the
        // Ticket Overview drill-down explicitly wants no product filter
        // (see TicketOverview.vue's handleViewDetails()), which must still
        // override a stale filter left in the persistent store. The key
        // being absent entirely (e.g. arriving from the sidebar) leaves any
        // saved filter alone.
        if ('product_id' in this.$route.query) {
            const productId = this.$route.query.product_id;
            store.filters.product_id = productId ? (Array.isArray(productId) ? productId : [productId]) : [];
        }

        if (this.$route.query.tags) {
            const tagIds = this.$route.query.tags;
            if (typeof tagIds === "object") {
                store.filters.ticket_tags = tagIds.map((tagId) => {
                    return parseInt(tagId);
                });
            } else {
                store.filters.ticket_tags = [parseInt(tagIds)];
            }
        }

        if (this.$route.query.search) {
            store.search = this.$route.query.search;
        }

        if (this.$route.query.filter_type) {
            store.filter_type = this.$route.query.filter_type;
            store.filters.status_type = this.$route.query.status_type;
        }

        // Cache-aware fetch: show cached data instantly, then silent background refresh
        if (store.shouldShowCache) {
            store.first_time_loading = false;
            this.$nextTick(() => {
                store.fetchTickets(true, true)
                    .then(() => this.saveFilters())
                    .catch(() => {});
            });
        } else {
            this.$nextTick(() => {
                this.fetchTickets();
            });
        }

        this.$setTitle(this.$t("All Tickets"));
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.addEventListener("keydown", this.handleKeydown);
        }
    },
    beforeUnmount() {
        if(this.appVars.keyboard_shortcuts === 'yes') {
            window.removeEventListener("keydown", this.handleKeydown);
        }
    }
};
</script>
