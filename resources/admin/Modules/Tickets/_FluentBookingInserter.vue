<template>
    <div ref="bookingBox" class="fs_fluent_booking_box" v-if="appVars.fluent_booking && appVars.fluent_booking.active">
        <el-popover
            placement="bottom-end"
            :width="560"
            trigger="click"
            :visible="visible"
            popper-class="fs_popover fs_fluent_booking_popover"
        >
            <template #reference>
                <el-button
                    size="small"
                    class="fs_fluent_booking_toolbar_button"
                    :aria-label="$t('Call a Meeting')"
                    @click="togglePopover"
                >
                    <el-tooltip
                        effect="dark"
                        :content="$t('Call a Meeting')"
                        placement="top"
                        trigger="hover"
                        popper-class="fs_tooltip"
                        :disabled="visible"
                    >
                        <span class="fs_fluent_booking_tooltip_target">
                            <IconPack icon-key="call_meeting" :width="24" :height="24" />
                        </span>
                    </el-tooltip>
                </el-button>
            </template>

            <div ref="popoverContent" class="fs_fluent_booking_inserter" v-loading="loading">
                <div class="fs_fluent_booking_header">
                    <h4>{{ $t('Call a Meeting') }}</h4>
                    <p>{{ $t('Choose a meeting type, review available times, then insert selected booking links into your reply.') }}</p>
                </div>

                <template v-if="eventTypes.length">
                    <el-form label-position="top">
                        <el-form-item :label="$t('Meeting Type')">
                            <el-select
                                v-model="selectedEventId"
                                class="fs_select_field"
                                filterable
                                :placeholder="$t('Select Meeting Type')"
                                @change="handleEventChanged"
                            >
                                <el-option
                                    v-for="eventType in eventTypes"
                                    :key="eventType.id"
                                    :value="eventType.id"
                                    :label="getEventLabel(eventType)"
                                />
                            </el-select>
                        </el-form-item>

                        <el-form-item :label="$t('Offer Availability')">
                            <el-select
                                v-model="availabilityRange"
                                class="fs_select_field"
                                @change="handleAvailabilityRangeChange"
                            >
                                <el-option
                                    v-for="rangeOption in availabilityRanges"
                                    :key="rangeOption.value"
                                    :value="rangeOption.value"
                                    :label="rangeOption.label"
                                />
                            </el-select>

                            <div v-if="isSpecificDatesRange" class="fs_fluent_booking_specific_dates">
                                <el-date-picker
                                    ref="specificDatesPicker"
                                    v-model="specificDates"
                                    type="dates"
                                    value-format="YYYY-MM-DD"
                                    class="fs_text_input fs_fluent_booking_specific_dates_picker"
                                    popper-class="fs_fluent_booking_specific_dates_panel"
                                    :placeholder="$t('Select up to 7 dates')"
                                    :editable="false"
                                    :clearable="false"
                                    :disabled="!selectedEventId"
                                    :disabled-date="isSpecificDateDisabled"
                                    @change="handleSpecificDatesChanged"
                                    @visible-change="handleSpecificDatePickerVisibleChange"
                                    @panel-change="handleSpecificDatePanelChange"
                                />
                                <div class="fs_fluent_booking_specific_dates_meta">
                                    <span>{{ specificDatesHelperText }}</span>
                                    <strong>{{ specificDates.length }}/7</strong>
                                </div>
                            </div>
                        </el-form-item>

                        <div class="fs_fluent_booking_availability">
                            <div class="fs_fluent_booking_preview_head">
                                <div class="fs_fluent_booking_preview_title">
                                    <strong>{{ $t('Preview') }}</strong>
                                    <el-tooltip
                                        effect="dark"
                                        :content="$t('Available times are based off a sample of this event type’s upcoming availability. You can further customize times offered below')"
                                        placement="top"
                                        popper-class="fs_tooltip fs_fluent_booking_preview_tooltip"
                                    >
                                        <el-button text class="fs_fluent_booking_preview_info" :aria-label="$t('Preview help')">i</el-button>
                                    </el-tooltip>
                                </div>
                                <span>{{ selectedSlots.length }} {{ $t('selected') }}</span>
                            </div>

                            <div class="fs_fluent_booking_timezone" v-if="availabilityTimezone">
                                {{ $t('Displayed time zone') }}:
                                <strong>{{ availabilityTimezone }}</strong>
                            </div>

                            <div v-if="availabilityLoading" class="fs_fluent_booking_skeleton">
                                <div
                                    v-for="row in 3"
                                    :key="row"
                                    class="fs_fluent_booking_skeleton_day"
                                >
                                    <span></span>
                                    <div>
                                        <i v-for="slot in 5" :key="slot"></i>
                                    </div>
                                </div>
                            </div>

                            <template v-else-if="availabilityDays.length">
                                <div
                                    v-for="day in availabilityDays"
                                    :key="day.date"
                                    class="fs_fluent_booking_day"
                                >
                                    <div class="fs_fluent_booking_day_head">
                                        <h5>{{ day.label }}</h5>
                                        <div class="fs_fluent_booking_day_actions">
                                            <fluent-booking-slot-editor
                                                :day="day"
                                                :visible="editingDayDate === day.date"
                                                :selected-slot-ids="getSelectedSlotIdsForDay(day)"
                                                @apply="(slots) => onSlotEditorApply(day, slots)"
                                                @close="closeSlotEditor"
                                            >
                                                <template #reference>
                                                    <span class="fs_action_button_wrapper fs_fluent_booking_action_wrapper">
                                                       <el-button
                                                           class="fs_action_button fs_fluent_booking_day_action"
                                                           @click="openSlotEditor(day)"
                                                           text
                                                           icon="EditPen">
                                                      </el-button>
                                                    </span>
                                                </template>
                                            </fluent-booking-slot-editor>

                                            <span class="fs_action_button_wrapper fs_fluent_booking_action_wrapper">
                                                <el-button
                                                    text
                                                    class="fs_action_button fs_fluent_booking_day_delete"
                                                    :aria-label="$t('Delete row')"
                                                    :disabled="availabilityDays.length <= 1"
                                                    @click="deleteAvailabilityDay(day)"
                                                >
                                                    <IconPack icon-key="delete" :width="16" :height="16" />
                                                </el-button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="fs_fluent_booking_slots">
                                        <el-button
                                            v-for="slot in getVisibleDaySlots(day)"
                                            :key="slot.id"
                                            class="fs_fluent_booking_slot"
                                            :class="{ 'is-selected': isSlotSelected(slot) }"
                                            @click="openSlotEditor(day)"
                                        >
                                            {{ getSlotPreviewLabel(slot) }}
                                        </el-button>
                                    </div>
                                </div>
                            </template>

                            <div v-else-if="!availabilityLoading" class="fs_fluent_booking_no_slots">
                                {{ availabilityEmptyMessage }}
                            </div>
                        </div>

                        <el-form-item :label="$t('Message')">
                            <el-input
                                v-model="message"
                                type="textarea"
                                :rows="2"
                                :placeholder="$t('Add a message to include with the booking link (optional)')"
                            />
                        </el-form-item>

                        <div class="fs_fluent_booking_actions">
                            <el-button class="fs_outline_btn" @click="closePopover">
                                {{ $t('Cancel') }}
                            </el-button>
                            <el-button
                                class="fs_outline_btn"
                                :disabled="!selectedEventId || !selectedSlots.length || copying"
                                v-loading="copying"
                                @click="copyAvailability"
                            >
                                {{ $t('Copy to Clipboard') }}
                            </el-button>
                            <el-button
                                class="fs_filled_btn"
                                type="primary"
                                :disabled="!selectedEventId || creating"
                                v-loading="creating"
                                @click="insertBookingLink"
                            >
                                {{ selectedSlots.length ? $t('Insert with Times') : $t('Insert Booking Link') }}
                            </el-button>
                        </div>
                    </el-form>
                </template>

                <div v-else-if="!loading" class="fs_fluent_booking_empty">
                    <p>{{ emptyMessage }}</p>
                    <a v-if="adminUrl" :href="adminUrl" target="_blank" rel="noopener">
                        {{ $t('Open FluentBooking') }}
                    </a>
                </div>
            </div>
        </el-popover>
    </div>
