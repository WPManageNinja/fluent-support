<template>
    <div class="fs_box_wrapper">
        <div class="fs_dashboard_wrapper">
            <div v-html="dashboard_notice"></div>
            <div class="fs_head_section">
                <div class="fs_user_greetings_section">
                    <div class="fs_greetings_content">
                        <div class="fs_user_avatar">
                            <img :src="me.photo" :alt="me.full_name" />
                        </div>
                        <div class="fs_greetings_text">
                            <h1 class="fs_greeting_main">
                                <span class="fs_page_title fs_page_title_secondary_color">{{ $t("Good") }} {{ greetingTime }}</span>
                                <span class="fs_page_title">, {{ me.full_name }} 👋🏻</span>
                            </h1>
                            <p class="fs_page_subtitle">{{ $t("Welcome back to Fluent Support") }}</p>
                        </div>
                    </div>
                </div>
                <div></div>
                <div class="fs_button_group">
                    <div class="fs_button_group_inner">
                        <button
                            @click="drawer = true"
                            class="fs_settings_button"
                        >
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.26954 5.72492C3.38204 5.48742 3.51329 5.26242 3.66329 5.04367L3.63829 3.41867C3.63829 3.26867 3.70079 3.12492 3.81954 3.02492C4.40079 2.53742 5.06329 2.14367 5.79454 1.88117C5.93829 1.83117 6.09454 1.84367 6.21954 1.92492L7.61329 2.76242C7.87579 2.74367 8.13829 2.74367 8.40079 2.76242L9.79454 1.92492C9.92579 1.84992 10.082 1.83117 10.2258 1.88117C10.9383 2.13742 11.607 2.51867 12.2008 3.01867C12.3133 3.11242 12.382 3.26242 12.3758 3.41242L12.3508 5.03742C12.5008 5.25617 12.632 5.48117 12.7445 5.71867L14.1633 6.50617C14.2945 6.58117 14.3883 6.70617 14.4133 6.85617C14.5445 7.59992 14.5508 8.37492 14.4133 9.13117C14.3883 9.28117 14.2945 9.40617 14.1633 9.48117L12.7445 10.2687C12.632 10.5062 12.5008 10.7312 12.3508 10.9499L12.3758 12.5749C12.3758 12.7249 12.3133 12.8687 12.1945 12.9687C11.6133 13.4562 10.9508 13.8499 10.2195 14.1124C10.0758 14.1624 9.91954 14.1499 9.79454 14.0687L8.40079 13.2312C8.13829 13.2499 7.87579 13.2499 7.61329 13.2312L6.21954 14.0687C6.08829 14.1437 5.93204 14.1624 5.78829 14.1124C5.07579 13.8562 4.40704 13.4749 3.81329 12.9749C3.70079 12.8812 3.63204 12.7312 3.63829 12.5812L3.66329 10.9562C3.51329 10.7374 3.38204 10.5124 3.26954 10.2749L1.85079 9.48742C1.71954 9.41242 1.62579 9.28742 1.60079 9.13742C1.46954 8.39367 1.46329 7.61867 1.60079 6.86242C1.62579 6.71242 1.71954 6.58742 1.85079 6.51242L3.26954 5.72492Z" stroke="#525866" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8.00073 10.9999C9.65759 10.9999 11.0007 9.65679 11.0007 7.99994C11.0007 6.34308 9.65759 4.99994 8.00073 4.99994C6.34388 4.99994 5.00073 6.34308 5.00073 7.99994C5.00073 9.65679 6.34388 10.9999 8.00073 10.9999Z" stroke="#525866" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <button
                            class="fs_refresh_button"
                            @click="defalutSettings()"
                        >
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.59725 1.82476C3.95817 0.645503 5.69924 -0.0025049 7.5 7.27699e-06C11.6423 7.27699e-06 15 3.35776 15 7.50001C15 9.10201 14.4975 10.587 13.6425 11.805L11.25 7.50001H13.5C13.5001 6.32373 13.1544 5.17336 12.506 4.19195C11.8576 3.21054 10.9349 2.44138 9.85288 1.9801C8.77082 1.51882 7.57704 1.38578 6.41997 1.59752C5.2629 1.80926 4.19359 2.35643 3.345 3.17101L2.59725 1.82476ZM12.4028 13.1753C11.0418 14.3545 9.30076 15.0025 7.5 15C3.35775 15 0 11.6423 0 7.50001C0 5.89801 0.5025 4.41301 1.3575 3.19501L3.75 7.50001H1.5C1.4999 8.67629 1.84556 9.82665 2.494 10.8081C3.14244 11.7895 4.06505 12.5586 5.14712 13.0199C6.22918 13.4812 7.42296 13.6142 8.58003 13.4025C9.7371 13.1908 10.8064 12.6436 11.655 11.829L12.4028 13.1753Z" fill="#525866"/>
                            </svg>

                            <span>{{ $t('Reset') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <DashboardStatistics :stats="total_data.TicketStatistics.stats" />

            <!-- Dashboard Draggable Content Component -->
            <DashboardDraggableContent
                :loading="loading"
                :dashboard_notice="dashboard_notice"
                :dashboard_param="dashboard_param"
                :active_names="active_names"
                :total_data="total_data"
                @change="handleChange"
            />

            <!-- Settings Drawer -->
            <DashboardSettingsDrawer
                v-model="drawer"
                :dashboard-param="dashboard_param"
                @close="cancelClick"
                @widget-toggled="handleWidgetToggle"
            />
        </div>
    </div>
</template>

<script type="text/babel">
import DashboardDraggableContent from "./DashboardDraggableContent.vue";
import DashboardStatistics from './DashboardStatistics.vue';
import DashboardSettingsDrawer from './DashboardSettingsDrawer.vue';

export default {
    name: "DynamicDashboard",
    components: {
        DashboardDraggableContent,
        DashboardStatistics,
        DashboardSettingsDrawer,
    },

    data() {
        return {
            // Component state
            drawer: false,
            me: this.appVars.me,
            can_access_unassigned_tickets: false,
            loading: false,
            dashboard_notice: "",
            total_data: {
                MentionedTicket: {},
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
                        icon: 'bookmark',
                        show: true,
                        heading: this.$t("Your Bookmarked Tickets"),
                        active_names: "mentionedTicket",
                        image: "/images/dashboard_drawer_bookmark.png",

                    },
                                        {
                        id: 4,
                        component: "TicketsByProduct",
                        show: true,
                        icon: 'box',
                        heading: this.$t("active_tickets_by_products"),
                        active_names: "ticketsByProduct",
                        image: "/images/dashboard_drawer_product_wise_tickets.png",
                    },
                ],
                second_column: [
                    {
                        id: 3,
                        component: "SuggestedTicket",
                        show: true,
                        icon: 'lightbulb',
                        heading: this.$t("Here Are A Few Tickets You May Want To Take A Look At"),
                        active_names: "suggestedTicket",
                        image: "/images/dashboard_drawer_suggested_tickets.png",
                    },
                    {
                        id: 5,
                        component: 'AgentPerformance',
                        icon: 'user_avatar',
                        show: this.appVars.me.permissions.includes('fst_agent_today_performance'),
                        heading: this.$t('Agent Performance (Today)'),
                        active_names: 'agentPerformance',
                        image: "/images/dashboard_drawer_agent_performance.png",
                    }
                ],
                greetingMessage: true,
            },
            settings_data: {},
            app_ready: false,
            active_names: {},
            default_active_names: {
                mentionedTicket: ["mentionedTicket"],
                suggestedTicket: ["suggestedTicket"],
                ticketsByProduct: ["ticketsByProduct"],
                agentPerformance: ["agentPerformance"],
            },
        };
    },

    computed: {
        canManageSettings() {
            return this.appVars.me.permissions.includes('fst_manage_settings');
        },
        greetingTime() {
            const m = this.$dayjs();
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
                g = this.$t("afternoon");
            } else if (currentHour >= split_evening) {
                g = this.$t("evening");
            } else {
                g = this.$t("morning");
            }

            return g;
        }
    },

    watch: {
        dashboard_param: {
            handler(newValue, oldValue) {
                this.$saveData("dashboard_settings_data", newValue);
                this.$saveData(
                    "prev_dashboard_default_settings",
                    this.dashboard_settings_data
                );
            },
            deep: true
        }
    },

    mounted() {
        if (!this.appVars.has_mailboxes && this.canManageSettings) {
            this.$router.push({ name: "setup", query: { t: Date.now() } });
        }
        this.can_access_unassigned_tickets =
            this.appVars.me.permissions.indexOf(
                "fst_manage_unassigned_tickets"
            ) > -1;
        this.migrateDashboardSettings();
        this.fetchStat();
        this.getDashboardSettings();
        this.getComponentState();
        jQuery(
            ".update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error"
        ).remove();
    },

    methods: {
        migrateDashboardSettings() {
            // Check if migration is needed
            const migrationDone = this.$getData("dashboard_migration_v2_done");
            if (migrationDone) {
                return; // Already migrated
            }

            // Get old settings
            const oldSettings = this.$getData("dashboard_settings_data");
            if (!oldSettings) {
                // No old settings, mark migration as done
                this.$saveData("dashboard_migration_v2_done", true);
                return;
            }

            // Check if old settings contain TicketStatistics component (old structure)
            const hasOldStructureInFirstColumn = oldSettings.first_column?.some(
                item => item.component === "TicketStatistics"
            );
            const hasOldStructureInSecondColumn = oldSettings.second_column?.some(
                item => item.component === "TicketStatistics"
            );
            const hasOldStructure = hasOldStructureInFirstColumn || hasOldStructureInSecondColumn;

            if (hasOldStructure) {
                // Migrate: Remove TicketStatistics from both columns
                const newSettings = {
                    first_column: (oldSettings.first_column || []).filter(
                        item => item.component !== "TicketStatistics"
                    ),
                    second_column: (oldSettings.second_column || []).filter(
                        item => item.component !== "TicketStatistics"
                    ),
                    greetingMessage: oldSettings.greetingMessage !== false // default to true
                };

                // Ensure all required properties exist on all items
                newSettings.first_column = newSettings.first_column.map(item => ({
                    ...item,
                    icon: item.icon || this.getIconForComponent(item.component),
                    image: item.image || this.getImageForComponent(item.component)
                }));

                newSettings.second_column = newSettings.second_column.map(item => ({
                    ...item,
                    icon: item.icon || this.getIconForComponent(item.component),
                    image: item.image || this.getImageForComponent(item.component)
                }));

                // Update IDs to be sequential
                newSettings.first_column.forEach((item, index) => {
                    item.id = index + 1;
                });
                newSettings.second_column.forEach((item, index) => {
                    item.id = newSettings.first_column.length + index + 1;
                });

                // Save migrated settings
                this.$saveData("dashboard_settings_data", newSettings);
                this.$saveData("prev_dashboard_default_settings", this.dashboard_settings_data);

                // Migrate collapse state - remove ticketStatistics key
                const collapseState = this.$getData("component_collapse_data");
                if (collapseState && collapseState.ticketStatistics) {
                    delete collapseState.ticketStatistics;
                    this.$saveData("component_collapse_data", collapseState);
                }
            }

            // Mark migration as complete
            this.$saveData("dashboard_migration_v2_done", true);
        },

        getIconForComponent(componentName) {
            const iconMap = {
                'MentionedTicket': 'bookmark',
                'SuggestedTicket': 'lightbulb',
                'TicketsByProduct': 'box',
                'AgentPerformance': 'user_avatar'
            };
            return iconMap[componentName] || 'box';
        },

        getImageForComponent(componentName) {
            const imageMap = {
                'MentionedTicket': '/images/dashboard_drawer_bookmark.png',
                'SuggestedTicket': '/images/dashboard_drawer_suggested_tickets.png',
                'TicketsByProduct': '/images/dashboard_drawer_product_wise_tickets.png',
                'AgentPerformance': '/images/dashboard_drawer_agent_performance.png'
            };
            return imageMap[componentName] || '/images/dashboard_drawer_bookmark.png';
        },

        handleChange() {
            this.$saveData("component_collapse_data", this.active_names);
        },

        defalutSettings() {
            this.$saveData("dashboard_settings_data", this.dashboard_settings_data);
            this.$saveData("component_collapse_data", this.default_active_names);
            this.getComponentState();
            this.getDashboardSettings();
        },

        cancelClick() {
            this.drawer = false;
        },

        handleWidgetToggle(data) {
            // Save the updated dashboard settings when a widget is toggled
            this.$saveData("dashboard_settings_data", this.dashboard_param);
        },

        checkMove(e) {
            window.console.log("Future index: " + e.draggedContext.futureIndex);
        },

        getComponentState() {
            let collapseState = this.$getData("component_collapse_data");
            if (collapseState) {
                this.active_names = collapseState;
            } else {
                this.active_names = this.default_active_names;
            }
        },

        getDashboardSettings() {
            let prev_dashboard_default_settings = this.$getData(
                "prev_dashboard_default_settings"
            );
            this.settings_data = this.$getData("dashboard_settings_data");
            if (
                JSON.stringify(prev_dashboard_default_settings) ===
                JSON.stringify(this.dashboard_settings_data)
            ) {
                this.dashboard_param = this.settings_data;
            } else {
                this.$saveData(
                    "prev_dashboard_default_settings",
                    this.dashboard_settings_data
                );
                this.dashboard_param = this.$getData(
                    "prev_dashboard_default_settings"
                );
            }
        },

        fetchStat() {
            this.loading = true;
            let withData = [
                "suggested_tickets",
                "overall_stats",
                "individual_stat",
                "ticket_to_watch",
                "tickets_by_products",
            ];
            if (this.appVars.me.permissions.includes("fst_agent_today_performance")) {
                withData.push("agent_today_stats");
            }

            this.$get("tickets/my_stats", {
                with: withData,
            })
                .then((response) => {
                    this.dashboard_notice = response.dashboard_notice;
                    this.total_data.MentionedTicket = response.ticket_to_watch;
                    this.total_data.SuggestedTicket.suggested_tickets =
                        response.suggested_tickets;
                    this.total_data.SuggestedTicket.overall_stats =
                        response.overall_stats;
                    this.total_data.TicketStatistics.stats = response.stats;
                    this.total_data.TicketStatistics.individual_stat =
                        response.individual_stat;
                    this.total_data.TicketsByProduct =
                        response.tickets_by_product;

                    if (response.agent_today_stats) {
                        this.total_data.AgentPerformance = response.agent_today_stats;
                    }

                    this.app_ready = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    }
};
</script>
