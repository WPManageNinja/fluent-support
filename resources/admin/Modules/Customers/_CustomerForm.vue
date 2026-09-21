<template>
    <div v-loading="loading" class="fs_customer_form">
        <form-builder :fields="basic_fields" :form-data="customer"/>
        <form-builder :fields="address_fields" :form-data="customer"/>

        <div class="fs_form_buttons" v-if="$route.params.customer_id || source === 'ticket'">
            <el-button class="fs_filled_btn" @click="updateCustomer()" type="success" v-if="customer.id">{{ $t('Update Customer') }}</el-button>
            <el-button class="fs_filled_btn" @click="createCustomer()" type="primary" v-else>{{ $t('Create Customer') }}</el-button>
        </div>
    </div>
</template>
<script type="text/babel">
import FormBuilder from '../../Pieces/FormElements/_FormBuilder';

export default {
    name: 'CustomerForm',
    props: ['customer', 'source'],
    components: {
        FormBuilder
    },
    emits: ['updated'],
    data() {
        return {
            basic_fields: {},
            address_fields: {},
            loading: false,
        }
    },
    mounted() {
        this.fetchCustomerField();
    },
    methods: {
        async createCustomer() {
            this.loading = true;
            this.$post('customers', this.customer)
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right'
                    });
                    this.$emit('updated', response.customer);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        async fetchCustomerField() {
            this.loading = true;
            this.$get('customers/customerField/' + (this.customer.id ? this.customer.id : 0),
                { user_id: this.customer.user_id ? this.customer.user_id : 0 }
            )
                .then(response => {
                    this.address_fields = response.customerField.address_fields;
                    this.basic_fields = response.customerField.basic_fields;

                    // Add required indicators for first name and email
                    if (this.basic_fields.first_name) {
                        this.basic_fields.first_name.required = true;
                    }
                    if (this.basic_fields.email) {
                        this.basic_fields.email.required = true;
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },

        async updateCustomer() {
            this.loading = true;
            this.$put(`customers/${this.customer.id}`, this.customer)
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: 'success',
                        position: 'bottom-right'
                    });
                    this.$emit('updated', response.customer);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    }
}
</script>
