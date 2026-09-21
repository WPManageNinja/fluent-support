<template>
    <el-pagination
                   :background="background"
                   :disabled="disabled"
                   :layout="layout"
                   @current-change="changePage"
                   @size-change="changeSize"
                   :hide-on-single-page="false"
                   :current-page.sync="pagination.current_page"
                   :page-sizes="page_sizes"
                   :page-size="pagination.per_page"
                   :total="pagination.total"
    />
</template>

<script>
    export default {
        name: 'Pagination',
        props: {
            pagination: {
                required: true,
                type: Object
            },
            layout: {
                type: String,
                default: 'total, sizes, prev, pager, next'
            },
            background: {
                type: [Boolean, String],
                default: false
            },
            disabled: {
                type: Boolean,
                default: false
            }
        },
        emits: ['fetch'],
        computed: {
            page_sizes() {
                return [
                    10,
                    20,
                    50,
                    80,
                    100,
                    120,
                    150
                ];
            }
        },
        methods: {
            changePage(page) {
                this.pagination.current_page = page;
                this.$emit('fetch');
            },
            changeSize(size) {
                this.pagination.per_page = size;
                // The previous page number may not exist at the new size (e.g.
                // page 3 of 20/page has no equivalent at 50/page) — reset to 1
                // so callers never fetch a page that's gone empty out from
                // under them.
                this.pagination.current_page = 1;
                this.$emit('fetch');
            }
        }
    };
</script>
