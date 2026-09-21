import { h } from 'vue';
import app from '@/admin/Bits/elements';
import Rest from '@/admin/Bits/Rest';

import {
    applyFilters,
    addFilter,
    addAction,
    doAction,
    removeAllActions
} from '@wordpress/hooks';

import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import 'dayjs/locale/en-gb';

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.extend(isSameOrAfter);
dayjs.locale('en-gb');

const appStartTime = new Date();


export default class FluentFramework {
    constructor() {
        this.doAction = doAction;
        this.addFilter = addFilter;
        this.addAction = addAction;
        this.applyFilters = applyFilters;
        this.removeAllActions = removeAllActions;
        this.$rest = Rest;
        this.appVars = window.fluentSupportAdmin;
        this.app = this.extendVueConstructor();
    }

    extendVueConstructor() {
        const self = this;
        app.mixin({
            methods: {
                addFilter,
                applyFilters,
                doAction,
                addAction,
                removeAllActions,
                longLocalDate: self.longLocalDate,
                longLocalDateTime: self.longLocalDateTime,
                dateTimeFormat: self.dateTimeFormat,
                localDate: self.localDate,
                ucFirst: self.ucFirst,
                ucWords: self.ucWords,
                slugify: self.slugify,
                $dayjs: dayjs,
                $get: self.$get,
                $post: self.$post,
                $del: self.$del,
                $put: self.$put,
                $patch: self.$patch,
                $handleError: self.handleError,
                $saveData: self.saveData,
                $getData: self.getData,
                $removeData: self.removeData, // From FluentFrameWorkHelper.js
                $timeDiff: self.humanDiffTime,
                $waitingTime: self.waitingTime,
                convertToText: self.convertToText,
                getCurrencySymbol: self.getCurrencySymbol,
                smartDate: self.smartDate, // From FluentFrameWorkHelper.js
                $copyToClipboard: self.copyToClipboard, // From FluentFrameWorkHelper.js
                $notify: self.notify, // From FluentFrameWorkHelper.js
                $confirm: self.confirm, // From FluentFrameWorkHelper.js
                $scrollToRef: self.scrollToRef, // From FluentFrameWorkHelper.js
                $getSourceIcon: self.getSourceIcon,
                $setTitle(title) {
                    document.title = title;
                },
                $t(string) {
                    return window.window.fluentSupportAdmin.i18n[string] || string;
                },
                renewOptions(type) {
                    this.$get(type)
                        .then(response => {
                            if (response.option_key) {
                                self.appVars[response.option_key] = response.options;
                            }
                        });
                }
            }
        });
        return app;
    }

    getExtraComponents() {
        return {
            'ticket-header': {
                template: `<h1>OK</h1>`
            }
        }
    }

    registerBlock(blockLocation, blockName, block) {
        this.addFilter(blockLocation, this.appVars.slug, function (components) {
            components[blockName] = block;
            return components;
        });
    }

    registerTopMenu(title, route) {
        if (!title || !route.name || !route.path || !route.component) {
            return;
        }

        this.addFilter('fluent_framework_top_menus', this.appVars.slug, function (menus) {
            menus = menus.filter(m => m.route !== route.name);
            menus.push({
                route: route.name,
                title: title
            });
            return menus;
        });

        this.addFilter('fluent_framework_global_routes', this.appVars.slug, function (routes) {
            routes = routes.filter(r => r.name !== route.name);
            routes.push(route);
            return routes;
        });
    }

    $get(url, options = {}) {
        return Rest.get(url, options);
    }

    $post(url, options = {}) {
        return Rest.post(url, options);
    }

    $del(url, options = {}) {
        return Rest.delete(url, options);
    }
    $put(url, options = {}) {
        return Rest.put(url, options);
    }
    $patch(url, options = {}) {
        return Rest.patch(url, options);
    }

    dateTimeFormat(date, format) {
        const dateString = (date === undefined) ? null : date;
        const dateObj = dayjs(dateString);
        return dateObj.isValid() ? dateObj.format(format) : null;
    }

    localDate(date) {
        return dayjs.utc(date).local();
    }

    longLocalDate(date) {
        return this.dateTimeFormat(
            date, 'ddd, DD MMM, YYYY'
        );
    }

    saveData(key, data) {
        let existingData = window.localStorage.getItem('__fluentsupport_data');

        if (!existingData) {
            existingData = {};
        } else {
            existingData = JSON.parse(existingData);
        }

        existingData[key] = data;

        window.localStorage.setItem('__fluentsupport_data', JSON.stringify(existingData));
    }

    getData(key, defaultValue = false) {
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

    removeData(key) {
        let existingData = window.localStorage.getItem('__fluentsupport_data');

        if (!existingData) {
            return [];
        } else {
            existingData = JSON.parse(existingData);
        }

        delete existingData[key];

        window.localStorage.setItem('__fluentsupport_data', JSON.stringify(existingData));
    }

    longLocalDateTime(date) {
        return this.dateTimeFormat(
            date, 'ddd, DD MMM, YYYY hh:mm:ssa'
        );
    }

    ucFirst(text) {
        if (!text || typeof text !== 'string') {
            return '';
        }
        return text[0].toUpperCase() + text.slice(1).toLowerCase();
    }

    ucWords(text) {
        return (text + '').replace(/^(.)|\s+(.)/g, function ($1) {
            return $1.toUpperCase();
        })
    }

    slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-') // Replace spaces with -
            .replace(/[^\w\\-]+/g, '') // Remove all non-word chars
            .replace(/\\-\\-+/g, '-') // Replace multiple - with single -
            .replace(/^-+/, '') // Trim - from start of text
            .replace(/-+$/, ''); // Trim - from end of text
    }

