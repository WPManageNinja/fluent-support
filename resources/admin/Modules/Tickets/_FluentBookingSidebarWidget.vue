<template>
    <collapsible-widget
        v-if="appVars.fluent_booking && appVars.fluent_booking.active && shouldShowWidget"
        :title="$t('Meeting')"
        icon="Calendar"
        :default-expanded="true"
        class="fs_sidebar_widget fs_fluent_booking_sidebar_widget"
    >
        <div class="fs_fluent_booking_sidebar" v-loading="loading">
            <div v-if="errorMessage && !loading" class="fs_fb_notice fs_fb_error">
                {{ errorMessage }}
            </div>

            <template v-if="nextMeeting">
                <div class="fs_fb_section_header">
                    <span class="fs_fb_section_label">{{ $t('Next Meeting') }}</span>
                    <a
                        v-if="meetings.upcoming.length > 1 && bookingsListUrl"
                        class="fs_fb_section_count fs_fb_section_count_link"
                        :href="bookingsListUrl"
                        target="_blank"
                        rel="noopener"
                        :title="$t('View upcoming bookings in FluentBooking')"
                    >
                        +{{ meetings.upcoming.length - 1 }} {{ $t('more') }}
                    </a>
                    <span v-else-if="meetings.upcoming.length > 1" class="fs_fb_section_count">
                        +{{ meetings.upcoming.length - 1 }} {{ $t('more') }}
                    </span>
                </div>
                <div class="fs_fb_meeting_card">
                    <div class="fs_fb_meeting_top">
                        <div class="fs_fb_meeting_status" :class="getStatusClass(nextMeeting.status)">
                            <span class="fs_fb_status_dot"></span>
                            {{ nextMeeting.status_label }}
                        </div>
                        <span v-if="nextMeeting.calendar_title" class="fs_fb_calendar_name">{{ nextMeeting.calendar_title }}</span>
                    </div>
                    <div class="fs_fb_meeting_main">
                        <div class="fs_fb_date_box">
                            <span>{{ getDatePart(nextMeeting.local_start_time || nextMeeting.start_time, 'day') }}</span>
                            <strong>{{ getDatePart(nextMeeting.local_start_time || nextMeeting.start_time, 'date') }}</strong>
                        </div>
                        <div class="fs_fb_meeting_meta">
                            <div class="fs_fb_meeting_title">{{ nextMeeting.event_title || $t('Meeting') }}</div>
                            <div class="fs_fb_meeting_time">{{ nextMeeting.date_text }}</div>
                            <div v-if="nextMeeting.timezone" class="fs_fb_meeting_timezone">
                                {{ $t('Timezone') }}: {{ nextMeeting.timezone }}
                            </div>
                            <div v-if="nextMeeting.internal_note" class="fs_fb_internal_note">
                                <span>{{ $t('Internal Note') }}</span>
                                <p>{{ nextMeeting.internal_note }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="fs_fb_actions">
                        <a
                            v-if="nextMeeting.join_url"
                            class="el-button el-button--small fs_filled_btn"
                            :href="nextMeeting.join_url"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ $t('Join Meeting') }}
                        </a>
                        <a
                            v-if="nextMeeting.admin_url"
                            class="el-button el-button--small fs_outline_btn"
                            :href="nextMeeting.admin_url"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ $t('View') }}
                        </a>
                    </div>
                </div>
            </template>

            <template v-if="pastMeetings.length">
                <div class="fs_fb_section_header">
                    <span class="fs_fb_section_label">{{ $t('Past Meetings') }}</span>
                    <a
                        v-if="pastBookingsListUrl"
                        class="fs_fb_section_count fs_fb_section_count_link"
                        :href="pastBookingsListUrl"
                        target="_blank"
                        rel="noopener"
                        :title="$t('View completed bookings in FluentBooking')"
                    >
                        {{ pastMeetings.length }}
                    </a>
                    <span v-else class="fs_fb_section_count">{{ pastMeetings.length }}</span>
                </div>
                <ul class="fs_fb_meeting_list">
                    <li v-for="meeting in pastMeetings" :key="meeting.id" class="fs_fb_meeting_item">
                        <div class="fs_fb_meeting_item_content">
                            <a
                                v-if="meeting.admin_url"
                                class="fs_fb_meeting_title fs_fb_meeting_title_link"
                                :href="meeting.admin_url"
                                target="_blank"
                                rel="noopener"
                                :title="$t('Open exact booking in FluentBooking')"
                            >
                                {{ meeting.event_title || $t('Meeting') }}
                            </a>
                            <div v-else class="fs_fb_meeting_title">{{ meeting.event_title || $t('Meeting') }}</div>
                            <div class="fs_fb_meeting_time">{{ meeting.date_text }}</div>
                            <div v-if="meeting.internal_note" class="fs_fb_internal_note fs_fb_internal_note_compact">
                                <span>{{ $t('Internal Note') }}</span>
                                <p>{{ meeting.internal_note }}</p>
                            </div>
                        </div>
                        <span class="fs_status_badge" :class="getStatusClass(meeting.status)">
                            {{ meeting.status_label }}
                        </span>
                    </li>
                </ul>
            </template>

        </div>
    </collapsible-widget>
