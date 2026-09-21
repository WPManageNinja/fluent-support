<template>
    <div>
        <el-select :multiple="field.is_multiple" clearable filterable :placeholder="field.placeholder" v-model="modelValueLocal">
            <template v-if="field.extra_options">
                <el-option v-for="(option, optionValue) in field.extra_options" :key="option.id" :value="option.id" :label="option.title"></el-option>
            </template>
            <el-option
                v-for="agent in agents"
                :key="agent.id"
                :label="agent.full_name"
                :value="agent.id">
                <span style="float: left">{{ agent.full_name }}</span>
                <span v-if="agent.show_id" style="float: right; color: #8492a6; font-size: 13px">{{ agent.id }}</span>
            </el-option>
        </el-select>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'AgentSelectors',
    props: ['field', 'modelValue'],
    emits: ['update:modelValue'],
    data() {
        return {
            agents: this.appVars.support_agents,
            loading: false,
            modelValueLocal: this.modelValue
        }
    },
    watch: {
        modelValueLocal(value) {
            this.$emit('update:modelValue', value);
        }
    }
}
</script>
