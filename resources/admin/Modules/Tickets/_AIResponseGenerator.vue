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
                <div class="fs_header_text">{{ $t(title) }}</div>
                <div class="fs_description_text">{{ $t(description) }}</div>
            </div>

            <div class="fs_close">
                <el-button class="fs_close_button" @click="closeModal">
                    <img :src="appVars.asset_url + 'images/closeIcon.svg'" alt="">
                </el-button>
            </div>
        </div>

        <div class="fs_draft" v-if="draftData.length > 1">
            <el-button class="fs_draft_button" @click="showDraft = !showDraft">
                <span>{{$t('Draft')}}</span>
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
            <div v-loading="loading" class="fs_response_container" v-if="aiResponse && !loading">
                <div class="fs_response_header">
                    <div class="fs_resize">
                        <el-button class="fs_resize_button" text @click="isFullSize = !isFullSize">
                            <img :src="appVars.asset_url + 'images/resize.svg'" alt="">
                        </el-button>
                    </div>
                    <div class="fs_response_actions">
                        <div class="fs_copy_text">
                            <el-button class="fs_copy_text_button" text @click="copyText">
                                <img :src="appVars.asset_url + 'images/copyText.svg'" alt="">
                            </el-button>
                        </div>

                        <div class="fs_regenerate">
                            <el-button class="fs_regenerate_button" @click="generateResponse(finalPrompts)">
                                <img :src="appVars.asset_url + 'images/regenerate.svg'" alt="">
                            </el-button>
                        </div>

                        <div class="fs_response_insert_button">
                            <el-button class="fs_insert_button" @click="insertReply()">{{ $t('Insert Content') }}</el-button>
                        </div>
                    </div>
                </div>
                <div :class="['fs_response_content', { 'full-size': isFullSize }]">
                    <div v-html="formattedResponse"></div>
                </div>
            </div>
            <div class="fs_ai_response_loading" v-if="loading">
                <el-skeleton :rows="4" animated />
            </div>
        </div>

        <div class="fs_main_content">
            <div class="fs_prompt_wrapper">
                <textarea v-model="prompt" rows="3" :placeholder="$t('Enter your prompt here...')" class="fs_textarea" required></textarea>
                <div class="fs_prompt_button">
                    <el-button class="fs_prompt_submit" @click="generateResponse(prompt)" >
                        <img :src="appVars.asset_url + 'images/aiPromptSubmitButton.svg'" alt="">
                    </el-button>
                </div>
            </div>
            <div v-if="errorMessage" class="fs_error_message">{{ errorMessage }}</div>
            <div>
                <div class="fs_prompt_subtitle">{{ $t('Some General Prompts') }}</div>
                <div class="fs_prompt_options_container">
                    <div
                        v-for="prompt in presetPrompts"
                        :key="prompt.text"
                        :class="['fs_prompt_option', { 'fs_prompt_option_selected': prompt === selectedPrompt }]"
                        @click="selectPresetPrompt(prompt)"
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

<script type="text/babel">
import { parseMarkdown } from '@/admin/Composable/useMarkdown';

export default {
    name: 'AIResponseGenerator',
    props: ['selectedText', 'type'],
    data() {
        return {
            prompt: '',
            presetPrompt: '',
            errorMessage: '',
            aiResponse: '',
            loading: false,
            ticketID: 0,
            selectedPrompt: '',
            isFullSize: false,
            presetPrompts: [],
            draftData: [],
            showDraft: false,
            finalPrompts: []
        };
    },
    computed: {
        title() {
            const modifyResponseTitle = 'Enhance Responses with AI';
            const generateResponseTitle = 'Generate Responses with AI';
            return this.type === 'modifyResponse' ? modifyResponseTitle : generateResponseTitle;
        },
        description() {
            const modifyResponseDescription = 'Refine ticket responses with AI to enhance clarity and precision.';
            const generateResponseDescription = 'Let AI generate ticket responses to enhance support efficiency.';
            return this.type === 'modifyResponse' ? modifyResponseDescription : generateResponseDescription;
        },
        formattedResponse() {
            return parseMarkdown(this.aiResponse);
        }
    },
    methods: {
        saveDraft() {
            const draftKey = this.type === 'modifyResponse' ? 'modifyResponseDraft' : 'createResponseDraft';
            const draft = JSON.parse(this.$getData(draftKey) || '[]') || [];
            if (draft.length >= 3) {
                draft.shift();
            }
            draft.push(this.aiResponse);
            this.$saveData(draftKey, JSON.stringify(draft));
            this.draftData = draft;
        },
        generateResponse(prompt) {
            if (!prompt.trim()) {
                this.errorMessage = 'Prompt is required.';
                return;
            }
            this.errorMessage = '';

            this.loading = true;
            const requestData = {
                content: prompt,
                id: this.ticketID,
                type: this.type
            };

            if (this.type !== 'createResponse') {
                requestData.selectedText = this.selectedText;
                requestData.type = this.type;
            }

            // ai/ prefix routes are registered by the Pro plugin
            this.$post(`ai/${this.ticketID}/generate-response`, requestData)
                .then(response => {
                    this.aiResponse = response;
                    this.loading = false;
                    this.finalPrompts = prompt;
                    if (this.prompt || this.aiResponse) {
                        this.selectedPrompt = '';
                        this.saveDraft();
                    }
                })
                .catch(errors => {
                    this.loading = false;
                    this.$handleError(errors);
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
            this.removeDraft();
            this.$emit('close');
            this.resetData();
        },
        async copyText() {
            try {
                await navigator.clipboard.writeText(this.aiResponse);
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
            this.removeDraft();
        },
        insertReply() {
            // Emit the formatted HTML instead of raw text to preserve formatting
            this.$emit('insert', this.formattedResponse);
            this.resetData();
        },
        fetchPresets() {
            // ai/ prefix routes are registered by the Pro plugin
            this.$get('ai/preset-prompts', { type: this.type })
                .then(response => {
                    this.presetPrompts = response;
                })
                .catch(errors => {
                    this.$handleError(errors);
                });
        },
        removeDraft() {
            const draftKey = this.type === 'modifyResponse' ? 'modifyResponseDraft' : 'createResponseDraft';
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
        }
    },
    mounted() {
        this.ticketID = parseInt(this.$route.params.ticket_id);
        this.fetchPresets();
        this.removeDraft();
    }
};
</script>



