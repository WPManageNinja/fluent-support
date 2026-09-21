<template>
    <div class="fs_ticket_importer">
        <div class="fs_box_wrapper">
            <div class="fs_box_header">
                <div class="fs_box_head">
                    <h3>{{ $t('Ticket Migration Settings') }}</h3>
                </div>
            </div>
            <div v-if="!loading" class="fs_box_body">
                <div v-if="settings.length" class="fs_importer_list">
                    <div v-for="(setting, index) in settings" :key="setting.handler" class="fs_importer_item">
                        <div class="fs_importer_item_content">
                            <div class="fs_importer_content">
                                <h3 class="fs_importer_title">
                                    {{ setting.name }}
                                    <span v-if="setting.last_migrated" class="fs_last_migration_inline">
                                        ({{ $t('Last migration:') }} {{ setting.last_migrated }})
                                    </span>
                                </h3>
                                <p class="fs_importer_description">
                                    <template v-if="setting?.type=='sass'">
                                        {{ $t('Migrate tickets from') }} {{ setting.name }} {{ $t('in one click.') }}
                                    </template>
                                    <template v-else>
                                        {{ $t('This migrator will migrate total') }} <strong>{{ setting.tickets }}</strong> {{ $t('tickets with') }} <strong>{{ setting.replies }}</strong> {{ $t('replies and') }} <strong>{{ setting.customers }}</strong> {{ $t("customers. If you already migrate tickets then it won't migrate existing tickets.") }}
                                    </template>
                                </p>


                            </div>

                            <div class="fs_importer_actions">
                                <template v-if="imporing && currently_importing == setting.handler">
                                    <el-button disabled plain>
                                        {{ $t('Importing...') }}
                                    </el-button>
                                </template>
                                <template v-else-if="deleting && currently_importing == setting.handler">
                                    <el-button disabled plain>
                                        {{ $t('Deleting...') }}
                                    </el-button>
                                </template>
                                <template v-else>
                                    <el-button
                                        v-if="setting.type=='sass'"
                                        class="fs_outline_btn"
                                        @click="(openSettings=true)&&(currently_importing=setting.handler)"
                                        plain>
                                        {{ $t('Import Tickets') }}
                                    </el-button>
                                    <el-button
                                        v-else
                                        class="fs_outline_btn"
                                        @click="importTickets(setting.handler)"
                                        plain>
                                        {{ $t('Import Tickets') }}
                                    </el-button>
                                </template>
                            </div>
                        </div>

                            <!-- Progress bars -->
                        <div class="fs_progress_wrap">
                            <div v-if="imporing && currently_importing == setting.handler" class="fs_progress_container">
                                <div class="fs_progress_line">
                                    <div class="fs_progress_text_wrapper">
                                        <span class="fs_progress_text">{{ $t('Migrating...') }}</span>
                                        <span class="fs_progress_percentage">{{ completed }}%</span>
                                    </div>

                                    <div class="fs_progress_bar_wrapper">
                                        <el-progress
                                            :text-inside="false"
                                            :stroke-width="8"
                                            :percentage="completed"
                                            :show-text="false"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="deleting && currently_importing == setting.handler"  class="fs_progress_container">
                                <div class="fs_progress_line">
                                    <span class="fs_progress_text">{{ $t('Deleting...') }}</span>
                                    <div class="fs_progress_bar_wrapper">
                                        <el-progress
                                            :stroke-width="8"
                                            :percentage="50"
                                            status="exception"
                                            :indeterminate="true"
                                            :show-text="false"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Separator line (except for last item) -->
                        <hr v-if="index < settings.length - 1" class="fs_divider">
                    </div>
                </div>

                <div v-else class="fs_no_importers">
                    <h2>{{ $t('Import from other Support Tickets Plugins') }}</h2>
                    <p>{{ $t('If you want to migrate tickets from other ticketing system like') }} <strong>{{ $t('Awesome Support') }}</strong> {{ $t('or') }} <strong>{{ $t('Support Candy') }}</strong> {{ $t('WordPress plugin then you can migrate from this section.') }}</p>
                    <p>{{ $t('Currently no migration is available for this site') }}</p>
                </div>
            </div>

            <div class="fs_box_body" v-else>
                <el-skeleton class="fs_skeleton" :rows="5" animated/>
            </div>

            <!-- Delete confirmation dialog -->
            <el-dialog v-if="!had_tickets" v-model="import_done" :title="$t('Delete Imported Tickets')" class="fs_dialog">
                <span>{{ $t('Do you want to delete all imported tickets and its data?') }}</span>
                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="import_done = false" type="primary">{{ $t('No') }}</el-button>
                        <el-button type="danger" @click="deleteOldTicketsWithData(currently_importing)">
                            {{ $t('Yes') }}
                        </el-button>
                    </span>
                </template>
            </el-dialog>

            <!-- Importer configuration modals -->
            <el-dialog
                v-if="currently_importing=='helpscout'"
                v-model="openSettings"
                :title="$t('Configure Help Scout Import')"
                width="60%"
                class="fs_dialog"
            >
                <help-scout-importer
                    :show="openSettings"
                    :settings="config"
                    :previously_imported="previous_migration_data.helpscout?.previously_imported"
                    @restart_previous_migration="restartTicketMigration('helpscout')"
                    @import="importTickets(currently_importing)"
                    @close="openSettings=false"
                />
            </el-dialog>

            <el-dialog
                v-if="currently_importing=='freshdesk'"
                v-model="openSettings"
                :title="$t('Configure FreshDesk Import')"
                width="60%"
                class="fs_dialog"
            >
                <fresh-desk-importer
                    :show="openSettings"
                    :settings="config"
                    :previously_imported="previous_migration_data.freshdesk?.previously_imported"
                    @restart_previous_migration="restartTicketMigration('freshdesk')"
                    @import="importTickets(currently_importing)"
                    @close="openSettings=false"
                />
            </el-dialog>

            <el-dialog
                v-if="currently_importing=='zendesk'"
                v-model="openSettings"
                :title="$t('Configure Zendesk Import')"
                width="60%"
                class="fs_dialog"
            >
                <zendesk-importer
                    :show="openSettings"
                    :settings="config"
                    :previously_imported="previous_migration_data.zendesk?.previously_imported"
                    @restart_previous_migration="restartTicketMigration('zendesk')"
                    @import="importTickets(currently_importing)"
                    @close="openSettings=false"
                />
            </el-dialog>
        </div>
    </div>
