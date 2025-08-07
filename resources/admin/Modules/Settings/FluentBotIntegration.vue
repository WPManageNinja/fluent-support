<template>
    <div class="fs_bot_container">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h2>{{ $t('Fluent Bot Integration') }}</h2>
                </div>
                <div class="fs_box_actions fs_toggle_container">
                    <span class="fs_toggle_label">{{ $t('Disabled') }}</span>
                    <el-switch
                        @change="saveConfiguration"
                        v-model="isEnabled"
                        active-value="true"
                        inactive-value="false"
                    />
                    <span class="fs_toggle_label">
                        {{ $t('Enabled') }}
                    </span>
                </div>
            </div>

            <div v-if="!fetching" class="fs_box_body">
                <!-- General Bot Configuration -->
                <div class="fs_section">
                    <h3>{{ $t('General Bot') }}</h3>
                    <p class="fs_section_description">
                        {{ $t('This bot will handle general queries for all products') }}
                    </p>

                    <div class="fs_bot_config_card">
                        <div class="fs_input_row">
                            <label>{{ $t('API Key') }} <span style="color: #999; font-size: 12px;">({{ $t('Optional') }})</span></label>
                            <el-input
                                v-model="config.generalApiKey"
                                show-password
                                :placeholder="$t('Enter API Key (Optional)')"
                            />
                        </div>
                        <div class="fs_input_row">
                            <label>{{ $t('Bot ID') }} <span style="color: #e74c3c;">*</span></label>
                            <el-input
                                v-model="config.generalBotId"
                                show-password
                                :placeholder="$t('Enter General Bot ID (Required)')"
                            />
                        </div>
                    </div>
                </div>

                <!-- Product-specific Configuration -->
                <div class="fs_section">
                    <h3>{{ $t('Product-Specific Bots') }}</h3>
                    <p class="fs_section_description">
                        {{ $t('Configure specialized bots trained for specific products') }}
                    </p>

                    <div class="fs_product_mappings">
                        <div class="fs_card_list">
                            <el-card
                                v-for="(mapping, index) in config.productMappings"
                                :key="index"
                                class="fs_mapping_card"
                                shadow="hover"
                            >
                                <div class="fs_product_header">
                                    <span class="fs_product_name">
                                        {{ mapping.productTitle }}
                                    </span>
                                    <el-button
                                        type="danger"
                                        circle
                                        size="small"
                                        @click="removeMapping(index)"
                                        class="fs_delete_btn"
                                    >
                                        <el-icon><Delete /></el-icon>
                                    </el-button>
                                </div>

                                <div class="fs_input_row">
                                    <label>{{ $t('API Key') }} <span style="color: #999; font-size: 12px;">({{ $t('Optional') }})</span></label>
                                    <el-input
                                        v-model="mapping.apiKey"
                                        show-password
                                        :placeholder="$t('Enter API Key (Optional)')"
                                    />
                                </div>

                                <div class="fs_input_row">
                                    <label>{{ $t('Bot ID') }} <span style="color: #e74c3c;">*</span></label>
                                    <el-input
                                        v-model="mapping.botId"
                                        show-password
                                        :placeholder="$t('Enter Bot ID (Required)')"
                                    />
                                </div>
                            </el-card>
                        </div>
                        <div class="fs_add_product_section">
                            <el-select
                                v-model="selectedProduct"
                                :placeholder="$t('Select a product')"
                                class="fs_product_select"
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
                                type="success"
                                @click="addProductMapping"
                                :disabled="!selectedProduct"
                            >
                                {{ $t('+ Add Product Bot') }}
                            </el-button>
                        </div>
                    </div>

                </div>

                <div class="fs_actions">
                    <el-button type="success" @click="saveConfiguration">
                        {{ $t('Save Configuration') }}
                    </el-button>
                </div>
            </div>

            <div style="padding: 20px; background: white;" class="fs_box_body fs_narrow_promo" v-else>
                <el-skeleton :rows="5" animated/>
            </div>
        </div>
    </div>
</template>

<script>
import { Delete } from '@element-plus/icons-vue';

export default {
    name: 'FluentBotIntegration',
    components: { Delete },
    data() {
        return {
            isEnabled: 'false',
            isLoading: false,
            isSaving: false,
            fetching: true,
            config: {
                generalApiKey: '',
                generalBotId: '',
                productMappings: []
            },
            allProducts: [],
            selectedProduct: null,
            originalConfig: null,
            isWatcherReady: false
        };
    },
    computed: {
        availableProducts() {
            const mappedIds = this.config.productMappings.map(m => m.productId);
            return this.allProducts.filter(p => !mappedIds.includes(p.id));
        }
    },
    methods: {
        fetchData() {
            this.isLoading = true;

            this.$get("settings/fluent-bot-integration")
                .then(data => {
                    this.config = {
                        generalApiKey: data.generalApiKey || '',
                        generalBotId: data.generalBotId || '',
                        productMappings: data.productMappings || []
                    };
                    this.allProducts = data.products || [];
                    this.isEnabled = data.isEnabled || 'false';
                    this.originalConfig = JSON.parse(JSON.stringify(this.config));
                    this.isLoading = false;
                    this.fetching = false;
                })
                .catch((errors) => {
                    this.isLoading = false;
                    this.$handleError(errors)
                });
        },

        saveConfiguration() {
            this.isSaving = true;

            this.$post("settings/fluent-bot-integration", {
                ...this.config,
                isEnabled: this.isEnabled
            })
            .then((response) => {
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
                apiKey: '',
                botId: ''
            });
            this.selectedProduct = null;
        },

        removeMapping(index) {
            this.config.productMappings.splice(index, 1);
        },
    },

    mounted() {
        this.fetchData();
    }
};
</script>




