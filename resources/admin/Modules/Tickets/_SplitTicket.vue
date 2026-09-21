<template>
    <el-form :data="ticket_data" label-position="top">
        <el-form-item :label="$t('Ticket Title')" class="fs_form_item">
            <el-input v-model="ticket_data.title" :placeholder="$t('Ticket Title')" />
        </el-form-item>
        <el-form-item :label="$t('Ticket Content')" class="fs_form_item">
            <wp-editor v-model="ticket_data.content" />
        </el-form-item>

        <el-row :gutter="30">
            <el-col :md="12" :xs="24">
                <el-form-item :label="$t('Ticket Priority')" class="fs_form_item">
                    <el-select v-model="ticket_data.client_priority" :placeholder="$t('Ticket Priority')">
                        <el-option v-for="(priorityValue, priorityKey) in priorities" :key="priorityKey" :value="priorityKey" :label="priorityValue"></el-option>
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
                <el-form-item :label="$t('Ticket Product')" v-if="products.length" class="fs_form_item">
                    <el-select v-model="ticket_data.product_id" filterable clearable :placeholder="$t('Ticket Product')">
                        <el-option v-for="product in products" :key="product.id" :value="product.id" :label="product.title"></el-option>
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
                <el-form-item :label="$t('Ticket Mailbox')" v-if="mailboxes.length" class="fs_form_item">
                    <el-select v-model="ticket_data.mailbox_id" :placeholder="$t('Ticket Mailbox')">
                        <el-option v-for="mailbox in mailboxes" :key="mailbox.id" :value="mailbox.id" :label="mailbox.name"></el-option>
                    </el-select>
                </el-form-item>
            </el-col>
        </el-row>

        <el-form-item>
            <el-button class="fs_filled_btn" @click="splitTicket" :disabled=" spliting || !ticket_data.title " v-loading="spliting" type="primary">
                {{ $t('Split Ticket') }}
            </el-button>
        </el-form-item>
    </el-form>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';
export default {
    name: "SplitTicket",
    components: { WpEditor },
    props: {
        ticket_data: {
            type: Object,
            required: true,
        },
        spliting: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            priorities: this.appVars.client_priorities,
            products: this.appVars.support_products,
            mailboxes: this.appVars.mailboxes,
        };
    },

    methods: {
        splitTicket() {
            this.$emit('split-ticket');
        },
    },
};
</script>
