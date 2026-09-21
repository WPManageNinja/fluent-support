<template>
    <div
        class="fs_widget_header"
        :class="{ 'fs_widget_expanded': expanded }"
        @click="toggleExpanded"
    >
        <div class="fs_widget_header_content">
            <div class="fs_widget_title_wrapper">
                <slot name="icon">
                    <el-icon size="18" v-if="icon" class="fs_widget_icon">
                        <component :is="icon" />
                    </el-icon>
                </slot>
                <h3 class="fs_widget_title" v-if="!isHtml">{{ title }}</h3>
                <h3 class="fs_widget_title" v-else v-html="title"></h3>
            </div>
            <el-icon class="fs_widget_toggle_icon">
                <component :is="expanded ? 'ArrowUp' : 'ArrowDown'" />
            </el-icon>
        </div>
        <div v-if="expanded" class="fs_widget_divider"></div>
    </div>
</template>

<script>

export default {
    name: 'WidgetHeader',
    props: {
        title: {
            type: String,
            required: true
        },
        icon: {
            type: [String, Object],
            default: null
        },
        expanded: {
            type: Boolean,
            default: false
        }
    },
    emits: ['toggle'],
    computed: {
        isHtml() {
            // Check if title contains HTML tags
            return this.title && /<[^>]+>/.test(this.title);
        }
    },
    methods: {
        toggleExpanded() {
            this.$emit('toggle');
        }
    }
}
</script>

