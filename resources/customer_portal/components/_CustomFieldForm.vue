<template>
    <div class="fs_custom_fields_wrap">
        <div v-if="appReady" class="fs_custom_fields fs_tk_row">
            <template v-for="(field, fieldName) in computedFields" :key="fieldName">
                <div v-if="field.is_renderable" class="fs_tk_col">
                    <el-form-item class="fs_input_label" :label="field.label" :required="field.required=='yes'">
                        <!-- Labels are admin authored and can mix RTL and LTR text, so let the
                             browser resolve direction from the string itself. -->
                        <template #label><span dir="auto">{{ field.label }}</span></template>
                        <el-input
                            v-if="field.type == 'text' || field.type == 'number' || field.type == 'textarea'"
                            :type="field.type"
                            dir="auto"
                            :placeholder="field.placeholder"
                            v-model="custom_data[field.slug]"
                            :class="{ 'error': errors.get('custom_data.'+field.slug) }"
                        />
                        <el-date-picker
                            v-else-if="field.type == 'date-range'"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            range-separator="-"
                            start-placeholder="Start date"
                            end-placeholder="End date"
                            v-model="custom_data[field.slug]"
                            style="width: 100%"
                            popper-class="fs_portal_daterange_popper"
                            :class="{ 'error': errors.get('custom_data.'+field.slug) }"
                        />
                        <el-select :placeholder="field.placeholder" clearable filterable
                                   v-else-if="field.type == 'select-one'"
                                   :class="{ 'error': errors.get('custom_data.'+field.slug) }"
                                   v-model="custom_data[field.slug]">
                            <el-option v-for="option in field.options" :key="option"
                                       :value="option" :label="option"><span dir="auto">{{ option }}</span></el-option>
                        </el-select>
                        <el-select :placeholder="field.placeholder" clearable filterable
                                   v-else-if="field.type == 'select'"
                                   :filterable="field.filterable"
                                   :multiple="field.multiple"
                                   :class="{ 'error': errors.get('custom_data.'+field.slug) }"
                                   v-model="custom_data[field.slug]">
                            <el-option v-for="option in field.options" :key="option.id"
                                       :value="option.id" :label="option.title"><span dir="auto">{{ option.title }}</span></el-option>
                        </el-select>
                        <el-radio-group v-else-if="field.type == 'radio'" v-model="custom_data[field.slug]">
                            <el-radio v-for="option in field.options" :key="option"
                                      :value="option"><span dir="auto">{{ option }}</span></el-radio>
                        </el-radio-group>
                        <el-checkbox-group v-else-if="field.type == 'checkbox'" v-model="custom_data[field.slug]">
                            <el-checkbox v-for="option in field.options" :key="option"
                                         :value="option" :label="option"><span dir="auto">{{ option }}</span></el-checkbox>
                        </el-checkbox-group>
                        <!--                        <error :error="errors.get('custom_data.'+field.slug)"/>-->

                        <div v-if="errors.get('custom_data.'+field.slug)" class="error-message">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM7.4 7.4V11H8.6V7.4H7.4ZM7.4 5V6.2H8.6V5H7.4Z"
                                    fill="#FB3748"/>
                            </svg>
                            <error :error="errors.get('custom_data.'+field.slug)"/>
                        </div>
                    </el-form-item>
                </div>
            </template>
        </div>
        <div v-else-if="loading_remote" style="width: 100%; height: 20px" v-loading="true"></div>
    </div>
</template>

<script type="text/babel">
import isEmpty from 'lodash/isEmpty';
import isArray from 'lodash/isArray';
import each from 'lodash/each';
import Errors from '@/common/Errors';
import Error from '@/common/Error';


