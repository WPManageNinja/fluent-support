<template>

        <span class="fs_inline_checkbox_label">
            <el-checkbox
                :true-value="field.true_label"
                :false-value="falseValue"
                :disabled="field.disabled"
                v-model="model"
            >
                {{ field.checkbox_label }}
            </el-checkbox>
            <el-tooltip v-if="field.help" popper-class="sidebar-popper" effect="dark" placement="top">
                <template #content>
                    <span v-html="field.help"></span>
                </template>
                <el-icon class="fs_inline_checkbox_help_icon">
                    <InfoFilled />
                </el-icon>
            </el-tooltip>
        </span>
</template>

<script type="text/babel">
export default {
    name: 'InlineCheckbox',
    props: ['field', 'value', 'modelValue'],
    emits: ['input', 'update:modelValue'],
    computed: {
        falseValue() {
            return this.field.false_label || this.field['false-label'];
        },
        model: {
            get() {
                return this.modelValue !== undefined ? this.modelValue : this.value;
            },
            set(value) {
                this.$emit('input', value);
                this.$emit('update:modelValue', value);
            }
        }
    }
}
</script>
