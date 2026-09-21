<template>
    <div class="fs_pagination_container">
        <div class="fs_pagination_left">
            <span class="fs_page_text">
                {{ $t('Page') }} {{ pagination.current_page }} {{ $t('of') }} {{ Math.ceil(pagination.total / pagination.per_page) }}
            </span>
            <el-select
                v-model="pagination.per_page"
                class="fs_per_page_select"
                @change="changeSize"
            >
                <el-option
                    v-for="size in page_sizes"
                    :key="size"
                    :label="`${size} / ${$t('page')}`"
                    :value="size"
                />
            </el-select>
        </div>

        <el-pagination
            :background="false"
            layout="prev, pager, next"
            @current-change="changePage"
            @size-change="changeSize"
            :hide-on-single-page="false"
            :current-page.sync="pagination.current_page"
            :page-size="pagination.per_page"
            :total="pagination.total"
        />
    </div>
</template>

<script>
export default {
    name: 'Pagination',
    props: {
        pagination: {
            required: true,
            type: Object
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
            this.$emit('fetch');
        }
    }
};
</script>
