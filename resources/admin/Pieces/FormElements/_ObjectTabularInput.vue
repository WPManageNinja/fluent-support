<template>
    <table class="fs_table">
        <thead>
            <tr>
                <th>{{field.source_label}}</th>
                <th>{{field.new_label}}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(option, optionKey) in field.options">
                <th>{{option}}</th>
                <td>
                    <el-input v-model="modelValue[optionKey]" />
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script type="text/babel">
export default {
    name: 'ObjectTabularInput',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    watch: {
        modelValue(value) {
            this.$emit('update:modelValue', value);
        }
    }
}
</script>
