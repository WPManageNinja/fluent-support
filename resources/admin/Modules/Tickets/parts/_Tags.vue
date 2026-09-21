<template>
  <div v-if="appVars.ticket_tags.length" v-loading="doing_action" class="fs_tags" :class="{'fs_ticket_tags_mode': true}">
    <!-- Ticket List Mode - Show first tag + count -->
    <template v-if="mode === 'ticket_list' && tags && tags.length">
      <div class="fs_tag_item">
        <img :src="appVars.asset_url + 'images/tags.svg'" alt="">
        <span class="fs_tag_text">{{ tags[0].title }}</span>
        <el-tooltip
            v-if="tags.length > 1"
            effect="dark"
            placement="top"
            popper-class="fc-tooltip"
        >
          <template #content>
            <div v-for="tag in tags.slice(1)" :key="tag.id">
              {{ tag.title }}
            </div>
          </template>
          <span class="fs_tag_count">+{{ tags.length - 1 }}</span>
        </el-tooltip>
      </div>
    </template>

    <!-- Default Mode - Show all tags with new style -->
    <template v-else>
      <div class="fs_tag_item" v-for="(tag, tagIndex) in tags" :key="tag.id">
          <img :src="appVars.asset_url + 'images/tags.svg'" alt="">
          <div class="fs_tag_text">
              {{ tag.title }}
          </div>
        <el-icon
          v-if="creatable"
          class="fs_tag_close_icon"
          @click="removeTag(tagIndex, tag)"
        >
          <Close />
        </el-icon>
      </div>

      <!-- Add tag functionality - always show if creatable -->
      <el-popover
          v-if="creatable"
          placement="bottom"
          trigger="click"
      >
        <template #reference>
          <span style="cursor: pointer" class="fs_add_tag_icon fs_tag_item"><el-icon><Plus /></el-icon></span>
        </template>
        <div class="fs_tags_add_wrapper">
          <h3>{{$t('Add Tags')}}</h3>
          <ul v-loading="adding_tag" v-if="available_tags.length">
            <li v-for="tag in available_tags" :key="tag.id" @click="attachTag(tag)">
              <el-icon> <Plus/> </el-icon>
              {{tag.title}}
            </li>
          </ul>
          <p v-else>{{$t('No available tags found')}}</p>
        </div>
      </el-popover>
    </template>
  </div>
</template>
<script type="text/babel">

export default {
    name: "ticketTags",
    props: ["ticket_id", "tags", "creatable", "mode"],

    data() {
        return {
            doing_action: false,
            adding_tag: false
        };
    },
    computed: {
        available_tags() {
            if (!this.creatable) {
                return [];
            }

            const appliedTagIds = this.tags.map(t => parseInt(t.id));

            return this.appVars.ticket_tags.filter(tag => {
                return appliedTagIds.indexOf(parseInt(tag.id)) === -1;
            });
        },
    },

    methods: {
        removeTag(tagIndex, tag) {
            this.doing_action = true;

            this.$del(`tickets/${this.ticket_id}/tags/${tag.id}`)
                .then((response) => {
                    this.tags.splice(tagIndex, 1);
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.doing_action = false;
                });
        },

        attachTag(tag) {
            this.adding_tag = true;

            this.$post(`tickets/${this.ticket_id}/tags`, {
                tag_id: tag.id,
            })
                .then((response) => {
                    this.tags.push(tag);
                    this.$notify({
                        message: response.message,
                        type: "success",
                        position: "bottom-right",
                    });
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .always(() => {
                    this.adding_tag = false;
                });
        },
    },
};
</script>

