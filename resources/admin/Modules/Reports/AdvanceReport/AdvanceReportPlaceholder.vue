<template>
    <div class="fs_inside_menu_component_header">
        <div class="fs_component_head">
            <h3 class="fs_page_title">{{ title }}</h3>
        </div>
    </div>
    <NarrowPromo
        :heading="title"
        :description="resolvedDescription"
        :button-text="buttonText"
        :utm-content="utmContent"
    />
</template>

<script type="text/babel">
import NarrowPromo from "@/admin/Components/NarrowPromo";

export default {
    name: "AdvanceReportPlaceholder",
    components: {
        NarrowPromo
    },
    props: {
        title: {
            type: String,
            required: true
        },
        description: {
            type: String,
            default: ''
        },
        buttonText: {
            type: String,
            default: ''
        },
        utmContent: {
            type: String,
            default: ''
        }
    },
    computed: {
        resolvedDescription() {
            return this.description || this.$t('This report is coming soon.');
        }
    }
};
</script>
