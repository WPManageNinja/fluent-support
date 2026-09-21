<template>
    <div class="fs_freshdesk_importer">
        <el-form :data="settings" label-position="top">
            <el-form-item :label="$t('Freshdesk Domain')" class="fs_form_item">
                <el-input class="fs_text_input" v-model="settings.domain" :placeholder="$t('Freshdesk Domain')" />
            </el-form-item>
            <el-form-item :label="$t('API Key')" class="fs_form_item">
                <el-input class="fs_text_input" v-model="settings.access_token" :placeholder="$t('API Key')" />
            </el-form-item>
        </el-form>

        <!-- Footer actions -->
        <div class="fs_importer_footer">
            <el-checkbox
                v-if="Object.keys(previously_imported).length > 0 && settings.domain == previously_imported.domain"
                v-model="start_from_previous_migration"
                :label="$t('An incomplete migration exists. Would you like to resume from the previous one?')"
                size="large"
            />
            <div class="fs_importer_actions">
                <el-button class="fs_outline_btn" @click="$emit('close')">{{ $t('Cancel') }}</el-button>
                <el-button
                    v-if="!start_from_previous_migration"
                    class="fs_filled_btn"
                    @click="$emit('import')"
                    :disabled="!settings.domain || !settings.access_token">
                    {{ $t('Import Tickets') }}
                </el-button>
                <el-button
                    v-if="start_from_previous_migration"
                    class="fs_filled_btn"
                    @click="$emit('restart_previous_migration')"
                    :disabled="!settings.domain || !settings.access_token">
                    {{ $t('Resume Previous Migration') }}
                </el-button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FreshDeskImporter',
    props: ['show', 'settings', 'previously_imported'],
    emits: ['import', 'close', 'restart_previous_migration'],
    data() {
        return {
            mailboxes: {},
            start_from_previous_migration: false,
        }
    },
    methods: {
        getAccessToken() {
            let url = 'https://api.helpscout.net/v2/oauth2/token?grant_type=authorization_code&code=' + this.settings.code +'&client_id='+ this.settings.app_id +'&client_secret=' + this.settings.app_secret;
            const request = {
                url: url,
                method: 'POST',
                body: JSON.stringify({
                    grant_type: 'authorization_code',
                    code: this.settings.code,
                    client_id: this.settings.app_id,
                    client_secret: this.settings.app_secret,
                }),
            };

            jQuery.ajax(request).done(response=> {
                this.settings.access_token = response.access_token;
                this.settings.refresh_token = response.refresh_token;
                this.settings.token_type = response.token_type;
                this.settings.expires_in = response.expires_in;
                if (this.settings.access_token) {
                    this.getMailboxes();
                }
            }).fail(error => {
                this.$handleError(error);
            })
        },
        getMailboxes() {
            const that = this;
            const settings = {
                url: 'https://api.helpscout.net/v2/mailboxes',
                method: "GET",
                headers: {
                    Authorization: "Bearer " + this.settings.access_token
                },
            };

            jQuery.ajax(settings).done(response => {
                that.mailboxes = response._embedded.mailboxes;
            }).fail(error => {
                this.$handleError(error);
            });
        },
        handleCancel() {
            this.show = false;
        },
    }
}
</script>