export default {
    name: 'CustomFieldForm',
    props: ['custom_data', 'ticket', 'exceptions'],
    components: {
        Error
    },
    watch: {
        'exceptions': function (newVal, oldVal) {
            this.errors.record(newVal);
        }
    },
    data() {
        return {
            appReady: false,
            fields: this.appVars.custom_fields,
            labelPosition: 'top',
            loading_remote: false,
            formattedProducts: {},
            errors: new Errors()
        }
    },
    methods: {
        fetchRemoteFields() {
            this.loading_remote = true;
            this.$get('custom-fields-rendered')
                .then(response => {
                    this.fields = response.custom_fields_rendered;
                    this.initFields();
                })
                .catch((errors) => {
                    console.log(errors);
                })
                .finally(() => {
                    this.loading_remote = false;
                });
        },
        initFields() {
            if (isEmpty(this.fields)) {
                return false;
            }

            each(this.fields, (field, fieldName) => {
                if (field.type == 'checkbox') {
                    if (!this.custom_data[fieldName] || !isArray(this.custom_data[fieldName])) {
                        this.custom_data[fieldName] = [];
                    }
                }
            });

            each(this.appVars.support_products, (product) => {
                this.formattedProducts[product.id] = product.title;
            });

            this.appReady = true;
        },
        isRenderable(field) {
            if (field.has_logics != 'yes' || !field.conditions || !field.conditions.length) {
                return true;
            }
            let singlePass = false;
            let allPassed = true;
            each(field.conditions, (condition) => {
                if (this.dependancyPass(condition)) {
                    singlePass = true;
                } else {
                    this.custom_data[field.slug] = [];
                    allPassed = false;
                }
            });

            if (field.match_type == 'all') {
                return singlePass && allPassed;
            }

            return singlePass;
        },
        /**
         * Helper function for show/hide dependent elements
         & @return {Boolean}
         */
        compare(sourceVal, operator, givenVal) {
            if (givenVal === undefined) {
                return false;
            }
            if (typeof sourceVal == 'string') {
                sourceVal = sourceVal.toLowerCase();
            }

            if (typeof givenVal == 'string') {
                givenVal = givenVal.toLowerCase();
            }

            if (isArray(givenVal)) {
                givenVal = givenVal.map((val) => {
                    return val.toLowerCase();
                });
            }

            switch (operator) {
                case '=':
                    if (isArray(givenVal)) {
                        return givenVal.indexOf(sourceVal) !== -1;
                    }
                    return sourceVal == givenVal
                case '!=':
                    if (isArray(givenVal)) {
                        return givenVal.indexOf(sourceVal) === -1;
                    }
                    if (sourceVal !== '' && (givenVal === '' || givenVal === undefined)) {
                        return false;
                    }
                    return sourceVal != givenVal
                case 'contains':
                    sourceVal = sourceVal.toString();
                    return givenVal.indexOf(sourceVal) !== -1;
                case 'not_contains':
                    sourceVal = sourceVal.toString();
                    return givenVal.indexOf(sourceVal) === -1;
                case 'lt':
                    return givenVal < sourceVal;
                case 'gt':
                    return givenVal > sourceVal;
                default:
                    return false
            }
        },

        /**
         * Checks if a prop is dependent on another
         * @param condition
         * @return {boolean}
         */
        dependancyPass(condition) {
            if (condition && condition.item_key && condition.operator) {
                let itemKey = condition.item_key;
                let sourceValue = '';

                if (itemKey.indexOf('ticket_') === 0) { // it's a ticket property
                    itemKey = itemKey.replace('ticket_', '');
                    if (itemKey == 'product_id') {
                        sourceValue = this.formattedProducts[this.ticket[itemKey]];
                    } else {
                        sourceValue = this.ticket[itemKey];
                    }
                } else {
                    sourceValue = this.custom_data[itemKey];
                }

                if (!sourceValue) {
                    sourceValue = '';
                }

                if (this.compare(condition.value, condition.operator, sourceValue)) {
                    return true;
                }

                return false;
            }
            return true;
        }
    },
    computed: {
        computedFields() {
            let fieldData = Object.keys(this.fields);
            let data = this.fields;
            fieldData.forEach((i) => {
                data[i].is_renderable = this.isRenderable(data[i]);
            });

            return data;
        }
    },
    mounted() {
        if (isEmpty(this.fields)) {
            return false;
        }

        if (this.appVars.has_pro && this.appVars.has_custom_ajax_fields) {
            this.fetchRemoteFields();
        } else {
            this.initFields();
        }

    }
}
</script>
