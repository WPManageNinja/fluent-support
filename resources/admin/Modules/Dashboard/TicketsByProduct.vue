<template>
    <div class="fs_tickets_by_product">
        <div v-if="hasData" class="fs_product_grid">
            <div 
                v-for="(stat, statKey) in component_data"
                :key="statKey"
                class="fs_product_item"
            >
                <p class="fs_product_label">{{ stat.title }}</p>
                <p class="fs_product_count">{{ stat.count }}</p>
            </div>
        </div>
        
        <div v-else class="fs_no_products">
            <span>
                {{ $t('No product data available') }}
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: "TicketsByProduct",
    props: {
        component_data: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        hasData() {
            return this.component_data && 
                   typeof this.component_data === 'object' && 
                   Object.keys(this.component_data).length > 0;
        }
    }
};
</script>
