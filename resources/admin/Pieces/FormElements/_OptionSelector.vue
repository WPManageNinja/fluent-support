<template>
    <div class="fs_options_selector">
        <el-select class="fs_select_field" :size="field.size" v-model="model" :multiple="field.is_multiple"
                   :placeholder="field.placeholder"
                   clearable
                   filterable>
            <el-option
                v-for="option in options"
                :key="option.id"
                :value="option.id"
                :label="option.title"><span v-html="option.title"></span></el-option>
        </el-select>
    </div>
</template>

<script>
import each from 'lodash/each';

export default {
    name: 'OptionSelector',
    props: ['value', 'field', 'modelValue'],
    data() {
        return {
            options: [],
            model: this.modelValue,
            element_ready: false,
        }
    },
    watch: {
        model(value) {
            this.$emit('update:modelValue', value);
        }
    },
    methods: {
        setOptions() {
            const validKeys = {
                ticket_statuses: 'object_type',
                client_priorities: 'object_type',
                admin_priorities: 'object_type',
                ticket_tags: 'array_type',
                support_products: 'array_type',
                support_agents: 'object_type',
                mailboxes: 'object_type'
            }

            const optionKey = this.field.option_key;

            if (!validKeys[optionKey] || !this.appVars[optionKey]) {
                return false;
            }

            if (validKeys[optionKey] == 'object_type') {
                if(optionKey == 'support_agents'){
                    each(this.appVars[optionKey], (item) => {
                        this.options.push({
                            id: item.id,
                            title: item.full_name,
                        });
                    })
                }
                else if(optionKey == 'mailboxes'){
                    each(this.appVars[optionKey], (item) => {
                        this.options.push({
                            id: item.id,
                            title: item.name,
                        });
                    })
                }
                else {
                    each(this.appVars[optionKey], (value, key) => {
                        this.options.push({
                            id: key,
                            title: value,
                        });
                    });
                }
            } else {
                this.options = this.appVars[optionKey];
            }
        }
    },
    mounted() {
        this.setOptions();
    }
}
</script>
