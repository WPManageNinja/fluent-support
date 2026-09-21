<template>
    <div class="fs_custom_fields">
        <template v-if="appReady">
            <el-form @submit.prevent="handleFormSubmit" :data="formData" :label-position="labelPosition">
                <el-row v-if="fields" :gutter="30">
                    <el-col v-for="(field, fieldName) in computedFields" :key="fieldName" :xs="24" :md="12" v-show="field.is_renderable">
                        <el-form :data="formData" :label-position="labelPosition" @submit.prevent="handleFormSubmit">
                                <el-form-item :label="field.label">
                                    <!-- Labels are admin authored and can mix RTL and LTR text, so let the
                                         browser resolve direction from the string itself. -->
                                    <template #label><span dir="auto">{{ field.label }}</span></template>
                                    <el-input v-if="field.type == 'text' || field.type == 'number' || field.type == 'textarea'"
                                            :type="field.type"
                                            dir="auto"
                                            v-model="formData[field.slug]"
                                            @keyup.enter.prevent="handleEnterKey"
                                            class="fs_text_input fs_text_input_40"/>
                                    <el-date-picker v-else-if="field.type == 'date-range'"
                                            type="daterange" value-format="YYYY-MM-DD"
                                            range-separator="-"
                                            start-placeholder="Start date"
                                            end-placeholder="End date"
                                            v-model="formData[field.slug]"
                                            style="width: 100%"
                                            class="fs_text_input fs_text_input_40"/>
                                    <el-select class="fs_select_field" v-else-if="field.type == 'select-one'" v-model="formData[field.slug]">
                                        <el-option v-for="option in field.options" :key="option"
                                                :value="option" :label="option"><span dir="auto">{{ option }}</span></el-option>
                                    </el-select>
                                    <el-select class="fs_select_field" v-else-if="field.type == 'select'" :filterable="field.filterable"
                                            :multiple="field.multiple"
                                            v-model="formData[field.slug]">
                                        <el-option v-for="option in field.options" :key="option.id"
                                                :value="option.id" :label="option.title"><span dir="auto">{{ option.title }}</span></el-option>
                                    </el-select>
                                    <el-radio-group v-else-if="field.type == 'radio'" v-model="formData[field.slug]">
                                        <el-radio v-for="option in field.options" :key="option"
                                                :value="option"><span dir="auto">{{ option }}</span></el-radio>
                                    </el-radio-group>
                                    <el-checkbox-group v-else-if="field.type == 'checkbox'" v-model="formData[field.slug]">
                                        <el-checkbox v-for="option in field.options" :key="option"
                                                    :value="option" :label="option"><span dir="auto">{{ option }}</span></el-checkbox>
                                    </el-checkbox-group>
                                    <p v-else>{{ $t('Not editable') }}</p>
                                </el-form-item>
                        </el-form>
                    </el-col>
                </el-row>
                <el-button v-if="ticket_id"
                        v-loading="saving"
                        :disabled="saving"
                        @click.prevent="saveEditedCustomFieldData()"
                        type="primary"
                        native-type="button"
                        class="fs_filled_btn fs_button_right">
                    {{ $t('Save') }}
                </el-button>
            </el-form>
        </template>
        <el-skeleton v-else-if="loading_remote" :rows="3" animated/>
    </div>
</template>

<script type="text/babel">
import isEmpty from 'lodash/isEmpty';
import isArray from 'lodash/isArray';
import each from 'lodash/each';

export default   {
    name: 'CustomFieldForm',
    props: ['custom_data', 'ticket_id', 'ticket', 'type'],
    emits: ['syncData'],
    data() {
        return {
            appReady: false,
            fields: false,
            labelPosition: 'top',
            formData: {},
            formattedProducts: {},
            saving: false,
            loading_remote: false
        }
    },
    methods: {
        fetchRemoteFields() {
            this.loading_remote = true;
            this.$get('tickets/' + this.ticket_id + '/custom-data', {
                with: ['rendered_fields']
            })
                .then(response => {
                    this.formData = response.custom_data;
                    this.fields = response.rendered_fields;
                    this.initFields();
                })
                .catch((errors) => {
                    console.log(errors);
                })
                .always(() => {
                    this.loading_remote = false;
                });
        },
        initFields() {
            if (isEmpty(this.fields)) {
                return false;
            }

            if (!this.ticket_id) {
                this.formData = this.custom_data;
            }

            if (!isEmpty(this.custom_data)) {
                this.formData = this.custom_data;
            }

            if (isEmpty(this.formData)) {
                this.formData = {};
            }

            each(this.fields, (field, fieldName) => {
                if (field.type == 'checkbox') {
                    if (!this.formData[fieldName] || !isArray(this.formData[fieldName])) {
                        this.formData[fieldName] = [];
                    } else if (!this.formData[fieldName]) {
                        this.formData[fieldName] = '';
                    }
                }
            });

            each(this.appVars.support_products, (product) => {
                this.formattedProducts[product.id] = product.title;
            });

            this.appReady = true;
        },
        saveEditedCustomFieldData() {
            this.saving = true;
            this.$post(`ticket-custom-fields/${this.ticket_id}/sync`, {
                custom_fields: this.formData
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });

                    this.$emit('syncData', response.custom_data_rendered);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        handleFormSubmit(event) {
            event.preventDefault();
            if (this.ticket_id && !this.saving) {
                this.saveEditedCustomFieldData();
            }
        },
        handleEnterKey(event) {
            event.preventDefault();
            if (this.ticket_id && !this.saving) {
                this.saveEditedCustomFieldData();
            }
        },
        isRenderable(field) {
            if (field.has_logics != 'yes' || !field.conditions || !field.conditions.length || this.type === 'update_ticket') {
                return true;
            }
            let singlePass = false;
            let allPassed = true;
            each(field.conditions, (condition) => {
                if (this.dependencyPass(condition)) {
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
        dependencyPass(condition) {
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
        if (isEmpty(this.appVars.custom_fields)) {
            return false;
        }

        if (this.appVars.has_custom_ajax_fields && this.ticket_id) {
            this.fetchRemoteFields();
        } else {
            this.fields = this.appVars.custom_fields;
            this.initFields();
        }

    }
}
</script>
