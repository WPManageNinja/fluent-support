<template>
    <div class="fs_add_trigger">
        <h3 class="fs_select_field_label" :class="actions_param_size ? 'fs_mt-16' : ''">{{ $t("Select Action") }}</h3>
        <ul class="fs_provider_selectors">
            <el-select @change="actionSuccess()" v-model="action.action_name" class="fs_select_field">
                <el-option
                    v-for="(action, actionName) in all_actions"
                    :value="actionName"
                    :label="action.title"
                ></el-option>
            </el-select>
        </ul>
    </div>
</template>

<script type="text/babel">
export default {
    name: "ActionAdder",
    props: ["all_actions", "actions_param_size"],
    emits: ["success"],
    data() {
        return {
            action: {
                title: "",
                action_name: "",
                settings: {},
            },
        };
    },
    computed: {
        selectedProvider() {
            const selectedProviderKey = this.action.action_provider;
            if (!selectedProviderKey) {
                return false;
            }

            return this.all_actions[selectedProviderKey];
        },
    },
    methods: {
        actionSuccess() {
            if (this.action.action_name) {
                let defaultSettings = {};
                if (
                    this.all_actions[this.action.action_name]
                        .settings_defaults
                ) {
                    defaultSettings = JSON.parse(
                        JSON.stringify(
                            this.all_actions[this.action.action_name]
                                .settings_defaults
                        )
                    );
                }
                this.action.is_open = true;
                this.action.settings = defaultSettings;
                this.$emit("success", this.action);
                setTimeout(() => {
                    this.action = {
                        title: "",
                        action_name: "",
                        settings: {},
                    };
                }, 200);
            }
        },
    },
};
</script>
