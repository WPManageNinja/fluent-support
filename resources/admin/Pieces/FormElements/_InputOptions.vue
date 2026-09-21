<template>
    <div>
        <el-select
            clearable
            filterable
            :multiple="field.multiple"
            :placeholder="field.placeholder"
            :model-value="modelValueLocal"
            @update:model-value="updateValue"
        >
            <el-option
                v-for="item in field.options"
                :key="item.id"
                :label="getOptionLabel(item)"
                :value="item.id"
            >
                <span class="fs_option_title">{{ item.title }}</span>
                <span class="fs_option_id" v-if="field.show_id">{{ item.id }}</span>
            </el-option>
        </el-select>
    </div>
</template>

<script>
export default {
    name: 'InputOptions',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    computed: {
        modelValueLocal() {
            return this.convertToInt(this.modelValue);
        }
    },
    methods: {
        convertToInt(value) {
            if (Array.isArray(value)) {
                return value.map(item => parseInt(item) || item);
            }
            return parseInt(value) || value;
        },
        updateValue(newValue) {
            if (JSON.stringify(newValue) !== JSON.stringify(this.modelValue)) {
                this.$emit('update:modelValue', newValue);
            }
        },
        getOptionLabel(item) {
            return this.field.show_id ? `${item.title} (${this.$t('Page ID')} #${item.id})` : item.title;
        }
    }
}
</script>
