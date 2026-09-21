<template>
    <el-dropdown trigger="click" placement="bottom-start" popper-class="fs_live_snapshot_popper" @command="() => {}">
        <button class="fs_pill_dropdown_btn fs_live_pill_btn">
            <IconPack icon-key="camera_line" :width="20" :height="20" />
            {{ triggerLabel }}
            <el-icon class="fs_dropdown_arrow"><ArrowDown /></el-icon>
        </button>
        <template #dropdown>
            <div class="fs_live_snapshot_panel">
                <button
                    class="fs_radio_row"
                    :class="{ 'is-selected': modelValue === 'live' }"
                    @click="selectMode('live')"
                >
                    <span class="fs_radio_dot" :class="{ 'is-selected': modelValue === 'live' }"></span>
                    <span class="fs_radio_label">{{ $t("Live") }}</span>
                </button>

                <button
                    class="fs_radio_row"
                    :class="{ 'is-selected': modelValue === 'snapshot' }"
                    @click="selectMode('snapshot')"
                >
                    <span class="fs_radio_dot" :class="{ 'is-selected': modelValue === 'snapshot' }"></span>
                    <span class="fs_radio_label">{{ $t("Snapshot") }}</span>
                </button>

                <div class="fs_snapshot_history" v-if="modelValue === 'snapshot'">
                    <p class="fs_snapshot_status" v-if="loading">{{ $t("Loading snapshots…") }}</p>
                    <p class="fs_snapshot_status" v-else-if="fetchError">{{ $t("Couldn't load snapshots.") }}</p>
                    <p class="fs_snapshot_status" v-else-if="!snapshotGroups.length">{{ $t("No snapshots yet.") }}</p>
                    <template v-else>
                        <div class="fs_snapshot_group" v-for="(group, gi) in snapshotGroups" :key="gi">
                            <p class="fs_snapshot_group_label">{{ group.label }}</p>
                            <button
                                class="fs_snapshot_item"
                                v-for="(item, ii) in group.items"
                                :key="ii"
                                :class="{ 'is-selected': isSelectedSnapshot(item) }"
                                @click="selectSnapshot(item)"
                            >
                                <span class="fs_radio_dot fs_radio_dot--sm" :class="{ 'is-selected': isSelectedSnapshot(item) }"></span>
                                <span class="fs_snapshot_time">{{ item.time }}</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </el-dropdown>
</template>

<script type="text/babel">
import { ArrowDown } from "@element-plus/icons-vue";
import IconPack from "@/admin/Components/IconPack.vue";
import dayjs from "dayjs";

export default {
    name: "LiveSnapshotSelector",
    props: {
        modelValue: {
            type: String,
            default: "live"
        },
        selectedSnapshot: {
            type: Object,
            default: null
        },
        reportType: {
            type: String,
            required: true
        }
    },
    emits: ["update:modelValue", "update:selectedSnapshot", "change"],
    components: {
        ArrowDown,
        IconPack
    },
    data() {
        return {
            loading: false,
            fetchError: false,
            snapshots: []
        };
    },
    computed: {
        triggerLabel() {
            if (this.modelValue === "snapshot") {
                if (!this.selectedSnapshot) {
                    return this.$t("Snapshot");
                }
                // Snapshots recur across multiple days, so the trigger must
                // show the date alongside the time — otherwise, once the
                // dropdown closes, there's no way to tell which day's
                // snapshot is being viewed (worst on pages like Workload
                // Health, which have no date-range control or banner).
                return `${dayjs(this.selectedSnapshot.date).format("MMM D")}, ${this.selectedSnapshot.time}`;
            }
            return this.$t("Live");
        },
        snapshotGroups() {
            const groups = [];
            const groupsByDate = {};

            this.snapshots.forEach((snapshot) => {
                const stamp = dayjs(snapshot.snapshot_time);
                const dateKey = stamp.format("YYYY-MM-DD");

                if (!groupsByDate[dateKey]) {
                    const dayLabel = stamp.isSame(dayjs(), "day")
                        ? this.$t("Today")
                        : stamp.isSame(dayjs().subtract(1, "day"), "day")
                            ? this.$t("Yesterday")
                            : null;
                    const formattedDate = stamp.format("MMM DD, YYYY").toUpperCase();
                    groupsByDate[dateKey] = {
                        label: dayLabel ? `${dayLabel}, ${formattedDate}` : formattedDate,
                        items: []
                    };
                    groups.push(groupsByDate[dateKey]);
                }

                groupsByDate[dateKey].items.push({
                    id: snapshot.id,
                    date: dateKey,
                    time: stamp.format("h:mm A")
                });
            });

            return groups;
        }
    },
    methods: {
        fetchSnapshots() {
            this.loading = true;
            this.fetchError = false;
            this.$get("advanced-reports/snapshots", { report_type: this.reportType })
                .then((response) => {
                    this.snapshots = response.snapshots;
                })
                .catch((error) => {
                    this.snapshots = [];
                    this.fetchError = true;
                    this.$handleError(error);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        selectMode(mode) {
            this.$emit("update:modelValue", mode);
            if (mode === "live") {
                this.$emit("update:selectedSnapshot", null);
                this.$emit("change", { mode, snapshot: null });
            } else {
                this.$emit("change", { mode, snapshot: this.selectedSnapshot });
            }
        },
        isSelectedSnapshot(item) {
            return this.selectedSnapshot && this.selectedSnapshot.id === item.id;
        },
        selectSnapshot(item) {
            this.$emit("update:modelValue", "snapshot");
            this.$emit("update:selectedSnapshot", item);
            this.$emit("change", { mode: "snapshot", snapshot: item });
        }
    },
    mounted() {
        this.fetchSnapshots();
    }
};
</script>
