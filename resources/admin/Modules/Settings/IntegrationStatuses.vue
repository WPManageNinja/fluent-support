<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{$t('Integration Statuses')}}</h3>
            </div>
        </div>
        <div v-if="!loading" class="fs_box_body fs_storage_integration_wrapper">
            <div class="fs_storage_integration_cards">
                <div v-for="(item, index) in connections" :key="index" class="fs_storage_integration_card">
                    <div class="fs_storage_card_content">
                        <div class="fs_storage_card_left">
                            <div class="fs_storage_icon_wrapper">
                                <img :src="item.logo" :alt="item.title" class="fs_storage_icon"/>
                            </div>
                            <div class="fs_storage_card_info">
                                <div class="fs_storage_card_header">
                                    <h3 class="fs_storage_card_title">{{ item.title }}</h3>
                                    <span v-if="item.is_integrated" class="fs_status_badge active">
                                        {{$t('Connected')}}
                                    </span>
                                </div>
                                <p class="fs_storage_card_description" v-html="item.description"></p>
                            </div>
                        </div>
                        <div class="fs_storage_card_right">
                            <div class="fs_storage_card_actions">
                                <el-button
                                    v-if="item.doc_url"
                                    @click="redirectToDocs(item.doc_url)"
                                    class="fs_outline_btn"
                                >
                                    {{$t('Learn More')}}
                                </el-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="fs_box_body fs_skeleton_loader">
            <el-skeleton :animated="true" :rows="3" />
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'IntegrationStatuses',
    data() {
        return {
            connections: {},
            loading: false,
        }
    },
    methods: {
        fetchSettings() {
            this.loading = true;
            this.$get('settings/integration-statuses')
                .then(response => {
                    this.connections = response.connections;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        redirectToDocs(link) {
            if (!link) {
                return;
            }
            window.open(link, '_blank', 'noopener');
        }
    },
    mounted() {
        this.fetchSettings();
        this.$setTitle('Integration Statuses');
    }
}
</script>
