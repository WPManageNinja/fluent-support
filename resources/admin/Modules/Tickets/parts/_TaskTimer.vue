<template>
    <div class="fs-task-timer-wrap">
        <!-- Progress Bar with Percentage and Status -->
        <div class="fs-progress-row">
            <span class="fs-progress-percentage">{{ percentileCompleted }}%</span>
            <span v-if="!totalLoggedLabel" class="fs-no-logging-text">{{ $t('No logging yet!') }}</span>
            <span v-else="totalLoggedLabel" class="fs-no-logging-text">{{ totalLoggedLabel }} {{ $t('logged') }}</span>
        </div>

        <el-progress
            :color="progressColor"
            :percentage="percentileCompleted > 100 ? 100 : percentileCompleted"
            :show-text="false"
            :stroke-width="8">
        </el-progress>

        <!-- Time Labels Row -->
        <div class="fs-time-labels-row" v-if="estimatedMinutes">
            <el-popover
                ref="estimatedTimePop"
                popper-class="fs-timer-log-popover"
                :placement="estimateTimePopoverPlacement"
                :width="400"
                trigger="click">
                <template #reference>
                    <span class="fs-time-label-text">{{ totalLoggedLabel }} {{ $t('logged') }}</span>
                </template>
                <el-table stripe :data="tracks" style="width: 100%">
                    <el-table-column type="expand" width="30">
                        <template #default="{ row }">
                            <div class="fs_detail_log">
                                <p><b>{{ $t('Logged at:') }}</b> {{ smartDate(row.created_at, true) }}</p>
                                <p><b>{{ $t('Work Description:') }}</b> {{ row.message }}</p>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column prop="agent" :label="$t('Agent')">
                        <template #default="{ row }">
                            <div class="fs-time-track-agent">
                                <el-avatar :src="row.agent?.photo" :size="24"></el-avatar>
                                <a
                                    :href="`/wp-admin/user-edit.php?user_id=${row.agent.user_id}`"
                                    target="_blank">
                                    {{ row.agent?.full_name }}
                                </a>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column prop="billable_minutes" :label="$t('Time')" width="80">
                        <template #default="{ row }">
                            <span>{{ formatMinutes(row.billable_minutes) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column width="80" prop="created_at" :label="$t('Date')">
                        <template #default="{ row }">
                            <span>{{ smartDate(row.created_at) }}</span>
                        </template>
                    </el-table-column>
                </el-table>
            </el-popover>

            <el-popover
                ref="estimatedTimeForm"
                placement="right"
                :width="400"
                v-model:visible="estimatedForm"
                trigger="click">
                <template #reference>
                    <span class="fs-time-label-text">
                        {{ totalEstimationLabel }} {{ $t('estimated') }}
                    </span>
                </template>
                <div class="fs-estimation-popover">
                    <h4 class="fs-estimation-title">{{ $t('Set Estimated Time') }}</h4>
                    <HourMinuteInput v-model="estimatedMinutes"/>
                    <el-button
                        :disabled="updating"
                        v-loading="updating"
                        @click="updateEstimatedTime"
                        class="fs_filled_btn"
                        type="success">
                        {{ $t('Update Estimation') }}
                    </el-button>
                </div>
            </el-popover>
        </div>

        <!-- Action Buttons Row -->
        <div class="fs-action-buttons-row">
            <el-button @click="toggleLog" class="fs_outline_btn">
                <el-icon><Plus/></el-icon>
                <span>{{ $t('Add Log') }}</span>
            </el-button>

            <el-popover
                v-if="!estimatedMinutes"
                ref="estimatedTimeFormBtn"
                placement="right"
                :width="400"
                v-model:visible="estimatedForm"
                trigger="click">
                <template #reference>
                    <el-button class="fs_outline_btn">
                        {{ $t('Set Estimation') }}
                    </el-button>
                </template>
                <div class="fs-estimation-popover">
                    <h4 class="fs-estimation-title">{{ $t('Set Estimated Time') }}</h4>
                    <HourMinuteInput v-model="estimatedMinutes"/>
                    <el-button
                        :disabled="updating"
                        v-loading="updating"
                        @click="updateEstimatedTime"
                        class="fs_filled_btn"
                        type="success">
                        {{ $t('Update Estimation') }}
                    </el-button>
                </div>
            </el-popover>
        </div>

        <!-- New Log Form -->
        <div v-if="logForm" class="fs-new-timer">
            <el-form ref="newLogForm" v-loading="updating" label-position="top">
                <el-form-item :label="$t('Total Hours')">
                    <HourMinuteInput v-model="newLog.working_minutes"/>
                </el-form-item>
                <el-form-item :label="$t('Work Description')">
                    <el-input
                        :placeholder="$t('Describe this log entry')"
                        v-model="newLog.message"
                        type="textarea"
                        :rows="4"/>
                </el-form-item>
                <el-form-item class="fs-form-actions">
                    <el-button type="primary" :disabled="updating" v-loading="updating" @click="logTime()" class="fs_filled_btn">
                        {{ $t('Submit Log') }}
                    </el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script type="text/babel">
import HourMinuteInput from './_HourMinuteInput.vue';

export default {
    name: 'TaskTimer',
    props: ['ticket_id', 'customer_id'],
    components: {HourMinuteInput},
    data() {
        return {
            tracks: [],
            estimatedMinutes: 0,
            loading: false,
            updating: false,
            estimatedForm: false,
            logForm: false,
            estimateTimePopoverPlacement: 'left',
            newLog: {
                working_minutes: '',
                message: '',
                started_at: ''
            }
        };
    },
    computed: {
        percentileCompleted() {
            if (!this.estimatedMinutes) return 0;
            return Math.floor((this.totalLoggedMinutes / this.estimatedMinutes) * 100);
        },

        totalLoggedMinutes() {
            return this.tracks.reduce((sum, t) => sum + t.billable_minutes, 0);
        },

        progressColor() {
            return this.percentileCompleted > 100 ? '#EF4444' : '#3B82F6';
        },

        totalLoggedLabel() {
            return this.formatMinutes(this.totalLoggedMinutes);
        },

        totalEstimationLabel() {
            return this.formatMinutes(this.estimatedMinutes);
        },

        textColor() {
            return this.percentileCompleted >= 15 ? 'white' : 'black';
        }
    },

    methods: {
        fetchTracks() {
            this.loading = true;

            this.$get(`time-tracks/${this.ticket_id}`)
                .then(response => {
                    this.tracks = response.tracks;
                    this.estimatedMinutes = response.estimated_minutes;
                })
                .catch(error => this.$handleError(error));
        },

        toggleLog() {
            this.logForm = !this.logForm;
        },

        formatMinutes(minutes) {
            const intMinutes = parseInt(minutes);
            if (!intMinutes) return '';
            const hours = Math.floor(intMinutes / 60);
            const remMinutes = intMinutes % 60;
            return hours ? `${hours}h ${remMinutes}m` : `${remMinutes}m`;
        },

        logTime() {
            if (this.newLog.working_minutes < 1) {
                alert('Please enter valid time');
                return;
            }

            this.updating = true;
            this.$post(`time-tracks/${this.ticket_id}`, {
                customer_id: this.customer_id,
                ...this.newLog
            })
                .then(response => {
                    this.tracks.push(response.track);
                    this.logForm = false;
                    this.updating = false;
                    this.newLog = {working_minutes: '', message: '', started_at: ''};
                })
                .catch(error => this.$handleError(error))
                .always(() => {
                    this.updating = false;
                    this.logForm = false;
                });
        },

        updateEstimatedTime() {
            this.updating = true;

            this.$post(`time-tracks/${this.ticket_id}/estimated-time`, {
                estimated_minutes: this.estimatedMinutes
            })
                .then(() => {
                    this.updating = false;
                    this.estimatedForm = false;
                })
                .catch(error => this.$handleError(error))
                .always(() => (this.updating = false));
        }
    },

    mounted() {
        this.fetchTracks();
        if (window.innerWidth < 817) {
            this.estimateTimePopoverPlacement = 'bottom';
        }
    }
};
</script>




