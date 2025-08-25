<template>
    <div class="wp_vue_editor_wrapper">
        <div class="fs_action_buttons">
            <div class="fs_ai_tools_box" v-if="fluentBotIntegration">
                <el-popover
                    placement="bottom"
                    :width="480"
                    trigger="click"
                    :visible="showFluentBotAIResponseBox"
                >
                    <template #reference>
                        <el-button class="fs_fluent_bot_response_button" @click="showFluentBotAIResponseBox = !showFluentBotAIResponseBox">
                            <div>
                                <img :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                            </div>
                            <p>
                                {{$t('Ask FluentBot')}}
                            </p>
                        </el-button>
                    </template>
                    <div class="fs_template_inserter">
                        <div>
                            <FluentBotAIResponseGenerator type="createResponse" :ticketId="ticketId" :productID="productID" :is_agent="is_agent" @close="closeFluentBotAIResponsePromptBox" @insert="insertAIResponse"/>
                        </div>
                    </div>
                </el-popover>
            </div>
            <div class="fs_ai_tools_box" v-if="openAIIntegration">
                <el-popover
                    placement="bottom"
                    :width="480"
                    trigger="click"
                    :visible="showAIResponseBox"
                >
                    <template #reference>
                        <el-button class="fs_openAI_response_button" @click="showAIResponseBox = !showAIResponseBox">
                            <div>
                                <img :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                            </div>
                            <p>
                                {{$t('Ask AI')}}
                            </p>
                        </el-button>
                    </template>
                    <div class="fs_template_inserter">
                        <div>
                            <AIResponseGenerator type="createResponse" @close="closeAIResponsePromptBox" @insert="insertAIResponse"/>
                        </div>

                    </div>
                </el-popover>
            </div>

            <div class="fs_cc_email_toggle_button" v-if="showCcToggleButton">
                <el-button size="small" type="primary" v-if="!add_cc" @click="handleCc('show')">
                    <span>{{ $t('Add Cc') }}</span>
                </el-button>
                <el-button size="small" type="danger" v-else @click="handleCc('hide')">
                    <span>{{ $t('Discard Cc') }}</span>
                </el-button>
            </div>

            <div class="fc_shortcode_box" v-if="hasShortcodes" >
                <el-dropdown type="primary" trigger="click" :popper-options="{ strategy: 'fixed' }">
                    <el-button size="small" type="primary">
                        {{$t('Shortcodes')}} <el-icon style="vertical-align: middle;"><ArrowDown /></el-icon>
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu class="fs_shortcode_dropdown">
                            <el-dropdown-item v-for="(value, key) in shortcodes" :key="key" :value="key" @click="insertShortcode">
                                {{ value }}
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>
            </div>

            <div class="fc_saved_replies_box" v-if="showSavedReplies">
                <template-inserter @insert="insertTemplate" />
            </div>
        </div>
        <ImagePasteUploader ref="imagePasteUploader" @imagePath="setImagePath" v-loading="loadingImage"/>
        <textarea v-if="hasWpEditor" class="wp_vue_editor" :id="editor_id">
            {{ modelValue }}
        </textarea>
        <textarea
            v-else
            class="wp_vue_editor wp_vue_editor_plain"
            v-model="plain_content"
            @click="updateCursorPos"
        ></textarea>
        <div v-if="loadingImage" class="fs_loading_overlay">
        </div>
        <div class="fs_ai_modify_response_box" v-if="showActionBar && aiIntegration" :style="actionBarStyle">
            <el-popover
                placement="bottom"
                :width="480"
                trigger="click"
                :visible="showChatGPTPromptBox"
            >
                <template #reference>
                    <el-button class="fs_ai_popover_button" @click="editSelection()" size="small" type="default">
                        <img :src="appVars.asset_url + 'images/aiIcon.svg'" alt="">
                    </el-button>
                </template>
                <div class="fs_template_inserter">
                    <div>
                        <AIResponseGenerator type="modifyResponse" :selectedText="selectedText" @close="closeSelectedTextPromptBox" @insert="insertAIResponse"/>
                    </div>
                </div>
            </el-popover>

        </div>
    </div>
</template>

