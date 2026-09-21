<template>
    <tr class="fs_rich_filter_item">
        <td class="fs_action_filter_field">
            {{ ucFirst(itemConfig.provider) }} <span class="fs_provider_separator">/</span> {{ itemConfig.label }}
        </td>
        <td class="fs_filter_operator fs_action_filter_field">
            <el-select class="fs_select_field" :placeholder="$t('Select Operator')" @visible-change="maybeOperatorSelected"
                       v-model="item.operator">
                <el-option v-for="(optionLabel,option) in operatorOptions" :key="option" :value="option"
                           :label="optionLabel"></el-option>
            </el-select>
        </td>
        <td class="fs_filter_value fs_action_filter_value">
            <el-input class="fs_text_input" v-if="!itemConfig.type || itemConfig.type === 'text'" :placeholder="$t('Condition Value')"
                      type="text" v-model="item.value"/>
            <template v-if="itemConfig.type === 'selections'">
                <template v-if="itemConfig.component === 'options_selector'">
                    <option-selector v-model="item.value" :field="{
                        is_multiple: itemConfig.is_multiple,
                        size: 'mini',
                        option_key: itemConfig.option_key
                    }"></option-selector>
                </template>
                <template v-else-if="itemConfig.options">
                    <el-select class="fs_select_field" :multiple="itemConfig.is_multiple" :placeholder="$t('Select Option')"
                               v-model="item.value">
                        <el-option v-for="(optionLabel,option) in itemConfig.options" :key="option" :value="option"
                                   :label="optionLabel"></el-option>
                    </el-select>
                </template>
                <pre v-else>{{ itemConfig }}</pre>
            </template>
            <template v-else-if="itemConfig.type === 'dates'">
                <el-input class="fs_text_input" v-if="item.operator === 'days_before' || item.operator === 'days_within'"
                          type="number" :placeholder="$t('Days')" v-model="item.value"/>
                <el-date-picker value-format="YYYY-MM-DD" v-else-if="item.operator === 'date_range'"
                    v-model="item.value"
                    type="daterange"
                    :range-separator="$t('To')"
                    :start-placeholder="$t('Start')"
                    :end-placeholder="$t('End')"
                    :unlink-panels=true
                >
                </el-date-picker>
                <el-date-picker v-else value-format="YYYY-MM-DD"
                                v-model="item.value"></el-date-picker>

            </template>
        </td>
        <td>
            <div class="fs_delete_condition_btn" @click="removeItem()" type="danger" plain>
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.25 3H15V4.5H13.5V14.25C13.5 14.4489 13.421 14.6397 13.2803 14.7803C13.1397 14.921 12.9489 15 12.75 15H2.25C2.05109 15 1.86032 14.921 1.71967 14.7803C1.57902 14.6397 1.5 14.4489 1.5 14.25V4.5H0V3H3.75V0.75C3.75 0.551088 3.82902 0.360322 3.96967 0.21967C4.11032 0.0790176 4.30109 0 4.5 0H10.5C10.6989 0 10.8897 0.0790176 11.0303 0.21967C11.171 0.360322 11.25 0.551088 11.25 0.75V3ZM12 4.5H3V13.5H12V4.5ZM5.25 6.75H6.75V11.25H5.25V6.75ZM8.25 6.75H9.75V11.25H8.25V6.75ZM5.25 1.5V3H9.75V1.5H5.25Z" fill="#525866"/>
                </svg>
            </div>
        </td>
    </tr>
</template>

<script>
import isArray from 'lodash/isArray';
import OptionSelector from '../../../../Pieces/FormElements/_OptionSelector';

export default {
    name: "RichFilterItem",
    props: ['item', 'filterLabels'],
    components: {
        OptionSelector
    },
    data() {
        return {}
    },
    computed: {
        operatorOptions() {
            const type = this.itemConfig.type;
            if (!type || type === 'text') {
                if (this.itemConfig.provider === 'tickets') {
                    return {
                        contains: 'includes',
                        not_contains: 'does not includes'
                    }
                }
                return {
                    '=': 'equal',
                    '!=': 'does not equal',
                    contains: 'includes',
                    not_contains: 'does not includes'
                }
            }
            if (type === 'selections') {

                if(this.itemConfig.is_singular_value) {
                    return {
                        in: 'includes in',
                        not_in: 'not includes in'
                    };
                }

                if (this.itemConfig.is_multiple) {
                    return {
                        in: 'includes in any of',
                        not_in: 'not includes in any',
                        in_all: 'includes all of',
                        not_in_all: 'includes none of'
                    };
                }

                if (this.itemConfig.option_key === 'waiting_for_reply') {
                    return {
                        '=': 'equal',
                        '!=': 'does not equal',
                    }
                }

                return {
                    in: 'includes in',
                    not_in: 'not includes in'
                };
            }
            if (type === 'dates') {
                return {
                    before: 'before',
                    after: 'after',
                    date_equal: 'in the date',
                    days_before: 'before days',
                    days_within: 'within days',
                    date_range: 'within dates'
                }
            }
            return {}
        },
        itemConfig() {
            const key = this.item.source.join('-');
            return this.filterLabels[key] || {}
        }
    },
    methods: {
        closingSource(status) {
            if (!status) {
                setTimeout(() => {
                    jQuery(this.$el).find('.fs_filter_operator .el-select').trigger('click');
                }, 300);
            }
        },
        maybeOperatorSelected(status) {
            if (!status && this.item.operator) {
                if (this.itemConfig.type == 'dates') {
                    this.item.value = '';
                }
                setTimeout(() => {
                    jQuery(this.$el).find('.fs_filter_value input').focus();
                }, 200);
            }
        },
        removeItem() {
            this.$emit('removeItem');
        }
    },
    mounted() {
        if (this.itemConfig.is_multiple && !isArray(this.item.value)) {
            this.item.value = [];
        }
        if (!this.item.operator) {
            const objectValues = Object.keys(this.operatorOptions);
            if (objectValues.length) {
                this.item.operator = objectValues[0];
                jQuery(this.$el).find('.fs_filter_operator .el-select').trigger('click');
            }
        }
    }
}
</script>

<style scoped>

</style>
