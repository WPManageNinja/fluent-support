import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import 'dayjs/locale/en-gb';

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.locale('en-gb');

const appStartTime = new Date();

export function dateTimeHelper() {
     function humanDiffTime(date) {
        const dateString = (date === undefined) ? null : date;
        if (!dateString) {
            return '';
        }
        const endTime = new Date();
        const timeDiff = endTime - appStartTime; // in ms
        const dateObj = dayjs(dateString);
        return dateObj.from(dayjs(window.fluentSupportAdmin.server_time).add(timeDiff, 'milliseconds'));
    }

    function dateTimeFormat(date, format) {
        const dateString = (date === undefined) ? null : date;
        const dateObj = dayjs(dateString);
        return dateObj.isValid() ? dateObj.format(format) : null;
    }
    function localDate(date) {
        return dayjs.utc(date).local();
    }
    function longLocalDate(date) {
        return dateTimeFormat(
            date, 'ddd, DD MMM, YYYY'
        );
    }

    return {
        humanDiffTime,
        dateTimeFormat,
        localDate,
        longLocalDate
    }
}
