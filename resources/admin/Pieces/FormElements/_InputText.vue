<template>
    <el-input
        :disabled="field.disabled"
        :type="field.data_type"
        :placeholder="field.placeholder"
        :min="field.min"
        :max="field.max"
        v-model="modelValueLocal"
    ></el-input>
</template>

<script type="text/babel">
export default {
    name: 'InputText',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    data() {
        return {
            modelValueLocal: this.modelValue
        }
    },
    watch: {
        modelValueLocal(value) {
            this.$emit('update:modelValue', value);
        }
    }
}
</script>
