<template>
    <div v-loading="loading" class="fs_activity_settings">
        <el-form label-position="top">
            <el-form-item class="fs_form_item" :label="$t('Automatically delete AI activity logs after days')">
                <el-input
                    type="number"
                    min="1"
                    max="60"
                    v-model="ai_activity_settings.delete_days"
                    class="fs_text_input"
                />
            </el-form-item>
            <el-form-item class="fs_form_item fs_mt-10">
                <el-checkbox
                    true-label="yes"
                    false-label="no"
                    v-model="ai_activity_settings.disable_logs"
                >
                    {{ $t("Disable Activity Logs") }}
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
    name: 'AIActivitySettings',
    emits: ['updated'],
    data() {
        return {
            loading: false,
            saving: false,
            ai_activity_settings: {},
        }
    },
    methods: {
        async fetchSettings() {
            this.loading = true;
            await this.$get('ai-activity-logger/settings')
                .then(response => {
                    this.ai_activity_settings = response.ai_activity_settings;
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
            await this.$post('ai-activity-logger/settings', {
                ai_activity_settings: this.ai_activity_settings,
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
