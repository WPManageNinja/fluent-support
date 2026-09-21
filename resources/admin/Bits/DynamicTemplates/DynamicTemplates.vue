<script setup>
import {defineComponent, getCurrentInstance, onBeforeMount, onMounted, onBeforeUnmount, ref, watch} from "vue";
import * as VueInstance from 'vue';

import Rest from "@/admin/Bits/Rest.js";
import VueTemplateLoader from '@/admin/Bits/DynamicTemplates/VueTemplateLoader.vue';
import CollapsibleWidget from '@/admin/Modules/Tickets/parts/_CollapsibleWidget.vue';

const emit = defineEmits(['change']);
const props = defineProps({
    widgetsQuery: {
        type: Object,
        default: () => ({}),
        required: false,
    },
    filter: {
        type: String,
        default: '',
        required: false,
    },
    data: {
        required: false
    },
});

const widgets = ref([]);

// Server widgets: keyed by response index (assigned once per fetch, stable within a fetch)
// JS hook widgets: keyed by filter result index prefixed with 'js_' (stable across repeated calls)
const assignWidgetId = (widget, stableIndex) => {
    if (!widget._wid) {
        widget._wid = widget.id || widget.form_name
            || (widget.type + '_' + (widget.title || '') + '_' + (widget.component || '') + '_' + stableIndex).replace(/\s+/g, '_');
    }
    return widget;
};

const readonlyVueInstance = VueInstance.readonly(VueInstance);

const componentsMap = {};
const componentSourceMap = {};
const resolveComponents = (appComponents) => {
    const adminHooks = window.fluentSupportAdmin?.hooks;
    if (!adminHooks) return;

    window.fluentSupportAdmin.app = {
        vue: readonlyVueInstance,
        components: appComponents,
        xhr: Rest,
    };

    let components = adminHooks.applyFilters(props.filter + '_components', [], readonlyVueInstance, appComponents);
    components.forEach(({name, component}) => {
        // Re-define only when the source component reference changes
        if (componentSourceMap[name] !== component) {
            componentSourceMap[name] = component;
            componentsMap[name] = defineComponent(component);
        }
    });
};

const fetchWidgets = () => {
    const query = {
        filter: props.filter,
        data: props.widgetsQuery
    };

    Rest.get('widgets', query).then((data) => {
        if (data && Array.isArray(data.widgets)) {
            // Keep JS hook widgets, replace server widgets
            const jsWidgets = widgets.value.filter(w => w._jsHook);
            data.widgets.forEach((w, i) => assignWidgetId(w, 'srv_' + i));
            widgets.value = jsWidgets.concat(data.widgets);
        }
    });
};

onBeforeMount(fetchWidgets);

// Re-fetch only when the query values actually change
let lastQueryStr = JSON.stringify(props.widgetsQuery);
watch(() => JSON.stringify(props.widgetsQuery), (newVal) => {
    if (newVal !== lastQueryStr) {
        lastQueryStr = newVal;
        fetchWidgets();
    }
});

// Capture instance once during setup — getCurrentInstance() returns null inside setTimeout/addEventListener
const currentInstance = getCurrentInstance();

const applyJsHooks = () => {
    const adminHooks = window.fluentSupportAdmin?.hooks;
    if (!adminHooks || !currentInstance) return;

    let appComponents = currentInstance.appContext.components;
    resolveComponents(appComponents);

    let dynamicTemplates = adminHooks.applyFilters(props.filter, [], readonlyVueInstance, appComponents);
    if (!Array.isArray(dynamicTemplates) || !dynamicTemplates.length) return;

    // Assign stable IDs from filter result position (same hooks = same order = same IDs)
    dynamicTemplates.forEach((w, i) => { w._jsHook = true; assignWidgetId(w, 'js_' + i); });

    // Always replace JS hook widgets with fresh set.
    // Vue's :key diffing reuses DOM for matching _wid values, preventing unnecessary re-mounts.
    const serverWidgets = widgets.value.filter(w => !w._jsHook);
    widgets.value = serverWidgets.concat(dynamicTemplates);
};