    handleError(response) {
        if (response.responseJSON) {
            response = response.responseJSON;
        }
        let errorMessage = '';
        if (typeof response === 'string') {
            errorMessage = response;
        } else if (response && response.message) {
            errorMessage = response.message;
        } else {
            errorMessage = this.convertToText(response);
        }
        if (!errorMessage) {
            errorMessage = 'Something is wrong!';
        }

        const rawLines = errorMessage.split('<br />');
        const message = rawLines.length > 1
            ? h('div', rawLines.filter(Boolean).map(line => h('p', { style: 'margin: 0' }, line)))
            : errorMessage;

        this.$notify({
            type: 'error',
            title: 'Error',
            message,
            position: 'bottom-right'
        });
    }

    convertToText(obj) {
        const string = [];
        if (typeof (obj) === 'object' && (obj.join === undefined)) {
            for (const prop in obj) {
                string.push(this.convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'object' && !(obj.join === undefined)) {
            for (const prop in obj) {
                string.push(this.convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'function') {

        } else if (typeof (obj) === 'string') {
            string.push(obj)
        }

        return string.join('<br />')
    }

    humanDiffTime(date) {
        const dateString = (date === undefined) ? null : date;
        if (!dateString) {
            return '';
        }
        const endTime = new Date();
        const timeDiff = endTime - appStartTime; // in ms
        const dateObj = dayjs(dateString);
        return dateObj.from(dayjs(window.fluentSupportAdmin.server_time).add(timeDiff, 'milliseconds'));
    }

    waitingTime(time1, time2) {
        if (!time2 || !time1) {
            return '';
        }
        time1 = dayjs(time1);
        time2 = dayjs(time2);
        return time2.from(time1);
    }

    getCurrencySymbol(currencyCode) {
        const currencySymbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'JPY': '¥',
            'CNY': '¥',
            'KRW': '₩',
            'INR': '₹',
            'CAD': 'C$',
            'AUD': 'A$',
            'CHF': 'CHF',
            'SEK': 'kr',
            'NOK': 'kr',
            'DKK': 'kr',
            'PLN': 'zł',
            'CZK': 'Kč',
            'HUF': 'Ft',
            'RUB': '₽',
            'BRL': 'R$',
            'MXN': '$',
            'ZAR': 'R',
            'SGD': 'S$',
            'HKD': 'HK$',
            'NZD': 'NZ$',
            'THB': '฿',
            'MYR': 'RM',
            'PHP': '₱',
            'IDR': 'Rp',
            'VND': '₫',
            'TRY': '₺',
            'ILS': '₪',
            'AED': 'د.إ',
            'SAR': 'ر.س',
            'EGP': 'ج.م',
            'NGN': '₦',
            'KES': 'KSh',
            'GHS': '₵',
            'XOF': 'CFA',
            'XAF': 'FCFA',
            'MAD': 'د.م.',
            'TND': 'د.ت',
            'DZD': 'د.ج',
            'LYD': 'ل.د',
            'SDG': 'ج.س.',
            'ETB': 'Br',
            'UGX': 'USh',
            'TZS': 'TSh',
            'RWF': 'RF',
            'MWK': 'MK',
            'ZMW': 'ZK',
            'BWP': 'P',
            'SZL': 'L',
            'LSL': 'L',
            'NAD': 'N$',
            'MZN': 'MT',
            'AOA': 'Kz',
            'CVE': '$',
            'GMD': 'D',
            'GNF': 'FG',
            'LRD': 'L$',
            'SLL': 'Le',
            'STD': 'Db'
        };

        return currencySymbols[currencyCode] || currencyCode;
    }

    smartDate(dateString, withTime = false) {
        if (!dateString) {
            return "";
        }
        let format = "D MMM, YYYY";
        if (dayjs(dateString).isSame(new Date(), "year")) {
            format = "D MMM";
            if (withTime) {
                format = "D MMM, hh:mm a";
            }
        }

        const dateObj = dayjs(dateString);

        return dateObj.isValid() ? dateObj.format(format) : null;
    }

    copyToClipboard(text, successMessage = 'Copied to clipboard!', errorMessage = 'Failed to copy to clipboard') {
        const notify = this.$notify || this.notify;

        if (typeof notify !== 'function') {
            return Promise.resolve(false);
        }

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

    notify(config) {
        ElNotification({
            position: config.position || 'bottom-right',
            ...config
        });
    }

    confirm(config) {
        return ElMessageBox.confirm(config.message, config.title, config.options);
    }

    scrollToRef(ref) {
        if (ref) {
            ref.scrollIntoView({ behavior: 'smooth' });
        }
    }
    getSourceIcon(source) {
        if (!source) return null;
        const iconFileName = 'source' + this.ucFirst(source) + '.svg';
        return window.fluentSupportAdmin.asset_url + 'images/' + iconFileName;
    }
}
