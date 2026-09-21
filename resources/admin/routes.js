import Dashboard from './Modules/Dashboard/Dashboard.vue';
import Setup from './Components/Setup';
import TicketsView from './Modules/Tickets/TicketsView';
import AllTickets from './Modules/Tickets/AllTickets';
import ViewTicket from './Modules/Tickets/ViewTicket';

import SettingsView from './Modules/Settings/SettingsView';
import BusinessSettings from './Modules/Settings/BusinessSettings';
import Products from './Modules/Settings/Products';
import TicketTags from './Modules/Settings/TicketTags';
import Agents from './Modules/Settings/SupportStaffs';
import IntegrationView from './Modules/Settings/IntegrationView';
import RecaptchaView from './Modules/Settings/RecaptchaView'
import TicketFormConfig from "./Modules/Settings/TicketFormConfig";
import CustomFields from "./Modules/Settings/CustomFields";
import LicenseManagement from "./Modules/Settings/LicenseManagement";
import FluentCRMIntegration from "./Modules/Settings/FluentCRMIntegration";
import AutoCloseSettings from "./Modules/Settings/AutoCloseSettings";
import UploadIntegrationView from './Modules/Settings/FileUploadSettings/UploadIntegrationView';

import Customers from './Modules/Customers/Customers';
import SavedReplies from './Modules/SavedReplies/Replies';
import CustomerPage from "./Modules/Customers/CustomerPage";

import MailBoxRoot from './Modules/MailBoxes/MailBoxRoot';
import ChooseMailBox from './Modules/MailBoxes/ChooseMailBox';
import MailBoxSettings from './Modules/MailBoxes/MailBoxSettings';
import MailBoxEmailSettings from './Modules/MailBoxes/BoxEmailSettings';
import MailBoxEmailPiping from './Modules/MailBoxes/MailBoxEmailPiping';

import Report from './Modules/Reports/Report';

import Workflows from './Modules/Workflows/AllWorkflows';
import EditWorkFlow from './Modules/Workflows/EditWorkFlow';

import Activity from "./Modules/ActivityLogger/Activity";
import NotificationsPage from './Modules/Notifications/NotificationsPage.vue';

import IncomingWebhook from "./Modules/Settings/IncomingWebhook";
import TicketImporter from "./Modules/Settings/TicketImporter/TicketImporter";
import HelpScoutAuthorization from "./Modules/Settings/TicketImporter/HelpScout/HelpScoutAuthorization";
import IntegrationStatuses from './Modules/Settings/IntegrationStatuses';
import AIIntegration from "@/admin/Modules/Settings/AIIntegration.vue";
import FluentBotIntegration from "@/admin/Modules/Settings/FluentBotIntegration.vue";
import McpIntegration from "@/admin/Modules/Settings/McpIntegration.vue";

export default [
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard,
        meta: {
            active: 'dashboard'
        }
    },
    {
        path: '/setup',
        name: 'setup',
        component: Setup,
        meta: {
            active: ''
        }
    },
    {
        path: '/tickets',
        component: TicketsView,
        meta: {
            active: 'tickets'
        },
        children: [
            {
                name: 'tickets',
                path: '',
                component: AllTickets
            },
            {
                name: 'view_ticket',
                path: ':ticket_id/view',
                component: ViewTicket,
                props: true
            },
        ]
    },
    {
        path: '/settings',
        component: SettingsView,
        meta: {
            active: 'settings'
        },
        children: [
            {
                name: 'global_settings',
                path: '',
                component: BusinessSettings
            },
            {
                name: 'reCaptcha',
                path: 'reCaptcha',
                component: RecaptchaView
            },
            {
                name: 'ticket-form-config',
                path: 'ticket-form-config',
                component: TicketFormConfig
            },
            {
                name: 'custom_fields',
                path: 'custom-fields',
                component: CustomFields
            },
            {
                name: 'tags',
                path: 'ticket-tags',
                component: TicketTags
            },
            {
                name: 'products',
                path: 'products',
                component: Products
            },
            {
                path: 'support-staffs',
                name: 'support-staffs',
                component: Agents
            },
            {
                path: 'integration',
                name: 'integration',
                component: IntegrationView
            },
            {
                path: 'upload_integration',
                name: 'upload_integration',
                component: UploadIntegrationView
            },
            {
                path: 'fluentcrm-integration',
                name: 'fluentcrm_integration',
                component: FluentCRMIntegration
            },
            {
                path: 'license-management',
                name: 'license',
                component: LicenseManagement
            },
            {
                path: 'incoming-webhook',
                name: 'incoming-webhook',
                component: IncomingWebhook
            },
            {
                path: 'auto-close',
                name: 'auto_close',
                component: AutoCloseSettings
            },
            {
                path: 'ticket_importer',
                name: 'ticket_importer',
                component: TicketImporter
            },
            {
                path: '/integration_statuses',
                name: 'integration_statuses',
                component: IntegrationStatuses,
                props: true,
            },
            {
                path: '/ai_integration',
                name: 'ai_integration',
                component: AIIntegration,
                props: true,
            },
            {
                path: '/fluent_bot_integration',
                name: 'fluent_bot_integration',
                component: FluentBotIntegration,
                props: true,
            },
            {
                path: '/ai-agents',
                name: 'mcp_settings',
                component: McpIntegration,
            },

        ]
    },
    {
        path: '/mailboxes',
        component: ChooseMailBox,
        name: 'mailboxes',
        meta: {
            active: 'mailboxes'
        }
    },
    {
        path: '/notifications',
        name: 'notifications',
        component: NotificationsPage,
        meta: {
            active: ''
        }
    },
    {
        path: '/mailboxes/:box_id',
        component: MailBoxRoot,
        props: true,
        meta: {
            active: 'mailboxes'
        },
        children: [
            {
                props: true,
                path: 'settings',
                name: 'box_settings',
                component: MailBoxSettings
            },
            {
                props: true,
                path: 'email_settings',
                name: 'email_settings',
                component: MailBoxEmailSettings
            },
            {
                props: true,
                path: 'email_piping',
                name: 'email_piping',
                component: MailBoxEmailPiping
            }
        ]
    },
    {
        path: '/customers',
        name: 'Customers',
        component: Customers,
        meta: {
            active: 'customers'
        }
    },
    {
        path: '/customers/:customer_id',
        name: 'view_customer',
        component: CustomerPage,
        props: true,
        meta: {
            active: 'customers'
        }
    },
    {
        path: '/saved-replies',
        name: 'saved_replies',
        component: SavedReplies,
        meta: {
            active: 'saved_replies'
        }
    },
    {
        path: '/reports',
        name: 'reports',
        component: Report,
        meta: {
            active: 'reports'
        }
    },
    {
        path: '/workflows',
        name: 'workflows',
        component: Workflows,
        meta: {
            active: 'workflows'
        }
    },
    {
        path: '/workflows/:workflow_id/edit',
        name: 'edit-workflow',
        component: EditWorkFlow,
        props: true,
        meta: {
            active: 'workflows'
        }
    },
    {
        path: '/activity',
        name: 'activity',
        component: Activity,
        meta: {
            active: 'activity'
        }
    },
    {
        path: '/help_scout',
        name: 'help_scout',
        component: HelpScoutAuthorization,
        props: true,
    },
    {
        path: "/:catchAll(.*)*",
        name: 'not_found',
        component: Dashboard,
        meta: {
            active: 'dashboard'
        }
    }
];
