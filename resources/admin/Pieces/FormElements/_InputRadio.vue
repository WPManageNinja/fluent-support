<template>
    <el-radio-group
        :class="field.wrapper_class"
        v-model="modelValueLocal"
    >
        <el-radio
            v-for="(item, itemIndex) in field.options"
            :key="itemIndex"
            :value="item.id"
        >
            {{ item.label }}
        </el-radio>
    </el-radio-group>
</template>

<script type="text/babel">
export default {
    name: 'InputRadio',
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
