import dayjs from 'dayjs';
import { $t } from '@/admin/Bits/i18n';

const shortcuts = [
  {
    text: $t("Today"),
    value: () => {
      const start = dayjs().startOf('day').toDate();
      const end = dayjs().endOf('day').toDate();
      return [start, end];
    },
  },
  {
    text: $t("This Week"),
    value: () => {
      const start = dayjs().startOf('week').toDate();
      const end = dayjs().endOf('week').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last Week"),
    value: () => {
      const start = dayjs().subtract(1, 'week').startOf('week').toDate();
      const end = dayjs().subtract(1, 'week').endOf('week').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last Month"),
    value: () => {
      const start = dayjs().subtract(1, 'month').startOf('month').toDate();
      const end = dayjs().subtract(1, 'month').endOf('month').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last 3 Months"),
    value: () => {
      const start = dayjs().subtract(3, 'month').startOf('month').toDate();
      const end = dayjs().endOf('day').toDate();
      return [start, end];
    },
  },
  {
      text: $t("Last 6 Months"),
      value: () => {
        const start = dayjs().subtract(6, 'month').startOf('month').toDate();
        const end = dayjs().endOf('day').toDate();
        return [start, end];
      },
  },
  {
      text: $t("Last 1 Year"),
      value: () => {
        const start = dayjs().subtract(1, 'year').startOf('day').toDate();
        const end = dayjs().endOf('day').toDate();
        return [start, end];
      },
  },
];

const additionalShortcuts = [
  {
    text: $t("Today"),
    value: () => {
      const start = dayjs().startOf('day').toDate();
      const end = dayjs().endOf('day').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Yesterday"),
    value: () => {
      const start = dayjs().subtract(1, 'day').startOf('day').toDate();
      const end = dayjs().subtract(1, 'day').endOf('day').toDate();
      return [start, end];
    },
  },
  {
    text: $t("This Week"),
    value: () => {
      const start = dayjs().startOf('week').toDate();
      const end = dayjs().endOf('week').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last 7 Days"),
    value: () => {
      const start = dayjs().subtract(6, 'day').startOf('day').toDate();
      const end = dayjs().endOf('day').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last 2 Weeks"),
    value: () => {
      const start = dayjs().subtract(2, 'week').startOf('week').toDate();
      const end = dayjs().subtract(1, 'week').endOf('week').toDate();
      return [start, end];
    },
  },
  {
    text: $t("This Month"),
    value: () => {
      const start = dayjs().startOf('month').toDate();
      const end = dayjs().endOf('month').toDate();
      return [start, end];
    },
  },
  {
    text: $t("Last Month"),
    value: () => {
      const start = dayjs().subtract(1, 'month').startOf('month').toDate();
      const end = dayjs().subtract(1, 'month').endOf('month').toDate();
      return [start, end];
    },
  },
];

export { shortcuts, additionalShortcuts };
