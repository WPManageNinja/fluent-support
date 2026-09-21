/**
 * Utility functions for timesheet-related calculations and exports
 */
export const timesheetUtils = {
    /**
     * Calculate total minutes for a specific user across all dates
     * @param {Object} dateLabels - Array of date labels
     * @param {Object} timeSheets - Timesheet data object
     * @param {Number} id - ID of the user
     * @returns {string} Formatted total minutes
     */
    calculateEntityTotalMinutes: (dateLabels, timeSheets, id) => {
        const minutes = dateLabels.reduce((acc, date) => {
            const sheets = timeSheets?.[date]?.[id] || [];
            return acc + sheets.reduce((acc, sheet) => acc + sheet.billable_minutes, 0);
        }, 0);
        return timesheetUtils.formatMinutes(minutes);
    },

    /**
     * Calculate total minutes for a specific agent on a specific date
     * @param {Object} timeSheets - Timesheet data object
     * @param {string} date - Date string
     * @param {Number} id - ID
     * @returns {string} Formatted total minutes
     */
    calculateTimeSheetTotal: (timeSheets, date, id) => {
        const sheets = timeSheets?.[date]?.[id] ?? [];
        const minutes = sheets.reduce((acc, sheet) => acc + sheet.billable_minutes, 0);
        return timesheetUtils.formatMinutes(minutes);
    },

    /**
     * Format minutes into a human-readable time string
     * @param {Number} minutes - Total minutes to format
     * @returns {string} Formatted time string
     */
    formatMinutes: (minutes) => {
        let intMinutes = parseInt(minutes);
        if (!intMinutes) return '--';

        const hours = Math.floor(intMinutes / 60);
        intMinutes %= 60;

        if (!hours) return `${intMinutes}m`;
        if (!intMinutes) return `${hours}h`;
        return `${hours}h ${intMinutes}m`;
    },

    /**
     * Generate export URL for timesheet report
     * @param {Object} config - Configuration object for export
     * @param {Array} config.selectedItems - Selected items to export
     * @param {Array} config.dateRange - Date range for the report
     * @param {string} config.action - Export action name
     * @returns {void}
     */
    exportReport: (config) => {
        const { selectedItems, dateRange, action, itemKey } = config;

        const formattedDateRange = Array.isArray(dateRange) && dateRange.length === 2 
            ? dateRange.join(',') 
            : (dateRange || '');
        
        let formattedSelectedItems;
        if (Array.isArray(selectedItems)) {
            formattedSelectedItems = selectedItems.length > 0 
                ? selectedItems.join(',') 
                : (itemKey === 'mailbox_id' ? null : 'null');
        } else if (selectedItems === null || selectedItems === undefined || selectedItems === '') {
            formattedSelectedItems = (itemKey === 'mailbox_id') ? null : 'null';
        } else {
            formattedSelectedItems = String(selectedItems);
        }

        const queryParams = new URLSearchParams({
            action,
            _wpnonce: window.fluentSupportAdmin.nonce,
        });

        queryParams.append('dateRange', formattedDateRange);
        if (formattedSelectedItems !== null) {
            queryParams.append(itemKey, formattedSelectedItems);
        }

        location.href = `${window.fluentSupportAdmin.ajaxurl}?${queryParams.toString()}`;
    }
};
