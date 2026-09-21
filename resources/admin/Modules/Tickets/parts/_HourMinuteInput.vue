<template>
    <div class="fs_minute_input">
        <div class="fs-time-input-wrapper">
            <div class="fs-time-input-container">
                <el-input
                    type="number"
                    v-model="form.hour"
                    :min="0"
                    :step="1"
                    class="fs-time-input">
                </el-input>
                <div class="fs-time-label-box">
                    <span class="fs-time-label">{{ $t('Hours') }}</span>
                </div>
            </div>
        </div>
        <div class="fs-time-input-wrapper">
            <div class="fs-time-input-container">
                <el-input
                    type="number"
                    v-model="form.minute"
                    :min="0"
                    :max="59"
                    :step="1"
                    class="fs-time-input">
                </el-input>
                <div class="fs-time-label-box">
                    <span class="fs-time-label">{{ $t('Minutes') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'HourMinuteInput',
    props: ['modelValue'],
    emits: ['update:modelValue'],
    data() {
        return {
            form: {
                hour: Math.floor(this.modelValue / 60),
                minute: Math.floor(this.modelValue % 60)
            }
        }
    },
    watch: {
        modelValue: {
            handler(newVal) {
                const newHour = Math.floor(newVal / 60);
                const newMinute = Math.floor(newVal % 60);

                // Only update if values are different to avoid infinite loop
                if (this.form.hour !== newHour || this.form.minute !== newMinute) {
                    this.form.hour = newHour;
                    this.form.minute = newMinute;
                }
            },
            immediate: true
        },
        form: {
            handler() {
                const totalMinutes = parseInt(this.form.hour || 0) * 60 + parseInt(this.form.minute || 0);
                this.$emit('update:modelValue', totalMinutes);
            },
            deep: true
        }
    }
}
</script>
