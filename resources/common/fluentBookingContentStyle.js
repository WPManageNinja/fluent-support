export function getFluentBookingContentStyle(options = {}) {
    const {
        titleColor = '#0E121B',
        textSecondary = '#525866',
        textPrimary = '#0E121B',
        borderColor = '#E1E4EA',
        backgroundColor = '#FFFFFF',
        subtleBackground = '#F5F7FA',
        radius = '8px',
        focusRing = 'rgba(153, 160, 174, 0.16)'
    } = options;

    return `
        .fs_fluent_booking_suggested_times { margin: 0 0 20px; }
        .fs_fluent_booking_suggested_times__title { margin: 0 0 12px; }
        .fs_fluent_booking_suggested_times__meta { margin: 0 0 8px; color: ${textSecondary}; font-size: 13px; line-height: 18px; }
        .fs_fluent_booking_suggested_times__change { color: ${textPrimary}; font-weight: 500; text-decoration: none; }
        .fs_fluent_booking_suggested_times__change:hover,
        .fs_fluent_booking_suggested_times__change:focus { color: ${textPrimary}; text-decoration: underline; }
        .fs_fluent_booking_suggested_times__group { margin: 0 0 18px; }
        .fs_fluent_booking_suggested_times__heading { margin: 0 0 10px; color: ${titleColor}; font-size: 16px; line-height: 24px; }
        .fs_fluent_booking_suggested_times__slots { font-size: 0; line-height: 0; }
        .fs_fluent_booking_suggested_times__slot {
            display: inline-block;
            margin: 0 8px 8px 0;
            padding: 6px 12px;
            border: 1px solid ${borderColor};
            border-radius: ${radius};
            background: transparent;
            color: ${textSecondary};
            font-size: 13px;
            line-height: 18px;
            font-weight: 500;
            letter-spacing: -0.084px;
            text-decoration: none;
            white-space: nowrap;
        }
        .fs_fluent_booking_suggested_times__slot:hover,
        .fs_fluent_booking_suggested_times__slot:focus {
            border-color: ${borderColor};
            background: ${subtleBackground};
            color: ${textPrimary};
            text-decoration: none;
        }
        .fs_fluent_booking_suggested_times__slot:focus {
            box-shadow: 0 0 0 2px ${backgroundColor}, 0 0 0 4px ${focusRing};
        }
    `;
}
