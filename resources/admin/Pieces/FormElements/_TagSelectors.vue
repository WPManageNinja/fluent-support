<template>
    <div>
        <el-select clearable :multiple="field.is_multiple" filterable :placeholder="field.placeholder" v-model="modelValueLocal">
            <template v-if="field.extra_options">
                <el-option v-for="(option, optionValue) in field.extra_options" :key="option.id" :value="option.id" :label="option.title"></el-option>
            </template>
            <el-option
                v-for="tag in tags"
                :key="tag.id"
                :label="tag.title"
                :value="tag.id">
                <span style="float: left">{{ tag.title }}</span>
                <span v-if="tag.show_id" style="float: right; color: #8492a6; font-size: 13px">{{ tag.id }}</span>
            </el-option>
        </el-select>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'TagSelectors',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    data() {
        return {
            tags: this.appVars.ticket_tags,
            loading: false,
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
