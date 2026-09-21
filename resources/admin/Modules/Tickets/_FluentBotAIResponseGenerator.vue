<template>
    <div class="fs_container fs_fluent_bot_ai_response_generator">
        <div class="fs_ai_header_section">
            <el-select
                v-if="!noBotAvailable"
                v-model="selectedProduct"
                :placeholder="$t('Select a product')"
                size="small"
                class="fs_select_field fs_product_dropdown fs_header_product_select"
                filterable
                clearable
                :value-key="'id'"
                :default-value="0"
            >
                <el-option
                    v-if="hasGeneralBot"
                    :key="0"
                    :label="$t('General Bot')"
                    :value="0"
                />
                <el-option
                    v-for="product in products"
                    :key="product.id"
                    :label="product.title"
                    :value="product.id"
                />
            </el-select>

            <el-dropdown
                v-if="!noBotAvailable"
                trigger="click"
                class="fs_header_more"
                @command="handleConversationCommand"
            >
                <el-button class="fs_action_button fs_header_more_btn" text icon="More" :aria-label="$t('Conversation options')" />
                <template #dropdown>
                    <el-dropdown-menu class="fs_global_dropdown">
                        <el-dropdown-item command="new">
                            <div class="fs_dropdown_item_content">
                                <el-icon class="fs_dropdown_icon"><Plus /></el-icon>
                                {{ $t('New conversation') }}
                            </div>
                        </el-dropdown-item>
                        <el-dropdown-item command="past" divided>
                            <div class="fs_dropdown_item_content">
                                <el-icon class="fs_dropdown_icon"><Clock /></el-icon>
                                {{ $t('Past conversations') }}
                            </div>
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </template>
            </el-dropdown>

            <el-button class="fs_close_button" @click="closeModal" :aria-label="$t('Close panel')">
                <img :src="appVars.asset_url + 'images/closeIcon.svg'" :alt="$t('Close panel')">
            </el-button>
        </div>

        <div class="fs_draft" v-if="draftData.length > 1">
            <el-button class="fs_draft_button" @click="showDraft = !showDraft">
                <span>{{ $t('Draft') }}</span>
                <img :class="['fs_draft_arrow', { 'rotate-down': showDraft }]" :src="appVars.asset_url + 'images/arrowRight.svg'" alt="">
            </el-button>
            <div>
                <el-collapse-transition>
                    <div class="fs_draft_widget" v-show="showDraft" >
                        <div
                            v-for="(draft, index) in draftData"
                            :key="index"
                            class="fs_draft_item"
                            @click="selectDraft(draft)"
                        >
                            <h3>Draft {{index+1}}</h3>
                            <p>{{ getSnippet(draft) }}</p>
                        </div>
                    </div>
                </el-collapse-transition>
            </div>
        </div>

        <div class="fs_response_section" ref="chatContainer">
            <div v-if="loadingMessages" class="fs_messages_loading">
                <el-skeleton :rows="3" animated />
            </div>
            <div v-else-if="noBotAvailable && messages.length === 0 && !hasLiveActivityHere" class="fs_empty_state fs_empty_state_no_bot">
                <div class="fs_empty_state_icon">
                    <img
                        loading="lazy"
                        :src="appVars.asset_url + 'images/fluentBotDark.svg'"
                        class="fs_icon"
                    />
                </div>
                <div class="fs_empty_state_text">{{ $t('No FluentBot configured') }}</div>
                <div class="fs_empty_state_description">{{ $t('Add a general bot or a product-specific bot in FluentBot settings before chatting.') }}</div>
                <router-link
                    v-if="canManageBotSettings"
                    :to="{ name: 'fluent_bot_integration' }"
                    class="el-button el-button--primary fs_filled_btn fs_empty_state_cta"
                >
                    {{ $t('Open FluentBot settings') }}
                </router-link>
            </div>
            <div v-else-if="noBotForThisProduct && messages.length === 0 && !hasLiveActivityHere" class="fs_empty_state fs_empty_state_no_bot">
                <div class="fs_empty_state_icon">
                    <img
                        loading="lazy"
                        :src="appVars.asset_url + 'images/fluentBotDark.svg'"
                        class="fs_icon"
                    />
                </div>
                <div class="fs_empty_state_text">{{ $t('No bot for this product') }}</div>
                <div class="fs_empty_state_description">{{ $t('No bot is mapped to this ticket\'s product. Pick a configured product from the dropdown above, or add a bot for this product in FluentBot settings.') }}</div>
                <router-link
                    v-if="canManageBotSettings"
                    :to="{ name: 'fluent_bot_integration' }"
                    class="el-button el-button--primary fs_filled_btn fs_empty_state_cta"
                >
                    {{ $t('Open FluentBot settings') }}
                </router-link>
            </div>
            <div v-else-if="messages.length === 0 && !hasLiveActivityHere" class="fs_empty_state">
                <div class="fs_empty_state_icon">
                    <img
                        loading="lazy"
                        :src="appVars.asset_url + 'images/fluentBotDark.svg'"
                        class="fs_icon"
                    />
                </div>
                <div class="fs_empty_state_text">{{ $t(title) }}</div>
                <div class="fs_empty_state_description">{{ $t(description) }}</div>
            </div>

            <div v-if="messages.length > 0 || hasLiveActivityHere" class="fs_chat_messages">
                <div v-if="nextCursor" class="fs_load_more">
                    <el-button text size="small" :disabled="loadingMessages" @click="fetchChatMessages(nextCursor)">{{ $t('Load older messages') }}</el-button>
                </div>
                <template v-for="(msg, index) in messages" :key="msg.message_id || `local-${index}`">
                    <div :class="['fs_chat_row', 'fs_chat_row_' + msg.role]">
                        <div :class="['fs_chat_bubble', 'fs_chat_' + msg.role]">
                            <div v-if="msg.role === 'user'" class="fs_chat_bubble_content">{{ msg.content }}</div>
                            <template v-else>
                                <div class="fs_chat_bubble_content fs_response_text" v-html="msg.htmlContent"></div>
                                <div v-if="msg.sources && msg.sources.length" class="fs_chat_sources">
                                    <ul>
                                        <li v-for="(source, sIdx) in msg.sources" :key="source.url || source.source || sIdx">
                                            <a :href="sanitizeUrl(source.url || source.source)" target="_blank" rel="noopener noreferrer">{{ source.title }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div v-if="msg.role === 'ai'" class="fs_chat_actions">
                        <button
                            v-if="msg.message_id && (!msg.feedback || msg.feedback.reaction === 'positive')"
                            :class="['fs_feedback_btn', { 'fs_feedback_active': msg.feedback && msg.feedback.reaction === 'positive' }]"
                            :aria-pressed="msg.feedback && msg.feedback.reaction === 'positive'"
                            :aria-label="$t('Helpful')"
                            @click="handleFeedback(index, 'positive')"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                        </button>
                        <button
                            v-if="msg.message_id && (!msg.feedback || msg.feedback.reaction === 'negative')"
                            :class="['fs_feedback_btn', { 'fs_feedback_active fs_feedback_negative': msg.feedback && msg.feedback.reaction === 'negative' }]"
                            :aria-pressed="msg.feedback && msg.feedback.reaction === 'negative'"
                            :aria-label="$t('Not helpful')"
                            @click="handleFeedback(index, 'negative')"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10zM17 2h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                        </button>
                        <button class="fs_feedback_btn" @click="copyMessageText(msg.content)" :aria-label="$t('Copy')" :title="$t('Copy')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <button class="fs_feedback_btn fs_insert_btn" @click="insertReply(msg.content)" :title="$t('Insert to editor')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                            <span>{{ $t('Insert') }}</span>
                        </button>
                    </div>
                </template>

                <div v-if="isGeneratingHere" class="fs_chat_bubble fs_chat_ai">
                    <div class="fs_chat_bubble_content fs_response_text" v-html="formattedResponse"></div>
                    <div v-if="typingQueue.length === 0" class="fs_streaming_indicator">
                        <span>{{ streamStatusText }}</span><span class="fs_typing_dots">●●●</span>
                    </div>
                </div>

                <div v-if="isAwaitingHere" class="fs_chat_bubble fs_chat_ai">
                    <el-skeleton :rows="2" animated />
                </div>

                <div v-if="errorMessage" class="fs_chat_error">
                    {{ errorMessage }}
                </div>
            </div>
        </div>

        <div class="fs_main_content">
            <div class="fs_prompt_presets_scroll">
                <el-tooltip
                    v-for="preset in presetPrompts"
                    :key="preset.text"
                    :content="preset.description"
                    placement="top"
                    effect="dark"
                    popper-class="fs_tooltip fs_preset_tooltip"
                >
                    <div
                        :class="['fs_preset_chip', { 'fs_preset_chip_selected': preset === selectedPrompt, 'disabled': isStreaming || composerDisabled }]"
                        role="button"
                        :tabindex="isStreaming || composerDisabled ? -1 : 0"
                        :aria-disabled="isStreaming || composerDisabled"
                        @click="!isStreaming && !composerDisabled && selectPresetPrompt(preset)"
                        @keydown.enter.prevent="!isStreaming && !composerDisabled && selectPresetPrompt(preset)"
                        @keydown.space.prevent="!isStreaming && !composerDisabled && selectPresetPrompt(preset)"
                    >
                        {{ preset.label }}
                    </div>
                </el-tooltip>
            </div>
            <div class="fs_prompt_box">
                <textarea ref="promptTextarea" v-model="prompt" rows="2" :placeholder="composerPlaceholder" class="fs_textarea" required :disabled="isStreaming || composerDisabled" @input="autoResizeTextarea" @keydown.enter.exact.prevent="sendMessage"></textarea>
                <div class="fs_prompt_toolbar">
                    <div class="fs_toolbar_left">
                        <el-tooltip :content="includeTicketContent ? $t('Ticket content included') : $t('Ticket content excluded')" placement="top" effect="dark" popper-class="fs_tooltip">
                            <button type="button" :class="['fs_toolbar_btn', { 'fs_toolbar_btn_off': !includeTicketContent }]" @click="includeTicketContent = !includeTicketContent">
                                <svg v-if="includeTicketContent" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                <span>{{ $t('Ticket Content') }}</span>
                            </button>
                        </el-tooltip>
                        <el-tooltip :content="webSearch ? $t('Web search enabled') : $t('Web search disabled')" placement="top" effect="dark" popper-class="fs_tooltip">
                            <button type="button" :class="['fs_toolbar_btn', { 'fs_toolbar_btn_off': !webSearch }]" @click="webSearch = !webSearch">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M11 3a13 13 0 0 0 0 16M11 3a13 13 0 0 1 0 16M3 11h16"/><line v-if="!webSearch" x1="2" y1="2" x2="20" y2="20"/></svg>
                                <span>{{ $t('Web Search') }}</span>
                            </button>
                        </el-tooltip>
                        <div class="fs_temp_toggle_wrap fs_toolbar_popup_wrap">
                            <button type="button" class="fs_toolbar_btn" :aria-label="$t('Temperature')" aria-haspopup="dialog" :aria-expanded="showTempPicker" @click="showTempPicker = !showTempPicker">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                                <span>{{ temperature.toFixed(1) }}</span>
                            </button>
                            <div v-if="showTempPicker" class="fs_temp_panel fs_toolbar_popup">
                                <div class="fs_temp_head">
                                    <span>{{ $t('Temperature') }}</span>
                                    <span class="fs_temp_value">{{ temperature.toFixed(1) }}</span>
                                </div>
                                <div :class="['fs_temp_guide', 'fs_temp_guide_' + temperatureGuide.tone]">
                                    <div class="fs_temp_guide_head">
                                        <span class="fs_temp_guide_range">{{ temperatureGuide.range }}</span>
                                        <span v-if="temperatureGuide.recommended" class="fs_temp_guide_tag">{{ $t('Recommended') }}</span>
                                    </div>
                                    <p class="fs_temp_guide_behavior">{{ temperatureGuide.behavior }}</p>
                                    <p class="fs_temp_guide_row">
                                        <span class="fs_temp_guide_label">{{ $t('Watch out') }}</span>{{ temperatureGuide.risk }}
                                    </p>
                                    <p class="fs_temp_guide_row">
                                        <span class="fs_temp_guide_label">{{ $t('Best for') }}</span>{{ temperatureGuide.fit }}
                                    </p>
                                </div>
                                <input
                                    type="range"
                                    class="fs_temp_range"
                                    :min="0"
                                    :max="2"
                                    :step="0.1"
                                    v-model.number="temperature"
                                    :style="{ '--fs-temp-pct': (temperature / 2 * 100) + '%' }"
                                    :aria-label="$t('Temperature')"
                                />
                                <div class="fs_temp_labels">
                                    <span>{{ $t('Precise (0)') }}</span>
                                    <span>{{ $t('Creative (2)') }}</span>
                                </div>
                                <p class="fs_temp_hint">{{ $t('Higher = more varied answers. Saved in this browser.') }}</p>
                            </div>
                        </div>
                        <div class="fs_context_toggle_wrap fs_toolbar_popup_wrap">
                            <el-tooltip :content="contextPickerTooltip" placement="top" effect="dark" popper-class="fs_tooltip">
                                <button
                                    type="button"
                                    :class="['fs_toolbar_btn', { 'fs_toolbar_btn_off': ticketConversations.length === 0 }]"
                                    :disabled="ticketConversations.length === 0"
                                    :aria-label="contextPickerTooltip"
                                    @click="ticketConversations.length > 0 && (showContextPicker = !showContextPicker)"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4c0-1.1.9-2 2-2h8a2 2 0 0 1 2 2v5Z"/>
                                        <path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/>
                                    </svg>
                                    <span>{{ selectedContextIds.length }}/{{ ticketConversations.length }}</span>
                                </button>
                            </el-tooltip>
                            <div v-if="showContextPicker" class="fs_context_picker fs_toolbar_popup">
                                <div class="fs_context_search">
                                    <input
                                        ref="contextSearchInput"
                                        v-model="contextSearchQuery"
                                        :placeholder="$t('Search responses...')"
                                        type="text"
                                    />
                                </div>
                                <div class="fs_context_list">
                                    <div
                                        v-for="conv in filteredContextConversations"
                                        :key="conv.id"
                                        class="fs_context_item"
                                    >
                                        <el-checkbox
                                            :model-value="selectedContextIds.includes(conv.id)"
                                            @change="toggleContext(conv.id)"
                                            size="small"
                                        />
                                        <el-tooltip
                                            :content="decodeHtml(conv.preview)"
                                            placement="top"
                                            effect="dark"
                                            popper-class="fs_tooltip fs_context_tooltip"
                                            :show-after="500"
                                            :disabled="!conv.preview || conv.preview.length < 25"
                                        >
                                            <div class="fs_context_label"
                                                 role="button"
                                                 tabindex="0"
                                                 @click="toggleContext(conv.id)"
                                                 @keydown.enter.prevent="toggleContext(conv.id)"
                                                 @keydown.space.prevent="toggleContext(conv.id)">
                                                <span class="fs_context_role" :class="'fs_role_' + conv.role">{{ conv.role === 'customer' ? $t('Customer') : (conv.name || $t('Agent')) }}</span>
                                                <span class="fs_context_preview">{{ conv.preview }}</span>
                                            </div>
                                        </el-tooltip>
                                    </div>
                                </div>
                                <div class="fs_context_actions">
                                    <el-button size="small" text @click="selectedContextIds = ticketConversations.map(c => c.id)">{{ $t('All') }}</el-button>
                                    <el-button size="small" text @click="selectedContextIds = []">{{ $t('None') }}</el-button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="fs_toolbar_send" @click="generateResponse(prompt)" :disabled="isStreaming || loadingMessages || composerDisabled" :aria-label="$t('Send message')">
                        <img :src="appVars.asset_url + 'images/aiPromptSubmitButton.svg'" alt="">
                    </button>
                </div>
            </div>
        </div>

        <el-dialog
            v-model="botSwitchDialogVisible"
            :title="$t('Switch Knowledge Base')"
            width="400px"
            :close-on-click-modal="false"
            append-to-body
            class="fs_dialog"
            @close="cancelProductSwitch"
        >
            <p>{{ $t('Switching products changes the knowledge base used for responses. What would you like to do with the current conversation?') }}</p>
            <template #footer>
                <div class="fs_dialog_footer">
                    <el-button @click="cancelProductSwitch">{{ $t('Cancel') }}</el-button>
                    <el-button @click="forkConversation">{{ $t('Keep Messages') }}</el-button>
                    <el-button class="fs_filled_btn" @click="newConversation">{{ $t('Start Fresh') }}</el-button>
                </div>
            </template>
        </el-dialog>

        <el-dialog
            v-model="pastDialogVisible"
            :title="$t('Past conversations')"
            width="400px"
            append-to-body
            class="fs_dialog fs_past_conversations_dialog"
        >
            <div v-if="loadingPast" class="fs_past_loading">
                <el-skeleton :rows="3" animated />
            </div>
            <div v-else-if="pastConversations.length === 0" class="fs_past_empty">
                {{ $t('No past conversations yet.') }}
            </div>
            <ul v-else class="fs_past_list">
                <li
                    v-for="conv in pastConversations"
                    :key="conv.chat_id"
                    :class="['fs_past_item', { 'fs_past_item_active': conv.chat_id === activeChatId }]"
                    role="button"
                    tabindex="0"
                    @click="switchToConversation(conv)"
                    @keydown.enter.prevent="switchToConversation(conv)"
                >
                    <span class="fs_past_title">{{ conv.title }}</span>
                    <span class="fs_past_meta">
                        <span v-if="conv.chat_id === activeChatId" class="fs_past_active_badge">{{ $t('Active') }}</span>
                        {{ formatPastDate(conv.created_at) }}
                    </span>
                </li>
            </ul>
        </el-dialog>

        <el-dialog
            v-model="feedbackDialogVisible"
            :title="$t('We\'re sorry — can you tell us what went wrong?')"
            width="360px"
            :close-on-click-modal="false"
            append-to-body
            class="fs_dialog"
            @close="cancelFeedback"
        >
            <textarea
                ref="feedbackTextarea"
                v-model="feedbackComment"
                :placeholder="$t('Optional: tell us what was wrong...')"
                class="fs_feedback_textarea"
                rows="3"
            ></textarea>
            <template #footer>
                <div class="fs_dialog_footer">
                    <el-button @click="cancelFeedback">{{ $t('Cancel') }}</el-button>
                    <el-button class="fs_filled_btn" @click="submitNegativeFeedback(feedbackComment)">{{ $t('Submit') }}</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import { marked } from 'marked';
import hljs from 'highlight.js';
import { Plus, Clock } from '@element-plus/icons-vue';
import { parseMarkdown } from '@/admin/Composable/useMarkdown';
import { $t } from '@/admin/Bits/i18n';
import 'highlight.js/styles/github.css'; // GitHub theme for syntax highlighting

// Temperature slider guidance. `max` is an exclusive upper bound, ordered ascending;
// the last row catches everything above.
const TEMPERATURE_BANDS = [
    { max: 0.05, tone: 'neutral', range: $t('0.0 — Deterministic'), behavior: $t('Same prompt gives near-identical answers. Tightest grounding in your documents, but terse and formulaic.'), risk: $t('can repeat itself, and keeps one framing even when that framing is wrong.'), fit: $t('knowledge-base answers, extraction, classification.') },
    { max: 0.35, tone: 'safe', recommended: true, range: $t('0.1–0.3 — Grounded'), behavior: $t('Still tightly grounded, with slight wording variation between runs. Reads noticeably less robotic.'), risk: $t('almost nothing. Safest natural-sounding band.'), fit: $t('support replies, ticket summaries.') },
    { max: 0.75, tone: 'neutral', range: $t('0.4–0.7 — Balanced'), behavior: $t('Clear rewording run to run. Starts paraphrasing your sources instead of quoting them.'), risk: $t('mild fact drift — it will smooth over gaps in the context instead of flagging them.'), fit: $t('general assistant chat, copy drafting.') },
    { max: 1.05, tone: 'caution', range: $t('0.8–1.0 — Creative'), behavior: $t('Varied structure and wording. Will volunteer information that is not in your documents.'), risk: $t('made-up details climb clearly, and tool arguments get sloppy.'), fit: $t('brainstorming, marketing copy.') },
    { max: 1.55, tone: 'warning', range: $t('1.1–1.5 — Divergent'), behavior: $t('Strong divergence. Novel phrasings and unusual analogies.'), risk: $t('formatting breaks down and the answer can contradict its own context.'), fit: $t('idea generation only.') },
    { max: Infinity, tone: 'danger', range: $t('1.6–2.0 — Unstable'), behavior: $t('Rare tokens appear often and coherence degrades fast.'), risk: $t('word salad, unexpected language switching, runaway repetition.'), fit: $t('not usable for customer-facing replies.') }
];

export default {
    name: 'FluentBotAIResponseGenerator',
    components: { Plus, Clock },
    props: ['aiProvider', 'ticketId', 'productID', 'addContextId', 'ticketResponses'],
    emits: ['close', 'insert', 'contextAdded'],

    data() {
        return {
            prompt: '',
            presetPrompt: '',
            errorMessage: '',
            aiResponse: '',
            loading: false,
            selectedPrompt: '',
            isFullSize: true,
            presetPrompts: [],
            draftData: [],
            showDraft: false,
            finalPrompts: '',
            products: [],
            hasGeneralBot: false,
            selectedProduct: this.productID ? parseInt(this.productID) : 0,
            chatId: null,
            isStreaming: false,
            // Which conversation owns the in-flight turn. isStreaming is a global
            // lock (only one stream at a time); this says *where* it belongs, so
            // switching away doesn't leave a stale "Generating" on another chat.
            streamingChatId: null,
            // Epoch an unnamed in-flight turn started under; a reset bumps the epoch so
            // its bubble stops belonging to a freshly-opened conversation.
            streamingEpoch: null,
            // Latest `status` frame ({ stage, label? }) — what the agent is doing
            // before the first token arrives. Cleared once content starts streaming.
            streamStatus: null,
            streamBuffer: '',
            displayedText: '',
            typingQueue: [],
            messages: [],
            nextCursor: null,
            loadingMessages: false,
            pendingSources: null,
            feedbackDialogVisible: false,
            feedbackComment: '',
            pendingFeedbackMessageId: null,
            pastDialogVisible: false,
            pastConversations: [],
            activeChatId: null,
            loadingPast: false,
            switchingConversation: false,
            pendingMessageId: null,
            ticketConversations: [],
            selectedContextIds: [],
            showContextPicker: false,
            showTempPicker: false,
            contextSearchQuery: '',
            includeTicketContent: true,
            webSearch: false,
            temperature: (() => {
                const v = parseFloat(localStorage.getItem('fs_fluentbot_temperature'));
                return Number.isFinite(v) ? Math.min(2, Math.max(0, v)) : 0;
            })(),
            contextReady: false,
            botSwitchDialogVisible: false,
            pendingProductSwitch: null,
            seedMessages: null,
            configReady: false
        };
    },

    created() {
        this._isRevertingProduct = false;
        this._streamAbort = null;
        this._resumeAbort = null;
        this._requestEpoch = 0;
        // In-flight chat-history fetch, so a mid-load switch can wait then refetch.
        this._historyFetch = null;
        // Identifies a send so a superseded stream settling late can't touch state
        // that now belongs to a newer turn.
        this._streamToken = 0;
        this._turnPrompt = null;
        this._pendingChatReset = false;
        this._streamBuffer = '';
        this._streamRafId = null;
        this._configuredProductIds = new Set();
    },

    computed: {
        // Does the in-flight turn belong to the conversation on screen? A named turn
        // matches by chat id; an unnamed one (chat_id not yet arrived) belongs only if
        // no reset has bumped the epoch since it started.
        turnBelongsHere() {
            if (this.streamingChatId !== null) return this.streamingChatId === this.chatId;
            return this.streamingEpoch === this._requestEpoch;
        },
        // Streaming tokens for THIS conversation.
        isGeneratingHere() {
            return this.isStreaming && this.turnBelongsHere;
        },
        // What the agent is doing right now, from the backend's `status` frames.
        // A tool-supplied label (e.g. an MCP tool name) wins over the stage map;
        // an unknown or absent stage falls back to the generic wording.
        streamStatusText() {
            const s = this.streamStatus;
            if (s && s.label) {
                return String(s.label);
            }
            const stages = {
                searching: this.$t('Searching the knowledge base'),
                web_searching: this.$t('Searching the web'),
                reading_sources: this.$t('Reading sources'),
                calling_tool: this.$t('Working'),
            };
            return (s && stages[s.stage]) || this.$t('Generating');
        },
        // Request sent for THIS conversation, no tokens yet.
        isAwaitingHere() {
            return this.loading && !this.isStreaming && !this.aiResponse && this.turnBelongsHere;
        },
        // Anything to render for THIS conversation beyond its stored messages.
        // Drives the transcript container and suppresses the empty state.
        hasLiveActivityHere() {
            return this.isGeneratingHere || this.isAwaitingHere;
        },
        ticketResponseIds() {
            return (this.ticketResponses || [])
                .filter(c => c.conversation_type === 'response')
                .map(c => c.id)
                .join(',');
        },
        filteredContextConversations() {
            if (!this.contextSearchQuery.trim()) {
                return this.ticketConversations;
            }
            const q = this.contextSearchQuery.toLowerCase();
            return this.ticketConversations.filter(c =>
                (c.preview && c.preview.toLowerCase().includes(q)) ||
                (c.name && c.name.toLowerCase().includes(q)) ||
                (c.role && c.role.toLowerCase().includes(q))
            );
        },
        title() {
            return 'Generate Responses with FluentBot';
        },
        noBotAvailable() {
            return this.configReady && !this.hasGeneralBot && this.products.length === 0;
        },
        // Ticket's own product has no mapped bot, no general bot, but OTHER products are configured.
        // User must pick a configured product from the dropdown before chatting.
        noBotForThisProduct() {
            if (!this.configReady || this.hasGeneralBot || this.products.length === 0) return false;
            const sel = parseInt(this.selectedProduct) || 0;
            return sel === 0 || !this._configuredProductIds.has(sel);
        },
        composerDisabled() {
            return this.noBotAvailable || this.noBotForThisProduct;
        },
        composerPlaceholder() {
            if (this.noBotAvailable) return this.$t('Configure a bot in FluentBot settings to start chatting');
            if (this.noBotForThisProduct) return this.$t('Pick a configured product from the dropdown to start chatting');
            return this.$t('Enter your prompt here...');
        },
        contextPickerTooltip() {
            const total = this.ticketConversations.length;
            if (total === 0) {
                return this.$t('No prior replies on this ticket yet — nothing to pick as context');
            }
            const selected = this.selectedContextIds.length;
            if (selected === total) {
                return this.$t('All ticket replies sent as context');
            }
            if (selected === 0) {
                return this.$t('No ticket replies sent as context');
            }
            return this.$t('{0} of {1} ticket replies sent as context')
                .replace('{0}', selected)
                .replace('{1}', total);
        },
        // Live guidance for the temperature slider — band resolved from the current value.
        temperatureGuide() {
            const band = TEMPERATURE_BANDS.find(b => this.temperature < b.max);
            return {
                ...band
            };
        },
        canManageBotSettings() {
            return !!(this.appVars?.me?.permissions?.includes('fst_manage_settings'));
        },
        description() {
            return 'Let FluentBot generate ticket responses to enhance support efficiency.';
        },
        formattedResponse() {
            return parseMarkdown(this.aiResponse);
        },
    },

    watch: {
        // When the parent ticket route changes while the panel stays mounted (v-show),
        // hard-reset chat/context state so API calls never target the previous ticket.
        ticketId(newId, oldId) {
            if (newId === oldId) return;
            this.resetForTicketChange();
        },
        productID(newPid) {
            const next = newPid ? parseInt(newPid) : 0;
            this.setProductSilently(next);
            if (this.configReady) {
                this.applyProductFallback();
            }
        },
        temperature(v) {
            const clamped = Math.min(2, Math.max(0, Number.isFinite(v) ? v : 0));
            if (clamped !== v) {
                this.temperature = clamped;
                return;
            }
            try {
                localStorage.setItem('fs_fluentbot_temperature', String(clamped));
            } catch (e) {}
        },
        addContextId: {
            handler(id) {
                if (id) {
                    this.applyPendingContextId(id);
                }
            },
            immediate: true
        },
        selectedContextIds: {
            handler() {
                if (this.contextReady) {
                    this.saveContextSelection();
                }
            },
            deep: true
        },
        // _isRevertingProduct guards programmatic assignments — see setProductSilently()
        selectedProduct(newVal, oldVal) {
            if (this._isRevertingProduct || newVal === oldVal) return;
            if (!this.chatId && this.messages.length === 0) return;

            this.setProductSilently(oldVal);
            this.pendingProductSwitch = newVal;
            this.botSwitchDialogVisible = true;
        },
        includeTicketContent() {
            if (this.contextReady) {
                this.saveContextSelection();
            }
        },
        ticketResponseIds(newIds, oldIds) {
            if (!oldIds || newIds === oldIds) return;
            const prevIds = this.ticketConversations.map(c => c.id);
            this.buildTicketConversations();
            const addedIds = this.ticketConversations.filter(c => !prevIds.includes(c.id)).map(c => c.id);
            if (addedIds.length > 0) {
                this.selectedContextIds.push(...addedIds);
                if (this.contextReady) {
                    this.saveContextSelection();
                }
            }
        },
        showContextPicker(open) {
            if (open) {
                // Both pickers occupy the same slot above the toolbar.
                this.showTempPicker = false;
                this.$nextTick(() => {
                    this.$refs.contextSearchInput?.focus();
                });
            } else {
                this.contextSearchQuery = '';
            }
        },
        showTempPicker(open) {
            if (open) {
                this.showContextPicker = false;
            }
        },
        // Intentionally no watcher on formattedResponse — running hljs.highlightAll()
        // on every SSE chunk is O(n × chunks) CPU cost. Highlighting is deferred to
        // stream completion via highlightStreamedResponse() for live messages, and
        // static messages get highlighted in prepareMessage().
    },

    methods: {
        // Function to format text for editor insertion with better formatting
        getFormattedTextForEditor(text) {
            if (!text) return '';

            // Convert markdown to well-formatted plain text while preserving structure
            return text
                // Convert headings to bold text with proper spacing
                .replace(/#{1,6}\s+(.+)/g, (match, heading) => `\n\n**${heading.trim()}**\n\n`)

                // Preserve bold formatting
                .replace(/\*\*([^*]+)\*\*/g, '**$1**')

                // Convert italic to emphasis
                .replace(/\*([^*]+)\*/g, '_$1_')

                // Format code blocks with proper indentation
                .replace(/```(\w*)\n?([\s\S]*?)```/g, (match, lang, code) => {
                    const formattedCode = code.trim()
                        .split('\n')
                        .map(line => `    ${line}`) // Indent each line
                        .join('\n');
                    return `\n\n${lang ? `${lang.toUpperCase()} Code:` : 'Code:'}\n${formattedCode}\n\n`;
                })

                // Format inline code
                .replace(/`([^`]+)`/g, '`$1`')

                // Convert links to readable format
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '$1 ($2)')

                // Format bullet points
                .replace(/^\s*[-*+]\s+/gm, '• ')

                // Format numbered lists
                .replace(/^\s*(\d+)\.\s+/gm, '$1. ')

                // Clean up excessive line breaks but preserve paragraph structure
                .replace(/\n{3,}/g, '\n\n')

                // Ensure proper spacing around formatted elements
                .replace(/(\*\*[^*]+\*\*)/g, '\n$1\n')
                .replace(/\n{3,}/g, '\n\n')

                .trim();
        },

        // Keep the old function for copy functionality (plain text)
        getCleanTextForEditor(text) {
            if (!text) return '';

            // Remove markdown syntax for plain text insertion
            return text
                .replace(/#+\s+/g, '') // Remove headings
                .replace(/\*\*/g, '') // Remove bold
                .replace(/\*/g, '') // Remove italic
                .replace(/`{3}[\s\S]*?`{3}/g, match => {
                    // Preserve code blocks but remove the backticks
                    return match
                        .replace(/^```\w*\n/, '') // Remove opening ```
                        .replace(/```$/, ''); // Remove closing ```
                })
                .replace(/`([^`]+)`/g, '$1') // Remove inline code markers
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '$1') // Convert links to text
                .trim();
        },

        saveDraft() {
            const draftKey = 'createResponseDraft';
            const draft = JSON.parse(this.$getData(draftKey) || '[]') || [];
            if (draft.length >= 3) {
                draft.shift();
            }
            // Save the clean text version for drafts
            const cleanText = this.getCleanTextForEditor(this.aiResponse);
            draft.push(cleanText);
            this.$saveData(draftKey, JSON.stringify(draft));
            this.draftData = draft;
        },

        addToStream(text) {
            if (!text || text.trim() === '') return;
            // Coalesce chunks via requestAnimationFrame: Vue reactivity + markdown re-parse
            // fires at most once per frame (~60fps) instead of once per SSE chunk.
            // Cuts CPU/layout cost dramatically on long streamed answers.
            this._streamBuffer = (this._streamBuffer || '') + text;
            if (this._streamRafId) return;
            this._streamRafId = requestAnimationFrame(() => {
                this._streamRafId = null;
                if (this._streamBuffer) {
                    this.aiResponse += this._streamBuffer;
                    this._streamBuffer = '';
                    this.scrollToBottom();
                }
            });
        },

        // Drain any buffered chunk immediately. Call before finalizing a message so the
        // last partial frame isn't dropped when the stream ends.
        flushStreamBuffer() {
            if (this._streamRafId) {
                cancelAnimationFrame(this._streamRafId);
                this._streamRafId = null;
            }
            if (this._streamBuffer) {
                this.aiResponse += this._streamBuffer;
                this._streamBuffer = '';
            }
        },

        // Helper function to process SSE events
        handleChatIdReceived(chatId) {
            if (chatId && chatId !== this.chatId) {
                this.chatId = chatId;
                this.saveChatIdToServer(chatId);
            }
            // Name the in-flight turn as soon as the backend resolves its chat, so
            // switching away can tell this conversation apart from any other.
            if (chatId && this.isStreaming && this.streamingChatId === null) {
                this.streamingChatId = chatId;
            }
        },

        /**
         * Read an SSE body and dispatch each frame to onEvent (return true to stop);
         * onDone fires when the body ends normally. Keeps reading even after the agent
         * navigates away — only the terminal frame can unlock the composer. Callers
         * decide per frame whether it may touch the conversation on screen.
         */
        consumeSseStream(response, onEvent, onDone) {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            if (!response.body) {
                throw new Error('Streaming not supported in this browser');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            const readStream = () => {
                return reader.read().then(({ done, value }) => {
                    if (done) {
                        if (onDone) onDone();
                        return;
                    }

                    buffer += decoder.decode(value, { stream: true });

                    // Process SSE data line by line to handle malformed events
                    const lines = buffer.split('\n');
                    let currentDataLines = [];
                    let currentEventType = 'message'; // Default to message

                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];

                        if (line.startsWith('event: ')) {
                            // Process any accumulated data before switching events
                            if (currentDataLines.length > 0) {
                                if (onEvent(currentEventType, currentDataLines.join('\n'))) return;
                                currentDataLines = [];
                            }
                            currentEventType = line.substring(7).trim();
                        } else if (line.startsWith('data: ')) {
                            currentDataLines.push(line.substring(6));
                        } else if (line.trim() === '') {
                            // Empty line - process accumulated data
                            if (currentDataLines.length > 0) {
                                if (onEvent(currentEventType, currentDataLines.join('\n'))) return;
                                currentDataLines = [];
                            }
                        }
                    }

                    // Process any remaining data
                    if (currentDataLines.length > 0) {
                        if (onEvent(currentEventType, currentDataLines.join('\n'))) return;
                    }

                    // Keep the last incomplete line in buffer
                    buffer = lines[lines.length - 1] || '';

                    return readStream();
                });
            };

            return readStream();
        },

        processEventData(eventType, data, trimmedPrompt) {
            if (eventType === 'message' && data && data.trim() !== '') {
                // The answer has started; progress copy has served its purpose.
                this.streamStatus = null;
                this.streamBuffer += data;
                this.addToStream(data);
            } else if (eventType === 'status' && data) {
                // Progress hint while the agent searches or runs a tool, emitted
                // before any token. A tool-supplied label is shown verbatim.
                try {
                    const parsed = JSON.parse(data);
                    this.streamStatus = (parsed && typeof parsed === 'object') ? parsed : { stage: String(parsed) };
                } catch (e) {
                    this.streamStatus = { stage: data.trim() };
                }
            } else if (eventType === 'chat_id' && data) {
                this.handleChatIdReceived(data.trim());
            } else if (eventType === 'sources' && data) {
                try {
                    this.pendingSources = JSON.parse(data);
                } catch (e) {
                    // not valid JSON
                }
            } else if (eventType === 'message_id' && data) {
                // fluent-bot message ids are UUIDs — keep as string, never parseInt.
                this.pendingMessageId = data.trim() || null;
            } else if (eventType === 'token_usage') {
                // Acknowledged but no action needed on frontend
            } else if (eventType === 'done' || eventType === 'end') {
                // Drain any RAF-buffered chunk so the last partial frame isn't lost.
                this.flushStreamBuffer();
                if (this.aiResponse) {
                    const msg = { role: 'ai', content: this.aiResponse, message_id: this.pendingMessageId, feedback: null };
                    if (this.pendingSources && this.pendingSources.length) {
                        msg.sources = this.pendingSources;
                    }
                    this.messages.push(this.prepareMessage(msg));
                    this.pendingSources = null;
                    this.pendingMessageId = null;
                }
                this.loading = false;
                this.isStreaming = false;
                this.streamingChatId = null;
                this.streamStatus = null;
                this.aiResponse = '';
                this.finalPrompts = trimmedPrompt;
                this._turnPrompt = null;
                this.selectedPrompt = '';

                this.scrollToBottom();
                return true; // Signal to stop processing
            } else if (eventType === 'error') {
                this.loading = false;
                this.isStreaming = false;
                this.streamingChatId = null;
                this.streamStatus = null;
                // Prefer the server-supplied reason (e.g. missing API key config)
                // so agents get an actionable message, not a generic failure.
                let message = this.$t('Failed to generate response. Please try again.');
                try {
                    const parsed = JSON.parse(data);
                    if (parsed && (parsed.error || parsed.message)) {
                        message = parsed.error || parsed.message;
                    }
                } catch (e) {
                    // data was not JSON — keep the generic message.
                }
                this.errorMessage = message;
                return true; // Signal to stop processing
            }
            return false; // Continue processing
        },

        sendMessage() {
            if (!this.prompt.trim() || this.isStreaming || this.loadingMessages) return;
            this.generateResponse(this.prompt);
        },

        prepareMessage(msg) {
            if (msg.role === 'ai' && msg.content && !msg.htmlContent) {
                msg.htmlContent = this.formatMarkdown(msg.content);
            }
            return msg;
        },

        formatMarkdown(text) {
            return parseMarkdown(text);
        },

        copyMessageText(text) {
            const cleanText = this.getCleanTextForEditor(text);
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(cleanText).then(() => {
                    this.$notify({ message: this.$t("Copied to clipboard"), type: "success", position: "bottom-right" });
                }).catch(() => {
                    this.fallbackCopy(cleanText);
                });
            } else {
                this.fallbackCopy(cleanText);
            }
        },

        fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                this.$notify({ message: this.$t("Copied to clipboard"), type: "success", position: "bottom-right" });
            } catch (e) {
                this.$notify({ message: this.$t("Something went wrong"), type: "danger", position: "bottom-right" });
            }
            document.body.removeChild(textarea);
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        generateResponse(prompt) {
            if (this.noBotAvailable) {
                this.errorMessage = this.$t('Add a bot in FluentBot settings before chatting.');
                return;
            }
            if (this.noBotForThisProduct) {
                this.errorMessage = this.$t('Pick a configured product from the dropdown before sending.');
                return;
            }
            const trimmedPrompt = prompt.trim();

            if (!trimmedPrompt) {
                this.errorMessage = 'Prompt is required.';
                return;
            }

            this.messages.push({ role: 'user', content: trimmedPrompt });
            this.prompt = '';
            this.$nextTick(() => {
                const textarea = this.$refs.promptTextarea;
                if (textarea) textarea.style.height = 'auto';
            });

            this.errorMessage = '';
            this.loading = true;
            this.isStreaming = true;
            // Claim the turn for the conversation on screen. A new chat has no id
            // yet; handleChatIdReceived fills it in when the chat_id frame lands.
            this.streamingChatId = this.chatId;
            this.streamStatus = null;
            // The prompt isn't persisted until the turn completes, so remember it —
            // coming back to this conversation mid-turn has nothing else to show it.
            this._turnPrompt = trimmedPrompt;
            this.aiResponse = '';
            this.displayedText = '';
            this.typingQueue = [];
            this.streamBuffer = '';
            this.scrollToBottom();

            const requestData = {
                content: trimmedPrompt,
                id: this.ticketId,
                type: 'createResponse',
                provider: this.aiProvider,
                product_id: this.selectedProduct,
                web_search: this.webSearch,
                temperature: this.temperature,
            };

            // Only forward context selection once it has loaded — sending an
            // empty list before then would force selected-only mode and drop
            // the full ticket history. When omitted, server falls back to full
            // ticket context.
            if (this.contextReady) {
                requestData.selected_conversations = this.selectedContextIds;
                requestData.include_ticket_content = this.includeTicketContent;
            }

            // Include chat_id if we have one from previous interactions
            if (this.chatId) {
                requestData.chat_id = this.chatId;
            }

            // Signal server to drop any stored chat mapping for this ticket
            // (covers race where the async DELETE from clear/fork/new hasn't landed yet)
            if (this._pendingChatReset) {
                requestData.reset_chat = true;
                this._pendingChatReset = false;
            }

            // Seed prior conversation into new bot thread (fork scenario)
            if (this.seedMessages) {
                requestData.conversation_history = this.seedMessages;
                this.seedMessages = null;
            }

            // Use fetch for proper streaming
            const baseUrl = this.appVars.rest.url;
            const nonce = this.appVars.rest.nonce;

            if (this._streamAbort) {
                this._streamAbort.abort();
            }
            // A fresh send supersedes any reconnect still tailing an earlier turn.
            this.abortResume();
            this._streamAbort = new AbortController();
            const myToken = ++this._streamToken;
            // Epoch at send time; new/switch/fork bumps it, so an unnamed turn from
            // before the reset can't be mistaken for the fresh conversation.
            const myEpoch = this._requestEpoch;
            this.streamingEpoch = myEpoch;
            // True while this turn's conversation is the one on screen. Off-screen the
            // stream keeps draining but must not paint into whatever the agent opened.
            // Identity mirrors turnBelongsHere: a named turn is matched by chat id, so
            // switching away and back re-owns it; an unnamed one (chat_id not arrived)
            // has only the epoch, which a reset bumps to disown it.
            const onScreen = () => this._streamToken === myToken
                && (this.streamingChatId === null
                    ? myEpoch === this._requestEpoch
                    : this.streamingChatId === this.chatId);

            fetch(`${baseUrl}/fluent-bot/${this.ticketId}/generate-stream-response`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify(requestData),
                signal: this._streamAbort.signal
            })
                .then(response => this.consumeSseStream(
                    response,
                    (eventType, data) => {
                        if (this._streamToken !== myToken) return true; // superseded

                        if (!onScreen()) {
                            if (eventType === 'done' || eventType === 'end' || eventType === 'error') {
                                // Ended off-screen: release the composer, but don't push
                                // this answer onto the current conversation — it's already
                                // persisted and belongs to a different one.
                                this.releaseTurn();
                                return true;
                            }
                            // chat_id/message_id retarget the panel — a background turn
                            // must not, or it would repoint chatId and append its answer
                            // to whatever the agent is reading.
                            if (eventType === 'chat_id' || eventType === 'message_id') {
                                return false;
                            }
                            // Everything else only accumulates into aiResponse, which
                            // stays hidden until the agent returns to this conversation.
                        }

                        return this.processEventData(eventType, data, trimmedPrompt);
                    },
                    () => {
                        if (this._streamToken !== myToken) return;
                        if (!onScreen()) {
                            this.releaseTurn();
                            return;
                        }
                        if (this.aiResponse) {
                            const msg = { role: 'ai', content: this.aiResponse, message_id: this.pendingMessageId, feedback: null };
                            if (this.pendingSources && this.pendingSources.length) {
                                msg.sources = this.pendingSources;
                            }
                            this.messages.push(this.prepareMessage(msg));
                            this.pendingSources = null;
                            this.pendingMessageId = null;
                            this.aiResponse = '';
                        }
                        this.loading = false;
                        this.isStreaming = false;
                        this.streamingChatId = null;
                        this.streamStatus = null;
                        this._turnPrompt = null;
                        this.finalPrompts = trimmedPrompt;
                        this.selectedPrompt = '';

                        this.scrollToBottom();
                    },
                ))
                .catch(error => {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    this.loading = false;
                    this.isStreaming = false;
                    this.streamingChatId = null;
                    this.streamStatus = null;
                    this.errorMessage = 'Failed to generate response. Please try again.';
                });
        },

        selectPresetPrompt(preset) {
            this.selectedPrompt = preset;
            const selectedPrompt = this.presetPrompts.find(item => item.text === preset.text);
            this.presetPrompt = `${selectedPrompt.description}`;
            this.prompt = '';
            this.generateResponse(this.presetPrompt);
        },

        isSelected(prompt) {
            return this.selectedPrompt === prompt;
        },

        closeModal() {
            // Visibility-only — preserve chat/state so reopening restores the session.
            // Full reset belongs in explicit actions (clearChat, newConversation) or
            // ticket navigation (resetForTicketChange via watcher).
            this.$emit('close');
        },

        async copyText() {
            try {
                // Copy the clean text version instead of HTML
                const cleanText = this.getCleanTextForEditor(this.aiResponse);
                await navigator.clipboard.writeText(cleanText);
                this.$notify({
                    message: this.$t("Copied to clipboard"),
                    type: "success",
                    position: "bottom-right",
                });
            } catch (error) {
                this.$notify({
                    message: this.$t("Something went wrong"),
                    type: "danger",
                    position: "bottom-right",
                });
            }
        },

        resetData() {
            this.aiResponse = '';
            this.selectedPrompt = '';
            this.prompt = '';
            this.chatId = null;
            this.isStreaming = false;
            this.streamingChatId = null;
            this.streamStatus = null;
            this.displayedText = '';
            this.typingQueue = [];
            this.streamBuffer = '';
            this.messages = [];
            this.nextCursor = null;
            this.pendingSources = null;

            this.removeDraft();
        },

        insertReply(content) {
            this.$emit('insert', parseMarkdown(content));
        },

        fetchPresets() {
            this.$get('fluent-bot/preset-prompts', {
                type: 'createResponse',
                provider: this.aiProvider
            })
                .then(response => {
                    this.presetPrompts = response;
                })
                .catch(errors => {
                    this.$handleError(errors);
                });
        },

        // Programmatically set selectedProduct without triggering the product-switch dialog
        setProductSilently(productId) {
            this._isRevertingProduct = true;
            this.selectedProduct = productId;
            this.$nextTick(() => { this._isRevertingProduct = false; });
        },

        // Pick best dropdown selection given current ticket product and configured bots:
        // ticket product with mapped bot → keep; else fall back to general bot if available.
        // If neither, leave selection empty so user must explicitly pick a configured product.
        applyProductFallback() {
            const configuredIds = this._configuredProductIds;
            const currentSelection = parseInt(this.selectedProduct) || 0;
            const selectionConfigured = currentSelection !== 0 && configuredIds.has(currentSelection);
            if (selectionConfigured) return;
            if (this.hasGeneralBot) {
                this.setProductSilently(0);
            } else {
                this.setProductSilently('');
            }
        },

        fetchConfiguredProducts() {
            // Use ticket-safe runtime config endpoint (AgentTicketPolicy) instead of
            // admin-only settings endpoint, so non-admin agents can populate the dropdown.
            this.$get('fluent-bot/runtime-config')
                .then(data => {
                    this.hasGeneralBot = !!data.hasGeneralBot;
                    const configuredIds = new Set((data.configuredProductIds || []).map(id => parseInt(id)));
                    const allProducts = this.appVars.support_products || [];
                    this.products = allProducts.filter(p => configuredIds.has(parseInt(p.id)));
                    this._configuredProductIds = configuredIds;
                    this.applyProductFallback();
                    this.configReady = true;
                })
                .catch((err) => {
                    console.error('FluentBot: fetch configured products failed', err);
                    this.products = [];
                    this.hasGeneralBot = false;
                    this.configReady = true;
                });
        },

        removeDraft() {
            const draftKey = 'createResponseDraft';
            this.removeData(draftKey);
            this.draftData = [];
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

        getSnippet(text) {
            return text.length > 30 ? text.substring(0, 30) + '...' : text;
        },

        selectDraft(draft) {
            this.aiResponse = draft;
            this.displayedText = draft;
            this.typingQueue = [];
            this.streamBuffer = draft;
        },

        saveChatIdToServer(chatId) {
            if (!chatId) return;
            // Label the conversation in history by its first user message.
            const firstUserMsg = this.messages.find(m => m.role === 'user');
            const title = firstUserMsg ? String(firstUserMsg.content || '').slice(0, 60) : '';
            this.$post(`fluent-bot/${this.ticketId}/chat-id`, {
                chat_id: chatId,
                product_id: this.selectedProduct,
                title: title
            }).catch((err) => {
                console.error('FluentBot: save chat_id failed', err);
                this.errorMessage = this.$t('Failed to save chat session. Your messages may not persist on reload.');
            });
        },

        // Hard-reset in-memory state when the panel's ticket prop changes (route nav).
        // Panel stays mounted via v-show for perf, so we must explicitly clear per-ticket
        // state and re-bootstrap against the new ticket.
        resetForTicketChange() {
            if (this._streamAbort) {
                this._streamAbort.abort();
                this._streamAbort = null;
            }
            this.abortResume();
            this._requestEpoch++;
            this.streamingEpoch = null;
            this._pendingChatReset = false;
            this.messages = [];
            this.chatId = null;
            this.nextCursor = null;
            this.seedMessages = null;
            this.aiResponse = '';
            this.isStreaming = false;
            this.streamingChatId = null;
            this.streamStatus = null;
            this.loading = false;
            this.streamBuffer = '';
            this.selectedPrompt = '';
            this.errorMessage = '';
            this.prompt = '';
            this.pendingSources = null;
            this.pendingMessageId = null;
            this.ticketConversations = [];
            this.selectedContextIds = [];
            this.contextReady = false;
            this.showContextPicker = false;
            this.contextSearchQuery = '';
            this.includeTicketContent = true;
            this.botSwitchDialogVisible = false;
            this.pendingProductSwitch = null;
            this.buildTicketConversations();
            this.loadContextSelection();
            this.restoreChatState();
        },

        restoreChatState() {
            // Guard against stale responses: a slow chat-id fetch from a prior
            // ticket must not overwrite state after navigating to another ticket.
            const epoch = this._requestEpoch;
            const ticketId = this.ticketId;
            this.$get(`fluent-bot/${ticketId}/chat-id`)
                .then(response => {
                    if (epoch !== this._requestEpoch || ticketId !== this.ticketId) return;
                    if (response.chat_id) {
                        this.chatId = response.chat_id;
                        // Restore the product the chat was created with
                        if (response.product_id !== null && response.product_id !== undefined) {
                            this.setProductSilently(response.product_id);
                        }
                        const restoredChatId = response.chat_id;
                        // A refresh mid-turn leaves the answer running upstream; pick it
                        // back up once the stored transcript is on screen.
                        this.fetchChatMessages().then(() => this.tryResume(restoredChatId));
                    }
                })
                .catch((err) => { console.error('FluentBot: restore chat state failed', err); });
        },

        // Returns a promise so callers can chain tryResume() and be sure the stored
        // transcript has landed before a reconnect starts appending to it.
        fetchChatMessages(cursor) {
            // Already loading: wait for it, then refetch under the current epoch (a switch bumped it).
            if (this.loadingMessages && this._historyFetch) {
                return this._historyFetch.then(() => this.fetchChatMessages(cursor));
            }
            this.loadingMessages = true;
            // Capture epoch so a stale response can't overwrite state after newConversation/forkConversation
            const epoch = this._requestEpoch;
            const params = { product_id: this.selectedProduct };
            if (cursor) {
                params.cursor = cursor;
            }

            const request = this.$get(`fluent-bot/${this.ticketId}/chat-messages`, params)
                .then(response => {
                    if (epoch !== this._requestEpoch) return;

                    const apiMessages = (response.data || [])
                        .map(msg => {
                            const m = {
                                role: (msg.role === 'visitor' || msg.role === 'human') ? 'user' : 'ai',
                                content: msg.content,
                                message_id: msg.id || null,
                                feedback: msg.feedback || null
                            };
                            if (msg.sources && msg.sources.length) {
                                m.sources = msg.sources;
                            }
                            return this.prepareMessage(m);
                        })
                        .reverse();

                    if (cursor) {
                        // Paging older messages: drop any already held, then prepend.
                        // Dedup only here — a full reload replaces the list, so filtering
                        // there would delete messages already on screen.
                        const existingIds = new Set(this.messages.map(m => m.message_id).filter(Boolean));
                        const older = apiMessages.filter(m => !m.message_id || !existingIds.has(m.message_id));
                        const container = this.$refs.chatContainer;
                        const prevHeight = container ? container.scrollHeight : 0;
                        this.messages = [...older, ...this.messages];
                        this.$nextTick(() => {
                            if (container) {
                                container.scrollTop = container.scrollHeight - prevHeight;
                            }
                        });
                    } else {
                        // Full reload: the server response is the transcript.
                        this.messages = apiMessages;
                        this.scrollToBottom();
                    }

                    this.nextCursor = response.next_cursor || null;
                })
                .catch((err) => {
                    console.error('FluentBot: fetch chat messages failed', err);
                    this.errorMessage = cursor
                        ? this.$t('Failed to load older messages. Please try again.')
                        : this.$t('Failed to load chat history. Please try again.');
                })
                .always(() => {
                    this.loadingMessages = false;
                    this._historyFetch = null;
                });
            this._historyFetch = request;
            return request;
        },

        handleConversationCommand(command) {
            if (command === 'new') {
                this.startNewConversation();
            } else if (command === 'past') {
                this.openPastConversations();
            }
        },

        // Start a fresh conversation: reset UI + clear the server-side active
        // chat_id so the next message creates a new upstream chat. The previous
        // conversation stays recorded in the ticket's history (Past conversations).
        startNewConversation() {
            // An in-flight turn keeps draining in the background so it can release the
            // composer when it ends. Clearing the flags here would unlock the composer
            // early and let a second turn start against a worker still doing the first.
            this.abortResume();
            this._requestEpoch++;
            this.streamingEpoch = null;
            this._pendingChatReset = true;
            this.messages = [];
            this.chatId = null;
            this.streamBuffer = '';
            this.selectedPrompt = '';
            this.errorMessage = '';
            this.nextCursor = null;
            this.contextReady = false;
            this.selectedContextIds = this.ticketConversations.map(c => c.id);
            this.includeTicketContent = true;
            this.contextReady = true;
            // Fire-and-forget: server-side mapping cleared so a fresh chat can be saved next stream
            this.$del(`fluent-bot/${this.ticketId}/chat-id`).catch((err) => { console.error('FluentBot: new conversation failed', err); });
        },

        openPastConversations() {
            this.pastDialogVisible = true;
            this.loadingPast = true;
            this.$get(`fluent-bot/${this.ticketId}/conversations`)
                .then(response => {
                    this.pastConversations = response.conversations || [];
                    this.activeChatId = response.active_chat_id || this.chatId || null;
                })
                .catch((err) => {
                    console.error('FluentBot: load past conversations failed', err);
                    this.$notify({
                        message: (err && err.message) || this.$t('Failed to load past conversations.'),
                        type: 'warning',
                        position: 'bottom-right'
                    });
                })
                .always(() => {
                    this.loadingPast = false;
                });
        },

        switchToConversation(conv) {
            if (!conv || !conv.chat_id || this.switchingConversation) return;
            if (conv.chat_id === this.chatId) {
                this.pastDialogVisible = false;
                return;
            }

            this.switchingConversation = true;
            this.$post(`fluent-bot/${this.ticketId}/conversations/switch`, { chat_id: conv.chat_id })
                .then(response => {
                    // Does NOT abort the in-flight turn — it keeps draining in the
                    // background (painting suppressed) so the composer stays locked
                    // until it ends, else each switch could pin a backend worker.
                    this.abortResume();
                    this._requestEpoch++;
                    this.streamingEpoch = null;
                    this._pendingChatReset = false;
                    this.messages = [];
                    this.nextCursor = null;
                    this.aiResponse = '';
                    this.errorMessage = '';
                    this.chatId = response.chat_id;
                    if (response.product_id !== null && response.product_id !== undefined) {
                        this.setProductSilently(response.product_id);
                    }
                    this.pastDialogVisible = false;
                    const openedChatId = response.chat_id;
                    // If the conversation being opened is still generating, reconnect
                    // to it instead of showing a transcript that stops mid-turn.
                    this.fetchChatMessages().then(() => this.tryResume(openedChatId));
                })
                .catch((err) => {
                    console.error('FluentBot: switch conversation failed', err);
                    this.$notify({
                        message: (err && err.message) || this.$t('Failed to open that conversation.'),
                        type: 'warning',
                        position: 'bottom-right'
                    });
                })
                .always(() => {
                    this.switchingConversation = false;
                });
        },

        // Clear the streaming state a turn owns, without touching the transcript.
        // Used when a turn ends while the agent is viewing another conversation.
        releaseTurn() {
            this.loading = false;
            this.isStreaming = false;
            this.streamingChatId = null;
            this.streamStatus = null;
            this.aiResponse = '';
            this.streamBuffer = '';
            this.displayedText = '';
            this.typingQueue = [];
            this.pendingSources = null;
            this.pendingMessageId = null;
            this._turnPrompt = null;
        },

        // Is the in-flight prompt already the last thing rendered? Only the tail can be
        // a genuine duplicate — matching anywhere would drop the prompt whenever the
        // agent asks the same question twice in one conversation.
        endsWithUserMessage(content) {
            const last = this.messages[this.messages.length - 1];
            return !!last && last.role === 'user' && last.content === content;
        },

        abortResume() {
            if (this._resumeAbort) {
                try { this._resumeAbort.abort(); } catch (e) { /* already settled */ }
                this._resumeAbort = null;
            }
        },

        /**
         * Reconnect to a turn still running upstream — after a refresh, or switching
         * away and back. Replays what streamed so far then tails it live, so an in-flight
         * turn renders instead of an empty panel. Skipped when this tab already streams
         * the chat (generateResponse owns it), which stops double-rendering.
         */
        tryResume(chatId) {
            if (!chatId) return;

            // This conversation is the one streaming live in this tab. The live stream
            // is still accumulating its answer, so reconnecting would render it twice.
            // Its prompt is not persisted yet, so put that back below the transcript.
            if (chatId === this.streamingChatId) {
                if (this._turnPrompt && !this.endsWithUserMessage(this._turnPrompt)) {
                    this.messages.push({ role: 'user', content: this._turnPrompt });
                    this.scrollToBottom();
                }
                return;
            }

            // A different conversation owns the live render state; leave it alone.
            if (this.isStreaming) return;

            this.abortResume();
            this._resumeAbort = new AbortController();

            const baseUrl = this.appVars.rest.url;
            const nonce = this.appVars.rest.nonce;

            let appended = false;
            // Did this reconnect claim the streaming state? If so it must release it on
            // every ending, including those with no `__done__` (aborted fetch, or a body
            // that just stops on the proxy/upstream timeout) — else a "Generating" bubble
            // sticks under an answer that already arrived.
            let claimed = false;
            const releaseIfMine = () => {
                if (!claimed || this.streamingChatId !== chatId) return;
                this.isStreaming = false;
                this.streamingChatId = null;
                this.streamStatus = null;
                this.aiResponse = '';
                claimed = false;
            };

            fetch(`${baseUrl}/fluent-bot/${this.ticketId}/chat-stream`, {
                method: 'GET',
                headers: { 'X-WP-Nonce': nonce, 'Accept': 'text/event-stream' },
                signal: this._resumeAbort.signal,
            })
                .then(response => this.consumeSseStream(response, (eventType, data) => {
                    // A late frame for a conversation the agent has left must not paint.
                    if (chatId !== this.chatId) return true;

                    if (eventType === 'idle') {
                        // Nothing buffered — the turn already finished, or never ran.
                        this.abortResume();
                        return true;
                    }

                    if (eventType === 'user') {
                        // The prompt isn't persisted until the turn completes, so the
                        // transcript fetch can't have it yet — render it from the buffer.
                        if (!this.endsWithUserMessage(data)) {
                            this.messages.push({ role: 'user', content: data });
                        }
                        this.isStreaming = true;
                        this.streamingChatId = chatId;
                        this.aiResponse = '';
                        this.displayedText = '';
                        this.typingQueue = [];
                        this.streamBuffer = '';
                        appended = true;
                        claimed = true;
                        this.scrollToBottom();
                        return false;
                    }

                    if (!appended) return false;

                    if (eventType === '__done__') {
                        this.flushStreamBuffer();
                        releaseIfMine();
                        this.abortResume();
                        // The turn is persisted by now; refetch so the answer arrives
                        // with its real message_id and feedback controls.
                        this.fetchChatMessages();
                        return true;
                    }

                    if (eventType === 'error') {
                        releaseIfMine();
                        this.abortResume();
                        this.errorMessage = this.$t('Failed to generate response. Please try again.');
                        return true;
                    }

                    // message / sources / message_id / token_usage render exactly as live.
                    return this.processEventData(eventType, data, '');
                }, () => {
                    // Body ended without a terminal frame. Whatever was rendered is
                    // partial, so hand the state back and let the transcript refetch
                    // settle what actually persisted.
                    if (!claimed) return;
                    releaseIfMine();
                    this.fetchChatMessages();
                }))
                .catch(err => {
                    // AbortError included: an aborted reconnect still has to release
                    // the state it claimed, or the generating bubble never goes away.
                    releaseIfMine();
                    if (err && err.name === 'AbortError') return;
                    // A failed reconnect is not worth surfacing: the turn is still
                    // running upstream and the next refetch will pick up the answer.
                    console.error('FluentBot: resume stream failed', err);
                });
        },

        formatPastDate(ts) {
            if (!ts) return '';
            try {
                return new Date(ts * 1000).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
            } catch (e) {
                return '';
            }
        },

        updateMessageByMessageId(messageId, updater) {
            const idx = this.messages.findIndex(m => m.message_id === messageId);
            if (idx === -1) return;
            this.messages[idx] = updater(this.messages[idx]);
        },

        handleFeedback(msgIndex, reaction) {
            const msg = this.messages[msgIndex];
            if (!msg || !msg.message_id) return;
            const targetMessageId = msg.message_id;

            // Toggle off — delete existing feedback
            if (msg.feedback && msg.feedback.reaction === reaction) {
                this.$del(`fluent-bot/${this.ticketId}/feedback/${msg.feedback.id}`, { product_id: this.selectedProduct })
                .then(() => {
                    this.updateMessageByMessageId(targetMessageId, m => ({ ...m, feedback: null }));
                })
                .catch((err) => {
                    console.error('FluentBot: delete feedback failed', err);
                    this.$notify({
                        message: (err && err.message) || this.$t('Failed to remove feedback. Please try again.'),
                        type: 'warning',
                        position: 'bottom-right'
                    });
                });
                return;
            }

            // Positive — create immediately
            if (reaction === 'positive') {
                this.createFeedback(targetMessageId, 'positive', null);
                return;
            }

            // Negative — open dialog for optional comment
            this.pendingFeedbackMessageId = targetMessageId;
            this.feedbackComment = '';
            this.feedbackDialogVisible = true;
            this.$nextTick(() => {
                if (this.$refs.feedbackTextarea) {
                    this.$refs.feedbackTextarea.focus();
                }
            });
        },

        cancelFeedback() {
            this.feedbackDialogVisible = false;
            this.pendingFeedbackMessageId = null;
            this.feedbackComment = '';
        },

        submitNegativeFeedback(comment) {
            this.feedbackDialogVisible = false;
            if (this.pendingFeedbackMessageId !== null) {
                this.createFeedback(this.pendingFeedbackMessageId, 'negative', comment || null);
                this.pendingFeedbackMessageId = null;
            }
        },

        createFeedback(messageId, reaction, comment) {
            this.$post(`fluent-bot/${this.ticketId}/feedback`, {
                message_id: messageId,
                reaction: reaction,
                comments: comment,
                product_id: this.selectedProduct
            })
            .then(response => {
                const feedbackData = response.data || response;
                this.updateMessageByMessageId(messageId, m => ({
                    ...m,
                    feedback: {
                        id: feedbackData.id,
                        message_id: messageId,
                        reaction: reaction,
                        comments: comment
                    }
                }));
            })
            .catch((err) => {
                console.error('FluentBot: create feedback failed', err);
                this.$notify({
                    message: (err && err.message) || this.$t('Failed to save feedback. Please try again.'),
                    type: 'warning',
                    position: 'bottom-right'
                });
            });
        },

        autoResizeTextarea() {
            const textarea = this.$refs.promptTextarea;
            if (!textarea) return;
            textarea.style.height = 'auto';
            const lineHeight = parseInt(getComputedStyle(textarea).lineHeight) || 20;
            const maxHeight = lineHeight * 16;
            textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + 'px';
        },

        // Pure in-memory rebuild of ticketConversations from the ticketResponses prop.
        // Does NOT fetch persisted context — call loadContextSelection() separately on
        // mount / ticket change. The ticketResponseIds watcher only rebuilds in-memory
        // and incrementally updates selectedContextIds to avoid per-response REST calls.
        buildTicketConversations() {
            this.ticketConversations = (this.ticketResponses || [])
                .filter(c => c.conversation_type === 'response')
                .map(c => {
                    const personType = c.person ? c.person.person_type : 'agent';
                    const name = c.person ? (c.person.first_name + ' ' + (c.person.last_name || '')).trim() : '';
                    const text = (c.content || '').replace(/<[^>]*>/g, '');
                    return {
                        id: c.id,
                        role: personType === 'customer' ? 'customer' : 'support_agent',
                        name: name,
                        preview: text.length > 80 ? text.substring(0, 80) + '...' : text,
                    };
                });
        },

        loadContextSelection() {
            const allIds = this.ticketConversations.map(c => c.id);

            this.$get(`fluent-bot/${this.ticketId}/context-selection`)
                .then(selResponse => {
                    const saved = selResponse.data;

                    if (saved && saved.selectedIds) {
                        const savedIds = saved.selectedIds || [];
                        const knownIds = saved.knownIds || [];
                        const newIds = allIds.filter(id => !knownIds.includes(id));

                        // Newly-appeared responses default to selected in-memory but are NOT
                        // persisted automatically — avoid writing on panel bootstrap. Next
                        // user toggle will trigger saveContextSelection via the watcher.
                        this.selectedContextIds = [
                            ...savedIds.filter(id => allIds.includes(id)),
                            ...newIds
                        ];

                        if (typeof saved.includeTicketContent === 'boolean') {
                            this.includeTicketContent = saved.includeTicketContent;
                        }
                    } else {
                        // No prior persistence — default all selected in-memory, but do not
                        // auto-save. Persist only when the user actually modifies selection.
                        this.selectedContextIds = allIds;
                    }

                    // Defer contextReady so queued watchers fire while it's still false
                    this.$nextTick(() => {
                        this.contextReady = true;
                    });
                })
                .catch(() => {
                    this.selectedContextIds = allIds;
                    this.$nextTick(() => { this.contextReady = true; });
                })
                .always(() => {
                    // Apply any deferred context ID from "Add to FluentBot" menu
                    if (this._deferredContextId) {
                        const id = this._deferredContextId;
                        this._deferredContextId = null;
                        if (!this.selectedContextIds.includes(id)) {
                            this.selectedContextIds.push(id);
                            this.saveContextSelection();
                        }
                        this.$emit('contextAdded');
                    }
                });
        },

        saveContextSelection() {
            if (this._contextSaveTimer) {
                clearTimeout(this._contextSaveTimer);
            }
            this._contextSaveTimer = setTimeout(() => {
                this._contextSaveTimer = null;
                this.$post(`fluent-bot/${this.ticketId}/context-selection`, {
                    selected_ids: this.selectedContextIds,
                    known_ids: this.ticketConversations.map(c => c.id),
                    include_ticket_content: this.includeTicketContent,
                }).catch((err) => { console.error('FluentBot: save context selection failed', err); });
            }, 300);
        },

        sanitizeUrl(url) {
            if (!url) return '#';
            try {
                const parsed = new URL(url, window.location.origin);
                return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : '#';
            } catch (e) {
                return '#';
            }
        },

        decodeHtml(text) {
            if (!text) return '';
            const el = document.createElement('textarea');
            el.innerHTML = text;
            return el.value;
        },

        hasOpenMenu() {
            return this.showContextPicker || this.showTempPicker || this.feedbackDialogVisible;
        },

        closeMenus() {
            if (this.showContextPicker) {
                this.showContextPicker = false;
            } else if (this.showTempPicker) {
                this.showTempPicker = false;
            } else if (this.feedbackDialogVisible) {
                this.feedbackDialogVisible = false;
            }
        },

        applyPendingContextId(id) {
            if (!id) return;

            // If conversations are loaded, apply immediately
            if (this.ticketConversations.length > 0) {
                if (!this.selectedContextIds.includes(id)) {
                    this.selectedContextIds.push(id);
                }
                this.$emit('contextAdded');
            } else {
                // Store it — loadContextSelection will pick it up after fetch
                this._deferredContextId = id;
            }
        },


        toggleContext(id) {
            const idx = this.selectedContextIds.indexOf(id);
            if (idx > -1) {
                this.selectedContextIds.splice(idx, 1);
            } else {
                this.selectedContextIds.push(id);
            }
        },

        cancelProductSwitch() {
            this.botSwitchDialogVisible = false;
            this.pendingProductSwitch = null;
        },

        forkConversation() {
            this.botSwitchDialogVisible = false;
            this._isRevertingProduct = true;
            // An in-flight turn keeps draining so it can release the composer itself.
            this.abortResume();
            this._requestEpoch++;
            this.streamingEpoch = null;
            this._pendingChatReset = true;
            // Cap seed payload: last 50 messages, ≤5000 chars each. Server-side also caps.
            const MAX_SEED_COUNT = 50;
            const MAX_SEED_CHARS = 5000;
            this.seedMessages = this.messages
                .filter(m => m.content)
                .slice(-MAX_SEED_COUNT)
                .map(m => ({
                    role: m.role === 'user' ? 'visitor' : 'ai',
                    content: String(m.content).slice(0, MAX_SEED_CHARS)
                }));
            // Clear server-side chat mapping so the new chat_id can be saved
            this.$del(`fluent-bot/${this.ticketId}/chat-id`).catch((err) => { console.error('FluentBot: fork — clear chat_id failed', err); });
            this.selectedProduct = this.pendingProductSwitch;
            this.chatId = null;
            this.nextCursor = null;
            this.errorMessage = '';
            this.pendingProductSwitch = null;
            this.$nextTick(() => { this._isRevertingProduct = false; });
        },

        newConversation() {
            this.botSwitchDialogVisible = false;
            this._isRevertingProduct = true;
            // An in-flight turn keeps draining so it can release the composer itself.
            this.abortResume();
            this._requestEpoch++;
            this.streamingEpoch = null;
            this._pendingChatReset = true;
            this.selectedProduct = this.pendingProductSwitch;
            this.messages = [];
            this.chatId = null;
            this.seedMessages = null;
            this.streamBuffer = '';
            this.selectedPrompt = '';
            this.errorMessage = '';
            this.nextCursor = null;
            this.contextReady = false;
            this.selectedContextIds = this.ticketConversations.map(c => c.id);
            this.includeTicketContent = true;
            this.contextReady = true;
            this.pendingProductSwitch = null;
            // Fire-and-forget: the new conversation does not depend on this delete completing
            this.$del(`fluent-bot/${this.ticketId}/chat-id`).catch((err) => { console.error('FluentBot: new conversation — clear chat_id failed', err); });
            this.$nextTick(() => { this._isRevertingProduct = false; });
        }
    },

    mounted() {
        // Configure marked with highlight.js
        marked.setOptions({
            breaks: false,
            gfm: true,
            headerIds: false,
            mangle: false,
            highlight: (code, lang) => {
                if (lang && hljs.getLanguage(lang)) {
                    return hljs.highlight(code, { language: lang }).value;
                }
                return hljs.highlightAuto(code).value;
            }
        });

        this.fetchConfiguredProducts();
        this.fetchPresets();
        this.buildTicketConversations();
        this.loadContextSelection();
        this.restoreChatState();
        this.removeDraft();

        this._handleClickOutside = (e) => {
            if (this.showContextPicker && this.$el && !e.target.closest('.fs_context_toggle_wrap')) {
                this.showContextPicker = false;
            }
            if (this.showTempPicker && this.$el && !e.target.closest('.fs_temp_toggle_wrap')) {
                this.showTempPicker = false;
            }
        };
        document.addEventListener('click', this._handleClickOutside);
    },

    beforeUnmount() {
        if (this._handleClickOutside) {
            document.removeEventListener('click', this._handleClickOutside);
        }
        if (this._contextSaveTimer) {
            clearTimeout(this._contextSaveTimer);
        }
        if (this._streamAbort) {
            this._streamAbort.abort();
        }
        this.abortResume();
        if (this._streamRafId) {
            cancelAnimationFrame(this._streamRafId);
            this._streamRafId = null;
        }
    }
};
</script>