let delayTimer = null;

onMounted(() => {
    // Try immediately for hooks registered before mount
    applyJsHooks();

    // Retry after delay to catch admin_footer scripts
    delayTimer = setTimeout(applyJsHooks, 300);

    // Listen for late-registering scripts (stays active for the component lifetime)
    window.addEventListener('fluent_support_widgets_ready', applyJsHooks);
});

onBeforeUnmount(() => {
    if (delayTimer) clearTimeout(delayTimer);
    window.removeEventListener('fluent_support_widgets_ready', applyJsHooks);
});

const getDynamicComponent = (component) => {
    return defineComponent(component);
};

const getDynamicComponentFromMap = (component) => {
    return componentsMap[component] || null;
};

const componentRefs = ref([]);

const callComponentCallbacks = async (callbackName, data) => {
    const promises = componentRefs.value.map((component) => {
        if (typeof component[callbackName] === 'function') {
            return component[callbackName](data);
        }
        return Promise.resolve();
    });
    await Promise.all(promises);
    return true;
};

const beforeUpdatingData = async (data) => {
    return await callComponentCallbacks('beforeUpdatingData', data);
};
const afterUpdatingData = async (data) => {
    return await callComponentCallbacks('afterUpdatingData', data);
};

const formRefs = ref();

const getFormStates = () => {
    let data = [];
    if (Array.isArray(formRefs.value)) {
        for (let form of formRefs.value) {
            data = {
                ...data,
                ...form.getState()
            };
        }
    }
    return data;
};

const resetForms = () => {
    if (Array.isArray(formRefs.value)) {
        for (let form of formRefs.value) {
            if (form.resetOnSave()) {
                form.resetForm();
            }
        }
    }
};

defineExpose({beforeUpdatingData, afterUpdatingData, getFormStates, resetForms});
</script>

<template>
    <template v-for="widget in widgets" :key="widget._wid">
        <!-- Widgets with card wrapper (html, chart, form, or use_card=true) -->
        <template v-if="(widget.type !== 'vue-component' && widget.type !== 'vue-template') || widget.use_card === true">
            <collapsible-widget
                :title="widget.title || ''"
                :icon="widget.icon || 'SetUp'"
                :default-expanded="widget.default_expanded !== false"
                class="fs_sidebar_widget fs_dynamic_widget"
            >
                <template v-if="widget.type === 'html'">
                    <div v-html="widget.content"></div>
                </template>

                <template v-else-if="widget.type === 'vue-component'">
                    <template v-if="typeof widget.component === 'object'">
                        <component ref="componentRefs" :is="getDynamicComponent(widget.component)" v-bind="{...widget, data: data}" />
                    </template>
                    <template v-else-if="getDynamicComponentFromMap(widget.component)">
                        <component ref="componentRefs" :is="getDynamicComponentFromMap(widget.component)" v-bind="{...widget, data: data}" />
                    </template>
                </template>

                <template v-else>
                    {{ widget.content }}
                </template>
            </collapsible-widget>
        </template>

        <!-- Vue component without card -->
        <template v-else-if="widget.type === 'vue-component'">
            <template v-if="typeof widget.component === 'object'">
                <component ref="componentRefs" :is="getDynamicComponent(widget.component)" v-bind="{...widget, data: data}" />
            </template>
            <template v-else-if="getDynamicComponentFromMap(widget.component)">
                <component ref="componentRefs" :is="getDynamicComponentFromMap(widget.component)" v-bind="{...widget, data: data}" />
            </template>
        </template>

        <!-- Vue template string -->
        <template v-else-if="widget.type === 'vue-template'">
            <VueTemplateLoader :widget="widget" :data="data" />
        </template>
    </template>
</template>