</template>

<script type="text/babel">
import CollapsibleWidget from './parts/_CollapsibleWidget.vue';

export default {
    name: 'FluentBookingSidebarWidget',
    components: {
        CollapsibleWidget
    },
    props: {
        ticketId: {
            type: [String, Number],
            required: true
        },
        refreshKey: {
            type: [String, Number, Boolean],
            default() {
                return ''
            }
        }
    },
    data() {
        return {
            loading: false,
            meetings: {
                upcoming: [],
                past: [],
                scope: '',
                message: ''
            },
            errorMessage: '',
            meetingsRequestKey: 0
        };
    },
    computed: {
        nextMeeting() {
            return this.meetings.upcoming?.[0] || null;
        },
        pastMeetings() {
            return this.meetings.past || [];
        },
        bookingsListUrl() {
            return this.getBookingsListUrl('upcoming');
        },
        pastBookingsListUrl() {
            return this.getBookingsListUrl('completed');
        },
        shouldShowWidget() {
            return this.loading || !!this.nextMeeting || this.pastMeetings.length > 0 || !!this.errorMessage;
        }
    },
    watch: {
        ticketId() {
            this.fetchMeetings();
        },
        refreshKey() {
            this.fetchMeetings();
        }
    },
    methods: {
        fetchMeetings() {
            if (!this.ticketId) {
                return;
            }

            const requestKey = ++this.meetingsRequestKey;
            const ticketId = this.ticketId.toString();

            this.loading = true;
            this.errorMessage = '';

            this.$get(`tickets/${ticketId}/fluent-booking/meetings`)
                .then((response) => {
                    if (requestKey !== this.meetingsRequestKey || ticketId !== this.ticketId.toString()) {
                        return;
                    }

                    this.meetings = response.meetings || {
                        upcoming: [],
                        past: [],
                        scope: '',
                        message: ''
                    };
                })
                .catch((errors) => {
                    if (requestKey !== this.meetingsRequestKey || ticketId !== this.ticketId.toString()) {
                        return;
                    }

                    this.errorMessage = this.$t('Could not load FluentBooking meetings.');
                    this.$handleError(errors);
                })
                .always(() => {
                    if (requestKey !== this.meetingsRequestKey || ticketId !== this.ticketId.toString()) {
                        return;
                    }

                    this.loading = false;
                });
        },
        getStatusClass(status) {
            const normalizedStatus = (status || '').toString().toLowerCase();

            if (['completed'].includes(normalizedStatus)) {
                return 'fs_status_closed';
            }

            if (['cancelled', 'rejected', 'no_show'].includes(normalizedStatus)) {
                return 'fs_status_cancelled';
            }

            if (['scheduled', 'pending'].includes(normalizedStatus)) {
                return 'fs_status_active';
            }

            return `fs_status_${normalizedStatus}`;
        },
        getBookingsListUrl(period) {
            const adminUrl = this.appVars.fluent_booking?.admin_url || '';

            if (!adminUrl) {
                return '';
            }

            return adminUrl.replace(/\/?$/, '/') + `scheduled-events?period=${period}`;
        },
        getDatePart(date, part) {
            if (!date) {
                return part === 'day' ? '--' : '';
            }

            const parsed = this.$dayjs(date);

            if (!parsed.isValid()) {
                return part === 'day' ? '--' : '';
            }

            return part === 'date' ? parsed.date() : parsed.format('ddd');
        },
        handleBookingLinkSent(event) {
            const ticketId = event?.detail?.ticket_id;

            if (!ticketId || ticketId.toString() !== this.ticketId.toString()) {
                return;
            }

            this.fetchMeetings();
        }
    },
    mounted() {
        window.addEventListener('fluent-support/fluent-booking-link-sent', this.handleBookingLinkSent);
        this.fetchMeetings();
    },
    beforeUnmount() {
        window.removeEventListener('fluent-support/fluent-booking-link-sent', this.handleBookingLinkSent);
    }
}
</script>
