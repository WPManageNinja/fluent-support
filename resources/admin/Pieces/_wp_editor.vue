<template>
    <div class="wp_vue_editor_wrapper">
        <div class="fs_action_buttons">
            <div class="fs_ai_tools_box" v-if="fluentBotIntegration">
                <el-button class="fs_fluent_bot_response_button" @click="toggleFluentBotPanel">
                    <img :src="fluentBotIconSrc" alt="" class="fs_fluent_bot_icon">
                    <p>
                        {{$t('Ask FluentBot')}}
                    </p>
                </el-button>
            </div>
            <div class="fs_ai_tools_box" v-if="openAIIntegration">
                <el-popover
                    placement="bottom"
                    :width="480"
                    trigger="click"
                    :visible="showAIResponseBox"
                >
                    <template #reference>
                        <el-button class="fs_openAI_response_button" @click="showAIResponseBox = !showAIResponseBox">
                            <img :src="appVars.asset_url + 'images/aiFillButton.svg'" alt="">
                            <p>
                                {{$t('Ask AI')}}
                            </p>
                        </el-button>
                    </template>
                    <div class="fs_template_inserter">
                        <div>
                            <AIResponseGenerator type="createResponse" @close="closeAIResponsePromptBox" @insert="insertAIResponse"/>
                        </div>

                    </div>
                </el-popover>
            </div>

            <div class="fs_cc_email_toggle_button" v-if="showCcToggleButton">
                <el-button size="small" type="primary" v-if="!add_cc" @click="handleCc('show')">
                    <span>{{ $t('Apply Cc') }}</span>
                </el-button>
                <el-button size="small" type="danger" v-else @click="handleCc('hide')">
                    <span>{{ $t('Discard Cc') }}</span>
                </el-button>
            </div>

            <div class="fs_saved_replies_box" v-if="showSavedReplies">
                <template-inserter @insert="insertTemplate" />
            </div>

            <div class="fs_shortcode_box" v-if="hasShortcodes" >
                <el-dropdown type="primary" trigger="click" :popper-options="{ strategy: 'fixed' }">
                    <el-button size="small" type="primary">
                        {{$t('Smart Codes')}} <el-icon style="vertical-align: middle;"><ArrowDown /></el-icon>
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu class="fs_global_dropdown fs_shortcode_dropdown">
                            <el-dropdown-item v-for="(value, key) in shortcodes" :key="key" :value="key" @click="insertShortcode">
                                {{ value }}
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>
            </div>

            <fluent-booking-inserter
                v-if="showFluentBooking && ticketId"
                :ticket-id="ticketId"
                @insert="insertContent"
            />
        </div>
        <ImagePasteUploader ref="imagePasteUploader" @imagePath="setImagePath" v-loading="loadingImage"/>
        <textarea v-if="hasWpEditor" class="wp_vue_editor" :id="editor_id">
            {{ modelValue }}
        </textarea>
        <textarea
            v-else
            :id="editor_id"
            class="wp_vue_editor wp_vue_editor_plain"
            v-model="plain_content"
            @input="handlePlainInput"
            @click="handlePlainSelectionChange"
            @keyup="handlePlainSelectionChange"
            @keydown="handleMentionKeydown"
            @blur="hideMentionSuggestions"
        ></textarea>
        <div
            v-if="showMentionSuggestions && filteredMentionAgents.length"
            class="fs_mentions_dropdown"
            :style="mentionDropdownStyle"
        >
            <button
                v-for="(agent, index) in filteredMentionAgents"
                :key="agent.id"
                type="button"
                class="fs_mentions_option"
                :class="{ 'is-active': index === activeMentionIndex }"
                @mousedown.prevent="selectMentionAgent(agent)"
            >
                <span class="fs_mentions_option_name">{{ agent.first_name }} {{ agent.last_name }}</span>
            </button>
        </div>
        <div v-if="loadingImage" class="fs_loading_overlay">
        </div>
        <div class="fs_ai_modify_response_box" v-if="showActionBar && openAIIntegration" :style="actionBarStyle">
            <el-popover
                placement="bottom"
                :width="480"
                trigger="click"
                :visible="showChatGPTPromptBox"
                popper-class="fs_ai_response_popover"
            >
                <template #reference>
                    <el-button class="fs_ai_popover_button" @click="editSelection()" size="small" type="default">
                        <img :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                    </el-button>
                </template>
                <div class="fs_ai_response_box">
                    <div>
                        <AIResponseGenerator type="modifyResponse" :selectedText="selectedText" @close="closeSelectedTextPromptBox" @insert="insertAIResponse"/>
                    </div>
                </div>
            </el-popover>

        </div>
    </div>
