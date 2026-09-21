<template>
    <div class="fs_remote_selector">
        <el-select
        v-model="modelValueLocal"
        filterable
        remote
        class="fs_select_field"
        :placeholder="$t('Search')"
        :remote-method="fetchData"
        :loading="loading"
        :clearable="clearable"
        @change="handleChange">
            <el-option
                v-for="item in options"
                :key="item[value_selector]"
                :label="getLabel(item)"
                :value="item[value_selector]">
            </el-option>
        </el-select>
    </div>
</template>

<script type="text/babel">
import each from 'lodash/each';

export default {
    name: 'RemoteSelector',
    props: {
        api_path: String,
        response_key: String,
        value_selector: String,
        label_selectors: Array,
        label_joiner: { type: String, default: ' - ' },
        modelValue: [String, Number],
        clearable: { type: Boolean, default: false }
    },
    emits: ['update:modelValue', 'change'],
    watch: {
        modelValue(value) {
            this.modelValueLocal = value;
        },
        modelValueLocal(value) {
            this.$emit('update:modelValue', value);
        }
    },
    data() {
        return {
            options: [],
            loading: false,
            modelValueLocal: this.modelValue
        }
    },
    methods: {
        fetchData(query) {
            if (!query) {
                query = this.modelValue;
            }

            if (query !== '') {
                this.loading = true;
                this.$get(this.api_path, {
                    search: query,
                    order_by: this.value_selector,
                    order_type: 'ASC'
                })
                .then(response => {
                    if(response[this.response_key].data) {
                        this.options = response[this.response_key].data;
                    }
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
            } else {
                this.options = [];
            }
        },
        getLabel(item) {
            const labels = [];
            each(this.label_selectors, (selector) => {
                labels.push(item[selector]);
            });
            return labels.join(this.label_joiner);
        },
        handleChange(val) {
            this.$emit('change', val);
        }
    },
    mounted() {
        this.fetchData(this.modelValue);
    }
}
</script>
