<template>
    <div class="fs_container">
        <div class="fs_ai_header_section">
            <div class="fs_icon_container">
                <img
                    loading="lazy"
                    :src="appVars.asset_url + 'images/aiMagicIcon.svg'"
                    class="fs_icon"
                />
            </div>

            <div class="fs_text_container">
                <div class="fs_header_text">{{ translate(title) }}</div>
                <div class="fs_description_text">{{ translate(description) }}</div>
            </div>

            <div class="fs_close">
                <el-button class="fs_close_button" @click="closeModal">
                    <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                </el-button>
            </div>
        </div>

        <div class="fs_product_selector">
            <div class="fs_product_label">{{ translate('Select Product') }}</div>
            <el-select
                v-model="selectedProduct"
                placeholder="Select a product"
                size="small"
                class="fs_select_field fs_product_dropdown"
                filterable
                clearable
                :value-key="'id'"
                :default-value="0"
            >
                <el-option
                    :key="0"
                    :label="translate('General Bot (No specific product selected)')"
                    :value="0"
                />
                <el-option
                    v-for="product in products"
                    :key="product.id"
                    :label="product.title"
                    :value="product.id"
                />
            </el-select>
        </div>

        <div class="fs_draft" v-if="draftData.length > 1">
            <el-button class="fs_draft_button" @click="showDraft = !showDraft">
                <span>{{translate('Draft')}}</span>
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

        <div class="fs_response_section">
            <div v-loading="loading && !isStreaming" class="fs_response_container" v-if="aiResponse || isStreaming">
                <div class="fs_response_header">
                    <div class="fs_resize">
                        <el-button class="fs_resize_button" text @click="isFullSize = !isFullSize">
                            <img :src="appVars.asset_url + 'images/resize.svg'" alt="">
                        </el-button>
                    </div>
                    <div class="fs_response_actions">
                        <div class="fs_copy_text">
                            <el-button class="fs_copy_text_button" text @click="copyText" :disabled="isStreaming">
                                <img :src="appVars.asset_url + 'images/copyText.svg'" alt="">
                            </el-button>
                        </div>

                        <div class="fs_regenerate">
                            <el-button class="fs_regenerate_button" @click="generateResponse(finalPrompts)" :disabled="isStreaming">
                                <img :src="appVars.asset_url + 'images/regenerate.svg'" alt="">
                            </el-button>
                        </div>

                        <div class="fs_response_insert_button">
                            <el-button class="fs_insert_button" @click="insertReply(aiResponse)" :disabled="isStreaming">
                                {{ translate('Insert Content') }}
                            </el-button>
                        </div>
                    </div>
                </div>
                <div :class="['fs_response_content', { 'full-size': isFullSize, 'typing': isStreaming }]">
                    <div class="fs_response_text" v-html="formattedResponse"></div>
                    <div v-if="isStreaming && typingQueue.length === 0" class="fs_streaming_indicator">
                        <span>Generating</span><span class="fs_typing_dots">●●●</span>
                    </div>
                </div>
            </div>
            <div class="fs_ai_response_loading" v-if="loading && !isStreaming && !aiResponse">
                <el-skeleton :rows="4" animated />
            </div>
        </div>

        <div class="fs_main_content">
            <div class="fs_prompt_wrapper">
                <textarea v-model="prompt" rows="3" placeholder="Enter your prompt here..." class="fs_textarea" required :disabled="isStreaming"></textarea>
                <div class="fs_prompt_button">
                    <el-button class="fs_prompt_submit" @click="generateResponse(prompt)" :disabled="isStreaming">
                        <img :src="appVars.asset_url + 'images/aiPromptSubmitButton.svg'" alt="">
                    </el-button>
                </div>
            </div>
            <div v-if="errorMessage" class="fs_error_message">{{ errorMessage }}</div>
            <div>
                <div class="fs_prompt_subtitle">{{ translate('Some General Prompts') }}</div>
                <div class="fs_prompt_options_container">
                    <div
                        v-for="prompt in presetPrompts"
                        :key="prompt.text"
                        :class="['fs_prompt_option', { 'fs_prompt_option_selected': prompt === selectedPrompt, 'disabled': isStreaming }]"
                        @click="!isStreaming && selectPresetPrompt(prompt)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="2" :fill="isSelected(prompt) ? '#FFF' : '#717784'" />
                        </svg>
                        <div class="fs_prompt_option_text">{{ prompt.label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { reactive, toRefs, onMounted, computed, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { useFluentHelper, useNotify } from "@/admin/Composable/FluentFrameworkHelper";
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import hljs from 'highlight.js';

export default {
    name: 'FluentBotAIResponseGenerator',
    props: ['aiProvider', 'ticketId', 'productID'],

    setup(props, context) {
        const { post, get, translate, handleError, appVars, saveData, getData, removeData } = useFluentHelper();
        const route = useRoute();
        const emit = context.emit;
        const { notify } = useNotify();

        // Configure marked with highlight.js
        marked.setOptions({
            breaks: true,
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

        const state = reactive({
            prompt: '',
            presetPrompt: '',
            errorMessage: '',
            aiResponse: '',
            loading: false,
            ticketID: props.ticketId,
            selectedPrompt: '',
            isFullSize: true,
            presetPrompts: [],
            draftData: [],
            showDraft: false,
            finalPrompts: '',
            products: appVars.support_products,
            selectedProduct: props.productID ? parseInt(props.productID) : 0,
            conversationId: null,
            isStreaming: false,
            streamBuffer: '',
            displayedText: '',
            typingQueue: []
        });

        const title = 'Generate Responses with Fluent Bot';
        const description = 'Let Fluent Bot generate ticket responses to enhance support efficiency.';

        // Function to format text for editor insertion with better formatting
        const getFormattedTextForEditor = (text) => {
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
        };

        // Keep the old function for copy functionality (plain text)
        const getCleanTextForEditor = (text) => {
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
        };

        // Use marked library for markdown formatting
        const formattedResponse = computed(() => {
            if (!state.aiResponse) return '';

            try {
                // Use marked to convert markdown to HTML
                const html = marked.parse(state.aiResponse);

                // Sanitize and return the HTML output
                return DOMPurify.sanitize(html);
            } catch (error) {
                console.error('Error parsing markdown:', error);
                // Fallback to plain text with line breaks
                return DOMPurify.sanitize(state.aiResponse.replace(/\n/g, '<br>'));
            }
        });


        const saveDraft = () => {
            const draftKey = 'createResponseDraft';
            const draft = JSON.parse(getData(draftKey)) || [];
            if (draft.length >= 3) {
                draft.shift();
            }
            // Save the clean text version for drafts
            const cleanText = getCleanTextForEditor(state.aiResponse);
            draft.push(cleanText);
            saveData(draftKey, JSON.stringify(draft));
            state.draftData = draft;
        };

        const addToStream = (text) => {
            // Don't clean the text here - preserve original formatting from SSE
            if (text) {
                state.aiResponse += text;
            }
        };

        const generateResponse = (prompt) => {
            const trimmedPrompt = prompt.trim();

            if (!trimmedPrompt) {
                state.errorMessage = 'Prompt is required.';
                return;
            }

            state.errorMessage = '';
            state.loading = true;
            state.isStreaming = true;
            state.aiResponse = '';
            state.displayedText = '';
            state.typingQueue = [];
            state.streamBuffer = '';

            const requestData = {
                content: trimmedPrompt,
                id: state.ticketID,
                type: 'createResponse',
                provider: props.aiProvider,
                product_id: state.selectedProduct,
            };

            // Include conversation_id if we have one from previous interactions
            if (state.conversationId) {
                requestData.conversation_id = state.conversationId;
            }

            // Use fetch for proper streaming
            const baseUrl = appVars.rest.url;
            const nonce = appVars.rest.nonce;

            fetch(`${baseUrl}/fluent-bot/${state.ticketID}/generate-stream-response`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify(requestData)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    function readStream() {
                        return reader.read().then(({ done, value }) => {
                            if (done) {
                                state.loading = false;
                                state.isStreaming = false;
                                state.finalPrompts = trimmedPrompt;

                                if (state.prompt || state.aiResponse) {
                                    state.selectedPrompt = '';
                                    saveDraft();
                                }
                                return;
                            }

                            const chunk = decoder.decode(value, { stream: true });
                            buffer += chunk;

                            // Process complete SSE events
                            const events = buffer.split('\n\n');
                            buffer = events.pop() || ''; // Keep incomplete event in buffer

                            events.forEach(event => {
                                if (event.trim()) {
                                    const lines = event.split('\n');
                                    let eventType = '';
                                    let eventId = '';
                                    let dataLines = [];

                                    lines.forEach(line => {
                                        if (line.startsWith('event: ')) {
                                            eventType = line.substring(7).trim();
                                        } else if (line.startsWith('id: ')) {
                                            eventId = line.substring(4).trim();
                                        } else if (line.startsWith('data: ')) {
                                            dataLines.push(line.substring(6));
                                        }
                                    });

                                    // Join data lines preserving original structure
                                    const data = dataLines.join('\n');

                                    // Only append message events and ignore control characters
                                    if (eventType === 'message' && data && data !== '<|>') {
                                        // Add to stream buffer and display immediately - preserve original formatting
                                        state.streamBuffer += data;
                                        addToStream(data);
                                    } else if (eventType === 'conversation_id' && data) {
                                        state.conversationId = data.trim();
                                    } else if (eventType === 'end') {
                                        state.loading = false;
                                        state.isStreaming = false;
                                        state.finalPrompts = trimmedPrompt;
                                        if (state.prompt || state.aiResponse) {
                                            state.selectedPrompt = '';
                                            saveDraft();
                                        }
                                        return;
                                    } else if (eventType === 'error') {
                                        state.loading = false;
                                        state.isStreaming = false;
                                        state.errorMessage = 'Failed to generate response. Please try again.';
                                        return;
                                    }
                                }
                            });

                            return readStream();
                        });
                    }

                    return readStream();
                })
                .catch(error => {
                    state.loading = false;
                    state.isStreaming = false;
                    state.errorMessage = 'Failed to generate response. Please try again.';
                });
        };

        const selectPresetPrompt = (preset) => {
            state.selectedPrompt = preset;
            state.conversationId = null;
            const selectedPrompt = state.presetPrompts.find(item => item.text === preset.text);
            state.presetPrompt = `${selectedPrompt.description}`;
            state.prompt = '';
            generateResponse(state.presetPrompt);
        };

        const isSelected = (prompt) => {
            return state.selectedPrompt === prompt;
        };

        const closeModal = () => {
            removeDraft();
            emit('close');
            resetData();
        };

        const copyText = async () => {
            try {
                // Copy the clean text version instead of HTML
                const cleanText = getCleanTextForEditor(state.aiResponse);
                await navigator.clipboard.writeText(cleanText);
                notify({
                    message: "Copied to clipboard",
                    type: "success",
                    position: "bottom-right",
                });
            } catch (error) {
                notify({
                    message: "Something went wrong",
                    type: "danger",
                    position: "bottom-right",
                });
            }
        };

        const resetData = () => {
            state.aiResponse = '';
            state.selectedPrompt = '';
            state.prompt = '';
            state.conversationId = null;
            state.isStreaming = false;
            state.displayedText = '';
            state.typingQueue = [];
            state.streamBuffer = '';

            removeDraft();
        };

        const insertReply = (aiResponse) => {
            // Create a clean version for text editor insertion
            const cleanText = getCleanTextForEditor(aiResponse);
            emit('insert', cleanText);
            resetData();
        };

        const fetchPresets = () => {
            get('fluent-bot/preset-prompts', {
                type: 'createResponse',
                provider: props.aiProvider
            })
                .then(response => {
                    state.presetPrompts = response;
                })
                .catch(errors => {
                    handleError(errors);
                });
        };

        const removeDraft = () => {
            const draftKey = 'createResponseDraft';
            removeData(draftKey);
            state.draftData = [];
        };

        const getSnippet = (text) => {
            return text.length > 30 ? text.substring(0, 30) + '...' : text;
        };

        const selectDraft = (draft) => {
            state.aiResponse = draft;
            state.displayedText = draft;
            state.typingQueue = [];
            state.streamBuffer = draft;
        };

        onMounted(() => {
            fetchPresets();
            removeDraft();
        });

        // Clean up when component is destroyed
        onBeforeUnmount(() => {
            // No cleanup needed for direct streaming
        });

        return {
            ...toRefs(state),
            generateResponse,
            selectPresetPrompt,
            translate,
            insertReply,
            isSelected,
            copyText,
            closeModal,
            appVars,
            title,
            description,
            getSnippet,
            selectDraft,
            saveDraft,
            formattedResponse
        };
    }
};
</script>

<style>
.fs_product_selector {
    padding: 20px 20px 0 20px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.fs_product_selector .fs_product_label {
    color: #0E121B;
    font-size: 14px;
    font-style: normal;
    font-weight: 500;
    line-height: 20px;
    letter-spacing: -0.084px;
}

.fs_product_selector .fs_select_field .el-select__wrapper {
    display: flex;
    padding: 5px 5px 5px 12px;
    align-items: center;
    gap: 8px;
    align-self: stretch;
    border-radius: 10px;
    border: 1px solid #E1E4EA;
    background: #FFF;
    box-shadow: 0px 1px 2px 0px rgba(10, 13, 20, 0.03);
}

.fs_product_selector .fs_select_field .el-select__wrapper.is-focused {
    border-radius: 8px;
    border: 1px solid #0E121B;
    background: #FFF;
    box-shadow: 0px 0px 0px 2px #FFF, 0px 0px 0px 4px rgba(153, 160, 174, 0.16);
}

.fs_product_selector .fs_select_field .el-select__placeholder {
    color: #99A0AE;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 20px;
    letter-spacing: -0.084px;
}

.el-select-dropdown__wrap .el-select-dropdown__item.is-selected {
    color: #525866;
    font-weight: bold;
}

.fs_streaming_indicator {
    margin-top: 10px;
    display: flex;
    align-items: center;
    color: #666;
    font-size: 14px;
}

.fs_typing_dots {
    animation: typing 1.2s infinite;
    font-size: 14px;
    color: #0073aa;
    margin-left: 5px;
}

@keyframes typing {
    0%, 30% { opacity: 0.3; }
    60% { opacity: 1; }
    100% { opacity: 0.3; }
}

/* Add a subtle cursor effect to the response text while typing */
.fs_response_content {
    position: relative;
}

.fs_response_text {
    white-space: pre-line; /* Preserve line breaks but collapse multiple spaces */
    word-wrap: break-word;
    line-height: 1.6;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.fs_response_text p {
    margin: 0 0 1em 0;
    white-space: pre-line; /* Preserve line breaks within paragraphs */
}

.fs_response_text p:last-child {
    margin-bottom: 0;
}

.fs_response_text br {
    line-height: 1.6;
}

.fs_response_text strong {
    font-weight: 600;
    color: #1a1a1a;
}

.fs_response_text em {
    font-style: italic;
}

.fs_response_text code {
    background-color: #f1f3f4;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.9em;
    color: #d73a49;
}

.fs_response_text pre {
    background-color: #f6f8fa;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    padding: 16px;
    overflow-x: auto;
    margin: 1em 0;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    line-height: 1.4;
}

.fs_response_text pre code {
    background: none;
    padding: 0;
    border-radius: 0;
    color: #24292e;
    font-family: inherit;
    font-size: inherit;
    white-space: pre-wrap; /* Allow line wrapping */
    word-wrap: break-word;
    display: block;
    line-height: 1.5;
}

.fs_response_text code.language-php {
    color: #d73a49;
}

.fs_response_text pre code.language-php {
    color: #24292e;
}

.fs_response_text .bullet-point {
    margin-left: 0;
    padding-left: 0;
}

.fs_response_text span.bullet-point {
    display: inline;
    margin-left: 0;
}

.fs_response_text a {
    color: #0366d6;
    text-decoration: none;
}

.fs_response_text a:hover {
    text-decoration: underline;
}

.fs_response_content.typing::after {
    content: '|';
    animation: blink 1s infinite;
    color: #0073aa;
    font-weight: bold;
    margin-left: 1px;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

.fs_prompt_option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.fs_textarea:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Additional styles for highlight.js integration */
.hljs {
    display: block;
    overflow-x: auto;
    padding: 0.5em;
    color: #333;
    background: #f8f8f8;
}

/* Style fixes for markdown formatting */
.fs_response_text ul {
    list-style-type: disc;
    margin-left: 1.5em;
    margin-bottom: 1em;
    padding-left: 0;
}

.fs_response_text ol {
    list-style-type: decimal;
    margin-left: 1.5em;
    margin-bottom: 1em;
    padding-left: 0;
}

.fs_response_text li {
    margin-bottom: 0.5em;
    line-height: 1.6;
}

.fs_response_text h1,
.fs_response_text h2,
.fs_response_text h3,
.fs_response_text h4,
.fs_response_text h5,
.fs_response_text h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    font-weight: 600;
    color: #1a1a1a;
}

.fs_response_text h1 {
    font-size: 1.4em;
}

.fs_response_text h2 {
    font-size: 1.3em;
}

.fs_response_text h3 {
    font-size: 1.2em;
}

.fs_response_text h4 {
    font-size: 1.1em;
}

.fs_response_text h5 {
    font-size: 1.05em;
}

.fs_response_text h6 {
    font-size: 1em;
}

.fs_response_text blockquote {
    padding-left: 1em;
    border-left: 4px solid #e1e4e8;
    color: #6a737d;
    margin: 0 0 1em;
}

.fs_response_text table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 1em;
}

.fs_response_text table th,
.fs_response_text table td {
    padding: 8px;
    border: 1px solid #e1e4e8;
    text-align: left;
}

.fs_response_text table th {
    background-color: #f6f8fa;
}
</style>