</template>

<script type="text/babel">
import HelpScoutImporter from './HelpScout/HelpScoutImporter.vue';
import FreshDeskImporter from './FreshDesk/FreshDeskImporter.vue';
import ZendeskImporter from './Zendesk/ZendeskImporter';
export default {
    name: 'TicketImporter',
    components: {
        HelpScoutImporter,
        FreshDeskImporter,
        ZendeskImporter
    },
    data() {
        return {
            settings: {},
            imporing: false,
            import_page: 1,
            total_tickets: 0,
            completedPercentage: 0,
            completed: 0,
            loading: false,
            currently_importing: '',
            import_done: false,
            deleting: false,
            delete_page: 1,
            openSettings: false,
            config:{},
            sass_systems: ['helpscout', 'freshdesk','zendesk'],
            import_from_sass: false,
            had_tickets: true,
            previous_migration_data: []
        }
    },

    methods: {
        getImporterIcon(handler) {
            // Use the existing importer icon for all importers
            return this.appVars.asset_url + '/images/importer.svg';
        },
        fetchSettings() {
            this.loading = true;
            this.$get('ticket_importer').then((response) => {
                this.settings = response.stats;
                this.settings.forEach((setting) => {
                    const handler = setting.handler;
                    this.previous_migration_data[handler] = setting;
                });
            })
                .catch((e) => {
                    this.$handleError(e);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        restartTicketMigration(handler) {
            const previousData = this.previous_migration_data[handler].previously_imported;
            this.import_page = previousData.next_page;
            if (previousData.cursor) {
                this.config.cursor = previousData.cursor;
            }
            if (previousData.next_page_url) {
                this.config.next_page_url = previousData.next_page_url;
            }
            if (previousData.include_archived) {
                this.config.include_archived = previousData.include_archived;
            }
            this.importTickets(handler);
        },
        importTickets(handler) {
            this.imporing = true;

            if (this.openSettings) {
                this.openSettings = false;
            }

            this.currently_importing = handler;
            let query = {
                handler: handler,
                page: this.import_page
            };

            if (this.config){
                query.query = {
                    access_token: this.config.access_token,
                    mailbox: this.config.mailbox_id,
                };
            }

            if(this.config.domain){
                query.query.domain = this.config.domain;
            }

            if(this.config.email){
                query.query.email = this.config.email;
            }

            if(this.config.cursor){
                query.query.cursor = this.config.cursor;
            }

            if(this.config.next_page_url){
                query.query.next_page_url = this.config.next_page_url;
            }

            if(this.config.include_archived){
                query.query.include_archived = true;
            }

            this.$post('ticket_importer/import', query)
                .then(response => {
                    if(response.error){
                        this.$notify({
                            type: 'error',
                            message: response.message,
                            position: 'bottom-right'
                        })
                        this.imporing = false;
                        return;
                    }

                    if (response.has_more) {
                        this.import_page = response.next_page;
                        this.total_tickets = response.total_tickets;
                        this.completed = response.completed;
                        if (response.cursor) {
                            this.config.cursor = response.cursor;
                        }
                        if (response.next_page_url) {
                            this.config.next_page_url = response.next_page_url;
                        }
                        this.$nextTick(() => {
                            this.importTickets(handler);
                        });
                    } else {
                        this.$notify({
                            type: 'success',
                            message: response.message,
                            position: 'bottom-right'
                        })
                        this.imporing = false;

                        if(response.had_tickets){
                            this.had_tickets = response.had_tickets == 'yes' ? false : true;
                        }

                        if(!this.sass_systems.includes(handler)){
                            this.import_done = true;
                        } else{
                            this.config = {};
                            this.import_from_sass = true;
                        }
                        this.import_page = 1;

                        this.fetchSettings();
                    }
                })
                .catch((error) => {
                    this.$handleError(error);
                })
        },
        deleteOldTicketsWithData(handler) {
            this.deleting = true;
            this.import_done = false;
            this.currently_importing = handler;

            this.$del('ticket_importer/delete', {
                page: this.delete_page,
                handler: handler
            })
                .then(response => {
                    if (response.has_more) {
                        this.delete_page = response.next_page;
                        this.$nextTick(() => {
                            this.deleteOldTicketsWithData(handler);
                        });
                    } else {
                        this.$notify({
                            type: 'success',
                            message: response.message,
                            position: 'bottom-right'
                        })
                        this.deleting = false;
                        this.fetchSettings();
                    }
                })
                .catch(e => {
                    this.$handleError(e);
                })
        }
    },

    mounted() {
        this.fetchSettings();
        this.$setTitle('Ticket Migrator');
    }
}
</script>

<style lang="scss" scoped>

</style>
