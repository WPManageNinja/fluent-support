<template>
    <div class="wp_vue_editor_wrapper">
        <textarea v-if="hasWpEditor" class="wp_vue_editor" :id="editor_id">{{ plain_content }}</textarea>
        <textarea v-else
                  style="margin-top: 30px;"
                  class="wp_vue_editor wp_vue_editor_plain"
                  v-model="plain_content"
                  @click="updateCursorPos">
        </textarea>
    </div>
</template>

<script type="text/babel">
import { getFluentBookingContentStyle } from './fluentBookingContentStyle';

export default {
    name: 'simple_wp_editor',
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
        autofocus: {
            type: Boolean,
            default() {
                return false;
            }
        },
        height: {
            type: Number,
            default() {
                return 400;
            }
        },
        extra_style: {
            default() {
                return ''
            }
        },
        ticketId: {
            type: [Number, String],
            default() {
                return null;
            }
        },
        is_direct_paste: {
            type: Boolean,
            default() {
                return false;
            }
        }
    },
    data() {
        return {
            hasWpEditor: (!!window.wp?.editor && !!wp?.editor?.autop) || !!window.wp?.oldEditor,
            editor: window.wp?.oldEditor || window.wp?.editor,
            plain_content: this.modelValue,
            cursorPos: (this.modelValue) ? this.modelValue.length : 0,
            app_ready: false,
            currentEditor: false,
            isImageUploading: false
        }
    },
    watch: {
        plain_content() {
            this.$emit('update:modelValue', this.plain_content);
        }
    },
    methods: {
        initEditor() {
            if (!this.hasWpEditor) {
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');

            let defaultStyles = isDark
                ? 'body { background-color: #18181B !important; color: #F4F4F5 !important; } blockquote { padding: 10px; margin: 0; background: #27272A; border-left: 3px solid #3F3F46; } a { color: #60A5FA; }'
                : 'blockquote {padding: 10px 10px;margin: 0;background: #f2f2f2;}';

            const bookingStyles = getFluentBookingContentStyle(isDark ? {
                titleColor: '#F4F4F5',
                textSecondary: '#A1A1AA',
                textPrimary: '#E4E4E7',
                borderColor: '#3F3F46',
                backgroundColor: '#18181B',
                subtleBackground: '#27272A',
                radius: '8px',
                focusRing: 'rgba(63, 63, 70, 0.5)'
            } : {
                titleColor: '#111827',
                textSecondary: '#525866',
                textPrimary: '#0E121B',
                borderColor: '#E5E7EB',
                backgroundColor: '#FFFFFF',
                subtleBackground: '#F5F7FA',
                radius: '8px',
                focusRing: 'rgba(153, 160, 174, 0.16)'
            });
           // this.editor.remove(this.editor_id);
            const that = this;
            this.editor.initialize(this.editor_id, {
                mediaButtons: false,
                tinymce: {
                    auto_focus: that.autofocus,
                    min_height: that.height,
                    fontsize_formats: '8px 10px 12px 14px 16px 18px 24px 28px 30px 32px',
                    toolbar1: 'formatselect,code,table,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,underline,strikethrough,forecolor,removeformat,codeformat,outdent,indent,undo,redo',
                    setup(editor) {
                        editor.on('change', function (ed, l) {
                            that.changeContentEvent();
                        });

                        editor.on('paste', function (e) {
                            let hasImage = false;
                            for (let i = 0; i < e.clipboardData.items.length; ++i) {
                                let item = e.clipboardData.items[i];
                                if (item.kind == "file" && item.type.startsWith("image/")) {
                                    hasImage = true;
                                    const file = item.getAsFile();
                                    const name = e.clipboardData.getData("text") || 'clipboard-image.png';
                                    that.handleUploadImage(new File([file], name, {type: file.type}));
                                    e.preventDefault();
                                }
                            }

                            if(!hasImage) {
                                // set the cursor position at the end
                                setTimeout(() => {
                                    editor.selection.select(editor.getBody(), true);
                                    editor.selection.collapse(false);

                                    // scroll to the bottom
                                    editor.getBody().scrollTop = editor.getBody().scrollHeight + 100;
                                }, 10);
                            }
                        });

                        that.currentEditor = editor;
                    },
                    formats: {
                        // Changes the alignment buttons to add a class to each of the matching selector elements
                        alignleft: {
                            selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                            classes: 'align-left',
                            styles: {'text-align': 'left'}
                        },
                        aligncenter: {
                            selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                            classes: 'align-center',
                            styles: {'text-align': 'center'},
                            attributes: {align: 'center'}
                        },
                        alignright: {
                            selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img',
                            classes: 'align-right',
                            styles: {'text-align': 'right'},
                            attributes: {align: 'right'}
                        }
                    },
                    content_style: that.extra_style + defaultStyles + bookingStyles,
                    resize: true
                },
                quicktags: true
            });
        },
        insertHtml(content) {
            this.currentEditor.insertContent(content);
        },
        changeContentEvent() {
            const content = this.editor.getContent(this.editor_id);
            this.$emit('update:modelValue', content);
        },
        updateCursorPos() {
            var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
            this.cursorPos = cursorPos;
        },
        handleUploadImage(file, name, options) {
            if(!this.is_direct_paste) {
                this.$notify({
                    type: 'error',
                    message: this.$t('Direct paste is not enabled for this editor.'),
                    position: 'bottom-right'
                });
                return;
            }

            this.isImageUploading = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('ticket_id', this.ticketId);
            formData.append('intended_ticket_hash', this.appVars.intended_ticket_hash || '');
            formData.append('max_width', 1000);
            formData.append('max_height', 760);
            formData.append('type', 'direct_paste');
            formData.append('resize', true);

            this.$uploadFile('ticket_file_upload', formData)
                .then(response => {
                    if(response.attachments) {
                        this.insertHtml(`<img src="${response.attachments}" style="max-width: 700px; height: auto; width: 100%;" /><p>&nbsp;</p>`);
                    }
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .finally(() => {
                    this.isImageUploading = false;
                });
        }
    },
    mounted() {
        this.initEditor();
        this.app_ready = true;

        setTimeout(() => {
            if(!this.currentEditor) {
                this.initEditor();
            }
        }, 500);
    },
    beforeUnmount() {
        if (this.hasWpEditor) {
            this.editor.remove(this.editor_id);
        }
    }
}
</script>
