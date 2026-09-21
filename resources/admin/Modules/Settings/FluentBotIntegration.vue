<template>
    <div class="fs_bot_container">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t('FluentBot Integration') }}</h3>
                </div>
                <div class="fs_box_actions fs_toggle_container">
                    <span class="fs_toggle_label">{{ $t('Disabled') }}</span>
                    <el-switch
                        @change="saveToggle"
                        v-model="isEnabled"
                    />
                    <span class="fs_toggle_label">
                        {{ $t('Enabled') }}
                    </span>
                </div>
            </div>

            <div v-if="!fetching" class="fs_box_body">
                <el-tabs v-model="activeTab" class="fs_bot_tabs">
                    <el-tab-pane :label="$t('Bot Configuration')" name="configuration">
                        <div class="fs_padded_20 fs_business_settings_wrapper">
                            <div class="fs_section">
                                <div class="fs_section_head">
                                    <div>
                                        <h3>{{ $t('API Key') }}</h3>
                                        <p class="fs_section_description">
                                            {{ $t('Team API key from your FluentBot dashboard (Settings → API Keys). Required to connect.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="fs_bot_config_card">
                                    <el-form ref="apiKeyForm" :model="config" :rules="apiKeyRules" label-position="top">
                                        <el-form-item class="fs_form_item" :label="$t('API Key')" prop="generalApiKey">
                                            <el-input
                                                class="fs_text_input"
                                                v-model="config.generalApiKey"
                                                type="password"
                                                show-password
                                                :placeholder="hasApiKey ? $t('API key saved — leave blank to keep it') : $t('Paste your FluentBot API key (fbt_...)')"
                                            />
                                            <span v-if="hasApiKey" class="fs_section_description">{{ $t('A key is already saved. Enter a new one only to replace it.') }}</span>
                                        </el-form-item>
                                    </el-form>
                                </div>
                            </div>

                            <div class="fs_section">
                                <div class="fs_section_head">
                                    <div>
                                        <h3>{{ $t('General Bot') }}</h3>
                                        <p class="fs_section_description">
                                            {{ $t('This bot will handle general queries for all products') }}
                                        </p>
                                    </div>
                                    <el-switch
                                        v-model="config.generalBotEnabled"
                                        :aria-label="$t('Enable general bot')"
                                    />
                                </div>

                                <div class="fs_bot_config_card" v-if="config.generalBotEnabled">
                                    <el-form ref="generalBotForm" :model="config" :rules="generalBotRules" label-position="top">
                                        <el-form-item class="fs_form_item" :label="$t('Bot ID')" prop="generalBotId">
                                            <el-input
                                                class="fs_text_input"
                                                v-model="config.generalBotId"
                                                :placeholder="$t('Enter General Bot ID (Required)')"
                                            />
                                        </el-form-item>
                                    </el-form>
                                </div>
                            </div>

                            <div class="fs_section">
                                <h3>{{ $t('Product-Specific Bots') }}</h3>
                                <p class="fs_section_description">
                                    {{ $t('Configure specialized bots trained for specific products') }}
                                </p>

                                <div class="fs_product_mappings">
                                    <div class="fs_card_list">
                                        <div
                                            v-for="(mapping, index) in config.productMappings"
                                            :key="index"
                                            class="fs_mapping_card"
                                        >
                                            <div class="fs_product_header">
                                                <span class="fs_product_name">
                                                    {{ mapping.productTitle }}
                                                </span>
                                                <el-button
                                                    class="fs_stroke_btn fs_delete_btn"
                                                    circle
                                                    size="small"
                                                    @click="removeMapping(index)"
                                                >
                                                    <el-icon><Delete /></el-icon>
                                                </el-button>
                                            </div>

                                            <el-form :ref="'mappingForm_' + index" :model="mapping" :rules="mappingBotRules" label-position="top" class="fs_mapping_form">
                                                <el-form-item class="fs_form_item" :label="$t('Bot ID')" prop="botId">
                                                    <el-input
                                                        class="fs_text_input"
                                                        v-model="mapping.botId"
                                                        :placeholder="$t('Enter Bot ID (Required)')"
                                                    />
                                                </el-form-item>
                                            </el-form>
                                        </div>
                                    </div>
                                    <div class="fs_add_product_section">
                                        <el-select
                                            v-model="selectedProduct"
                                            :placeholder="$t('Select a product')"
                                            class="fs_product_select fs_select_field"
                                            filterable
                                            clearable
                                        >
                                            <el-option
                                                v-for="product in availableProducts"
                                                :key="product.id"
                                                :label="product.title"
                                                :value="product.id"
                                            />
                                        </el-select>
                                        <el-button
                                            class="fs_subtle_btn"
                                            @click="addProductMapping"
                                            :disabled="!selectedProduct"
                                        >
                                            {{ $t('+ Add Product Bot') }}
                                        </el-button>
                                    </div>
                                </div>
                            </div>

                            <div class="fs_save_settings_container">
                                <el-button class="fs_filled_btn" @click="saveConfiguration" :loading="isSaving">
                                    {{ $t('Save Configuration') }}
                                </el-button>
                            </div>
                        </div>
                    </el-tab-pane>

                    <el-tab-pane :label="$t('Prompt Options')" name="prompts">
                        <div class="fs_padded_20 fs_business_settings_wrapper">
                            <div class="fs_section">
                                <h3>{{ $t('Prompt Options') }}</h3>
                                <p class="fs_section_description">
                                    {{ $t('Customize the quick prompt options shown in the FluentBot chat panel') }}
                                </p>

                                <div class="fs_presets_list">
                                    <draggable
                                        v-model="presets"
                                        item-key="text"
                                        handle=".fs_drag_handle"
                                        :animation="250"
                                        ghost-class="fs_preset_ghost"
                                        chosen-class="fs_preset_chosen"
                                        :force-fallback="true"
                                        fallback-class="fs_preset_fallback"
                                        @end="updatePositions"
                                    >
                                        <template #item="{ element, index }">
                                            <div class="fs_preset_item">
                                                <div class="fs_drag_handle" aria-hidden="true">
                                                    <i class="el-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M6 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm5-9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg></i>
                                                </div>
                                                <div class="fs_preset_fields">
                                                    <el-input
                                                        v-model="element.label"
                                                        :placeholder="$t('Label')"
                                                        size="small"
                                                    />
                                                    <el-input
                                                        v-model="element.description"
                                                        type="textarea"
                                                        :rows="2"
                                                        :placeholder="$t('Prompt description (sent to AI)')"
                                                        size="small"
                                                    />
                                                </div>
                                                <el-button
                                                    class="fs_stroke_btn fs_delete_btn"
                                                    circle
                                                    size="small"
                                                    :aria-label="$t('Remove prompt option')"
                                                    @click="removePreset(index)"
                                                >
                                                    <el-icon><Delete /></el-icon>
                                                </el-button>
                                            </div>
                                        </template>
                                    </draggable>

                                    <div class="fs_preset_actions">
                                        <el-button class="fs_subtle_btn" @click="addPreset">
                                            {{ $t('+ Add Prompt Option') }}
                                        </el-button>
                                        <el-button class="fs_subtle_btn" @click="resetToDefaults">
                                            {{ $t('Reset to Defaults') }}
                                        </el-button>
                                        <el-button class="fs_filled_btn" @click="savePresets" :loading="isSavingPresets">
                                            {{ $t('Save Prompt Options') }}
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </div>

            <div class="fs_box_body fs_skeleton_loader fs_padded_20" v-else>
                <el-skeleton :rows="5" animated/>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import draggable from 'vuedraggable';
import { ElMessageBox } from 'element-plus';

export default {
    name: 'FluentBotIntegration',
    components: { draggable },
    data() {
        return {
            activeTab: 'configuration',
            isEnabled: false,
            isLoading: false,
            isSaving: false,
            isSavingPresets: false,
            fetching: true,
            config: {
                generalBotId: '',
                generalApiKey: '',
                generalBotEnabled: true,
                productMappings: []
            },
            allProducts: [],
            hasApiKey: false,
            selectedProduct: null,
            originalConfig: null,
            isWatcherReady: false,
            presets: []
        };
    },
    computed: {
        availableProducts() {
            const mappedIds = this.config.productMappings.map(m => m.productId);
            return this.allProducts.filter(p => !mappedIds.includes(p.id));
        },
        generalBotRules() {
            // Only require Bot ID when the general bot is enabled; disabled → no validation.
            if (!this.config.generalBotEnabled) {
                return {};
            }
            return {
                generalBotId: [{ required: true, message: this.$t('Bot ID is required'), trigger: 'blur' }]
            };
        },
        mappingBotRules() {
            return {
                botId: [{ required: true, message: this.$t('Bot ID is required'), trigger: 'blur' }]
            };
        },
        apiKeyRules() {
            // Write-only: a key is only required when none is stored yet. If one
            // exists, blank is allowed (the stored key is kept server-side).
            if (this.hasApiKey) {
                return {};
            }
            return {
                generalApiKey: [{ required: true, message: this.$t('API key is required'), trigger: 'blur' }]
            };
        }
    },
    methods: {
        fetchData() {
            this.isLoading = true;

            this.$get("settings/fluent-bot-integration")
                .then(data => {
                    this.config = {
                        generalBotId: data.generalBotId || '',
                        // Write-only: the raw key is never returned. Keep the input
                        // blank; hasApiKey drives the "saved" hint + validation.
                        generalApiKey: '',
                        // Default true for backward compatibility with configs saved before this flag existed.
                        generalBotEnabled: data.generalBotEnabled !== false,
                        productMappings: data.productMappings || []
                    };
                    this.hasApiKey = data.hasApiKey === true;
                    this.allProducts = data.products || [];
                    this.isEnabled = data.isEnabled === true || data.isEnabled === 'true';
                    this.originalConfig = JSON.parse(JSON.stringify(this.config));
                    this.isLoading = false;
                    this.fetching = false;
                })
                .catch((errors) => {
                    this.isLoading = false;
                    this.$handleError(errors)
                });
        },

        saveToggle() {
            // Turning ON must validate first (revert on fail); OFF saves as-is.
            if (this.isEnabled) {
                this.validateAllForms()
                    .then(() => this.submitConfiguration())
                    .catch(() => { this.isEnabled = false; });
                return;
            }
            this.submitConfiguration();
        },

        saveConfiguration() {
            this.validateAllForms().then(() => {
                this.submitConfiguration();
            }).catch(() => {});
        },

        validateAllForms() {
            const forms = [];

            // API key is always required — the FluentBot API rejects unauthenticated calls.
            if (this.$refs.apiKeyForm) {
                forms.push(this.$refs.apiKeyForm.validate());
            }

            // generalBotForm only renders when generalBotEnabled is true (v-if).
            // When disabled, skip — nothing to validate for general bot section.
            if (this.config.generalBotEnabled) {
                if (this.$refs.generalBotForm) {
                    forms.push(this.$refs.generalBotForm.validate());
                } else {
                    return Promise.reject();
                }
            }

            this.config.productMappings.forEach((_, index) => {
                const ref = this.$refs['mappingForm_' + index];
                const form = Array.isArray(ref) ? ref[0] : ref;
                if (form) {
                    forms.push(form.validate());
                }
            });

            return Promise.all(forms);
        },

        submitConfiguration() {
            this.isSaving = true;

            this.$post("settings/fluent-bot-integration", {
                ...this.config,
                isEnabled: this.isEnabled
            })
            .then((response) => {
                // Write-only: clear the input and track that a key is now stored.
                if (response.data && response.data.hasApiKey === true) {
                    this.hasApiKey = true;
                }
                this.config.generalApiKey = '';
                this.originalConfig = JSON.parse(JSON.stringify(this.config));
                this.isSaving = false;
                this.$notify({
                    message: response.message,
                    type: 'success',
                    position: 'bottom-right'
                });
            })
            .catch((errors) => {
                this.isSaving = false;
                this.$handleError(errors);
            });
        },

        addProductMapping() {
            if (!this.selectedProduct) return;

            const product = this.allProducts.find(p => p.id === this.selectedProduct);
            this.config.productMappings.push({
                productId: product.id,
                productTitle: product.title,
                botId: ''
            });
            this.selectedProduct = null;
        },

        removeMapping(index) {
            this.config.productMappings.splice(index, 1);
        },

        fetchPresets() {
            this.$get("settings/fluent-bot-presets")
                .then(data => {
                    this.presets = (data.presets || []).map((p, i) => ({
                        ...p,
                        position: p.position ?? i
                    }));
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },

        addPreset() {
            this.presets.push({
                label: '',
                text: 'preset_' + Date.now(),
                description: '',
                position: this.presets.length
            });
        },

        removePreset(index) {
            this.presets.splice(index, 1);
            this.updatePositions();
        },

        updatePositions() {
            this.presets.forEach((p, i) => {
                p.position = i;
            });
        },

        savePresets() {
            this.isSavingPresets = true;
            this.$post("settings/fluent-bot-presets", {
                presets: this.presets
            })
            .then((response) => {
                this.presets = response.presets || this.presets;
                this.$notify({
                    message: response.message,
                    type: 'success',
                    position: 'bottom-right'
                });
            })
            .catch((errors) => {
                this.$handleError(errors);
            })
            .always(() => {
                this.isSavingPresets = false;
            });
        },

        resetToDefaults() {
            ElMessageBox.confirm(
                this.$t('This will remove all custom prompt options and restore the original defaults. This action cannot be undone.'),
                this.$t('Reset to Defaults'),
                {
                    confirmButtonText: this.$t('Reset'),
                    cancelButtonText: this.$t('Cancel'),
                    type: 'warning',
                }
            ).then(() => {
                this.isSavingPresets = true;
                this.$post("settings/fluent-bot-presets", { presets: [] })
                    .then(() => {
                        this.fetchPresets();
                        this.$notify({
                            message: this.$t('Reset to defaults'),
                            type: 'success',
                            position: 'bottom-right'
                        });
                    })
                    .catch((errors) => {
                        this.$handleError(errors);
                    })
                    .always(() => {
                        this.isSavingPresets = false;
                    });
            }).catch(() => {});
        },
    },

    mounted() {
        this.fetchData();
        this.fetchPresets();
        this.$setTitle('FluentBot Integration');
    }
};
</script>
