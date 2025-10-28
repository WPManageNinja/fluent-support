<template>
    <div class="fs_dashboard_wrapper">
        <div class="fs_head_section">
            <div v-if="dashboard_param.greetingMessage">
                <h1>
                    {{ translate("Good") }} {{ greetingTime }}
                    {{ me.full_name + "!!!" }}
                </h1>
            </div>
            <div></div>
            <div class="fs_button_group">
                <div class="fs_button_group_inner">
                    <el-button
                        type="primary"
                        class="fs_drawer_button"
                        style="margin-left: 16px"
                        @click="drawer = true"
                    >
                        <el-icon>
                            <Setting />
                        </el-icon>
                    </el-button>

                    <el-button
                        type="info"
                        class="fs_drawer_button"
                        style="margin-left: 16px"
                        @click="defalutSettings()"
                    >
                        {{ translate("Reset") }}
                    </el-button>
                </div>
            </div>
        </div>
<!--        @todo: We have to refactor this code, have to make this draggable code shorter -->
        <div class="dashboard fs_box_wrapper" v-if="!loading">
            <div v-html="dashboard_notice"></div>
            <el-row :gutter="20">
                <el-col :span="12">
                    <draggable
                        v-model="dashboard_param.first_column"
                        ghost-class="ghost"
                        group="dashboard_component"
                        item-key="id"
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

            <el-drawer
                class="fs_dashboard_settings"
                v-model="drawer"
                :with-header="false"
                size="25%"
            >
                <div class="fs_drawer_content">
                    <div class="fs_settings_drawer">
                        <img
                            :src="
                                appVars.asset_url +
                                'images/ComponentIcons/GreetingMessage.png'
                            "
                            alt="Greetings"
                            class="fs_drawer_icon"
                        /><br>
                        <el-checkbox
                            v-model="dashboard_param.greetingMessage"
                            >{{ translate("Greeting Message") }}</el-checkbox
                        >
                    </div>

                    <div v-for="column_data in dashboard_param">
                        <div
                            class="fs_settings_drawer"
                            v-for="component_list_data in column_data"
                        >
                            <img
                                :src="
                                    appVars.asset_url +
                                    'images/ComponentIcons/' +
                                    component_list_data.component +
                                    '.png'
                                "
                                :alt="component_list_data.component"
                                class="fs_drawer_icon"
                            /><br>
                            <el-checkbox
                                v-model="component_list_data.show"
                                :label="
                                    translate(component_list_data.component)
                                "
                            />
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div style="flex: auto">
                        <el-button @click="cancelClick">{{
                            translate("Close")
                        }}</el-button>
                    </div>
                </template>
            </el-drawer>
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
    </div>
</template>

<script type="text/babel">
import draggable from "vuedraggable";
import TicketStatistics from "./TicketStatistics.vue";
import SuggestedTicket from "./SuggestedTicket.vue";
import MentionedTicket from "./MentionedTicket.vue";
import WatchingTickets from "./WatchingTickets.vue";
import TicketsByProduct from "./TicketsByProduct";
import AgentPerformance from './AgentPerformance.vue';
import { computed, watch, onMounted, reactive, toRefs } from "vue";
import { useFluentHelper } from "@/admin/Composable/FluentFrameworkHelper";
import {useRouter} from "vue-router";

