<template>
    <div class="fs_label_search_drawer">
        <!-- Fixed Header Section -->
        <div class="fs_label_search_drawer_header">
            <div class="fs_label_search_drawer_header_head">
                <div class="fs_label_search_title">
                    {{ $t("Saved Filters") }}
                </div>
                <div class="fs_label_search_close_btn">
                    <div class="icon" @click="closeModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M8 8L16 16M16 8L8 16" stroke="#1B2533" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="fs-table-items">
            <div
                v-if="labelSearchList.length"
                class="fs-each-search"
                v-for="(item, index) in labelSearchList"
                :key="index"
                :class="{ selected: selectedItem === index }"
                @click="selectItem(index)"
            >
                <div class="fs-search-name">{{ item.label_search_name }}</div>
                <div class="fs-search-actions">
                    <el-button icon="EditPen"  @click.stop="handleLabelSearchEdit(item)">
                    </el-button>
                    <el-popconfirm :title="$t('Are you sure to delete this?')" @confirm="handleLabelSearchDelete(item.id)">
                        <template #reference>
                            <el-button icon="Delete" @click.stop>
                            </el-button>
                        </template>
                    </el-popconfirm>
                </div>
            </div>
            <div v-else-if="!labelSearchList.length">
                <p class="fs-table-no-data">{{ $t('No Data Found') }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "LabelSearchDrawer",
    props: {
        labelSearchList: {
            type: Array,
            required: true,
            default: () => [],
        },
        filtersValue: {
            type: Array,
            required: true,
            default: () => [],
        }
    },
    data() {
        return {
            selectedItem: null,
            userSelected: false
        };
    },
    watch: {
        filtersValue: {
            handler(newFiltersValue) {
                if (!this.userSelected) {
                    this.selectedItem = null;
                }
                this.userSelected = false;
            },
            deep: true
        }
    },
    methods: {
        closeModal() {
            this.$emit("close", false);
        },
        selectItem(index) {
            this.selectedItem = index;
            this.userSelected = true;
            const selectedItem = this.labelSearchList[index];
            this.handleSavedSearch(selectedItem);
        },
        handleSavedSearch(item) {
            this.$emit("getSavedSearch", item);
        },
        handleLabelSearchEdit(item) {
            this.$emit("setLabelSearchItem", item);
        },
        handleLabelSearchDelete(id) {
            this.$emit("delete", id);
            this.selectedItem = null;
        }
    }
};
</script>


