<template>
    <div class="fs_collapsible_widget" :class="{ 'fs_widget_expanded': isExpanded }">
        <widget-header
            :title="title"
            :icon="icon"
            :expanded="isExpanded"
            @toggle="toggleWidget"
        >
            <template v-if="$slots.icon" #icon>
                <slot name="icon"></slot>
            </template>
        </widget-header>

        <transition name="fs-widget-slide">
            <div v-show="isExpanded" class="fs_widget_body">
                <slot></slot>
            </div>
        </transition>
    </div>
</template>

<script>
import WidgetHeader from './_WidgetHeader.vue';

export default {
    name: 'CollapsibleWidget',
    components: {
        WidgetHeader
    },
    props: {
        title: {
            type: String,
            required: true
        },
        icon: {
            type: [String, Object],
            default: null
        },
        defaultExpanded: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isExpanded: this.defaultExpanded
        }
    },
    methods: {
        toggleWidget() {
            this.isExpanded = !this.isExpanded;
        },
        expand() {
            this.isExpanded = true;
        },
        collapse() {
            this.isExpanded = false;
        }
    }
}
</script>

