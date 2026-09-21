<template>
    <div v-if="crm_profile" class="fs_crm_profile">
        <div class="fs_crm_heading">
            <a target="_blank" rel="noopener" :href="crm_profile.view_url">
                <span class="fs_taggables_header_title">{{$t('CRM')}}</span>
                <span class="el-tag el-tag--mini el-tag--danger" :class="'fc_status_'+crm_profile.status">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <path d="M8.60001 6.80039H12.8L7.40001 14.6004V9.20039H3.20001L8.60001 1.40039V6.80039Z" fill="white"/>
                    </svg>
                    {{ ucFirst(crm_profile.status) }}
                </span>
            </a>
        </div>
        <span class="fs_crm_profile_title" v-if="crm_profile.name_mismatch">{{ crm_profile.full_name }}</span>
        <div class="fs_taggables">
            <div class="fs_taggables_header">
                <span class="fs_taggables_header_title">{{$t('Contacts Tag')}}</span>
                <div class="fs_taggables_tags">
                    <span class="el-tag el-tag--mini el-tag--plain" v-for="tag in crm_profile.tags" :key="tag.id">{{tag.title }}</span>
                    <el-popover
                        v-if="can_add_tags"
                        placement="bottom"
                        :width="400"
                        :visible="popVisible"
                        trigger="manual"
                        popper-class="fs_popover"
                    >
                        <template #reference>
                        <span @click="popVisible = !popVisible" style="cursor: pointer"
                              class="fs_add_tag_icon el-tag el-tag--mini el-tag--plain"><el-icon style="vertical-align: middle;"><Plus /></el-icon>
                        </span>
                        </template>

                        <div class="fs_popover_content_wrapper">
                            <span class="fs_popover_close" @click="popVisible = false" :title="$t('Close')" role="button">
                                <el-icon><Close /></el-icon>
                            </span>
                            <h4 class="fs_popover_title">{{$t('Apply / Remove Tags on FluentCRM Profile')}}</h4>

                        <el-select :multiple="true" v-model="attachedTags"
                                   size="small">
                            <el-option
                                v-for="tag in all_tags"
                                :key="tag.id" :value="tag.id"
                                :label="tag.title"></el-option>
                        </el-select>

                        <el-button class="fs_filled_btn" v-loading="saving" @click="syncTags()" :disabled="saving" type="success" size="small">{{$t('Update Settings')}}
                        </el-button>
                        </div>

                    </el-popover>
                </div>
            </div>
        </div>


        <div class="fs_taggables">
            <div class="fs_taggables_header">
                <span class="fs_taggables_header_title">{{$t('Contacts List')}}</span>
                <div class="fs_taggables_tags">
                    <span class="el-tag el-tag--mini el-tag--plain" v-for="list in crm_profile.lists" :key="list.id">{{list.title}}</span>
                    <el-popover
                        v-if="can_add_tags"
                        placement="bottom"
                        :width="400"
                        :visible="listPopVisible"
                        trigger="manual"
                        popper-class="fs_popover"
                    >
                        <template #reference>
                        <span @click="listPopVisible = !listPopVisible" style="cursor: pointer"
                              class="fs_add_tag_icon el-tag el-tag--mini el-tag--plain"><el-icon style="vertical-align: middle;"><Plus /></el-icon>
                        </span>
                        </template>

                        <div class="fs_popover_content_wrapper">
                            <span class="fs_popover_close" @click="listPopVisible = false" :title="$t('Close')" role="button">
                                <el-icon><Close /></el-icon>
                            </span>
                            <h4 class="fs_popover_title">{{$t('Apply / Remove Lists on FluentCRM Profile')}}</h4>

                        <el-select
                            :multiple="true"
                            v-model="attachedLists"
                            size="small"
                            class="fs_select_field"
                        >
                            <el-option
                                v-for="list in all_lists"
                                :key="list.id" :value="list.id"
                                :label="list.title"></el-option>
                        </el-select>

                        <el-button v-loading="saving" @click="syncLists()" :disabled="saving" class="fs_filled_btn">{{$t('Update Settings')}}
                        </el-button>
                        </div>
                    </el-popover>
                </div>
            </div>

        </div>
    </div>
</template>

<script type="text/babel">
import each from 'lodash/each';

export default {
    name: 'CRMProfile',
    props: ['crm_profile'],
    data() {
        return {
            all_tags: this.appVars.fluentcrm_config.tags,
            all_lists: this.appVars.fluentcrm_config.lists,
            can_add_tags: this.appVars.fluentcrm_config.can_add_tags,
            attachedTags: [],
            attachedLists: [],
            popVisible: false,
            saving: false,
            listPopVisible: false,
        }
    },
    methods: {
        syncTags() {
            this.saving = true;
            this.$post('tickets/sync-fluentcrm-tags', {
                contact_id: this.crm_profile.id,
                tags: this.attachedTags
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    this.crm_profile.tags = response.tags;
                    this.popVisible = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });

        },
        syncLists() {
            this.saving = true;
            this.$post('tickets/sync-fluentcrm-lists', {
                contact_id: this.crm_profile.id,
                lists: this.attachedLists
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    this.crm_profile.lists = response.lists;
                    this.listPopVisible = false;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });

        }
    },
    mounted() {
        each(this.crm_profile.tags, (tag) => {
            this.attachedTags.push(tag.id);
        });
        each(this.crm_profile.lists, (list) => {
            this.attachedLists.push(list.id);
        });
    }
}
</script>
