<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t('File Storage Settings') }}</h3>
            </div>
        </div>
        <div v-if="!loading" class="fs_box_body fs_storage_integration_wrapper">
            <div class="fs_storage_integration_cards">
                <div v-for="(driver, driverName) in availableDrivers" :key="driverName" class="fs_storage_integration_card">
                    <div class="fs_storage_card_content">
                        <div class="fs_storage_card_left">
                            <div class="fs_storage_icon_wrapper">
                                <img :src="driver.icon" :alt="driver.title" class="fs_storage_icon"/>
                            </div>
                            <div class="fs_storage_card_info">
                                <div class="fs_storage_card_header">
                                    <h3 class="fs_storage_card_title">{{ driver.title }}</h3>
                                    <span v-if="enabled_driver == driverName" class="fs_status_badge active">
                                        {{ $t('Active') }}
                                    </span>
                                </div>
                                <p class="fs_storage_card_description" v-html="driver.description"></p>
                            </div>
                        </div>
                        <div class="fs_storage_card_right">
                            <div class="fs_storage_card_actions">
                                <template v-if="driverName === 'local' || driver.is_configured">
                                    <div class="fs_tk_filter fs_waiting_toggle">
                                        <el-switch
                                            @change="updateDriver(driverName)"
                                            :model-value="enabled_driver == driverName"
                                            :disabled="saving || (driverName === 'local' && enabled_driver === 'local')"
                                            v-loading="saving"
                                        ></el-switch>
                                    </div>
                                    <template v-if="driver.has_config && driverName !== 'local'">
                                        <el-button
                                            @click="showConfigDialog(driver, driverName)"
                                            class="fs_outline_btn"
                                        >
                                            <img :src="appVars.asset_url + 'images/gear.svg'" alt="">
                                        </el-button>
                                    </template>
                                </template>
                                <template v-else-if="driver.require_pro">
                                    <a
                                        class="fs_storage_manage_btn fs_storage_upgrade_btn"
                                        target="_blank"
                                        rel="noopener"
                                        :href="driver.upgrade_url"
                                    >
                                        {{ $t('Upgrade to Pro') }}
                                    </a>
                                </template>
                                <template v-else-if="driver.has_config">
                                    <el-button
                                        @click="showConfigDialog(driver, driverName)"
                                        class="fs_outline_btn"
                                    >
                                        <img :src="appVars.asset_url + 'images/gear.svg'" alt="">
                                    </el-button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="fs_box_body fs_skeleton_loader">
            <el-skeleton :animated="true" :rows="3" />
        </div>

        <el-dialog
            v-if="selectedDriver"
            :title="title"
            v-model="dialogVisible"
            @close="closeDialog"
            width="60%"
            class="fs_dialog"
            :append-to-body="true"
        >
            <o-auth2-settings
                v-if="selectedDriver && dialogVisible"
                @driverRemoved="handleDriverRemoved(selectedDriverName)"
                :driver_name="selectedDriverName"
                :driver="selectedDriver"
            />
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import OAuth2Settings from './OAuth2Settings.vue';

export default {
    name: 'UploadIntegrationView',
    components: {
        OAuth2Settings
    },
    data() {
        return {
            availableDrivers: {},
            enabled_driver: 'local',
            title: '',
            loading: false,
            saving: false,
            dialogVisible: false,
            hasCode: false,
            selectedDriver: null,
            selectedDriverName: '',
        }
    },
    methods: {
        fetchUploadSettings() {
            this.loading = true;
            this.$get('settings/remote-upload-settings')
                .then(response => {
                    this.availableDrivers = response.drivers;
                    this.enabled_driver = response.enabled_driver;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        updateDriver(driverName) {
            // Don't allow disabling local if it's the only active driver
            if (driverName === 'local' && this.enabled_driver === 'local') {
                return;
            }

            // If trying to disable the current driver, switch to local
            if (this.enabled_driver === driverName && driverName !== 'local') {
                driverName = 'local';
            }

            this.saving = true;
            this.$post('settings/update-remote-upload-driver', {
                driver: driverName
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });
                    this.enabled_driver = driverName;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        showConfigDialog(driver, driverName) {
            this.selectedDriverName = driverName;
            this.selectedDriver = driver;
            this.title = 'Configure ' + this.selectedDriver.title + ' Settings';
            this.dialogVisible = true;
        },
        closeDialog() {
            this.fetchUploadSettings();
            this.dialogVisible = false;
            this.selectedDriver = null;
            this.selectedDriverName = '';
        },
        handleDriverRemoved(driverName) {
            if(driverName == this.enabled_driver) {
                this.enabled_driver = 'local';
                this.updateDriver('local');
            }
            this.closeDialog();
        }
    },
    mounted() {
        this.fetchUploadSettings();
        if (this.$route.query.code) {
            this.hasCode = true;
        }
        this.$setTitle('File Storage Settings');
    }
}
</script>
