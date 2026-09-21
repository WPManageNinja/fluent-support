import Dashboard from './components/Dashboard';
import Ticket from './components/Ticket';
import CreateTicket from './components/CreateTicket';

export default [
    {
        name: 'dashboard',
        path: '/',
        component: Dashboard
    },
    {
        name: 'view_ticket',
        path: '/ticket/:ticket_id/view',
        component: Ticket,
        props: true
    },
    {
        name: 'create_ticket',
        path: '/ticket/create',
        component: CreateTicket,
        props: true
    },
    {
        path: "/:catchAll(.*)*",
        name: 'dashboard',
        component: Dashboard
    }
];
