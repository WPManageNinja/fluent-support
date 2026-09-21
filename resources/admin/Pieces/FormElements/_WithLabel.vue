<template>
    <el-form-item :class="['fs_field_wrapper', field.wrapper_class]">
        <div class="fs_field_label" v-if="field.label" slot="label">
        {{ field.label }}
            <el-tooltip v-if="field.help" popper-class="sidebar-popper" effect="dark" placement="top">
                <template #content>
                    <span v-html="field.help"></span>
                </template>
                <el-icon> <InfoFilled /> </el-icon>
            </el-tooltip>
        </div>
        <slot></slot>
        <p v-if="field.inline_help && field.type !== 'inline-checkbox'" class="fc_inline_help" v-html="field.inline_help"></p>
    </el-form-item>
</template>

<script type="text/babel">
export default {
    name: 'withLabelField',
    props: ['field']
}
</script>
