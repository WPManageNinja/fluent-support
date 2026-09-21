<template>
    <div>
        <el-select
            multiple
            clearable
            filterable
            collapse-tags
            collapse-tags-tooltip
            :placeholder="field.placeholder || 'Select...'"
            v-model="modelValueLocal"
        >
            <el-option
                v-for="option in field.options"
                :key="option.id"
                :label="option.label"
                :value="field.data_type === 'number' ? Number(option.id) : option.id">
            </el-option>
        </el-select>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'MultiSelect',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    computed: {
        modelValueLocal: {
            get() {
                return Array.isArray(this.modelValue) ? this.modelValue : [];
            },
            set(value) {
                this.$emit('update:modelValue', value);
            }
        }
    }
}
</script>