export default {
    name: "DynamicDashboard",
    components: {
        draggable,
        TicketStatistics,
        SuggestedTicket,
        MentionedTicket,
        WatchingTickets,
        TicketsByProduct,
        AgentPerformance
    },

    setup() {
        const {
            appVars,
            get,
            translate,
            handleError,
            saveData,
            getData,
            moment,
        } = useFluentHelper();
        const router = useRouter();
        const state = reactive({
            drawer: false,
            me: appVars.me,
            can_access_unassigned_tickets: false,
            loading: false,
            dashboard_notice: "",
            total_data: {
                MentionedTicket: {},
                WatchingTickets: [],
                TicketStatistics: {
                    stats: {},
                    individual_stat: {},
                },
                SuggestedTicket: {
                    suggested_tickets: [],
                    overall_stats: {},
                },
                TicketsByProduct: {},
                AgentPerformance: {},
            },
            dashboard_param: {},
            dashboard_settings_data: {
                first_column: [
                    {
                        id: 1,
                        component: "MentionedTicket",
                        show: true,
                        heading: translate("Your Bookmarked Tickets"),
                        active_names: "mentionedTicket",
                    },
                    {
                        id: 6,
                        component: "WatchingTickets",
                        show: true,
                        heading: translate("Watching Tickets"),
                        active_names: "watchingTickets",
                    },
                    {
                        id: 2,
                        component: "TicketStatistics",
                        show: true,
                        heading: translate("Your Overview For Today"),
                        active_names: "ticketStatistics",
                    },
                ],
                second_column: [
                    {
                        id: 3,
                        component: "SuggestedTicket",
                        show: true,
                        heading: translate("dashboard_sub_heading"),
                        active_names: "suggestedTicket",
                    },
                    {
                        id: 4,
                        component: "TicketsByProduct",
                        show: true,
                        heading: translate("active_tickets_by_products"),
                        active_names: "ticketsByProduct",
                    },
                    {
                        id: 5,
                        component: 'AgentPerformance',
                        show: appVars.me.permissions.includes('fst_agent_today_performance'),
                        heading: translate('Agent Performance (Today)'),
                        active_names: 'agentPerformance',
                    }
                ],
                greetingMessage: true,
            },
            settings_data: {},
            app_ready: false,
            active_names: {},
            default_active_names: {
                mentionedTicket: ["mentionedTicket"],
                ticketStatistics: ["ticketStatistics"],
                suggestedTicket: ["suggestedTicket"],
                ticketsByProduct: ["ticketsByProduct"],
                agentPerformance: ["agentPerformance"],
            },
        });

        watch(
            () => state.dashboard_param,
            (newValue, oldValue) => {
                saveData("dashboard_settings_data", newValue);
                saveData(
                    "prev_dashboard_default_settings",
                    state.dashboard_settings_data
                );
            },
            { deep: true }
        );
        const greetingTime = computed(() => {
            const m = moment();
            let g = null; //return g

            if (!m || !m.isValid()) {
                return;
            } //if we can't find a valid or filled moment, we return.

            const split_afternoon = 12; //24hr time to split the afternoon
            const split_evening = 17; //24hr time to split the evening
            const currentHour = parseFloat(m.format("HH"));

            if (
                currentHour >= split_afternoon &&
                currentHour <= split_evening
            ) {
                g = translate("afternoon");
            } else if (currentHour >= split_evening) {
                g = translate("evening");
            } else {
                g = translate("morning");
            }

            return g;
        });

        function handleChange() {
            saveData("component_collapse_data", state.active_names);
        }

        function defalutSettings() {
            saveData("dashboard_settings_data", state.dashboard_settings_data);
            saveData("component_collapse_data", state.default_active_names);
            getComponentState();
            getDashboardSettings();
        }

        function cancelClick() {
            state.drawer = false;
        }

        function checkMove(e) {
            window.console.log("Future index: " + e.draggedContext.futureIndex);
        }

        function getComponentState() {
            let collapseState = getData("component_collapse_data");
            if (collapseState) {
                state.active_names = collapseState;
            } else {
                state.active_names = state.default_active_names;
            }
        }

        function getDashboardSettings() {
            let prev_dashboard_default_settings = getData(
                "prev_dashboard_default_settings"
            );
            state.settings_data = getData("dashboard_settings_data");
            if (
                JSON.stringify(prev_dashboard_default_settings) ===
                JSON.stringify(state.dashboard_settings_data)
            ) {
                state.dashboard_param = state.settings_data;
            } else {
                saveData(
                    "prev_dashboard_default_settings",
                    state.dashboard_settings_data
                );
                state.dashboard_param = getData(
                    "prev_dashboard_default_settings"
                );
            }
        }

        function fetchStat() {
            state.loading = true;
            let withData = [
                "suggested_tickets",
                "overall_stats",
                "individual_stat",
                "ticket_to_watch",
                "watching_tickets", //with notifications
                "tickets_by_products",
            ];
            if (appVars.me.permissions.includes("fst_agent_today_performance")) {
                withData.push("agent_today_stats");
            }

            get("tickets/my_stats", {
                with: withData,
            })
                .then((response) => {
                    state.dashboard_notice = response.dashboard_notice;
                    state.total_data.MentionedTicket = response.ticket_to_watch;
                    state.total_data.WatchingTickets = response.watching_tickets || [];
                    state.total_data.SuggestedTicket.suggested_tickets =
                        response.suggested_tickets;
                    state.total_data.SuggestedTicket.overall_stats =
                        response.overall_stats;
                    state.total_data.TicketStatistics.stats = response.stats;
                    state.total_data.TicketStatistics.individual_stat =
                        response.individual_stat;
                    state.total_data.TicketsByProduct =
                        response.tickets_by_product;

                    state.app_ready = true;
                })
                .catch((errors) => {
                    handleError(errors);
                })
                .always(() => {
                    state.loading = false;
                });
        }

        onMounted(() => {
            if (!appVars.mailboxes.length) {
                router.push({ name: "setup", query: { t: Date.now() } });
            }
            state.can_access_unassigned_tickets =
                appVars.me.permissions.indexOf(
                    "fst_manage_unassigned_tickets"
                ) > -1;
            fetchStat();
            getDashboardSettings();
            getComponentState();
            jQuery(
                ".update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error"
            ).remove();
        });

        return {
            ...toRefs(state),
            greetingTime,
            handleChange,
            defalutSettings,
            cancelClick,
            checkMove,
            getComponentState,
            getDashboardSettings,
            fetchStat,
            appVars,
            get,
            translate,
            handleError,
            saveData,
            getData,
            moment,
        };
    },
};
</script>

