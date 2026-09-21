import Rest from '@/admin/Bits/Rest';

export default {
    fetchList(params) {
        return Rest.get('tickets', params);
    },

    bulkAction(data) {
        return Rest.post('tickets/bulk-actions', data);
    },

    bulkClose(data) {
        return Rest.post('tickets/bulk-actions', { ...data, bulk_action: 'close_tickets' });
    },

    bulkDelete(data) {
        return Rest.post('tickets/bulk-actions', { ...data, bulk_action: 'delete_tickets' });
    },

    saveLabelSearch(data) {
        return Rest.post('tickets/label-search', data);
    },

    fetchLabelSearches() {
        return Rest.get('tickets/label-search');
    },

    deleteLabelSearch(id) {
        return Rest.delete('tickets/' + id + '/label-search');
    }
};
