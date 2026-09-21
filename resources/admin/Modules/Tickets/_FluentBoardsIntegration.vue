<template>
    <div v-if="app_ready" class="fs_fluent_boards" >
        <div class="fs_fbs_select_container">
            <div class="fs_fbs_select_wrapper">
                <label for="select-board" class="fs_fbs_select_label fs_field_label">{{ $t("Select Board") }}</label>
                <el-select class="fs_select_field" id="select-board" @change="fetchStages" v-model="boardId">
                    <el-option
                        v-for="board in fluentBoardsData"
                        :key="board.id"
                        :value="board.id"
                        :label="board.title"></el-option>
                </el-select>
            </div>
            <div class="fs_fbs_select_wrapper">
                <label for="select-task" class="fs_fbs_select_label fs_field_label">{{ $t("Select Stage") }}</label>
                <el-select class="fs_select_field" id="select-task"  v-model="stageId" :disabled="!boardId">
                    <el-option
                        v-for="stage in  fluentBoardStages"
                        :key="stage.id"
                        :value="stage.id"
                        :label="stage.title"></el-option>
                </el-select>
            </div>
        </div>

        <div>
            <div class="fs_fbs_date_range">
                <div class="fs_start_date_picker">
                    <label for="ticket-content" class="fs_fbs_input_label fs_field_label">{{ $t("Start date ") }}</label>
                    <el-date-picker
                        v-model="startDate"
                        class="fs_date_picker fs_text_input"
                        type="date"
                        :placeholder="$t('Pick a day')"
                        value-format="YYYY-MM-DD"
                    />
                </div>
                <div class="fs_end_date_picker">
                    <label for="ticket-content" class="fs_fbs_input_label fs_field_label">{{ $t("End date ") }}</label>
                    <el-date-picker
                        v-model="endDate"
                        type="date"
                        class="fs_date_picker fs_text_input"
                        :placeholder="$t('Pick a day')"
                        value-format="YYYY-MM-DD"
                    />
                </div>
            </div>

            <div class="fs_fbs_input_container">
                <label for="ticket-title" class="fs_fbs_input_label fs_field_label">{{ $t("Task Title") }}</label>
                <el-input class="fs_text_input" id="ticket-title" v-model="task.title" />
            </div>

            <div class="fs_fbs_input_container">
                <label for="ticket-content" class="fs_fbs_input_label fs_field_label">{{ $t("Task Description") }}</label>
                <wp-editor id="ticket-content" v-model="task.content" />
            </div>


            <div class="fs_fbs_button_container">
                <div class="fs_text_right">
                    <el-button class="fs_filled_btn" :disabled="!isAddButtonEnabled" @click="createTask" type="success">
                        {{ $t('Create Task') }}
                    </el-button>
                </div>
                </div>
            </div>
        </div>
    <div v-else>
        <el-skeleton :rows="5" animated/>
    </div>
</template>

<script type="text/babel">
import WpEditor from '../../Pieces/_wp_editor';

export default {
    name: 'FluentBoardsIntegration',
    props: {
        ticket: {
            type: Object,
            required: true
        },
        fluentcrm_profile: {
            type: Object,
            required: true
        }
    },
    components: {
        WpEditor
    },
    data() {
        return {
            loading: false,
            fluentBoardsData: [],
            fluentBoardStages: [],
            boardId: null,
            stageId: null,
            app_ready: false,
            startDate: '',
            endDate: '',
            task: { ...this.ticket },
            has_pro: null
        };
    },
    computed: {
        isAddButtonEnabled() {
            return this.task.title !== '' && this.stageId !== null;
        }
    },
    mounted() {
        this.fetchBoards();
    },
    methods: {
        fetchBoards() {
            this.$get('tickets/fluent-boards/boards')
                .then(response => {
                    this.fluentBoardsData = response.boards;
                    this.app_ready = true;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },
        fetchStages() {
            this.$get('tickets/fluent-boards/stages/' + this.boardId)
                .then(response => {
                    this.fluentBoardStages = response.stages;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                });
        },
        createTask() {
            this.$post('tickets/fluent-boards/stages', {
                source_id: this.task.id,
                title: this.task.title,
                description: this.task.content,
                board_id: this.boardId,
                stage_id: this.stageId,
                started_at: this.startDate,
                due_at: this.endDate,
                source: 'FluentSupport',
                crm_contact_id: this.fluentcrm_profile.id || null
            })
                .then(response => {
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                    this.$emit('created');
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.loading = false;
                });
        }
    }
}
</script>
