<template>
    <wp-base-editor :showShortcodes="field.show_shortcodes" :showSavedReplies="field.show_saved_replies" v-model="modelValueLocal" />
</template>

<script type="text/babel">

import WpBaseEditor from '../_wp_editor';

export default {
    name: 'WPEditorField',
    components: {
        WpBaseEditor
    },
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
