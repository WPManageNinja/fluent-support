<template>
    <div class="fs_html_viewer" :class="field.wrapper_class">
        <div v-if="field.wrapper_class === 'fs_warn_alert_wrapper'" class="fs_warn_alert">
            <IconPack icon-key="warning" :width="20" :height="20" class="fs_warn_icon" />
            <div class="fs_warn_content" v-html="field.html"></div>
        </div>
        <div v-else v-html="field.html"></div>
    </div>
</template>

<script type="text/babel">
import IconPack from '@/admin/Components/IconPack.vue';

export default {
    name: 'HtmlViewer',
    components: {
        IconPack
    },
    props: ['field'],
}
</script>
