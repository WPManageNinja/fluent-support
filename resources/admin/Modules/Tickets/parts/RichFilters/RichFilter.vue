<template>
    <div class="fs_rich_filters">
        <div class="fs_rich_filters_wrapper">
            <table v-if="items.length" class="fs_full_width">
                <tbody class="fs_rich_filters_body">
                    <rich-filter-item v-for="(item,itemKey) in items" @removeItem="removeItem(itemKey)" :key="itemKey"
                                :filterLabels="filterLabels" :item="item" />
                </tbody>
            </table>

            <div v-if="Object.keys(items).length === 0" class="fs_filter_intro fs_rich_filter_actions fs_mt-4">
                <!-- <div class="fs_justify_between"> -->
                    <el-popover
                        placement="right"
                        width="450"
                        v-model:visible="addVisible"
                        trigger="manual">
                        <el-cascader-panel @change="maybeSelected"
                                        style="width: 100%"
                                        :options="filterOptions"
                                        v-model="new_item"/>
                        <template #reference>
                            <el-button class="fs_add_action_property" @click="addVisible = !addVisible" size="small">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M9.25 9.25V4.75H10.75V9.25H15.25V10.75H10.75V15.25H9.25V10.75H4.75V9.25H9.25Z" fill="#0E121B"/>
                                </svg>
                                {{$t('Add')}}
                            </el-button>
                        </template>
                    </el-popover>
                    <div
                        class="fs_delete_condition_btn"
                        role="button"
                        tabindex="0"
                        @click="deleteSection"
                        @keydown.enter.prevent="deleteSection"
                        @keydown.space.prevent="deleteSection">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.25 3H15V4.5H13.5V14.25C13.5 14.4489 13.421 14.6397 13.2803 14.7803C13.1397 14.921 12.9489 15 12.75 15H2.25C2.05109 15 1.86032 14.921 1.71967 14.7803C1.57902 14.6397 1.5 14.4489 1.5 14.25V4.5H0V3H3.75V0.75C3.75 0.551088 3.82902 0.360322 3.96967 0.21967C4.11032 0.0790176 4.30109 0 4.5 0H10.5C10.6989 0 10.8897 0.0790176 11.0303 0.21967C11.171 0.360322 11.25 0.551088 11.25 0.75V3ZM12 4.5H3V13.5H12V4.5ZM5.25 6.75H6.75V11.25H5.25V6.75ZM8.25 6.75H9.75V11.25H8.25V6.75ZM5.25 1.5V3H9.75V1.5H5.25Z" fill="#525866"/>
                        </svg>
                    </div>
                    <!-- <p class="fs_add_new_properties_text">{{ $t('Add new properties') }}</p> -->
                <!-- </div> -->
                <!-- <span class="fs_delete_action_property" @click="deleteSection" size="small">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2.4H12V3.6H10.8V11.4C10.8 11.5591 10.7368 11.7117 10.6243 11.8243C10.5117 11.9368 10.3591 12 10.2 12H1.8C1.64087 12 1.48826 11.9368 1.37574 11.8243C1.26321 11.7117 1.2 11.5591 1.2 11.4V3.6H0V2.4H3V0.6C3 0.44087 3.06321 0.288258 3.17574 0.175736C3.28826 0.0632141 3.44087 0 3.6 0H8.4C8.55913 0 8.71174 0.0632141 8.82426 0.175736C8.93679 0.288258 9 0.44087 9 0.6V2.4ZM9.6 3.6H2.4V10.8H9.6V3.6ZM4.2 5.4H5.4V9H4.2V5.4ZM6.6 5.4H7.8V9H6.6V5.4ZM4.2 1.2V2.4H7.8V1.2H4.2Z" fill="#FB3748"/>
                    </svg>
                    {{$t('Delete Section')}}
                </span> -->
            </div>

            <div v-else class="fs_filter_intro fs_rich_filter_actions">
                <el-popover
                    placement="right"
                    width="450"
                    v-model:visible="addVisible"
                    trigger="manual">
                    <el-cascader-panel @change="maybeSelected"
                                    style="width: 100%"
                                    :options="filterOptions"
                                    v-model="new_item"/>
                    <template #reference>
                        <el-button class="fs_add_action_property" @click="addVisible = !addVisible" size="small">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M9.25 9.25V4.75H10.75V9.25H15.25V10.75H10.75V15.25H9.25V10.75H4.75V9.25H9.25Z" fill="#0E121B"/>
                            </svg>
                            {{$t('Add')}}
                        </el-button>
                    </template>
                </el-popover>
                <!-- <span class="fs_delete_action_property" @click="deleteSection" size="small">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2.4H12V3.6H10.8V11.4C10.8 11.5591 10.7368 11.7117 10.6243 11.8243C10.5117 11.9368 10.3591 12 10.2 12H1.8C1.64087 12 1.48826 11.9368 1.37574 11.8243C1.26321 11.7117 1.2 11.5591 1.2 11.4V3.6H0V2.4H3V0.6C3 0.44087 3.06321 0.288258 3.17574 0.175736C3.28826 0.0632141 3.44087 0 3.6 0H8.4C8.55913 0 8.71174 0.0632141 8.82426 0.175736C8.93679 0.288258 9 0.44087 9 0.6V2.4ZM9.6 3.6H2.4V10.8H9.6V3.6ZM4.2 5.4H5.4V9H4.2V5.4ZM6.6 5.4H7.8V9H6.6V5.4ZM4.2 1.2V2.4H7.8V1.2H4.2Z" fill="#FB3748"/>
                    </svg>
                    {{$t('Delete Section')}}
                </span> -->
            </div>
        </div>
    </div>
</template>


<script>
import RichFilterItem from './_RichFilterItem';
import each from 'lodash/each';

export default {
    name: 'RichFilter',
    components: {
        RichFilterItem
    },
    props: ['items'],
    data() {
        return {
            addVisible: false,
            new_item: [],
            filterOptions: this.appVars.advanced_filter_options
        }
    },
    computed: {
        filterLabels() {
            const options = {};
            each(this.filterOptions, (option) => {
                each(option.children, (item) => {
                    options[option.value + '-' + item.value] = {
                        provider: option.value,
                        ...item
                    }
                });
            });
            return options
        }
    },
    methods: {
        maybeSelected() {
            if (this.new_item.length === 2) {
                let operator = '';

                if (this.new_item[0] === 'customer' && this.new_item[1] !== 'agent') {
                    operator = 'contains';
                }

                let tempItem = this.new_item;
                let exist = false;
                each(this.items, (item) => {
                    if(item.source[0] === tempItem[0] && item.source[1] === tempItem[1]){
                        exist = true;
                    }
                })
                if(!exist){
                    this.items.push({
                        source: [...this.new_item],
                        operator: operator,
                        value: ''
                    });
                }else{
                    this.$notify({
                        type: 'error',
                        message: this.$t('Selected item already selected'),
                        position: 'bottom-right'
                    })
                }

                this.addVisible = false;
                this.new_item = [];
            }
        },
        removeItem(index) {
            this.items.splice(index, 1);
            if (!this.items.length) {
                this.$emit('maybeRemove');
            }
        },
        deleteSection() {
            this.items.splice(0, this.items.length);
            this.$emit('maybeRemove');
        }
    }
}
</script>

<style scoped>

</style>