<script type="text/babel">
import ImagePasteUploader from './FormElements/_ImagePasteUploader';
export default {
    name: 'wp_editor',
    components: {
        TemplateInserter: () => true ? import('../Modules/Tickets/_templateInserter') : undefined,
        AIResponseGenerator: () => import('../Modules/Tickets/_AIResponseGenerator'),
        FluentBotAIResponseGenerator: () => import('../Modules/Tickets/_FluentBotAIResponseGenerator.vue'),
        ImagePasteUploader,
    },

    props: {
        editor_id: {
            type: String,
            default() {
                return 'wp_editor_' + Date.now() + parseInt(Math.random() * 1000);
            }
        },
        modelValue: {
            type: String,
            default() {
                return '';
            }
        },
        editor_shortcodes: {
            type: Object,
            default() {
                return {}
            }
        },
        height: {
            type: Number,
            default() {
                return 250;
            }
        },
        mediaButtons: {
            type: Boolean,
            default() {
                return true;
            }
        },
        autofocus: {
            type: Boolean,
            default() {
                return false;
            }
        },
        showCcToggleButton: {
            type: Boolean,
            default() {
                return false
            }
        },
        add_cc: {
            type: Boolean,
            default() {
                return false
            }
        },
        showShortcodes: {
            type: Boolean,
            default() {
                return false
            }
        },
        openAIIntegration: {
            type: Boolean,
            default() {
                return false
            }
        },
        fluentBotIntegration: {
            type: Boolean,
            default() {
                return false
            }
        },
        showSavedReplies: {
            type: Boolean,
            default() {
                return false
            }
        },
        ticketId: {
            type: String,
            default() {
                return ''
            }
        },
        productID: {
            type: String,
            default() {
                return false
            }
        },
        is_agent: {
            type: String,
            default() {
                return ''
            }
        },
        is_direct_paste: {
            type: Boolean,
            default() {
                return false
            }
        }
    },
    emits: ['update:modelValue', 'toggleCcOption'],
    data() {
        return {
            showButtonDesigner: false,
            hasWpEditor: (!!window.wp?.editor && !!wp?.editor?.autop) || !!window.wp?.oldEditor,
            editor: window.wp?.oldEditor || window.wp?.editor,
            plain_content: this.modelValue,
            cursorPos: (this.modelValue) ? this.modelValue.length : 0,
            app_ready: false,
            buttonInitiated: false,
            currentEditor: false,

            showActionBar: false,
            actionBarStyle: {},
            showChatGPTPromptBox: false,
            showAIResponseBox: false,
            showFluentBotAIResponseBox: false,
            selectedText: '',
            editorData: {},
            appVars: this.appVars,
            loadingImage: false
        }
    },
    computed: {
        shortcodes() {
            return this.editor_shortcodes;
        },
        hasShortcodes() {
            return this.editor_shortcodes && Object.keys(this.editor_shortcodes).length > 0;
        }
    },
    watch: {
        plain_content() {
            this.$emit('update:modelValue', this.plain_content);
        },
    },
    methods: {
        initEditor() {
            if (!this.hasWpEditor) {
                return;
            }

            this.editor.remove(this.editor_id);
            const that = this;
            const mceConfig = {
                height: that.height,
                toolbar1: 'formatselect,code,table,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,underline,strikethrough,forecolor,removeformat,codeformat,outdent,indent,undo,redo',
                setup(editor) {
                    editor.on('change', function (ed, l) {
                        that.changeContentEvent();
                    });
                    editor.on('mouseup', function (event) {
                        that.showActionBarOnSelection(editor);
                    });

                    if (that.is_direct_paste) {
                        editor.on('paste', function(event) {
                            const clipboardData = event.clipboardData || window.clipboardData;

                            if (clipboardData && clipboardData.items) {
                                let hasImage = false;

                                for (let i = 0; i < clipboardData.items.length; i++) {
                                    const item = clipboardData.items[i];
                                    if (item.kind === 'file') {
                                        hasImage = true;
                                        break;
                                    }
                                }

                                if (hasImage) {
                                    that.loadingImage = true;
                                    that.$refs.imagePasteUploader.handleImagePaste(event, that.ticketId, that.is_agent);
                                }
                            }
                        });
                    }
                }
            };

            if (this.autofocus) {
                mceConfig.auto_focus = this.editor_id;
            }

            const initializeEditor = () => {
                this.editor.initialize(this.editor_id, {
                    mediaButtons: this.mediaButtons,
                    tinymce: mceConfig,
                    quicktags: true
                });
            };

            if (document.readyState === "complete" || document.readyState === "interactive") {
                initializeEditor();
            } else {
                jQuery(document).ready(initializeEditor);
            }

            jQuery('#' + this.editor_id).on('change', function () {
                that.changeContentEvent();
            });
        },
        insertHtml(content) {
            this.currentEditor.insertContent(content);
        },
        changeContentEvent() {
            const content = this.editor.getContent(this.editor_id);
            this.$emit('update:modelValue', content);
        },

        closeSelectedTextPromptBox() {
            this.showChatGPTPromptBox = false;
            this.showActionBar = false;
        },

        closeAIResponsePromptBox() {
            this.showAIResponseBox = false;
        },

        closeFluentBotAIResponsePromptBox() {
            this.showFluentBotAIResponseBox = false;
        },

        handleCommand(command) {
            if (this.hasWpEditor) {
                window.tinymce.activeEditor.insertContent(command);
            } else {
                var part1 = this.plain_content.slice(0, this.cursorPos);
                var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                this.plain_content = part1 + command + part2;
                this.cursorPos += command.length;
            }
        },

        updateCursorPos() {
            var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
            this.cursorPos = cursorPos;
        },

        handleCc(command) {
            this.$emit('toggleCcOption', command);
        },

        insertShortcode(content) {
            const shortcode = content.target._value;

            if (this.hasWpEditor) {
                let tinyInstance = tinyMCE.get(this.editor_id);
                if (tinyInstance) {
                    tinyInstance.focus();
                    tinyInstance.insertContent(shortcode);
                    const updatedContent = tinyInstance.getContent();
                    this.$emit('update:modelValue', updatedContent);
                }
            } else {
                const textarea = document.querySelector(`#${this.editor_id}`);
                if (textarea) {
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;
                    const before = text.substring(0, start);
                    const after = text.substring(end, text.length);
                    const newValue = before + shortcode + after;
                    this.plain_content = newValue;
                    // Set cursor position after the inserted shortcode
                    this.$nextTick(() => {
                        textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
                        textarea.focus();
                    });
                }
            }
        },

        insertTemplate(content) {
            let tinyInstance = tinyMCE.editors[wpActiveEditor];
            tinyInstance.setContent(this.modelValue + content)
            this.$emit('update:modelValue', this.modelValue + content)
        },

        insertAIResponse(content) {
            let tinyInstance = tinyMCE.get(wpActiveEditor);

            const formattedContent = content
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>')
                .replace(/ {2,}/g, match => match.replace(/ /g, '&nbsp;'));

            tinyInstance.insertContent(formattedContent);

            this.$emit('update:modelValue', tinyInstance.getContent({ format: 'html' }));

            this.showChatGPTPromptBox = false;
            this.showAIResponseBox = false;
            this.showActionBar = false;
        },

        showActionBarOnSelection(editor) {
            this.editorData = editor;
            const selection = this.editorData.selection;

            if (!selection.isCollapsed()) {
                const selectedText = selection.getContent({ format: 'text' });

                if (selectedText.length > 0) {
                    // Get the bounding rectangle of the entire selection
                    const range = selection.getRng();
                    const rect = range.getBoundingClientRect();

                    // Find the bounding rect of the first line of the selection
                    const rangeStart = range.cloneRange();
                    rangeStart.setStart(rangeStart.startContainer, rangeStart.startOffset);
                    rangeStart.setEnd(rangeStart.startContainer, rangeStart.startOffset);
                    const rectStart = rangeStart.getBoundingClientRect();

                    this.actionBarStyle = {
                        top: `${rectStart.top + 40}px`,
                        left: `${rectStart.right + 30}px`,
                        position: 'absolute',
                        zIndex: 1
                    };

                    this.selectedText = selectedText;
                    this.showChatGPTPromptBox = false;
                    this.showActionBar = true;
                }
            } else {
                this.showActionBar = false;
            }
        },

        overallTicketResponse() {
            this.showAIResponseBox = !this.showAIResponseBox;
        },

        editSelection() {
            if (!this.editor) {
                console.error('TinyMCE editor instance or selection not available.');
                return;
            }

            const selection = this.editorData.selection;
            const selectedText = selection.getContent({ format: 'text' }).trim();
            if (selectedText) {
                this.selectedText = selectedText;
                this.showChatGPTPromptBox = true;
            }
        },
        setImagePath(imageUrl) {
            const tinyInstance = tinyMCE.get(this.editor_id);
            if (!tinyInstance) return;

            const imageElement = new Image();
            imageElement.src = imageUrl;

            imageElement.onload = () => {
                const maxDimension = 300;
                const {naturalWidth, naturalHeight} = imageElement;
                const aspectRatio = naturalWidth / naturalHeight;

                let width = naturalWidth;
                let height = naturalHeight;

                if (naturalWidth > naturalHeight) {
                    width = maxDimension;
                    height = maxDimension / aspectRatio;
                } else {
                    height = maxDimension;
                    width = maxDimension * aspectRatio;
                }

                const imgTag = `<img src="${imageUrl}" alt="Uploaded Image" style="width: ${width}px; height: ${height}px;" />`;
                tinyInstance.insertContent(imgTag);

                this.loadingImage = false;
            };
        },
    },
    beforeCreate() {
        if (window.fluentSupportAdmin) {
            this.$options.components.TemplateInserter = require('../Modules/Tickets/_templateInserter').default
            this.$options.components.AIResponseGenerator = require('../Modules/Tickets/_AIResponseGenerator').default
            this.$options.components.FluentBotAIResponseGenerator = require('../Modules/Tickets/_FluentBotAIResponseGenerator').default
        }
    },
    mounted() {
        this.initEditor();
        this.app_ready = true;
    }
}
</script>
<style lang="scss">
.wp_vue_editor {
    width: 100%;
    min-height: 100px;
}

