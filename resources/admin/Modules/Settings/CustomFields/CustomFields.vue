<template>

</template>

<script type="text/babel">
export default {
    name: 'TicketCustomFields',
    data() {
        return {
            fields: [],
            loading: false
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('')
        }
    },
    mounted() {
        if (this.has_pro) {
            this.fetch();
        }
    }
}
</script>
