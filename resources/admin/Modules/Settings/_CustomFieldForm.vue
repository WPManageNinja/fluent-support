<template>
    <el-form class="fs_custom_field_form" label-position="top" :data="item">
        <el-form-item :label="$t('Field Type')" class="fs_form_item">
            <el-select @change="changeFieldType()" :placeholder="$t('Select Field Type')" v-model="item.field_key" class="fs_select_field">
                <el-option
                    v-for="(fieldType, fieldKey) in field_types"
                    :key="fieldKey"
                    :label="fieldType.label"
                    :value="fieldKey"
                ></el-option>
            </el-select>
        </el-form-item>
        <template v-if="item.type">
            <el-row :gutter="30">
                <el-col :sm="12" :xs="24">
                    <el-form-item :label="$t('Public Label')" class="fs_form_item">
                        <el-input @keyup="maybeSetSlug()" :placeholder="$t('Custom Field Public Label')"
                                  v-model="item.label" class="fs_text_input"></el-input>
                    </el-form-item>
                </el-col>
                <el-col :sm="12" :xs="24">
                    <el-form-item :label="$t('Admin Label (Optional)')" class="fs_form_item">
                        <el-input @keyup="maybeSetSlug()" :placeholder="$t('Custom Field Admin Label')"
                                  v-model="item.admin_label" class="fs_text_input"></el-input>
                    </el-form-item>
                </el-col>
            </el-row>
            <el-row :gutter="30">
                <el-col :sm="12" :xs="24">
                    <el-form-item :label="$t('Slug (Optional)')" class="fs_form_item">
                        <el-input maxlength="20" :placeholder="$t('Custom Field Slug')" :disabled="form_type == 'update'"
                                  v-model="item.slug" class="fs_text_input">
                            <template v-if="form_type == 'new'" #prepend>cf_</template>
                        </el-input>
                        <p v-if="form_type == 'new'">{{ $t('You can not change the slug once save a custom field') }}</p>
                    </el-form-item>
                </el-col>
                <el-col :sm="12" :xs="24">
                    <el-form-item :label="$t('Placeholder')" class="fs_form_item">
                        <el-input :placeholder="$t('Field Placeholder')" v-model="item.placeholder" class="fs_text_input"/>
                    </el-form-item>
                </el-col>
            </el-row>

            <el-form-item v-if="hasOptions(item.type)" :label="$t('Field Value Options')" class="fs_form_item">
                <ul class="fs_custom_field_option_lists">
                    <li v-for="(optionName, optionIndex) in item.options" :key="optionIndex">
                        <el-input :placeholder="$t('Option Value')" v-model="item.options[optionIndex]" type="text" class="fs_text_input">
                            <template #suffix>
                                <el-icon v-if="item.options.length > 1" @click="removeOptionItem(optionIndex)" class="fs_clickable el-icon-close el-input__icon"><Delete /></el-icon>
                            </template>
                        </el-input>
                    </li>
                </ul>
                <el-input
                    class="input-new-tag fs_text_input"
                    v-if="optionInputVisible"
                    v-model="optionInputValue"
                    ref="saveTagInput"
                    :placeholder="$t('type and press enter')"
                    @keyup.enter="handleOptionInputConfirm"
                    @blur="handleOptionInputConfirm"
                >
                </el-input>
                <el-button v-else class="button-new-tag fs_filled_btn fs_ml_10" @click="showOptionInput">
                    + {{ $t('New Option') }}
                </el-button>
            </el-form-item>

            <el-row :gutter="30">
                <el-col :sm="12" :xs="24">
                    <el-form-item class="fs_form_item">
                        <el-checkbox true-label="yes" false-label="no" v-model="item.admin_only">
                            {{ $t('This is an agent only field') }}
                        </el-checkbox>
                    </el-form-item>
                </el-col>
                <el-col :sm="12" :xs="24">
                    <el-form-item class="fs_form_item">
                        <el-checkbox @change="initConditions(item)" v-model="item.has_logics" true-label="yes"
                                     false-label="no">{{ $t('Enable Conditional Logics') }}
                        </el-checkbox>
                    </el-form-item>
                </el-col>
                <el-col :sm="12" :xs="24">
                    <el-form-item class="fs_form_item">
                        <el-checkbox @change="initConditions(item)" v-model="item.required" true-label="yes"
                                     false-label="no">{{ $t('Required') }}
                        </el-checkbox>
                    </el-form-item>
                </el-col>
            </el-row>

            <template v-if="item.has_logics == 'yes' && item.conditions">
                <table style="margin-bottom: 20px;" class="fs_table fs_stripe">
                    <thead>
                    <tr>
                        <th>{{ $t('Field') }}</th>
                        <th>{{ $t('Operator') }}</th>
                        <th>{{ $t('Match Value') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(condition,conditionIndex) in item.conditions" :key="conditionIndex">
                        <td>
                            <el-select filterable v-model="condition.item_key" :placeholder="$t('Select Field')" class="fs_select_field">
                                <el-option
                                    v-for="field in keyedFields"
                                    :key="field.slug" :value="field.slug"
                                    :disabled="field.slug == item.slug"
                                    :label="field.admin_label || field.label"></el-option>
                            </el-select>
                        </td>
                        <td>
                            <el-select v-if="condition.item_key && keyedFields[condition.item_key]" filterable
                                       v-model="condition.operator"
                                       :placeholder="$t('Select Field')" class="fs_select_field">
                                <el-option :label="$t('Equal')" value="="/>
                                <el-option :label="$t('Not Equal')" value="!="/>
                                <template v-if="['number', 'date'].indexOf(keyedFields[condition.item_key].type) !== -1">
                                    <el-option :label="$t('Less than')" value="lt"/>
                                    <el-option :label="$t('Greater Than')" value="gt"/>
                                </template>
                                <template v-else-if="!keyedFields[condition.item_key].options">
                                    <el-option :label="$t('Contains')" value="contains"/>
                                    <el-option :label="$t('Not Contains')" value="not_contains"/>
                                </template>
                            </el-select>
                        </td>
                        <td>
                            <template
                                v-if="condition.operator && condition.item_key && keyedFields[condition.item_key]">
                                <el-select v-if="keyedFields[condition.item_key].options" v-model="condition.value"
                                           :placeholder="$t('Select Value')" class="fs_select_field">
                                    <el-option v-for="option in keyedFields[condition.item_key].options" :key="option"
                                               :label="option" :value="option"></el-option>
                                </el-select>
                                <el-input v-else
                                          :type="(keyedFields[condition.item_key].type == 'number') ? 'number' : 'text'"
                                          v-model="condition.value" :placeholder="$t('Type Compare Value')" class="fs_text_input"/>
                            </template>
                        </td>
                        <td class="fs_flex fs_action_td">
                            <el-button @click="addCondition(conditionIndex)" type="success"
                                       icon="Plus" class="fs_outline_btn"/>
                            <el-button @click="removeCondition(conditionIndex)" :disabled="item.conditions.length == 1"
                                       icon="Delete" class="fs_outline_btn"/>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <el-form-item :label="$t('Condition Match Type')">
                    <el-radio-group v-model="item.match_type">
                        <el-radio value="all">{{ $t('Match all conditions') }}</el-radio>
                        <el-radio value="any">{{ $t('Match any conditions') }}</el-radio>
                    </el-radio-group>
                </el-form-item>
            </template>
        </template>
    </el-form>
</template>

<script type="text/babel">
import each from 'lodash/each';

export default {
    name: 'FieldForm',
    props: ['field_types', 'item', 'form_type', 'fields'],
    data() {
        return {
            optionInputVisible: false,
            optionInputValue: ''
        };
    },
    computed: {
        keyedFields() {
            const supportProducts = [];
            each(this.appVars.support_products, (product) => {
                supportProducts.push(product.title);
            });
            const formattedFields = {
                ticket_title: {
                    label: 'Ticket Title',
                    slug: 'ticket_title',
                    type: 'text'
                },
                ticket_content: {
                    label: 'Ticket Content',
                    slug: 'ticket_content',
                    type: 'text'
                },
                ticket_client_priority: {
                    label: 'Ticket Client Priority',
                    slug: 'ticket_client_priority',
                    type: 'select-one',
                    options: Object.keys(this.appVars.client_priorities)
                },
                ticket_product_id: {
                    label: 'Selected Product or Service',
                    slug: 'ticket_product_id',
                    type: 'select-one',
                    options: supportProducts
                }
            };
            each(this.fields, (field) => {
                formattedFields[field.slug] = field;
            });

            return formattedFields;
        }
    },
    methods: {
        maybeSetSlug() {
            if (this.form_type != 'new') {
                return false;
            }
            const slug = this.item.label.toLowerCase().replace(/đ/gi, 'd').replace(/\s*$/g, '').replace(/\s+/g, '_').substring(0, 25);
            this.item['slug'] = slug;
        },
        changeFieldType() {
            const selectedType = this.item.field_key;
            const field = this.field_types[selectedType];

            this.item.type = field.type;
            this.item.label = '';

            if (this.hasOptions(field.type)) {
                if (!this.item.options) {
                    this.item.options = [
                        'Value Option 1'
                    ];
                }
            } else {
                delete this.item.options;
            }
        },
        hasOptions(type) {
            const optionTypeFields = ['select-one', 'select-multi', 'radio', 'checkbox'];
            return optionTypeFields.indexOf(type) !== -1;
        },
        showOptionInput() {
            this.optionInputVisible = true;
            this.$nextTick(() => {
                this.$refs.saveTagInput.focus();
            });
        },
        handleOptionInputConfirm() {
            const inputValue = this.optionInputValue;
            if (inputValue) {
                this.item.options.push(inputValue);
            }
            this.optionInputVisible = false;
            this.optionInputValue = '';
        },
        removeOptionItem(fieldIndex) {
            this.item.options.splice(fieldIndex, 1);
        },
        initConditions(item) {
            if (!item.conditions || !item.conditions.length) {
                item.conditions = [
                    {
                        item_key: '',
                        operator: '=',
                        value: ''
                    }
                ];
            }

            if (!item.match_type) {
                item.match_type = 'all';
            }
        },
        addCondition(conditionIndex) {
            this.item.conditions.splice(conditionIndex + 1, 0, {
                item_key: '',
                operator: '=',
                value: ''
            });
        },
        removeCondition(conditionIndex) {
            this.item.conditions.splice(conditionIndex, 1);
        }
    }
}
</script>
