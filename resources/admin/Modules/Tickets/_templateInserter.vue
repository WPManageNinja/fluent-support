<template>
    <el-popover
        placement="bottom-start"
        :width="640"
        trigger="manual"
        :visible="visible"
        popper-class="fs_popover fs_template_popover"
    >
        <template #reference>
            <el-button class="fs_text_btn" @click="initModal()" size="small" type="default">
                {{$t('Templates')}} <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
        </template>
        <div class="fs_template_inserter">
            <div class="fs_template_filters">
                <div class="fs_filter_item">
                    <label class="fs_filter_label">{{$t('Product')}}</label>
                    <el-select
                        @change="searchTemplates()"
                        clearable
                        v-model="selected_product"
                        :placeholder="$t('All Products')"
                        class="fs_select_field fs_template_select"
                    >
                        <el-option
                            v-for="product in products"
                            :label="product.title"
                            :value="product.id"
                            :key="product.id"
                        ></el-option>
                    </el-select>
                </div>
                <div class="fs_filter_item">
                    <label class="fs_filter_label">{{$t('Search')}}</label>
                    <el-input
                        @keyup.enter="searchTemplates()"
                        @clear="searchTemplates()"
                        clearable
                        :placeholder="$t('Search replies')"
                        v-model="search"
                        class="fs_text_input fs_table_search_input fs_template_search_input"
                    >
                        <template #prefix>
                            <el-icon class="el-input__icon"><Search /></el-icon>
                        </template>
                    </el-input>
                </div>
            </div>

            <div v-loading="loading" class="doc_blocks fs_saved_blocks">
                <ul v-if="replies.length" class="fs_saved_replies">
                    <li
                        class="fs_saved_reply_item"
                        @click="insertReply(reply)"
                        v-for="reply in replies"
                        :key="reply.id"
                    >
                        <div class="fs_reply_title">
                            <span>{{ reply.title }}</span>
                            <span v-if="reply.product?.title" class="fs_reply_badge">
                                {{ reply.product.title }}
                            </span>
                        </div>
                        <p class="fs_reply_excerpt">{{ getExcerpt(reply.content) }}</p>
                    </li>
                </ul>
                <div v-else class="fs_template_empty">
                    <p>{{$t('No saved replies found.')}}</p>
                </div>
                <div v-if="pagination.total" class="fs_pagination_wrapper">
                    <span class="fs_pagination_left">
                        <p>Page {{ pagination.current_page }} of {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                        <pagination
                            @fetch="searchTemplates()"
                            :pagination="pagination"
                            layout="sizes"
                        />
                    </span>
                    <span class="fs_pagination_right">
                        <pagination
                            @fetch="searchTemplates()"
                            :pagination="pagination"
                            :background="true"
                            layout="prev, pager, next"
                        />
                    </span>
                </div>
            </div>
        </div>
    </el-popover>
</template>

<script type="text/babel">
import Pagination from '../../Pieces/Pagination'
export default {
    name: 'TemplateInserter',
    components: { Pagination },
    data() {
        return {
            selected_product: '',
            search: '',
            products: this.appVars.support_products,
            replies: [],
            visible: false,
            loading: false,
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0,
            },
        };
    },

    methods: {
        async searchTemplates() {
            this.loading = true;
            try {
                const response = await this.$get("saved-replies", {
                    page: this.pagination.current_page,
                    product_id: this.selected_product,
                    search: this.search,
                    per_page: this.pagination.per_page,
                });

                window.fst_last_replies = response.replies;
                window.fst_last_replies.product_id = this.selected_product;
                window.fst_last_replies.search = this.search;

                this.replies = response.replies.data;
                this.pagination.total = response.replies.total;
            } catch (errors) {
                this.$handleError(errors);
            } finally {
                this.loading = false;
            }
        },

        initModal() {
            this.visible = !this.visible;

            if (this.visible && !window.fst_last_replies) {
                this.searchTemplates();
            }
        },

        insertReply(reply) {
            this.$emit("insert", reply.content);
            this.visible = false;
        },

        getExcerpt(content, limit = 200) {
            if (!content) return "";
            return content.replace(/<\/?("[^"]*"|'[^']*'|[^>])*(>|$)/g, "").substring(0, limit) + '...';
        },
    },

    mounted() {
        if (window.fst_last_replies) {
            this.selected_product = window.fst_last_replies.product_id;
            this.search = window.fst_last_replies.search;
            this.replies = window.fst_last_replies.data;
            this.pagination.total = window.fst_last_replies.total;
            this.pagination.current_page = window.fst_last_replies.current_page;
        }
    },
};
</script>
