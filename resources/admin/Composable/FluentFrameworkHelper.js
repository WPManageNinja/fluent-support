import { ElNotification, ElMessageBox } from 'element-plus';
const moment = require('moment');
require('moment/locale/en-gb');
moment.locale('en-gb');
import { dateTimeHelper } from "@/admin/Composable/dateTimeHelper";
import { useRestApi } from '@/admin/Composable/Rest';
import {
    applyFilters,
    addFilter,
    addAction,
    doAction,
    removeAllActions
} from '@wordpress/hooks';

export function useFluentHelper(){
    const { humanDiffTime, dateTimeFormat, localDate, longLocalDate } = dateTimeHelper();
    const { get, post, del, put, patch} = useRestApi();
    const { notify } = useNotify();
    const appVars = window.fluentSupportAdmin;
    const has_pro = window.fluentSupportAdmin.has_pro;

    function convertToText(obj) {
        const string = [];
        if (typeof (obj) === 'object' && (obj.join === undefined)) {
            for (const prop in obj) {
                string.push(convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'object' && !(obj.join === undefined)) {
            for (const prop in obj) {
                string.push(convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'function') {

        } else if (typeof (obj) === 'string') {
            string.push(obj)
        }
        return string.join('<br />')
    }

    function translate(string) {
        return window.window.fluentSupportAdmin.i18n[string] || string;
    }

    function ucFirst(text) {
        return text[0].toUpperCase() + text.slice(1).toLowerCase();
    }

    function ucWords(text) {
        return (text + '').replace(/^(.)|\s+(.)/g, function ($1) {
            return $1.toUpperCase();
        })
    }

    //Store in local storage
    function saveData(key, data) {
        let existingData = window.localStorage.getItem('__fluentsupport_data');

        if (!existingData) {
            existingData = {};
        } else {
            existingData = JSON.parse(existingData);
        }

        existingData[key] = data;

        window.localStorage.setItem('__fluentsupport_data', JSON.stringify(existingData));
    }

    //Get from local storage
    function  getData(key, defaultValue = false) {
        let existingData = window.localStorage.getItem('__fluentsupport_data');
        existingData = JSON.parse(existingData);
        if (!existingData) {
            return defaultValue;
        }

        if (existingData[key]) {
            return existingData[key];
        }

        return defaultValue;

    }

    function removeData(key) {
        let existingData = window.localStorage.getItem('__fluentsupport_data');

        if (!existingData) {
            return [];
        } else {
            existingData = JSON.parse(existingData);
        }

        delete existingData[key];

        window.localStorage.setItem('__fluentsupport_data', JSON.stringify(existingData));

    }

    function smartDate (dateString, withTime = false) {
        if (!dateString) {
            return "";
        }
        let format = "D MMM, YYYY";
        if (moment(dateString).isSame(new Date(), "year")) {
            format = "D MMM";
            if (withTime) {
                format = "D MMM, hh:mm a";
            }
        }

        const dateObj = moment(dateString);

        return dateObj.isValid() ? dateObj.format(format) : null;
    }

    //Error handler
    function handleError(response) {
        if (response.responseJSON) {
            response = response.responseJSON;
        }
        let errorMessage = '';
        if (typeof response === 'string') {
            errorMessage = response;
        } else if (response && response.message) {
            errorMessage = response.message;
        } else {
            errorMessage = convertToText(response);
        }
        if (!errorMessage) {
            errorMessage = 'Something is wrong!';
        }
        notify({
            type: 'error',
            title: 'Error',
            message: errorMessage,
            dangerouslyUseHTMLString: true,
            position: 'bottom-right'
        });
    }

    function setTitle(title) {
        document.title = title;
    }

    function renewOptions(type) {
        get(type)
            .then(response => {
                if (response.option_key) {
                    appVars[response.option_key] = response.options;

                }
            })
    }

    function copyToClipboard(text, successMessage = 'Copied to clipboard!', errorMessage = 'Failed to copy to clipboard') {
        if (!text) {
            notify({
                type: 'warning',
                title: 'Warning',
                message: 'Nothing to copy',
                position: 'bottom-right'
            });
            return Promise.resolve(false);
        }

        // Use modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(() => {
                notify({
                    type: 'success',
                    title: 'Success',
                    message: successMessage,
                    position: 'bottom-right'
                });
                return true;
            }).catch((err) => {
                console.error('Failed to copy:', err);
                notify({
                    type: 'error',
                    title: 'Error',
                    message: errorMessage,
                    position: 'bottom-right'
                });
                return false;
            });
        } else {
            // For non-secure contexts, show error message
            notify({
                type: 'error',
                title: 'Error',
                message: 'Copy to clipboard requires HTTPS',
                position: 'bottom-right'
            });
            return Promise.resolve(false);
        }
    }

    return {
        appVars,
        get,
        post,
        del,
        put,
        patch,
        convertToText,
        translate,
        ucFirst,
        ucWords,
        handleError,
        saveData,
        getData,
        removeData,
        moment,
        humanDiffTime,
        dateTimeFormat,
        localDate,
        longLocalDate,
        setTitle,
        has_pro,
        renewOptions,
        useScrollToRef,
        smartDate,
        copyToClipboard
    }
}

export function wpHooks() {
    return {
        applyFilters,
        addFilter,
        addAction,
        doAction,
        removeAllActions
    }
}

export function useNotify(){

    const notify= (config)=> {
        ElNotification({
            position: config.position || 'bottom-right',
            ...config
        })
    }

    return {
        notify
    }
}

export function useConfirm(){
    const confirm = (config)=> {
        return ElMessageBox.confirm(config.message, config.title, config.options);
    }

    return {
        confirm
    }
}

export function useScrollToRef (ref) {
     ref.scrollIntoView({ behavior: 'smooth' });
}
