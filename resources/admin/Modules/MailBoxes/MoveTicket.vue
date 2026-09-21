<template>
    <el-dialog
        :title="$t('Move all tickets to new Business Box')"
        v-model="move_ticket.show_modal"
        width="60%" @close="closeModal"
        class="fs_dialog fs_move_ticket_dialog">
        <el-form label-position="top">
            <el-form-item>
                <span class="fs_field_label">
                    {{ $t('Fallback Business') }}
                    <el-tooltip
                        effect="dark"
                        :content="$t('select_new_business_to_move_tickets')"
                        placement="top"
                        popper-class="fs-tooltip"
                    >
                        <el-icon> <InfoFilled /> </el-icon>
                    </el-tooltip>
                </span>
                <el-select v-model="move_ticket.fallback_box" :placeholder="$t('Select Business Box')" class="fs_select_field">
                    <el-option :disabled="mailbox.id == move_ticket.box_id" v-for="mailbox in mailboxes"
                               :key="mailbox.id" :value="mailbox.id"
                               :label="mailbox.name"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item :label="$t('All or Custom')">
                <el-select v-model="move_ticket.move_type" :placeholder="$t('Want to move all or custom selected')" @change='showTicket' class="fs_select_field">
                    <el-option v-for="_move_type in moveTypes"
                               :key="_move_type" :value="_move_type"
                               :label="_move_type"></el-option>
                </el-select>
            </el-form-item>
        </el-form>

        <template v-if="!!show_ticket_selection">
            <div class="fs_all_tickets fs_move_tickets_container" v-if="has_pro">
                <div class="fs_box_wrapper">
                    <h4>{{ $t('Filter ticket') }}</h4>
                    <hr/>
                    <div class="fs_box_head">
                        <el-form :data="filters" label-position="top">
                            <el-row :gutter="20">
                                <el-col :span="8">
                                    <el-form-item :label="$t('Select Customer')">
                                        <remote-selector
                                            v-model="filters.customer_id"
                                            response_key="customers"
                                            api_path="customers"
                                            value_selector="id"
                                            label_joiner=" - "
                                            :label_selectors="['full_name','email']"
                                            @change="customerChangeHandler"
                                            clearable
                                        />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="8">
                                    <el-form-item :label="$t('Select Product')">
                                        <remote-selector
                                            v-model="filters.product_id"
                                            response_key="products"
                                            api_path="products"
                                            value_selector="id"
                                            :label_selectors="['title']"
                                            @change="productChangeHandler"
                                            clearable
                                        />
                                    </el-form-item>
                                </el-col>
                                <el-col :span="7">
                                    <el-form-item :label="$t('Ticket Title')" class="fs_ticket_title_filter_item">
                                        <el-input v-model="filters.ticket_title" :placeholder="$t('Filter by ticket title')"
                                        @input="debouncedTicketTitleSearch" class="fs_text_input fs_ticket_title_input" />
                                    </el-form-item>
                                </el-col>
                            </el-row>
                        </el-form>
                    </div>
                    <div class="fs_box_body fs_move_ticket_list_body">
                        <div class="fs_move_ticket_header">
                            <h4>{{ $t('Select ticket that you want to move') }}</h4>
                            <span class="fs_selected_count" v-if="move_ticket.selected_tickets.length">
                                {{ move_ticket.selected_tickets.length }} {{ $t('selected') }}
                            </span>
                        </div>

                        <!-- Select All Section -->
                        <div class="fs_select_all_section" v-if="tickets.length">
                            <div class="fs_select_all_container">
                                <div class="fs_select_all">
                                    <el-checkbox
                                        :indeterminate="isIndeterminate"
                                        v-model="selectAllChecked"
                                        @change="handleSelectAll"
                                    >
                                        {{ $t('Select All') }}
                                    </el-checkbox>
                                    <span class="fs_total_count">
                                        {{ move_ticket.selected_tickets.length }} {{ $t('out of') }} {{ pagination.total }} {{ $t('tickets') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket List -->
                        <div class="fs_tickets_list fs_move_tickets_list" v-loading="loading">
                            <div
                                class="fs_ticket_item"
                                v-for="ticket in tickets"
                                :key="ticket.id"
                                :class="{ 'fs_ticket_selected': move_ticket.selected_tickets.includes(ticket.id) }"
                                @click="toggleTicketSelection(ticket.id)"
                            >
                                <div class="fs_ticket_main_content">
                                    <!-- Left Side: Checkbox, Title, Author -->
                                    <div class="fs_ticket_left_section">
                                        <div class="fs_ticket_title_row">
                                            <el-checkbox
                                                @click.stop
                                                :model-value="move_ticket.selected_tickets.includes(ticket.id)"
                                                @change="toggleTicketSelection(ticket.id)"
                                            />
                                            <div class="fs_ticket_title_group">
                                                <span class="fs_ticket_title">{{ ticket.title }}</span>
                                                <span v-if="ticket.customer" class="fs_ticket_author">
                                                    by {{ ticket.customer.first_name }} {{ ticket.customer.last_name }} #{{ ticket.id }}
                                                </span>
                                                <span v-if="ticket.product && ticket.product.title" class="fs_ticket_product_badge">
                                                    {{ ticket.product.title }}
                                                </span>
                                            </div>
                                            <span v-if="ticket.status === 'new'" class="fs_status_dot fs_status_dot_new"></span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="fs_tickets_empty_state" v-if="!tickets.length && !loading">
                                <img :src="appVars.asset_url + 'images/empty.svg'" alt="">
                                <p>{{ $t('No tickets found') }}</p>
                            </div>
                        </div>

                        <div class="fs_pagination_wrapper" v-if="tickets.length">
                            <span class="fs_pagination_left">
                                <p>{{ $t('Page') }} {{ pagination.current_page }} {{ $t('of') }} {{ Math.ceil(pagination.total / pagination.per_page) }}</p>
                            </span>
                            <span class="fs_pagination_right">
                                <pagination
                                    @fetch="showTicket('Custom')"
                                    :pagination="pagination"
                                    :background="true"
                                    layout="prev, pager, next"
                                />
                            </span>
                        </div>
                    </div>
                </div>

            </div>
            <NarrowPromo
                v-else
                :heading="$t('move_ticket_by_selecting')"
                :description="$t('pro_promo')"
                :button-text="$t('Upgrade To Pro')"
                utm-content="feature_lock_move_ticket"
            />
        </template>

        <template #footer v-if="has_pro">
            <span class="fs_dialog_footer">
                <el-button @click="move_ticket.show_modal = false" class="fs_outline_btn">{{ $t('Cancel') }}</el-button>
                <el-button v-loading="moving" :disabled="moving" type="success"
                           @click="moveTicketMailBox()" class="fs_filled_btn">{{ $t('Move') }} <span v-if="move_ticket.selected_tickets.length > 0"> ( {{move_ticket.selected_tickets.length}} )</span></el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script type="text/babel">
    import Pagination from '../../Pieces/Pagination';
    import RemoteSelector from '../../Pieces/RemoteSelector';
    import NarrowPromo from "@/admin/Components/NarrowPromo.vue";
    import debounce from "lodash/debounce";

    export default {
        name: 'MoveTicket',
        components: {
            Pagination,
            RemoteSelector,
            NarrowPromo
        },
        props: ['mailbox_id', 'mailboxes'],
        emits: ['update_mailbox', 'reset_me'],
        data() {
            return {
                count: 10,
                loading: false,
                pagination: {
                    current_page: 1,
                    total: 0,
                    per_page: 10
                },
                move_ticket: {
                    show_modal: this.mailbox_id ? true : false,
                    box_id: this.mailbox_id,
                    fallback_box: '',
                    move_type: 'All',
                    selected_tickets: []
                },
                show_ticket_selection: false,
                tickets: [],
                filters: {
                    status_type: '',
                    product_id: '',
                    mailbox_id: this.mailbox_id,
                    customer_id: '',
                    ticket_title: '',
                    notes: ''
                },
                customers: [],
                products: [],
                filtered: false,
                moving: false,
                moveTypes: ['All', 'Custom']
            }
        },
        computed: {
            selectAllChecked: {
                get() {
                    return this.tickets.length > 0 && this.move_ticket.selected_tickets.length === this.tickets.length;
                },
                set() {
                    // handled by handleSelectAll method
                }
            },
            isIndeterminate() {
                return this.move_ticket.selected_tickets.length > 0 && this.move_ticket.selected_tickets.length < this.tickets.length;
            }
        },
        created() {
            this.debouncedTicketTitleSearch = debounce(() => {
                this.pagination.current_page = 1;
                this.showTicket('Custom');
            }, 350);
        },
        methods: {
            load() {
                this.count += 5;
            },
            closeModal() {
                this.$emit('reset_me');
            },
            customerChangeHandler(val) {
                this.filters.customer_id = val ?? '';
                this.pagination.current_page = 1;
                this.showTicket('Custom');
            },
            productChangeHandler(val) {
                this.filters.product_id = val ?? '';
                this.pagination.current_page = 1;
                this.showTicket('Custom');
            },
            showTicket(moveType) {
                if (moveType === 'Custom') {
                    this.loading = true;
                    let query = {
                        order_by: 'id',
                        order_type: 'DESC',
                        filter_type: 'simple',
                        filters: this.filters,
                        page: this.pagination.current_page,
                        per_page: this.pagination.per_page,
                    };

                    this.$get(`mailboxes/${this.mailbox_id}/tickets`, query)
                        .then(response => {
                            this.tickets = response.tickets.data;
                            this.pagination.total = response.tickets.total;
                            this.show_ticket_selection = true;
                        })
                        .catch((errors) => {
                            this.$handleError(errors);
                        })
                        .always(() => {
                            this.loading = false;
                        });
                } else {
                    this.move_ticket.selected_tickets = [];
                    this.show_ticket_selection = false;
                    this.pagination.total = 0;
                }
            },
            setPage(val) {
                this.page = val;
            },
            toggleTicketSelection(ticketId) {
                const index = this.move_ticket.selected_tickets.indexOf(ticketId);
                if (index > -1) {
                    this.move_ticket.selected_tickets.splice(index, 1);
                } else {
                    this.move_ticket.selected_tickets.push(ticketId);
                }
            },
            handleSelectAll(checked) {
                if (checked) {
                    this.move_ticket.selected_tickets = this.tickets.map(ticket => ticket.id);
                } else {
                    this.move_ticket.selected_tickets = [];
                }
            },
            moveTicketMailBox() {
                if (!this.move_ticket.fallback_box || !this.move_ticket.box_id) {
                    alert(this.$t('Please provide fallback box'));
                    return false;
                }
                if (!this.move_ticket.move_type) {
                    alert(this.$t('Please provide You want to move all or custom selected tickets?'));
                    return false;
                }

                if (this.move_ticket.move_type == 'Custom' && Object.keys(this.move_ticket.selected_tickets).length < 1) {
                    alert(this.$t('Please select ticket first'));
                    return false;
                }

                this.moving = true;
                this.$put(`mailboxes/${this.move_ticket.box_id}/move_tickets`, {
                    new_box_id: this.move_ticket.fallback_box,
                    move_type: this.move_ticket.move_type,
                    ticket_ids: this.move_ticket.selected_tickets
                })
                    .then(response => {
                        this.show_ticket_selection = false;
                        this.move_ticket = {
                            show_modal: false,
                            box_id: this.mailbox_id,
                            fallback_box: '',
                            move_type: 'All',
                            selected_tickets: []
                        };
                        this.$notify({
                            type: 'success',
                            message: response.message,
                            position: 'bottom-right'
                        });
                        this.$emit('update_mailbox');
                    })
                    .catch((errors) => {
                        this.$handleError(errors);
                    })
                    .always(() => {
                        this.moving = false;
                    });
            },
            previewMove() {
                if (!this.move_ticket.fallback_box) {
                    alert(this.$t('Please select a fallback business box'));
                    return;
                }

                const selectedCount = this.move_ticket.move_type === 'All' ?
                    'all tickets' :
                    `${this.move_ticket.selected_tickets.length} selected tickets`;

                alert(`Preview: Moving ${selectedCount} to the selected business box`);
            }
        }
    }
</script>

<style lang="scss">
.fs_move_ticket_dialog {
    .fs_box_wrapper{
        h4{
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--fs-text-primary, #0E121B);
        }
    }
    .fs_ticket_product_badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        background: var(--fs-bg-subtle, #F5F7FA);
        border: 1px solid var(--fs-border-default, #E1E4EA);
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        color: var(--fs-text-secondary, #525866);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .fs_move_ticket_list_body {
        .fs_move_ticket_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--fs-border-default, #E1E4EA);
            h4 {
                margin: 0;
                font-size: 14px;
                font-weight: 600;
                color: var(--fs-text-primary, #0E121B);
            }

            .fs_selected_count {
                font-size: 13px;
                color: var(--fs-text-secondary, #525866);
                background: var(--fs-bg-subtle, #F5F7FA);
                padding: 4px 12px;
                border-radius: 12px;
            }
        }

        .fs_select_all_section {
            padding: 10px 0;
            margin-bottom: 0;
            .fs_select_all_container {
                display: flex;
                align-items: center;
                justify-content: space-between;

                .fs_select_all {
                    display: flex;
                    align-items: center;
                    gap: 12px;

                    .fs_total_count {
                        font-size: 12px;
                        color: var(--fs-text-secondary, #525866);
                    }
                }
            }
        }

        .fs_move_tickets_list {
            max-height: 350px;
            overflow-y: auto;
            border-radius: 8px;

            .fs_ticket_item {
                cursor: pointer;
                transition: background-color 0.15s ease;

                &.fs_ticket_selected {
                    background: var(--fs-state-information-light, #EFF6FF);
                }

                &:hover {
                    background: var(--bg-soft-50, #F8F9FA);
                }

                &.fs_ticket_selected:hover {
                    background: var(--fs-state-information-light, #DBEAFE);
                }
            }

            .fs_tickets_empty_state {
                padding: 40px 20px;
                text-align: center;

                img {
                    width: 80px;
                    height: 80px;
                    opacity: 0.6;
                    margin-bottom: 12px;
                }

                p {
                    color: var(--fs-text-secondary, #525866);
                    font-size: 14px;
                    margin: 0;
                }
            }
        }

        .fs_pagination_wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0 0;
            margin-top: 12px;
            border-top: 1px solid var(--fs-border-default, #E1E4EA);

            .fs_pagination_left p {
                margin: 0;
                font-size: 13px;
                color: var(--fs-text-secondary, #525866);
            }
        }
    }
}
</style>