.wp_vue_editor_wrapper {
    position: relative;
    width: 100%;

    .wp-media-buttons .insert-media {
        vertical-align: middle;
    }

    .popover-wrapper {
        z-index: 2;
        position: absolute;
        top: 0;
        right: 0;

        &-plaintext {
            left: auto;
            right: 0;
            top: -32px;
        }
    }

    .wp-editor-tabs {
        float: left;
    }
}

.mce-fluentcrm_editor_btn {
    button {
        font-size: 10px !important;
        border: 1px solid gray;
        margin-top: 3px;
    }

    &:hover {
        border: 1px solid transparent !important;
    }
}

.fs_action_buttons {
    display: inline-flex;
    position: absolute;
    right: 0px;
    gap: 10px;
    z-index: 2;
    align-items: center;
    .fc_saved_replies_box {
        button {
            padding: 6px 11px;
        }
    }
}

.action-bar {
    background-color: white;
    border: 1px solid #ccc;
    padding: 5px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

.fs_ai_tools_box {
    .fs_fluent_bot_response_button,
    .fs_openAI_response_button {
        align-items: center;
        padding: 5px 6px;
        height: 22px;
        font-size: 0;
        font-weight: 500;
        line-height: 1.33;
        color: #fff;
        border-radius: 4px;
        transition: background 0.3s, box-shadow 0.3s;

        span {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }

        p {
            font-size: 12px;
            font-weight: 500;
            line-height: 1.33;
        }
    }

    .fs_fluent_bot_response_button {
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.36) 0%,
                rgba(255, 255, 255, 0) 100%
        ),
        #07754f;
        box-shadow:
            0px 1px 2px 0px rgba(14, 18, 27, 0.24),
            0px 0px 0px 1px #07754f;

        &:hover {
            box-shadow:
                0 2px 4px 0 rgba(27, 28, 29, 0.64),
                0 0 0 1px #3a3b3d;
        }
    }

    .fs_openAI_response_button {
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.16) 0%,
                rgba(255, 255, 255, 0) 100%
        ),
        #0e121b;
        box-shadow:
            0 1px 2px 0 rgba(27, 28, 29, 0.48),
            0 0 0 1px #242628;

        &:hover {
            background: linear-gradient(
                    180deg,
                    rgba(255, 255, 255, 0.32) 0%,
                    rgba(255, 255, 255, 0.16) 100%
            ),
            #0e121b;
            box-shadow:
                0 2px 4px 0 rgba(27, 28, 29, 0.64),
                0 0 0 1px #3a3b3d;
        }
    }
}

.fs_ai_modify_response_box {
    .fs_ai_popover_button {
        padding: 6px;
        gap: 10px;
        border-radius: 50%;
        background: #0e121b;
        height: 30px;
        width: 30px;
    }
}

.wp_vue_editor_wrapper {
    position: relative;
}

.fs_loading_overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10;
    pointer-events: none;
}

.fc_shortcode_box {
    .el-dropdown-menu {
        max-height: 300px;
        overflow-y: auto;
        z-index: 9999 !important;
        position: relative;

        &::-webkit-scrollbar {
            width: 6px;
        }

        &::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        &::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;

            &:hover {
                background: #a8a8a8;
            }
        }
    }
}

// Ensure dropdown works properly in modals
.fs_shortcode_dropdown {
    max-height: 300px !important;
    overflow-y: auto !important;
    z-index: 9999 !important;

    // Prevent scroll events from bubbling to background
    &::-webkit-scrollbar {
        width: 6px;
    }

    &::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    &::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;

        &:hover {
            background: #a8a8a8;
        }
    }
}

.el-dropdown-menu.fs_shortcode_dropdown.el-popper {
    max-height: 300px !important;
    overflow-y: auto !important;
    z-index: 9999 !important;
    position: fixed !important;
}
</style>
