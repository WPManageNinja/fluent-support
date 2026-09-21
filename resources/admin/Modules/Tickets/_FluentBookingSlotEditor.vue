<template>
    <el-popover
        placement="bottom-end"
        :width="260"
        trigger="click"
        :visible="visible"
        popper-class="fs_fluent_booking_slot_editor"
    >
        <template #reference>
            <slot name="reference" />
        </template>

        <div class="fs_fluent_booking_slot_editor_body">
            <div class="fs_fluent_booking_slot_editor_header">
                <strong>{{ $t('Available times') }}</strong>
                <span>{{ draftSlotIds.length }} {{ $t('selected') }}</span>
            </div>
            <div class="fs_fluent_booking_slot_editor_links">
                <el-button class="fs_outline_btn" text @click="selectAll">
                    {{ $t('Select all') }}
                </el-button>
                <el-button class="fs_outline_btn" text @click="clearAll">
                    {{ $t('Clear') }}
                </el-button>
            </div>
            <div class="fs_fluent_booking_slot_checks">
                <el-checkbox
                    v-for="slot in day.slots"
                    :key="slot.id"
                    :model-value="draftSlotIds.includes(slot.id)"
                    class="fs_fluent_booking_slot_check"
                    :class="{ 'is-active': draftSlotIds.includes(slot.id) }"
                    @change="toggle(slot)"
                >
                    <span>{{ slot.time_label }}</span>
                </el-checkbox>
            </div>
            <div class="fs_fluent_booking_slot_editor_footer">
                <el-button class="fs_outline_btn" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </el-button>
                <el-button class="fs_filled_btn" @click="apply">
                    {{ $t('Done') }}
                </el-button>
            </div>
        </div>
    </el-popover>
</template>

<script type="text/babel">
export default {
    name: 'FluentBookingSlotEditor',
    props: {
        day: {
            type: Object,
            required: true
        },
        visible: {
            type: Boolean,
            default: false
        },
        selectedSlotIds: {
            type: Array,
            default: () => []
        }
    },
    emits: ['apply', 'close'],
    data() {
        return {
            draftSlotIds: []
        };
    },
    watch: {
        visible(newVal) {
            if (newVal) {
                this.draftSlotIds = [...this.selectedSlotIds];
            }
        }
    },
    methods: {
        selectAll() {
            this.draftSlotIds = this.day.slots.map((slot) => slot.id);
        },
        clearAll() {
            this.draftSlotIds = [];
        },
        toggle(slot) {
            if (this.draftSlotIds.includes(slot.id)) {
                this.draftSlotIds = this.draftSlotIds.filter((id) => id !== slot.id);
            } else {
                this.draftSlotIds.push(slot.id);
            }
        },
        apply() {
            this.$emit('apply', this.day.slots.filter((slot) => this.draftSlotIds.includes(slot.id)));
        }
    }
}
</script>