</template>

<script type="text/babel">
import ImagePasteUploader from './FormElements/_ImagePasteUploader';
import TemplateInserter from '../Modules/Tickets/_templateInserter.vue';
import AIResponseGenerator from '../Modules/Tickets/_AIResponseGenerator.vue';
import FluentBotAIResponseGenerator from '../Modules/Tickets/_FluentBotAIResponseGenerator.vue';
import FluentBookingInserter from '../Modules/Tickets/_FluentBookingInserter.vue';
import { getFluentBookingContentStyle } from '../../common/fluentBookingContentStyle';

export default {
    name: 'wp_editor',
    components: {
        TemplateInserter,
        AIResponseGenerator,
        FluentBookingInserter,
        FluentBotAIResponseGenerator,
        ImagePasteUploader,
    },

    props: {
        editor_id: {
            type: String,
            default() {
                return 'wp_editor_' + Date.now() + parseInt(Math.random() * 1000);
            }
        },
        modelValue: {
            type: String,
            default() {
                return '';
            }
        },
        editor_shortcodes: {
            type: Object,
            default() {
                return {}
            }
        },
        height: {
            type: Number,
            default() {
                return 250;
            }
        },
        mediaButtons: {
            type: Boolean,
            default() {
                return true;
            }
        },
        autofocus: {
            type: Boolean,
            default() {
                return false;
            }
        },
        showCcToggleButton: {
            type: Boolean,
            default() {
                return false
            }
        },
        add_cc: {
            type: Boolean,
            default() {
                return false
            }
        },
        showShortcodes: {
            type: Boolean,
            default() {
                return false
            }
        },
        openAIIntegration: {
            type: Boolean,
            default() {
                return false
            }
        },
        fluentBotIntegration: {
            type: Boolean,
            default() {
                return false
            }
        },
        showSavedReplies: {
            type: Boolean,
            default() {
                return false
            }
        },
        ticketId: {
            type: [String, Number],
            default() {
                return ''
            }
        },
        showFluentBooking: {
            type: Boolean,
            default() {
                return false
            }
        },
        productID: {
            type: [String, Number],
            default() {
                return ''
            }
        },
        is_agent: {
            type: [Boolean, String],
            default() {
                return false
            }
        },
        is_direct_paste: {
            type: Boolean,
            default() {
                return false
            }
        }
    },
    emits: ['update:modelValue', 'toggleCcOption', 'toggleFluentBot'],
    data() {
        return {
            showButtonDesigner: false,
            hasWpEditor: (!!window.wp?.editor && !!wp?.editor?.autop) || !!window.wp?.oldEditor,
            editor: window.wp?.oldEditor || window.wp?.editor,
            plain_content: this.modelValue,
            cursorPos: (this.modelValue) ? this.modelValue.length : 0,
            app_ready: false,
            buttonInitiated: false,
            currentEditor: false,

            showActionBar: false,
            actionBarStyle: {},
            showChatGPTPromptBox: false,
            showAIResponseBox: false,
            showFluentBotAIResponseBox: false,
            selectedText: '',
            editorData: {},
            loadingImage: false,
            showMentionSuggestions: false,
            mentionQuery: '',
            activeMentionIndex: 0,
            mentionSearchResults: [],
            mentionSearchLoading: false,
            mentionSearchTimer: null,
            mentionSearchLastQuery: '',
            mentionDropdownStyle: {
                top: '44px',
                left: '12px'
            },
            isDarkMode: document.body.classList.contains('fs-dark-mode')
        }
    },
    computed: {
        fluentBotIconSrc() {
            return this.appVars.asset_url + 'images/' + (this.isDarkMode ? 'fluentBotLight.svg' : 'fluentBotDark.svg');
        },
        shortcodes() {
            return this.editor_shortcodes;
        },
        hasShortcodes() {
            return this.editor_shortcodes && Object.keys(this.editor_shortcodes).length > 0;
        },
        filteredMentionAgents() {
            if (this.ticketId) {
                return this.mentionSearchResults.slice(0, 50);
            }

            const query = this.mentionQuery.toLowerCase();
            return (this.appVars?.support_agents || [])
                .filter(agent => {
                    if (!agent?.id) return false;
                    const name = `${agent.first_name} ${agent.last_name}`.toLowerCase();
                    const email = (agent.email || '').toLowerCase();
                    return name.includes(query) || email.includes(query);
                })
                .slice(0, 6);
        }
    },
    watch: {
        plain_content() {
            this.$emit('update:modelValue', this.plain_content);
        },
        ticketId() {
            this.mentionSearchResults = [];
            clearTimeout(this.mentionSearchTimer);
        },
    },
    methods: {
        handleThemeChange() {
            const isDarkMode = document.body.classList.contains('fs-dark-mode');

            if (isDarkMode === this.isDarkMode) {
                return;
            }

            this.isDarkMode = isDarkMode;

            if (this.hasWpEditor && window.tinyMCE && tinyMCE.get(this.editor_id)) {
                this.initEditor();
            }
        },
        initEditor() {
            if (!this.hasWpEditor) {
                return;
            }

            this.editor.remove(this.editor_id);
            const that = this;
            const isDarkMode = document.body.classList.contains('fs-dark-mode');
            const mentionChipBg = this.getThemeCssValue('--fs-status-info-bg', isDarkMode ? '#1E3A5F' : '#E1E4EA');
            const mentionChipText = this.getThemeCssValue('--fs-status-info-text', isDarkMode ? '#93C5FD' : '#1976D2');
            const mentionStyle = `.fs_agent_mention { display: inline-flex; align-items: center; border-radius: 6px; color: ${mentionChipText}; font-weight: 500; white-space: nowrap; } .fs_agent_mention[contenteditable="false"] { user-select: all; }`;
            const bookingStyle = getFluentBookingContentStyle({
                titleColor: this.getThemeCssValue('--fs-text-primary', isDarkMode ? '#E5E7EB' : '#0E121B'),
                textSecondary: this.getThemeCssValue('--fs-text-secondary', isDarkMode ? '#99A0AE' : '#525866'),
                textPrimary: this.getThemeCssValue('--fs-text-primary', isDarkMode ? '#E5E7EB' : '#0E121B'),
                borderColor: this.getThemeCssValue('--fs-stroke-soft', isDarkMode ? '#2B303B' : '#E1E4EA'),
                backgroundColor: this.getThemeCssValue('--fs-bg-primary', isDarkMode ? '#181B25' : '#FFFFFF'),
                subtleBackground: this.getThemeCssValue('--fs-bg-subtle', isDarkMode ? '#202531' : '#F5F7FA'),
                radius: this.getThemeCssValue('--fs-radius-md', '8px'),
                focusRing: this.getThemeCssValue('--fs-shadow-soft', 'rgba(153, 160, 174, 0.16)')
            });
            const mceConfig = {
                height: that.height,
                toolbar1: 'formatselect,code,table,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,underline,strikethrough,forecolor,removeformat,codeformat,outdent,indent,undo,redo',
                content_style: isDarkMode
                    ? `body { background-color: #181B25; color: #E5E7EB; } a { color: #93C5FD; } ${mentionStyle} ${bookingStyle}`
                    : `${mentionStyle} ${bookingStyle}`,
                setup(editor) {
                    editor.on('change', function (ed, l) {
                        that.changeContentEvent();
                    });
                    editor.on('mouseup', function (event) {
                        that.showActionBarOnSelection(editor);
                    });
                    editor.on('keyup click NodeChange', function () {
                        that.updateMentionSuggestions();
                    });
                    editor.on('keydown', function (event) {
                        that.handleMentionKeydown(event);
                    });
                    editor.on('blur', function () {
                        setTimeout(() => {
                            that.hideMentionSuggestions();
                        }, 120);
                    });

                    if (that.is_direct_paste) {
                        editor.on('paste', function(event) {
                            const clipboardData = event.clipboardData || window.clipboardData;

                            if (clipboardData && clipboardData.items) {
                                let hasImage = false;

                                for (let i = 0; i < clipboardData.items.length; i++) {
                                    const item = clipboardData.items[i];
                                    if (item.kind === 'file') {
                                        hasImage = true;
                                        break;
                                    }
                                }

                                if (hasImage) {
                                    that.loadingImage = true;
                                    that.$refs.imagePasteUploader.handleImagePaste(event, that.ticketId, that.is_agent);
                                }
                            }
                        });
                    }
                }
            };

            if (this.autofocus) {
                mceConfig.auto_focus = this.editor_id;
            }

            const initializeEditor = () => {
                this.editor.initialize(this.editor_id, {
                    mediaButtons: this.mediaButtons,
                    tinymce: mceConfig,
                    quicktags: true
                });
            };

            if (document.readyState === "complete" || document.readyState === "interactive") {
                initializeEditor();
            } else {
                jQuery(document).ready(initializeEditor);
            }

            jQuery('#' + this.editor_id).on('change', function () {
                that.changeContentEvent();
            });
        },
        insertHtml(content) {
            this.currentEditor.insertContent(content);
        },
        insertContent(content) {
            if (this.hasWpEditor) {
                let tinyInstance = tinyMCE.get(this.editor_id) || tinyMCE.get(wpActiveEditor);

                if (tinyInstance) {
                    tinyInstance.focus();
                    tinyInstance.insertContent(content);
                    this.$emit('update:modelValue', tinyInstance.getContent({ format: 'html' }));
                    return;
                }
            }

            const textarea = document.querySelector(`#${this.editor_id}`);
            const cursorPosition = textarea ? textarea.selectionStart : this.cursorPos;
            const beforeContent = this.plain_content.slice(0, cursorPosition);
            const afterContent = this.plain_content.slice(cursorPosition);

            this.plain_content = beforeContent + content + afterContent;
            this.cursorPos = cursorPosition + content.length;

            this.$nextTick(() => {
                if (textarea) {
                    textarea.selectionStart = textarea.selectionEnd = this.cursorPos;
                    textarea.focus();
                }
            });
        },
        changeContentEvent() {
            const content = this.editor.getContent(this.editor_id);
            this.$emit('update:modelValue', this.normalizeMentionMarkup(content));
        },
        getThemeCssValue(variableName, fallback = '') {
            const value = getComputedStyle(document.body).getPropertyValue(variableName).trim();

            return value || fallback;
        },
        normalizeMentionMarkup(content) {
            if (!content || typeof content !== 'string') {
                return content;
            }

            // Strip contenteditable — it is presentation-only and stripped by wp_kses on save.
            // Keep the span and data-mention-username intact so the stored HTML is styled
            // correctly and MentionParser can extract the agent ID for notifications.
            return content.replace(/\s+contenteditable=["'][^"']*["']/gi, '');
        },
        decodeHtmlEntities(value) {
            const element = document.createElement('textarea');
            element.innerHTML = String(value);

            return element.value;
        },
        getMentionTokenRange(content, cursorPosition) {
            const beforeCursor = content.slice(0, cursorPosition);
            const match = beforeCursor.match(/(^|[\s(])@([A-Za-z0-9._-]*)$/);

            if (!match) {
                return null;
            }

            return {
                start: beforeCursor.length - match[0].length + match[1].length,
                end: cursorPosition
            };
        },
        getEditorMentionRange(range) {
            if (
                !range ||
                !range.startContainer ||
                range.startContainer.nodeType !== 3 ||
                !range.collapsed
            ) {
                return null;
            }

            const text = range.startContainer.nodeValue || '';
            let tokenStart = range.startOffset;
            let tokenEnd = range.startOffset;

            while (tokenStart > 0 && /[A-Za-z0-9._-]/.test(text.charAt(tokenStart - 1))) {
                tokenStart--;
            }

            while (tokenEnd < text.length && /[A-Za-z0-9._-]/.test(text.charAt(tokenEnd))) {
                tokenEnd++;
            }

            if (tokenStart === 0 || text.charAt(tokenStart - 1) !== '@') {
                return null;
            }

            return {
                start: tokenStart - 1,
                end: tokenEnd
            };
        },

        closeSelectedTextPromptBox() {
            this.showChatGPTPromptBox = false;
            this.showActionBar = false;
        },

        closeAIResponsePromptBox() {
            this.showAIResponseBox = false;
        },

        toggleFluentBotPanel() {
            this.$emit('toggleFluentBot');
        },

        closeFluentBotAIResponsePromptBox() {
            this.showFluentBotAIResponseBox = false;
        },

        handleCommand(command) {
            if (this.hasWpEditor) {
                window.tinymce.activeEditor.insertContent(command);
            } else {
                var part1 = this.plain_content.slice(0, this.cursorPos);
                var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                this.plain_content = part1 + command + part2;
                this.cursorPos += command.length;
            }
        },

        updateCursorPos() {
            var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
            this.cursorPos = cursorPos;
        },
        handlePlainInput() {
            this.updateCursorPos();
            this.updateMentionSuggestions();
        },
        handlePlainSelectionChange() {
            this.updateCursorPos();
            this.updateMentionSuggestions();
        },
        supportsMentionSuggestions() {
            return this.is_agent === true || this.is_agent === 'yes' || this.is_agent === 'true';
        },
        getMentionQuery(content) {
            const match = content.match(/(?:^|[\s(])@([A-Za-z0-9._\- ]*)$/);

            if (!match) {
                return null;
            }

            return match[1];
        },
        getEditorTextBeforeCaret(editorInstance) {
            const selection = editorInstance?.selection;

            if (!selection) {
                return '';
            }

            const range = selection.getRng().cloneRange();
            range.setStart(editorInstance.getBody(), 0);

            return range.toString();
        },
        setMentionDropdownPosition() {
            if (this.hasWpEditor) {
                const editorInstance = tinyMCE.get(this.editor_id);
                const range = editorInstance?.selection?.getRng?.();

                if (!range) {
                    return;
                }

                const rect = range.getBoundingClientRect();
                const wrapperRect = this.$el.getBoundingClientRect();
                const left = Math.max(12, rect.left - wrapperRect.left);
                const top = Math.max(44, rect.bottom - wrapperRect.top + 8);

                this.mentionDropdownStyle = {
                    top: `${top}px`,
                    left: `${left}px`
                };

                return;
            }

            const textarea = this.$el.querySelector('.wp_vue_editor_plain');

            if (!textarea) {
                return;
            }

            this.mentionDropdownStyle = {
                top: `${textarea.offsetTop + 44}px`,
                left: `${textarea.offsetLeft + 12}px`
            };
        },
        updateMentionSuggestions() {
            if (!this.supportsMentionSuggestions()) {
                return;
            }

            let query = null;

            if (this.hasWpEditor) {
                const editorInstance = tinyMCE.get(this.editor_id);

                if (!editorInstance) {
                    this.hideMentionSuggestions();
                    return;
                }

                query = this.getMentionQuery(this.getEditorTextBeforeCaret(editorInstance));
            } else {
                const textarea = this.$el.querySelector('.wp_vue_editor_plain');
                const cursorPosition = textarea ? textarea.selectionStart : this.cursorPos;
                query = this.getMentionQuery(this.plain_content.slice(0, cursorPosition));
            }

            if (query === null || query.length < 1) {
                this.hideMentionSuggestions();
                return;
            }

            const wasHidden = !this.showMentionSuggestions;
            this.mentionQuery = query;

            if (this.ticketId) {
                this.triggerMentionSearch(query);
            } else if (!this.filteredMentionAgents.length) {
                this.hideMentionSuggestions();
                return;
            }

            if (wasHidden) {
                this.activeMentionIndex = 0;
            } else if (this.activeMentionIndex >= this.filteredMentionAgents.length) {
                this.activeMentionIndex = 0;
            }

            this.setMentionDropdownPosition();
            this.showMentionSuggestions = true;
        },
        triggerMentionSearch(query) {
            const searchTerm = query.trim();

            if (!searchTerm || searchTerm.length < 2) {
                clearTimeout(this.mentionSearchTimer);
                this.mentionSearchResults = [];
                this.mentionSearchLoading = false;
                this.mentionSearchLastQuery = '';
                return;
            }

            // Skip API call if the trimmed search term hasn't changed
            if (searchTerm === this.mentionSearchLastQuery) {
                return;
            }

            clearTimeout(this.mentionSearchTimer);
            this.mentionSearchLoading = true;

            const searchingFor = searchTerm;
            this.mentionSearchTimer = setTimeout(() => {
                this.$get(`tickets/${this.ticketId}/mentionable-agents`, {
                    search: searchingFor,
                    limit: 50
                }).then(response => {
                    // Discard stale responses if the user kept typing
                    if (searchingFor !== this.mentionQuery.trim()) return;
                    this.mentionSearchLastQuery = searchingFor;
                    this.mentionSearchResults = response?.agents || [];
                    if (this.activeMentionIndex >= this.mentionSearchResults.length) {
                        this.activeMentionIndex = 0;
                    }
                }).catch(() => {
                    if (searchingFor === this.mentionQuery.trim()) {
                        this.mentionSearchResults = [];
                    }
                }).always(() => {
                    if (searchingFor === this.mentionQuery.trim()) {
                        this.mentionSearchLoading = false;
                    }
                });
            }, 350);
        },
        hideMentionSuggestions() {
            this.showMentionSuggestions = false;
            this.mentionQuery = '';
            this.activeMentionIndex = 0;
            this.mentionSearchResults = [];
            this.mentionSearchLoading = false;
            this.mentionSearchLastQuery = '';
            clearTimeout(this.mentionSearchTimer);
        },
        handleMentionKeydown(event) {
            if (!this.showMentionSuggestions || !this.filteredMentionAgents.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.activeMentionIndex = (this.activeMentionIndex + 1) % this.filteredMentionAgents.length;
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeMentionIndex = (this.activeMentionIndex - 1 + this.filteredMentionAgents.length) % this.filteredMentionAgents.length;
                return;
            }

            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                this.selectMentionAgent(this.filteredMentionAgents[this.activeMentionIndex]);
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                this.hideMentionSuggestions();
            }
        },
        selectMentionAgent(agent) {
            if (!agent?.id) {
                return;
            }
            const displayName = `${agent.first_name} ${agent.last_name}`.trim();
            const agentId    = this.escapeHtmlAttribute(String(agent.id));
            // Plain-text path uses a parseable token so the backend can
            // extract the agent ID for notification dispatch without HTML.
            const mentionText = `@[${agent.id}:${displayName}] `;
            const mentionMarkup = `<span class="fs_agent_mention" data-mention-username="${agentId}" contenteditable="false">@${displayName}</span>&nbsp;`;

            if (this.hasWpEditor) {
                const editorInstance = tinyMCE.get(this.editor_id);

                if (editorInstance) {
                    const range = editorInstance.selection?.getRng?.();
                    const mentionRange = this.getEditorMentionRange(range);

                    if (mentionRange) {
                        range.setStart(range.startContainer, mentionRange.start);
                        range.setEnd(range.startContainer, mentionRange.end);
                        editorInstance.selection.setRng(range);
                        editorInstance.selection.setContent(mentionMarkup);
                    } else {
                        editorInstance.insertContent(mentionMarkup);
                    }

                    this.$emit('update:modelValue', this.normalizeMentionMarkup(editorInstance.getContent()));
                }
            } else {
                const textarea = this.$el.querySelector('.wp_vue_editor_plain');
                const start = textarea ? textarea.selectionStart : this.cursorPos;
                const end = textarea ? textarea.selectionEnd : this.cursorPos;
                const tokenRange = this.getMentionTokenRange(this.plain_content, start);

                if (!tokenRange) {
                    this.hideMentionSuggestions();
                    return;
                }

                const before = this.plain_content.slice(0, tokenRange.start);
                const after = this.plain_content.slice(tokenRange.end);

                this.plain_content = `${before}${mentionText}${after}`;

                this.$nextTick(() => {
                    if (textarea) {
                        const nextCursor = before.length + mentionText.length;
                        textarea.selectionStart = nextCursor;
                        textarea.selectionEnd = nextCursor;
                        textarea.focus();
                    }
                });
            }

            this.hideMentionSuggestions();
        },
        escapeHtmlAttribute(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        },

        handleCc(command) {
            this.$emit('toggleCcOption', command);
        },

        insertShortcode(content) {
            const shortcode = content.target._value;

            if (this.hasWpEditor) {
                let tinyInstance = tinyMCE.get(this.editor_id);
                if (tinyInstance) {
                    tinyInstance.focus();
                    tinyInstance.insertContent(shortcode);
                    const updatedContent = tinyInstance.getContent();
                    this.$emit('update:modelValue', updatedContent);
                }
            } else {
                const textarea = document.querySelector(`#${this.editor_id}`);
                if (textarea) {
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;
                    const before = text.substring(0, start);
                    const after = text.substring(end, text.length);
                    const newValue = before + shortcode + after;
                    this.plain_content = newValue;
                    // Set cursor position after the inserted shortcode
                    this.$nextTick(() => {
                        textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
                        textarea.focus();
                    });
                }
            }
        },

        insertTemplate(content) {
            let tinyInstance = tinyMCE.editors[wpActiveEditor];
            tinyInstance.setContent(this.modelValue + content)
            this.$emit('update:modelValue', this.modelValue + content)
        },

        insertAIResponse(content) {
            let tinyInstance = tinyMCE.get(wpActiveEditor);

            // Content should already be HTML at this point
            tinyInstance.insertContent(content);

            this.$emit('update:modelValue', tinyInstance.getContent({ format: 'html' }));

            this.showChatGPTPromptBox = false;
            this.showAIResponseBox = false;
            this.showFluentBotAIResponseBox = false;
            this.showActionBar = false;
        },

        showActionBarOnSelection(editor) {
            this.editorData = editor;
            const selection = this.editorData.selection;

            if (!selection.isCollapsed()) {
                const selectedText = selection.getContent({ format: 'text' });

                if (selectedText.length > 0) {
                    // Get the bounding rectangle of the entire selection
                    const range = selection.getRng();
                    const rect = range.getBoundingClientRect();

                    // Find the bounding rect of the first line of the selection
                    const rangeStart = range.cloneRange();
                    rangeStart.setStart(rangeStart.startContainer, rangeStart.startOffset);
                    rangeStart.setEnd(rangeStart.startContainer, rangeStart.startOffset);
                    const rectStart = rangeStart.getBoundingClientRect();

                    this.actionBarStyle = {
                        top: `${rectStart.top + 80}px`,
                        left: `${rectStart.right + 40}px`,
                        position: 'absolute',
                        zIndex: 1
                    };

                    this.selectedText = selectedText;
                    this.showChatGPTPromptBox = false;
                    this.showActionBar = true;
                }
            } else {
                this.showActionBar = false;
            }
        },

        overallTicketResponse() {
            this.showAIResponseBox = !this.showAIResponseBox;
        },

        editSelection() {
            if (!this.editor) {
                console.error('TinyMCE editor instance or selection not available.');
                return;
            }

            const selection = this.editorData.selection;
            const selectedText = selection.getContent({ format: 'text' }).trim();
            if (selectedText) {
                this.selectedText = selectedText;
                this.showChatGPTPromptBox = true;
            }
        },
        setImagePath(imageUrl) {
            const tinyInstance = tinyMCE.get(this.editor_id);
            if (!tinyInstance) return;

            const imageElement = new Image();
            imageElement.src = imageUrl;

            imageElement.onload = () => {
                const maxDimension = 300;
                const {naturalWidth, naturalHeight} = imageElement;
                const aspectRatio = naturalWidth / naturalHeight;

                let width = naturalWidth;
                let height = naturalHeight;

                if (naturalWidth > naturalHeight) {
                    width = maxDimension;
                    height = maxDimension / aspectRatio;
                } else {
                    height = maxDimension;
                    width = maxDimension * aspectRatio;
                }

                const imgTag = `<img src="${imageUrl}" alt="Uploaded Image" style="width: ${width}px; height: ${height}px;" />`;
                tinyInstance.insertContent(imgTag);

                this.loadingImage = false;
            };
        },
    },
    mounted() {
        this.initEditor();
        this.app_ready = true;
        window.addEventListener('fs-theme-changed', this.handleThemeChange);
    },
    beforeUnmount() {
        window.removeEventListener('fs-theme-changed', this.handleThemeChange);
    }
}
</script>
