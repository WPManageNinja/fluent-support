<template>
    <div>
        <el-select v-loading="loading" clearable filterable :placeholder="field.placeholder" v-model="modelValueLocal">
            <el-option
                v-for="(country,countryCode) in countries"
                :key="countryCode"
                :label="country"
                :value="countryCode">
            </el-option>
        </el-select>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'InputOptions',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    data() {
        return {
            countries: {},
            loading: false,
            modelValueLocal: this.modelValue
        }
    },
    watch: {
        modelValueLocal(value) {
            this.$emit('update:modelValue', value);
        }
    },
    methods: {
        fetchCountries() {
            if(window.fs_countries_options) {
                this.countries = window.fs_countries_options;
                return false;
            }

            this.loading = true;
            this.$get('options/countries')
                .then(response => {
                    window.fs_countries_options = response.countries;
                    this.countries = response.countries;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetchCountries();
    }
}
</script>
