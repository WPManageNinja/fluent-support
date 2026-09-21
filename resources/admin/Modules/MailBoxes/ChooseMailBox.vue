<template>
    <div class="fs_mailboxes">
        <div class="fs_box_wrapper">
            <div class="fs_component_header">
                <div class="fs_component_head">
                    <h3 class="fs_page_title">{{ $t("Business Inboxes") }}</h3>
                </div>
                <div class="fs_box_actions">
                    <el-button
                        class="fs_filled_btn"
                        @click="showNewBusinessModal()"
                        icon="Plus"
                    >
                        {{ $t("Add New Business Box") }}
                    </el-button>
                </div>
            </div>

            <div v-if="!fetching" class="fs_mailbox_grid">
                <div
                    v-for="box in mailboxes"
                    :key="box.id"
                    class="fs_mailbox_card"
                >
                    <div class="fs_mailbox_header">
                        <div class="fs_mailbox_title">
                            <div class="fs_title_row">
                                <h4>{{ box.name }}</h4>
                                <span v-if="box.is_default === 'yes'" class="fs_status_badge fs_status_active">
                                    {{ $t("Default") }}
                                </span>
                            </div>
                        </div>
                        <div class="fs_mailbox_header_actions">
                            <el-button
                                class="fs_settings_button"
                                @click="$router.push({
                                    name: 'box_settings',
                                    params: { box_id: box.id },
                                })"
                            >
                                <img :src="appVars.asset_url + 'images/gear.svg'"/>
                            </el-button>
                            <el-dropdown
                                @command="handleBoxCommand"
                                class="fs_mailbox_actions"
                                trigger="click"
                            >
                                <el-button class="fs_action_button" text>
                                    <img :src="appVars.asset_url + 'images/more.svg'"/>
                                </el-button>
                                <template #dropdown>
                                    <el-dropdown-menu class="fs_global_dropdown fs_table_more_actions_dropdown_menu">
                                        <el-dropdown-item
                                            v-if="'yes' !== box.is_default"
                                            :command="{
                                                type: 'set_default',
                                                box_id: box.id,
                                            }"
                                            icon="FolderChecked"
                                        >{{ $t("Set as Default") }}
                                        </el-dropdown-item>
                                        <el-dropdown-item
                                            v-if="box.tickets_count > 0"
                                            :command="{
                                                type: 'move',
                                                box_id: box.id,
                                            }"
                                            icon="EditPen"
                                        >{{ $t("Move Tickets") }}
                                        </el-dropdown-item>
                                        <el-dropdown-item
                                            :command="{
                                                type: 'delete',
                                                box_id: box.id,
                                            }"
                                            icon="Delete"
                                        >{{ $t("Delete") }}
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </div>
                    </div>

                    <div class="fs_mailbox_body">
                        <div class="fs_mailbox_field">
                            <span class="fs_mailbox_field_label">{{ $t("Email") }}</span>
                            <span class="fs_mailbox_field_value">{{ box.email }}</span>
                        </div>
                        <div class="fs_mailbox_field_row">
                            <div class="fs_mailbox_field">
                                <span class="fs_mailbox_field_label">{{ $t("Type") }}</span>
                                <span class="fs_mailbox_field_value">{{ box.box_type }}</span>
                            </div>
                            <div class="fs_mailbox_field">
                                <span class="fs_mailbox_field_label">{{ $t("Tickets") }}</span>
                                <span class="fs_mailbox_field_value">{{ box.tickets_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="fs_table_container fs_skeleton_loader">
                <el-skeleton :rows="5" animated />
            </div>
        </div>

        <el-dialog
            :title="$t('Add a New Business Inbox')"
            v-model="create_modal"
            width="60%"
            class="fs_dialog"
        >
            <el-form
                v-if="can_create_mailbox"
                :data="new_business"
                label-position="top"
            >
                <el-form-item :label="$t('Inbox Name')">
                    <el-input
                        type="text"
                        class="fs_text_input fs_text_input_40"
                        v-model="new_business.name"
                    ></el-input>
                </el-form-item>
                <el-form-item>
                    <template #label>
                        <span class="fs_field_label">
                            {{ $t('Support Inbox Email') }}
                            <el-tooltip
                                effect="dark"
                                :content="$t('email_can_be_send')"
                                placement="top"
                                popper-class="fs-tooltip"
                            >
                             <el-icon> <InfoFilled /> </el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-input
                        type="email"
                        class="fs_text_input fs_text_input_40"
                        v-model="new_business.email"
                    ></el-input>
                </el-form-item>
                <el-form-item :label="$t('Support Channel')">
                    <el-radio-group class="fs_choose_mailbox_type" v-model="new_business.box_type">
                        <el-radio value="web">{{
                            $t("Web Based")
                        }}</el-radio>
                        <el-radio
                            :disabled="!appVars.has_email_parser"
                            value="email"
                        >
                            {{ $t("email_based_heading") }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <NarrowPromo
                v-else
                :heading="$t('shared_inbox_message')"
                :description="$t('pro_promo')"
                :button-text="$t('Upgrade To Pro')"
                utm-content="feature_lock_shared_inbox"
            />

            <template #footer>
                <span v-if="can_create_mailbox" class="fs_dialog_footer">
                    <el-button class="fs_outline_btn" @click="create_modal = false">{{
                        $t("Cancel")
                    }}</el-button>
                    <el-button class="fs_filled_btn" @click="createMailBox()">{{
                        $t("Add Business Inbox")
                    }}</el-button>
                </span>
            </template>
        </el-dialog>

        <el-dialog
            :title="$t('Are You Sure? You can not undo this action.')"
            v-model="deleting_box.show_modal"
            width="60%"
            class="fs_dialog"
        >
            <el-form :data="deleting_box" label-position="top">
                <el-form-item>
                    <template #label>
                        <span class="fs_field_label">
                            {{ $t('Support Inbox Email') }}
                            <el-tooltip
                                effect="dark"
                                :content="$t('select_fallback_business')"
                                placement="top"
                                popper-class="fs-tooltip"
                            >
                             <el-icon> <InfoFilled /> </el-icon>
                            </el-tooltip>
                        </span>
                    </template>
                    <el-select
                        v-model="deleting_box.fallback_box"
                        class="fs_select_field"
                        :placeholder="
                            $t('Select related Product/Service')
                        "
                    >
                        <el-option
                            :disabled="mailbox.id == deleting_box.box_id"
                            v-for="mailbox in mailboxes"
                            :key="mailbox.id"
                            :value="mailbox.id"
                            :label="mailbox.name"
                        ></el-option>
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <span class="fs_dialog_footer">
                    <el-button class="fs_outline_btn" @click="deleting_box.show_modal = false">{{
                        $t("Cancel")
                    }}</el-button>
                    <el-button
                        v-loading="deleting"
                        :disabled="deleting"
                        class="fs_filled_btn"
                        @click="deleteMailBox()"
                        >{{
                            $t("Confirm Delete This Business")
                        }}</el-button
                    >
                </span>
            </template>
        </el-dialog>

        <template v-if="!!change_box">
            <move-ticket
                :mailbox_id="change_box"
                :mailboxes="mailboxes"
                @update_mailbox="fetch()"
                @reset_me="reset_me"
            ></move-ticket>
        </template>
    </div>
</template>

<script type="text/babel">
import MoveTicket from "./MoveTicket";
import NarrowPromo from "@/admin/Components/NarrowPromo.vue";

export default {
    name: "ChooseMailBox",
    components: {
        MoveTicket,
        NarrowPromo,
    },
    data() {
        return {
            fetching: true,
            mailboxes: [],
            create_modal: false,
            new_business: {
                box_type: "web",
                name: "",
                email: "",
                mapped_email: "",
                customer_portal_page_id: "",
            },
            creating: false,
            deleting_box: {
                show_modal: false,
                box_id: "",
                fallback_box: "",
            },
            deleting: false,
            change_box: "",
            set_default: "",
        };
    },
    computed: {
        can_create_mailbox() {
            if (this.appVars.has_pro) {
                return true;
            }
            if (this.mailboxes.length > 1) {
                return false;
            }
            return true;
        }
    },
    mounted() {
        this.fetch();
        this.$setTitle(this.$t("Business Inboxes"));
    },
    methods: {
        sortMailboxes(mailboxes) {
            return mailboxes.sort((a, b) => {
                if (a.is_default === 'yes' && b.is_default !== 'yes') {
                    return -1;
                }
                if (a.is_default !== 'yes' && b.is_default === 'yes') {
                    return 1;
                }
                return 0;
            });
        },
        fetch() {
            this.fetching = true;
            this.$get("mailboxes")
                .then((response) => {
                    // Sort mailboxes: default first, then others
                    this.mailboxes = this.sortMailboxes(response.mailboxes);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.fetching = false;
                });
        },
        update_mailbox(mailboxes) {
            this.change_box = "";
            this.mailboxes = this.sortMailboxes(mailboxes);
        },
        showNewBusinessModal() {
            this.new_business = {
                box_type: "web",
                name: "",
                email: "",
                mapped_email: "",
                customer_portal_page_id: "",
            };
            this.create_modal = true;
        },
        createMailBox() {
            this.creating = true;
            this.$post("mailboxes", {
                business: this.new_business,
            })
                .then((response) => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });

                    if (response.mailbox.box_type == "email") {
                        this.$router.push({
                            name: "email_piping",
                            params: { box_id: response.mailbox.id },
                        });
                    } else {
                        this.$router.push({
                            name: "box_settings",
                            params: { box_id: response.mailbox.id },
                        });
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.creating = false;
                });
        },
        handleBoxCommand(command) {
            if (command.type == "delete") {
                this.deleting_box.box_id = command.box_id;
                this.deleting_box.show_modal = true;
            } else if (command.type == "move") {
                this.change_box = command.box_id;
            } else if (command.type == "set_default") {
                this.setAsDefault(command.box_id);
            }
        },
        setAsDefault(boxId) {
            this.$put(`mailboxes/${boxId}/set_default`)
                .then((response) => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });
                    this.fetch();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },
        deleteMailBox() {
            this.deleting = true;
            this.$del(`mailboxes/${this.deleting_box.box_id}`, {
                fallback_id: this.deleting_box.fallback_box,
            })
                .then((response) => {
                    this.$notify({
                        type: "success",
                        message: response.message,
                        position: "bottom-right",
                    });
                    this.deleting_box.show_modal = false;
                    this.fetch();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.deleting = false;
                });
        },
        reset_me() {
            this.change_box = "";
        }
    }
};
</script>
