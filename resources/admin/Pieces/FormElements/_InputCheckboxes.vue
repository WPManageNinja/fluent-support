<template>
    <el-checkbox-group v-model="model">
        <el-checkbox
            v-for="(option, optionKey) in field.options"
            :key="optionKey"
            :value="optionKey"
        >
            {{ option }}
        </el-checkbox>
    </el-checkbox-group>
</template>

<script type="text/babel">
import isArray from 'lodash/isArray';

export default {
    name: 'CheckBoxGroup',
    props: ['field', 'value'],
    data() {
        return {
            model: this.value
        }
    },
    emits: ['input'],
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    created() {
        if (!isArray(this.value)) {
            this.model = [];
        }
    }
}
</script>
