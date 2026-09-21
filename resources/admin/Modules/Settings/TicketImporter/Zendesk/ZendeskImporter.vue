<template>
    <div class="fs_zendesk_importer">
        <el-form :data="settings" label-position="top">
            <el-form-item :label="$t('Zendesk Domain')" class="fs_form_item">
                <el-input class="fs_text_input" v-model="settings.domain" :placeholder="$t('Zendesk Domain')"/>
            </el-form-item>
            <el-form-item :label="$t('Email Address')" class="fs_form_item">
                <el-input class="fs_text_input" type="email" v-model="settings.email" :placeholder="$t('Email Address')"/>
            </el-form-item>
            <el-form-item :label="$t('API Key')" class="fs_form_item">
                <el-input class="fs_text_input" v-model="settings.access_token" :placeholder="$t('API Key')"/>
            </el-form-item>
            <el-form-item class="fs_form_item">
                <el-checkbox
                    v-model="settings.include_archived"
                    :label="$t('Include archived tickets')"
                    size="large"
                />
            </el-form-item>
        </el-form>

        <!-- Footer actions -->
        <div class="fs_importer_footer">
            <el-checkbox
                v-if="hasPreviousMigration"
                v-model="start_from_previous_migration"
                :label="$t('An incomplete migration exists. Would you like to resume from the previous one?') + ' (' + previously_imported.completed + '% ' + $t('completed') + ')'"
                size="large"
            />
            <div class="fs_importer_actions">
                <el-button class="fs_outline_btn" @click="$emit('close')">{{ $t('Cancel') }}</el-button>
                <el-button
                    v-if="!start_from_previous_migration"
                    class="fs_filled_btn"
                    @click="$emit('import')"
                    :disabled="!settings.domain || !settings.email || !settings.access_token">
                    {{ $t('Import Tickets') }}
                </el-button>
                <el-button
                    v-if="start_from_previous_migration"
                    class="fs_filled_btn"
                    @click="$emit('restart_previous_migration')"
                    :disabled="!settings.domain || !settings.email || !settings.access_token">
                    {{ $t('Resume Previous Migration') }}
                </el-button>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    name: 'ZendeskImporter',
    props: ['show', 'settings', 'previously_imported'],
    emits: ['import', 'close', 'restart_previous_migration'],
    data() {
        return {
            mailboxes: {},
            start_from_previous_migration: false,
        };
    },
    computed: {
        hasPreviousMigration() {
            return this.previously_imported
                && Object.keys(this.previously_imported).length > 0
                && this.previously_imported.domain
                && this.settings.domain === this.previously_imported.domain;
        }
    }
}
</script>