</template>

<script type="text/babel">
import IconPack from '../../Components/IconPack.vue';
import FluentBookingSlotEditor from './_FluentBookingSlotEditor.vue';

export default {
    name: 'FluentBookingInserter',
    components: {
        IconPack,
        FluentBookingSlotEditor
    },
    props: {
        ticketId: {
            type: [String, Number],
            required: true
        }
    },
    emits: ['insert'],
    data() {
        return {
            visible: false,
            loading: false,
            creating: false,
            copying: false,
            availabilityLoading: false,
            availabilityRequestKey: 0,
            specificDateAvailabilityLoading: false,
            specificDateAvailabilityRequestKey: 0,
            eventTypes: [],
            selectedEventId: '',
            availabilityRange: 'next_3_days',
            availabilityTimezone: '',
            availabilityMaxLookupDate: '',
            availabilityDays: [],
            selectedSlots: [],
            editingDayDate: '',
            specificDates: [],
            specificDatePanelMonth: this.$dayjs().format('YYYY-MM'),
            specificDateAvailabilityDays: [],
            message: '',
            serverEmptyMessage: '',
            adminUrl: this.appVars.fluent_booking?.admin_url || ''
        };
    },
    computed: {
        isSpecificDatesRange() {
            return this.availabilityRange === 'specific_dates';
        },
        availabilityRanges() {
            return [
                { label: this.$t('Next 3 days'),    value: 'next_3_days' },
                { label: this.$t('This week'),       value: 'this_week' },
                { label: this.$t('Next week'),       value: 'next_week' },
                { label: this.$t('Next 14 days'),    value: 'next_14_days' },
                { label: this.$t('Specific Dates'),  value: 'specific_dates' }
            ];
        },
        emptyMessage() {
            return this.serverEmptyMessage || this.$t('No public FluentBooking event types found. Please enable calendar sharing or publish an event type.');
        },
        specificDatesHelperText() {
            if (this.specificDateAvailabilityLoading) {
                return this.$t('Loading available dates...');
            }

            return this.$t('Only FluentBooking available dates can be selected');
        },
        availabilityEmptyMessage() {
            if (this.isSpecificDatesRange && !this.specificDates.length) {
                return this.$t('Select up to 7 dates to preview availability.');
            }

            return this.isSpecificDatesRange
                ? this.$t('No available times found for the selected dates.')
                : this.$t('No available times found for this range.');
        }
    },
    methods: {
        togglePopover() {
            if (this.visible) {
                this.closePopover();
                return;
            }

            this.visible = true;

            if (!this.eventTypes.length) {
                this.fetchEventTypes();
            } else {
                this.fetchAvailability();
            }
        },
        closePopover() {
            this.visible = false;
            this.closeSlotEditor();
        },
        handleOutsideClick(event) {
            if (!this.visible) {
                return;
            }

            const target = event.target;
            const bookingBox = this.$refs.bookingBox;
            const popoverContent = this.$refs.popoverContent;

            if (
                bookingBox?.contains(target) ||
                popoverContent?.contains(target) ||
                target.closest?.('.fs_fluent_booking_popover, .fs_fluent_booking_slot_editor, .fs_fluent_booking_specific_dates_panel, .el-select__popper, .fs_tooltip')
            ) {
                return;
            }

            this.closePopover();
        },
        fetchEventTypes() {
            this.loading = true;

            this.$get('tickets/fluent-booking/event-types')
                .then((response) => {
                    this.eventTypes = response.event_types || [];
                    this.adminUrl = response.status?.admin_url || this.adminUrl;

                    if (!this.eventTypes.length) {
                        this.serverEmptyMessage = response.status?.message || '';
                    } else if (!this.selectedEventId) {
                        this.selectedEventId = this.eventTypes[0].id;
                        this.handleEventChanged();
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        handleEventChanged() {
            this.selectedSlots = [];
            this.refreshSpecificDatesState();
            this.fetchAvailability();
        },
        handleAvailabilityRangeChange() {
            this.refreshSpecificDatesState();
            this.fetchAvailability();
        },
        fetchAvailability() {
            const requestKey = ++this.availabilityRequestKey;

            if (!this.selectedEventId) {
                this.clearAvailabilityPreview();
                return;
            }

            if (this.isSpecificDatesRange && !this.specificDates.length) {
                this.clearAvailabilityPreview();
                return;
            }

            this.availabilityLoading = true;

            const requestParams = {
                event_type_id: this.selectedEventId,
                range: this.availabilityRange
            };

            if (this.isSpecificDatesRange) {
                requestParams.selected_dates = this.specificDates;
            }

            this.$get(`tickets/${this.ticketId}/fluent-booking/availability`, requestParams)
                .then((response) => {
                    if (requestKey !== this.availabilityRequestKey) {
                        return;
                    }

                    const availability = response.availability || {};
                    this.availabilityTimezone = availability.timezone || '';
                    this.availabilityMaxLookupDate = availability.max_lookup_date || '';
                    this.availabilityDays = availability.days || [];
                    this.selectVisibleSlots();
                    this.syncSelectedSlots();
                })
                .catch((errors) => {
                    if (requestKey !== this.availabilityRequestKey) {
                        return;
                    }

                    this.clearAvailabilityPreview();
                    this.$handleError(errors);
                })
                .always(() => {
                    if (requestKey !== this.availabilityRequestKey) {
                        return;
                    }

                    this.availabilityLoading = false;
                });
        },
        clearAvailabilityPreview() {
            this.availabilityTimezone = '';
            this.availabilityDays = [];
            this.selectedSlots = [];
            this.availabilityLoading = false;
        },
        refreshSpecificDatesState() {
            if (!this.isSpecificDatesRange) {
                return;
            }

            if (this.specificDates.length) {
                this.specificDatePanelMonth = this.$dayjs(this.specificDates[0]).format('YYYY-MM');
            }

            this.fetchSpecificDateAvailability();
        },
        fetchSpecificDateAvailability() {
            const requestKey = ++this.specificDateAvailabilityRequestKey;

            if (!this.selectedEventId || !this.isSpecificDatesRange) {
                this.specificDateAvailabilityDays = [];
                this.specificDateAvailabilityLoading = false;
                return;
            }

            this.specificDateAvailabilityLoading = true;

            this.$get(`tickets/${this.ticketId}/fluent-booking/availability`, {
                event_type_id: this.selectedEventId,
                range: this.availabilityRange,
                calendar_month: this.specificDatePanelMonth
            })
                .then((response) => {
                    if (requestKey !== this.specificDateAvailabilityRequestKey) {
                        return;
                    }

                    const availability = response.availability || {};
                    this.availabilityMaxLookupDate = availability.max_lookup_date || '';
                    this.specificDateAvailabilityDays = availability.days || [];
                    this.syncSpecificDatesWithAvailability();
                })
                .catch((errors) => {
                    if (requestKey !== this.specificDateAvailabilityRequestKey) {
                        return;
                    }

                    this.specificDateAvailabilityDays = [];
                    this.$handleError(errors);
                })
                .always(() => {
                    if (requestKey !== this.specificDateAvailabilityRequestKey) {
                        return;
                    }

                    this.specificDateAvailabilityLoading = false;
                });
        },
        syncSpecificDatesWithAvailability() {
            // Only drop selected dates that fall in the currently viewed calendar month
            // and are no longer available. Dates from other months are left untouched
            // because we haven't loaded availability data for them yet.
            const availableDates = new Set(this.specificDateAvailabilityDays.map((day) => day.date));
            const syncedDates = this.specificDates.filter((date) => {
                return this.$dayjs(date).format('YYYY-MM') !== this.specificDatePanelMonth || availableDates.has(date);
            });

            if (syncedDates.length === this.specificDates.length) {
                return;
            }

            this.specificDates = syncedDates;

            if (this.specificDates.length) {
                this.fetchAvailability();
            } else {
                this.clearAvailabilityPreview();
            }
        },
        handleSpecificDatesChanged(value) {
            let nextDates = Array.isArray(value) ? [...new Set(value)].sort() : [];

            if (nextDates.length > 7) {
                nextDates = nextDates.slice(0, 7);
                this.$notify({
                    type: 'warning',
                    title: this.$t('Limit reached'),
                    message: this.$t('You can select up to 7 dates.'),
                    position: 'bottom-right'
                });
            }

            this.specificDates = nextDates;
            this.fetchAvailability();
        },
        handleSpecificDatePickerVisibleChange(visible) {
            if (visible) {
                this.fetchSpecificDateAvailability();
            }
        },
        handleSpecificDatePanelChange(date) {
            const panelDate = Array.isArray(date) ? date[0] : date;
            const nextMonth = this.$dayjs(panelDate).format('YYYY-MM');

            if (!nextMonth || nextMonth === 'Invalid Date') {
                return;
            }

            if (nextMonth === this.specificDatePanelMonth) {
                return;
            }

            this.specificDatePanelMonth = nextMonth;
            this.fetchSpecificDateAvailability();
        },
        isSpecificDateDisabled(date) {
            const day = this.$dayjs(date).startOf('day');

            if (!day.isValid() || day.isBefore(this.$dayjs().startOf('day'))) {
                return true;
            }

            if (this.availabilityMaxLookupDate) {
                const maxDate = this.$dayjs(this.availabilityMaxLookupDate).endOf('day');

                if (maxDate.isValid() && day.isAfter(maxDate)) {
                    return true;
                }
            }

            if (day.format('YYYY-MM') !== this.specificDatePanelMonth) {
                return true;
            }

            if (this.specificDateAvailabilityLoading) {
                return true;
            }

            return !this.specificDateAvailabilityDays.some((availabilityDay) => availabilityDay.date === day.format('YYYY-MM-DD'));
        },
        syncSelectedSlots() {
            this.selectedSlots = this.selectedSlots.filter((selectedSlot) => {
                return this.availabilityDays.some((day) => {
                    return day.slots.some((slot) => slot.id === selectedSlot.id);
                });
            });

        },
        selectVisibleSlots() {
            this.selectedSlots = [];

            this.availabilityDays.forEach((day) => {
                this.selectedSlots.push(...this.getDefaultDaySlots(day));
            });
        },
        getDefaultDaySlots(day) {
            return day.slots.slice(0, 5);
        },
        getVisibleDaySlots(day) {
            return day.slots.filter((slot) => this.isSlotSelected(slot));
        },
        getSlotPreviewLabel(slot) {
            return slot.time_label;
        },
        getSelectedSlotIdsForDay(day) {
            return day.slots
                .filter((slot) => this.isSlotSelected(slot))
                .map((slot) => slot.id);
        },
        openSlotEditor(day) {
            this.editingDayDate = day.date;
        },
        closeSlotEditor() {
            this.editingDayDate = '';
        },
        onSlotEditorApply(day, selectedDaySlots) {
            const daySlotIds = day.slots.map((slot) => slot.id);

            this.selectedSlots = [
                ...this.selectedSlots.filter((slot) => !daySlotIds.includes(slot.id)),
                ...selectedDaySlots
            ];

            this.closeSlotEditor();
        },
        deleteAvailabilityDay(day) {
            if (this.availabilityDays.length <= 1) {
                return;
            }

            const daySlotIds = day.slots.map((slot) => slot.id);

            this.availabilityDays = this.availabilityDays.filter((availabilityDay) => availabilityDay.date !== day.date);
            this.selectedSlots = this.selectedSlots.filter((slot) => !daySlotIds.includes(slot.id));

            if (this.editingDayDate === day.date) {
                this.closeSlotEditor();
            }
        },
        isSlotSelected(slot) {
            return this.selectedSlots.some((selectedSlot) => selectedSlot.id === slot.id);
        },
        async copyAvailability() {
            if (!this.selectedSlots.length) {
                return;
            }

            this.copying = true;

            try {
                const response = await this.createTokenizedBookingLink();
                const html = response.html || '';
                const plainText = response.plain_text || '';

                if (window.ClipboardItem) {
                    await navigator.clipboard.write([
                        new ClipboardItem({
                            'text/html': new Blob([html], { type: 'text/html' }),
                            'text/plain': new Blob([plainText], { type: 'text/plain' })
                        })
                    ]);
                } else {
                    await navigator.clipboard.writeText(plainText);
                }

                this.$notify({
                    type: 'success',
                    title: this.$t('Success'),
                    message: this.$t('Copied to clipboard'),
                    position: 'bottom-right'
                });
            } catch (error) {
                this.$notify({
                    type: 'error',
                    title: this.$t('Error'),
                    message: this.$t('Could not copy to clipboard'),
                    position: 'bottom-right'
                });
            } finally {
                this.copying = false;
            }
        },
        createTokenizedBookingLink() {
            return this.$post(`tickets/${this.ticketId}/fluent-booking/booking-link`, {
                event_type_id: this.selectedEventId,
                message: this.message,
                timezone: this.availabilityTimezone,
                selected_slots: this.selectedSlots.map((slot) => ({
                    start: slot.start
                }))
            });
        },
        insertBookingLink() {
            if (!this.selectedEventId) {
                return;
            }

            this.creating = true;

            this.createTokenizedBookingLink()
                .then((response) => {
                    this.$emit('insert', response.html);
                    this.closePopover();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.creating = false;
                });
        },
        getEventLabel(eventType) {
            const parts = [eventType.title];

            if (eventType.calendar_title) {
                parts.push(eventType.calendar_title);
            }

            if (eventType.duration) {
                parts.push(`${eventType.duration} ${this.$t('min')}`);
            }

            return parts.join(' • ');
        }
    },
    mounted() {
        document.addEventListener('mousedown', this.handleOutsideClick, true);
    },
    beforeUnmount() {
        document.removeEventListener('mousedown', this.handleOutsideClick, true);
    }
}
</script>