<style>
.draggable_component .el-collapse-item__arrow,
.draggable_component .el-collapse-item__arrow.is-active,
.draggable_component .el-collapse-item__arrow {
    cursor: pointer !important;
    background-color: white;
    border-radius: 50%;
    height: 30px;
    width: 30px;
}
.draggable_component .el-collapse-item__arrow:hover {
    background: #54b47e;
    color: white;
}
.draggable_component .el-collapse-item__wrap {
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    border-left: 1px solid rgb(226, 228, 231);
    box-shadow: 4px 2px 4px rgb(18 25 97 / 8%);
}

.draggable_component .el-collapse-item__header {
    padding: 8px 15px;
}

</style>

<style scoped>
.ghost {
    opacity: 0.5;
    text-align: center;
    border-style: dashed;
    border-radius: 10px;
    max-width: 670px;
    float: none;
}

.fs_settings_drawer {
    width: 85%;
    padding: 10px;
    margin-bottom: 10px;
    background: #fff;
    display: block;
    overflow: hidden;
}

.draggable_component {
    max-width: 670px;
    margin-left: auto;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgb(18 25 97 / 8%);
}
.fs_head_section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 25px;
    max-width: 1160px;
    margin-left: auto;
    margin-right: auto;
}
.fs_head_section h1 {
    line-height: 1.4;
    max-width: 680px;
    width: 100%;
    margin-left: auto;
}
.fs_head_section > :first-child {
    flex-basis: 50%;
}
.fs_button_group {
    margin: 15px 0px 15px 0px;
    flex-basis: 50%;
}
.fs_button_group .fs_button_group_inner {
    max-width: 680px;
    width: 100%;
    margin-right: auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.fs_drawer_content {
    margin-top: 30px;
}
.fs_component_skeleton {
    border: 1px solid #e3e8ee;
    border-radius: 10px;
    padding: 10px;
}

.dashboard .el-row .el-col:last-child .draggable_component {
    margin-right: auto;
    margin-left: 0;
}
.fs_dashboard_wrapper {
    margin: 0% 5% 0% 5%;
}

.el-collapse-item {
    position: relative;
}
.fs_component_header {
    width: 80%;
    clear: both;
    overflow: hidden;
    margin: 0;
    font-weight: 500;
    color: #000000;
    cursor: move;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-style: normal;
    font-size: 14px;
    line-height: 18px;
}
.fs_drawer_icon {
    width: 75%;
    height: 75%;
    border: 1px solid #453b391c;
    border-radius: 5px;
}

</style>
