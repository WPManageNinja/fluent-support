<template>
    <div class="fs_box_wrapper">
        <div class="fs_box_header">
            <div class="fs_box_head">
                <h3>{{ $t('MCP for AI Agents') }}</h3>
            </div>
        </div>

        <div v-if="fetching" class="fs_box_body fs_skeleton_loader">
            <el-skeleton :animated="true" :rows="5" />
        </div>

        <div v-else-if="loadFailed" class="fs_box_body fs_mcp_load_error">
            <p>{{ $t('Could not load MCP settings. The controls below are hidden until the server state is confirmed.') }}</p>
            <div class="fs_mcp_install_actions">
                <el-button type="primary" size="small" class="fs_filled_btn" @click="fetchStatus">{{ $t('Retry') }}</el-button>
            </div>
        </div>

        <div v-else class="fs_box_body fs_mcp_body">

            <!-- Enable toggle (always visible; adapter state does not disable it) -->
            <div class="fs_mcp_toggle_row">
                <div class="fs_mcp_toggle_text">
                    <p class="fs_mcp_toggle_title">{{ $t('Enable MCP for AI Agents') }}</p>
                    <p class="fs_mcp_toggle_desc">{{ $t('When enabled, Fluent Support exposes its tools through the WordPress Abilities API so AI agents can read and update support tickets with your authorization.') }}</p>
                </div>
                <el-switch
                    v-model="mcpEnabled"
                    :loading="toggling"
                    @change="handleToggle"
                />
            </div>

            <div class="fs_mcp_divider" />

            <!-- MCP ENABLED: show adapter status or install prompt -->
            <template v-if="mcpEnabled">

                <!-- Adapter not yet active -->
                <template v-if="!adapterAvailable">
                    <p class="fs_mcp_help_text">
                        {{ $t('Fluent Support ships AI agent tools, but they only become available once FluentHub is installed and active.') }}
                    </p>
                    <div class="fs_mcp_install_actions">
                        <el-button
                            v-if="canAutoInstall"
                            type="primary"
                            class="fs_filled_btn"
                            :loading="installing"
                            @click="installAdapter"
                        >
                            {{ toolkitInstalled ? $t('Activate FluentHub') : $t('Install FluentHub') }}
                        </el-button>
                        <template v-else>
                            <a
                                :href="toolkitDownloadUrl || pluginsUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="el-button el-button--primary el-button--default fs_filled_btn"
                            >
                                {{ $t('Get FluentHub') }}
                            </a>
                            <span class="fs_mcp_pro_tip">{{ $t('Tip: Fluent Support Pro can install it for you in one click.') }}</span>
                        </template>
                    </div>
                </template>

                <!-- Adapter active: status row + endpoint + connect section -->
                <template v-if="adapterAvailable">

                    <!-- Status: Connected -->
                    <div class="fs_mcp_status_row">
                        <div class="fs_mcp_status_info">
                            <span class="fs_mcp_status_label">{{ $t('Adapter') }}</span>
                            <span v-if="toolkitVersion" class="fs_mcp_status_sublabel">FluentHub {{ toolkitVersion }}</span>
                        </div>
                        <span class="fs_mcp_connected_badge">{{ $t('Connected') }}</span>
                    </div>

                    <!-- Endpoint URL -->
                    <div class="fs_mcp_field_group">
                        <label class="fs_mcp_label">{{ $t('Endpoint URL') }}</label>
                        <el-input :model-value="endpointUrl" readonly>
                            <template #append>
                                <el-button @click="copyEndpoint">{{ $t('Copy') }}</el-button>
                            </template>
                        </el-input>
                        <span class="fs_mcp_status_note">
                            <strong>{{ toolsCount }}</strong> {{ $t('tools registered') }}
                        </span>
                    </div>

                    <div class="fs_mcp_divider" />

                    <!-- Connect a client -->
                    <div class="fs_mcp_connect_section">
                        <h4 class="fs_mcp_section_title">{{ $t('Connect a client') }}</h4>
                        <p class="fs_mcp_help_text">
                            {{ $t('Create a WordPress application password for your account, enter it below, then copy the snippet for your AI client.') }}
                            <a :href="appPasswordsUrl" target="_blank" rel="noopener noreferrer">{{ $t('Create an application password') }}</a>
                        </p>

                        <div class="fs_mcp_creds_row">
                            <el-input
                                v-model="username"
                                :placeholder="$t('WordPress username')"
                            />
                            <el-input
                                v-model="appPassword"
                                type="password"
                                show-password
                                :placeholder="$t('Application password')"
                            />
                        </div>

                        <el-tabs v-model="activeClient">
                            <el-tab-pane
                                v-for="client in clients"
                                :key="client.key"
                                :label="client.label"
                                :name="client.key"
                            />
                        </el-tabs>

                        <div v-if="activeClient === 'claude-desktop'" class="fs_mcp_localdev">
                            <el-checkbox v-model="localDev" @change="loadSnippets">
                                {{ $t('Local / self-signed site (disable TLS verification)') }}
                            </el-checkbox>
                        </div>

                        <p v-if="activeSnippet && activeSnippet.instructions" class="fs_mcp_help_text fs_mcp_instructions">
                            {{ activeSnippet.instructions }}
                        </p>

                        <el-skeleton v-if="snippetLoading" :rows="4" animated />

                        <template v-else-if="activeSnippet">
                            <el-input
                                type="textarea"
                                :rows="7"
                                :model-value="renderedSnippet"
                                readonly
                            />
                            <div class="fs_mcp_copy_action">
                                <el-button
                                    type="primary"
                                    class="fs_filled_btn"
                                    :disabled="!renderedSnippet"
                                    @click="copySnippet"
                                >
                                    {{ $t('Copy snippet') }}
                                </el-button>
                            </div>
                        </template>
                    </div>

                </template>

            </template>

            <!-- MCP DISABLED: description / help text -->
            <template v-else>
                <p class="fs_mcp_disabled_desc">
                    {{ $t('MCP (Model Context Protocol) lets compatible AI clients like Claude Desktop, OpenAI Codex, and Cursor read and write your support data with your authorization. Enable the toggle above to connect an AI client to Fluent Support.') }}
                </p>
            </template>

        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'McpIntegration',

    data() {
        return {
            fetching: true,
            loadFailed: false,
            toggling: false,
            installing: false,
            snippetLoading: false,
            snippetsLoaded: false,

            mcpEnabled: false,
            adapterAvailable: false,
            toolkitInstalled: false,
            toolkitActive: false,
            toolkitVersion: null,
            canAutoInstall: false,
            toolkitDownloadUrl: '',
            pluginsUrl: '',
            endpointUrl: '',
            toolsCount: 0,
            appPasswordsUrl: '',

            username: '',
            appPassword: '',
            localDev: false,
            activeClient: 'claude-code',
            snippets: {},

            clients: [
                { key: 'claude-code',    label: 'Claude Code' },
                { key: 'claude-desktop', label: 'Claude Desktop' },
                { key: 'cursor',         label: 'Cursor' },
                { key: 'codex',          label: 'Codex' },
                { key: 'generic',        label: this.$t('Other') },
            ],
        };
    },

    computed: {
        activeSnippet() {
            return this.snippets[this.activeClient] || null;
        },

        renderedSnippet() {
            if (!this.activeSnippet || !this.activeSnippet.snippet) {
                return '';
            }
            let text = this.activeSnippet.snippet;
            const u = this.username.trim();
            const p = this.appPassword.trim();
            if (u && p) {
                try {
                    const basic = btoa(`${u}:${p}`);
                    text = text.split('<base64(your-username:application-password)>').join(basic);
                } catch (_) {
                    // btoa fails on non-latin1 characters — leave the placeholder in place
                }
                text = text.split('<your-username>').join(u);
                text = text.split('<your-application-password>').join(p);
            }
            return text;
        },
    },

    methods: {
        fetchStatus() {
            this.fetching = true;
            this.loadFailed = false;
            return this.$get('settings/mcp')
                .then((response) => {
                    this.mcpEnabled         = !!response.mcp_enabled;
                    this.adapterAvailable   = !!response.adapter_available;
                    this.toolkitInstalled   = !!response.toolkit_installed;
                    this.toolkitActive      = !!response.toolkit_active;
                    this.toolkitVersion     = response.toolkit_version || null;
                    this.canAutoInstall      = !!response.can_auto_install;
                    this.toolkitDownloadUrl = response.toolkit_download_url || '';
                    this.pluginsUrl         = response.plugins_url || '';
                    this.endpointUrl        = response.endpoint_url || '';
                    this.toolsCount         = response.tools_count || 0;
                    this.appPasswordsUrl    = response.app_passwords_url || '';
                    this.localDev           = !!response.is_local_dev;
                    if (response.current_user_login && !this.username) {
                        this.username = response.current_user_login;
                    }
                })
                .catch((errors) => {
                    this.loadFailed = true;
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },

        handleToggle(value) {
            this.toggling = true;
            this.$post('settings/mcp/toggle', { mcp_enabled: value })
                .then((response) => {
                    this.mcpEnabled = !!response.mcp_enabled;
                    this.$notify({ message: response.message, type: 'success', position: 'bottom-right' });
                    if (this.mcpEnabled && this.adapterAvailable && !this.snippetsLoaded) {
                        this.loadSnippets();
                    }
                })
                .catch((errors) => {
                    this.mcpEnabled = !value;
                    this.$handleError(errors);
                })
                .always(() => {
                    this.toggling = false;
                });
        },

        loadSnippets() {
            this.snippetLoading = true;
            this.$get('settings/mcp/config-snippets', { local_dev: this.localDev ? 'yes' : 'no' })
                .then((response) => {
                    this.snippets       = response.snippets || {};
                    this.snippetsLoaded = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.snippetLoading = false;
                });
        },

        installAdapter() {
            this.installing = true;
            this.$post('settings/mcp/install-adapter', {})
                .then((response) => {
                    const type = response.adapter_available ? 'success' : 'warning';
                    this.$notify({ message: response.message, type, position: 'bottom-right' });
                    this.toolkitInstalled = !!response.toolkit_installed;
                    this.toolkitActive    = !!response.toolkit_active;
                    if (response.adapter_available) {
                        // Reload to bring the endpoint fully online after adapter activation.
                        setTimeout(() => { window.location.reload(); }, 800);
                    } else {
                        this.installing = false;
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.installing = false;
                });
        },

        copyEndpoint() {
            this.$copyToClipboard(this.endpointUrl, this.$t('Endpoint URL copied'));
        },

        copySnippet() {
            this.$copyToClipboard(this.renderedSnippet, this.$t('Connection snippet copied'));
        },
    },

    mounted() {
        this.$setTitle(this.$t('MCP for AI Agents'));
        this.fetchStatus().then(() => {
            if (this.mcpEnabled && this.adapterAvailable) {
                this.loadSnippets();
            }
        });
    },
};
</script>

<style scoped>
/* Body layout — override the generic fs_box_body gap since we control spacing ourselves */
.fs_mcp_body {
    gap: 0 !important;
}

/* ── Load error state ────────────────────────────────────── */
.fs_mcp_load_error {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
}
.fs_mcp_load_error p {
    font-size: 13px;
    color: var(--fs-text-secondary, #525866);
    margin: 0;
}

/* ── Toggle row ──────────────────────────────────────────── */
.fs_mcp_toggle_row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    padding-bottom: 20px;
}
.fs_mcp_toggle_text {
    flex: 1;
    min-width: 0;
}
.fs_mcp_toggle_title {
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    color: var(--fs-text-primary, #0e121b);
    margin: 0 0 4px;
}
.fs_mcp_toggle_desc {
    font-size: 13px;
    line-height: 1.5;
    color: var(--fs-text-secondary, #525866);
    margin: 0;
}

/* ── Divider ─────────────────────────────────────────────── */
.fs_mcp_divider {
    width: 100%;
    height: 1px;
    background: var(--fs-border-default, #E1E4EA);
    border: none;
    margin: 0 0 20px;
}

/* ── Disabled state description ──────────────────────────── */
.fs_mcp_disabled_desc {
    font-size: 13px;
    line-height: 1.6;
    color: var(--fs-text-secondary, #525866);
    margin: 0;
}

/* ── Install actions ─────────────────────────────────────── */
.fs_mcp_install_actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

/* ── Status row (Connected badge) ────────────────────────── */
.fs_mcp_status_row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 20px;
}
.fs_mcp_status_info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.fs_mcp_status_label {
    font-size: 13px;
    font-weight: 500;
    color: var(--fs-text-primary, #0e121b);
    line-height: 20px;
}
.fs_mcp_status_sublabel {
    font-size: 12px;
    color: var(--fs-text-secondary, #525866);
    line-height: 18px;
}
.fs_mcp_connected_badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: #ecfdf3;
    color: #027a48;
    border: 1px solid #abefc6;
}
.fs_mcp_connected_badge::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #17b26a;
    flex-shrink: 0;
}

/* ── Endpoint URL field ──────────────────────────────────── */
.fs_mcp_field_group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}
.fs_mcp_label {
    font-size: 13px;
    font-weight: 500;
    color: var(--fs-text-primary, #0e121b);
}
.fs_mcp_status_note {
    font-size: 13px;
    color: var(--fs-text-secondary, #525866);
}
.fs_mcp_status_note strong {
    color: var(--fs-text-primary, #0e121b);
}

/* ── Connect a client section ────────────────────────────── */
.fs_mcp_connect_section {
    width: 100%;
}
.fs_mcp_section_title {
    font-size: 14px;
    font-weight: 600;
    color: var(--fs-text-primary, #0e121b);
    margin: 0 0 8px;
}
.fs_mcp_help_text {
    font-size: 13px;
    color: var(--fs-text-secondary, #525866);
    line-height: 1.5;
    margin: 0 0 16px;
}
.fs_mcp_instructions {
    margin-top: 12px;
    margin-bottom: 8px;
}

/* ── Credentials row ─────────────────────────────────────── */
.fs_mcp_creds_row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

/* ── Local dev checkbox ──────────────────────────────────── */
.fs_mcp_localdev {
    margin: 10px 0 14px;
}

/* ── Copy action ─────────────────────────────────────────── */
.fs_mcp_copy_action {
    margin-top: 10px;
}

/* ── Pro tip ─────────────────────────────────────────────── */
.fs_mcp_pro_tip {
    font-size: 12px;
    color: var(--fs-text-secondary, #525866);
    align-self: center;
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 640px) {
    .fs_mcp_toggle_row {
        flex-wrap: wrap;
    }
    .fs_mcp_creds_row {
        grid-template-columns: 1fr;
    }
}
</style>
