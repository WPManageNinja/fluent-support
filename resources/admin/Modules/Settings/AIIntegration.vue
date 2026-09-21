<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t('AI Model Setup') }}</h3>
            </div>
            <div class="fs_box_actions" v-if="has_pro && !loading">
                <el-button
                    class="fs_filled_btn"
                    type="primary"
                    :loading="saving"
                    @click="saveSettings"
                >
                    {{ $t('Save') }}
                </el-button>
            </div>
        </div>

        <template v-if="has_pro">
            <div class="fs_ai_skeleton" v-if="loading">
                <el-skeleton :animated="true" :rows="5" />
            </div>

            <div class="fs_ai_sections_wrapper" v-else>
                <!-- Enable AI -->
                <div class="fs_ai_section">
                    <div class="fs_ai_toggle_row">
                        <el-switch v-model="isEnabled" />
                        <div class="fs_ai_toggle_text">
                            <span class="fs_ai_toggle_title">{{ $t('Enable AI') }}</span>
                            <span class="fs_ai_toggle_desc">{{ $t('When enabled, AI will be available for response suggestions and ticket summaries.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- AI Provider -->
                <div class="fs_ai_section" v-if="isEnabled">
                    <div class="fs_ai_section_head">{{ $t('AI Provider') }}</div>
                    <div class="fs_ai_field_row">
                        <div class="fs_ai_field_label">
                            <p class="fs_ai_field_title">{{ $t('Provider') }}</p>
                            <p class="fs_ai_field_desc">{{ $t('Select the AI service you want to use.') }}</p>
                        </div>
                        <div class="fs_ai_field_input">
                            <el-select v-model="provider" @change="onProviderChange" class="fs_ai_select fs_select_field">
                                <el-option
                                    v-for="p in providers"
                                    :key="p.value"
                                    :value="p.value"
                                    :label="p.label"
                                />
                            </el-select>
                        </div>
                    </div>
                    <div class="fs_ai_divider"></div>
                    <div class="fs_ai_field_row">
                        <div class="fs_ai_field_label">
                            <p class="fs_ai_field_title">{{ $t('Model') }}</p>
                            <p class="fs_ai_field_desc">{{ $t('Choose the AI model for text generation.') }}</p>
                        </div>
                        <div class="fs_ai_field_input">
                            <el-select v-model="selectedModel" class="fs_ai_select fs_select_field">
                                <el-option
                                    v-for="m in modelOptions"
                                    :key="m.value"
                                    :value="m.value"
                                    :label="m.label"
                                />
                            </el-select>
                        </div>
                    </div>
                </div>

                <!-- API Key -->
                <div class="fs_ai_section" v-if="isEnabled && provider">
                    <div class="fs_ai_section_head">{{ $t('API Key') }}</div>
                    <div class="fs_ai_field_row">
                        <div class="fs_ai_field_label">
                            <p class="fs_ai_field_title">{{ $t('API Key') }}</p>
                            <p class="fs_ai_field_desc">{{ $t('Enter your API key. It will be stored securely.') }}</p>
                        </div>
                        <div class="fs_ai_field_input">
                            <el-input
                                v-model="apiKey"
                                autocomplete="off"
                                data-1p-ignore
                                data-lpignore="true"
                                :placeholder="isKeySaved ? $t('Enter a new key to replace the saved one') : $t('Enter your API key')"
                                class="fs_ai_input fs_text_input"
                            />
                            <p v-if="isKeySaved" style="margin-top: 6px; font-size: 12px; color: #6b7280;">
                                {{ $t('A key ending in') }} <strong>{{ apiKey.slice(-4) }}</strong> {{ $t('is saved.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <NarrowPromo
            v-else
            :heading="$t('Use AI for responses, ticket summaries, and sentiment analysis')"
            :description="$t('pro_promo')"
            :button-text="$t('Upgrade To Pro')"
             utm-content="feature_lock_ai_integration"
        />
    </div>
</template>

<script type="text/babel">
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: "AIIntegration",
    components: { NarrowPromo },
    data() {
        return {
            loading: false,
            saving: false,
            isEnabled: false,
            provider: "openai",
            apiKey: "",
            selectedModel: "",
            modelOptions: [],
            providers: [
                { value: "openai",    label: "OpenAI" },
                { value: "gemini",    label: "Google Gemini" },
                { value: "anthropic", label: "Anthropic Claude" },
            ],
            availableModels: {},
        };
    },
    computed: {
        isKeySaved() {
            return this.apiKey && this.apiKey.startsWith('****');
        },
    },
    methods: {
        onProviderChange(newVal) {
            this.modelOptions  = this.availableModels[newVal] || [];
            this.selectedModel = this.modelOptions[0]?.value || "";
            this.apiKey        = "";
        },

        fetchSettings() {
            this.loading = true;
            this.$get("settings/ai-integration")
                .then((response) => {
                    this.isEnabled      = response.enabled !== 'no';
                    this.provider       = response.provider || "openai";
                    this.apiKey         = response.api_key  || "";
                    this.availableModels = response.available_models || {};
                    this.modelOptions   = this.availableModels[this.provider] || [];
                    this.selectedModel  = response.model || (this.modelOptions[0] ? this.modelOptions[0].value : "");
                    this.loading = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.loading = false;
                });
        },

        saveSettings() {
            this.saving = true;
            this.$post("settings/ai-integration", {
                enabled:  this.isEnabled ? 'yes' : 'no',
                provider: this.provider,
                api_key:  this.apiKey,
                model:    this.selectedModel,
            })
                .then((response) => {
                    this.$notify({
                        message:  response.message,
                        type:     "success",
                        position: "bottom-right",
                    });
                    this.saving = false;
                    this.appVars.open_ai_integration = this.isEnabled && !!this.apiKey;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.saving = false;
                });
        },

    },
    mounted() {
        if (this.has_pro) {
            this.fetchSettings();
        }
        this.$setTitle("AI Model Setup");
    },
};
</script>
