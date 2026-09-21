<template>
    <div class="dashboard fs_box_wrapper" v-if="!loading">
        <el-row :gutter="20">
            <el-col :span="12">
                <draggable
                    v-model="dashboard_param.first_column"
                    ghost-class="ghost"
                    group="dashboard_component"
                    item-key="id"
                    handle=".fs_component_header"
                >
                    <template v-if="!loading" #item="{ element }">
                        <div v-if="element.show" class="draggable">
                            <div class="draggable_component">
                                <el-collapse
                                    v-model="
                                        active_names[element.active_names]
                                    "
                                    @change="handleChange"
                                >
                                    <el-collapse-item
                                        :name="element.active_names"
                                        class="fs_box_board"
                                    >
                                        <template #title>
                                            <div
                                                class="fs_component_header fs_label_medium fs_dashboard_component_header"
                                            >
                                                {{ element.heading }}
                                            </div>
                                        </template>
                                        <component
                                            :is="element.component"
                                            :component_data="
                                                total_data[
                                                    element.component
                                                ]
                                            "
                                            @navigate-to-ticket="navigateToTicket"
                                        >
                                        </component>
                                    </el-collapse-item>
                                </el-collapse>
                            </div>
                        </div>
                    </template>
                </draggable>
            </el-col>
            <el-col :span="12">
                <draggable
                    v-model="dashboard_param.second_column"
                    ghost-class="ghost"
                    group="dashboard_component"
                    item-key="id"
                    handle=".fs_component_header"
                >
                    <template #item="{ element }">
                        <div v-if="element.show" class="draggable">
                            <div class="draggable_component">
                                <el-collapse
                                    v-model="
                                        active_names[element.active_names]
                                    "
                                    @change="handleChange"
                                >
                                    <el-collapse-item
                                        :name="element.active_names"
                                        class="fs_box_board"
                                    >
                                        <template #title>
                                            <div
                                                class="fs_component_header"
                                            >
                                                {{ element.heading }}
                                            </div>
                                        </template>
                                        <component
                                            :is="element.component"
                                            :component_data="
                                                total_data[
                                                    element.component
                                                ]
                                            "
                                            @navigate-to-ticket="navigateToTicket"
                                        >
                                        </component>
                                    </el-collapse-item>
                                </el-collapse>
                            </div>
                        </div>
                    </template>
                </draggable>
            </el-col>
        </el-row>
    </div>

    <div class="fs_padded_20" v-else>
        <el-row :gutter="20">
            <el-col :span="12">
                <div class="fs_component_skeleton">
                    <el-skeleton :rows="6" animated />
                </div>
            </el-col>
            <el-col :span="12">
                <div class="fs_component_skeleton">
                    <el-skeleton :rows="6" animated />
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import draggable from "vuedraggable";
import SuggestedTicket from "./SuggestedTicket.vue";
import MentionedTicket from "./MentionedTicket.vue";
import TicketsByProduct from "./TicketsByProduct";
import AgentPerformance from './AgentPerformance.vue';
import IconPack from "../../Components/IconPack.vue";
export default {
    name: "DashboardDraggableContent",
    components: {
        draggable,
        SuggestedTicket,
        MentionedTicket,
        TicketsByProduct,
        AgentPerformance,
        IconPack
    },

    props: {
        loading: {
            type: Boolean,
            required: true
        },
        dashboard_notice: {
            type: String,
            default: ""
        },
        dashboard_param: {
            type: Object,
            required: true
        },
        active_names: {
            type: Object,
            required: true
        },
        total_data: {
            type: Object,
            required: true
        }
    },

    emits: ['change'],

    methods: {
        handleChange() {
            this.$emit('change');
        },
        navigateToTicket(ticket) {
            this.$router.push({
                name: "view_ticket",
                params: { ticket_id: ticket.id },
            });
        }
    }
};
</script>

