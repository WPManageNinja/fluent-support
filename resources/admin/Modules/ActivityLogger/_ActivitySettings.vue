<template>
    <div v-loading="loading" class="fs_activity_settings">
        <el-form label-position="top">
            <el-form-item :label="$t('Automatically delete activity logs after days')">
                <el-input
                    type="number"
                    min="1"
                    max="60"
                    v-model="activity_settings.delete_days"
                    class="fs_text_input fs_mb_5"
                />
            </el-form-item>
            <el-form-item class="fs_form_item fs_mt-10">
                <el-checkbox
                    true-label="yes"
                    false-label="no"
                    v-model="activity_settings.disable_logs"
                >
                    {{ $t("Disable Activity Logs") }}
                </el-checkbox>
            </el-form-item>
            <el-form-item class="fs_form_item">
                <el-checkbox
                    true-label="yes"
                    false-label="no"
                    v-model="activity_settings.open_link_in_new_tab"
                >
                    {{ $t("Open Ticket in New Tab") }}
                </el-checkbox>
            </el-form-item>
        </el-form>

        <div class="fs_dialog_footer fs_text_right">
            <el-button
                v-loading="saving"
                :disabled="saving"
                type="success"
                @click="updateSettings()"
                class="fs_filled_btn"
            >
                {{ $t("Update Settings") }}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">

export default {
    name: 'ActivitySettings',
    emits: ['updated'],
    data() {

        return {
            loading: false,
            saving: false,
            activity_settings: {},
        }
    },
    methods: {
        async fetchSettings() {
            this.loading = true;
            await this.$get('activity-logger/settings')
                .then(response => {
                    this.activity_settings = response.activity_settings;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        async updateSettings() {
            this.saving = true;
            await this.$post('activity-logger/settings', {
                activity_settings: this.activity_settings
            })
                .then(response => {
                    this.$notify({
                        type: 'success',
                        message: response.message,
                        position: 'bottom-right'
                    });

                    this.$emit('updated');
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.saving = false;
                });
        },
    },
    mounted() {
        this.fetchSettings();
    },
}
</script>
