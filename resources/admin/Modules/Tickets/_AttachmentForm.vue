<template>
    <div class="fs_attachments_form">
        <!-- Attachment Header - Proper Layout -->
        <div class="fs_attachment_header">
            <h4>{{ $t('Add Attachment') }}</h4>

            <div class="fs_attachment_row">
                <div class="fs_attachment_actions">
                    <el-upload
                        ref="uploadRef"
                        class="fs_attachment_upload"
                        drag
                        :accept="acceptedTypes"
                        multiple
                        :show-file-list="false"
                        :limit="uploadLimit"
                        :disabled="uploadLimit <= 0"
                        :on-exceed="handleExceed"
                        :before-upload="beforeUpload"
                        :on-success="handleUploadSuccess"
                        :on-error="handleUploadError"
                        :http-request="customUpload"
                    >
                        <div class="fs_upload_dragger">
                            <img :src="appVars.asset_url + 'images/uploadIcon.svg'" alt="">
                            <p class="fs_upload_dragger_text">
                                {{ $t('Browse File') }}
                            </p>
                        </div>
                    </el-upload>

                    <el-dropdown
                        v-if="attachments.length > 0"
                        placement="bottom-start"
                        trigger="click"
                    >
                        <el-button
                            class="fs_outline_btn"
                        >
                            <img :src="appVars.asset_url + 'images/attachmentIcon.svg'" alt="">
                            {{ attachments.length }}
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu class="fs_global_dropdown fs_attachment_dropdown">
                                <div
                                    v-for="(attachment, index) in attachments"
                                    :key="typeof attachment === 'string' ? attachment : (attachment.id || index)"
                                    class="fs_attachment_item"
                                >
                                    <div class="fs_attachment_icon">
                                        <span :class="getFileTypeClass(getAttachmentName(attachment))">
                                            {{ getFileExtension(getAttachmentName(attachment)) }}
                                        </span>
                                    </div>
                                    <div class="fs_attachment_details">
                                        <div class="fs_attachment_name">{{ getAttachmentName(attachment) }}</div>
                                        <div class="fs_attachment_size">{{ getAttachmentSize(attachment) }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        class="fs_attachment_remove"
                                        @click="removeAttachment(index)"
                                    >
                                        <el-icon><Delete /></el-icon>
                                    </button>
                                </div>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>

                    <el-tooltip
                        v-if="attachments.length > 0"
                        effect="dark"
                        :content="$t('Clear All Attachments')"
                        placement="top"
                    >
                        <el-button
                            class="fs_outline_btn"
                            @click="clearAllAttachments"
                        >
                            <img :src="appVars.asset_url + 'images/deleteIcon.svg'" alt="">
                        </el-button>
                    </el-tooltip>
                </div>

                <p class="fs_attachment_help">
                    ({{ $t('allowed_files_and_size') }})
                </p>
            </div>


        </div>

        <!-- Error Message -->
        <p v-if="error_message" class="fs_error_message" @click="error_message = ''">
            {{ error_message }}
        </p>
        <p v-if="uploadingCount" class="fs_attachment_uploading">
            {{ uploadingMessage }}
        </p>
    </div>
</template>

<script type="text/babel">

export default {
    name: 'AttachmentForm',
    props: ['ticket', 'attachments', 'is_agent'],
    emits: ['upload-state-change'],
    computed: {
        acceptedTypes() {
            return this.appVars.allowed_file_types || '*';
        },
        uploadLimit() {
            const max = parseInt(this.appVars.max_file_upload, 10) || 0;
            return Math.max(0, max - this.attachments.length - this.uploadingCount);
        },
        uploadingCount() {
            return this.pendingUploads;
        },
        uploadingMessage() {
            if (this.uploadingCount === 1) {
                return this.$t('Uploading 1 attachment. Please wait before submitting.');
            }

            return `Uploading ${this.uploadingCount} attachments. Please wait before submitting.`;
        }
    },
    data() {
        return {
            upload_url: this.appVars.rest.url+'/ticket_file_upload',
            upload_data: {
                ticket_id: this.ticket.id,
                intended_ticket_hash: this.appVars.intended_ticket_hash,
                is_agent: this.is_agent
            },
            requestHeaders: {
                'X-WP-Nonce': this.appVars.rest.nonce
            },
            error_message: '',
            showAttachmentList: false,
            attachmentInfo: {},
            pendingUploads: 0
        }
    },
    watch: {
        pendingUploads: {
            handler(count) {
                this.$emit('upload-state-change', {
                    uploading: count > 0,
                    count
                });
            },
            immediate: true
        }
    },
    methods: {
        beforeUpload() {
            const maxFiles = parseInt(this.appVars.max_file_upload, 10);
            if ((this.attachments.length + this.uploadingCount) >= maxFiles) {
                this.error_message = `${this.$t('You can upload maximum')} ${this.appVars.max_file_upload} ${this.$t('files')}`;
                return false;
            }
            return true;
        },

        handleExceed() {
            this.error_message = `${this.$t('You can upload maximum')} ${this.appVars.max_file_upload} ${this.$t('files')}`;
        },

        customUpload(options) {
            const { file, onSuccess, onError } = options;
            const formData = new FormData();
            const uploadKey = `${file.uid || file.name}-${Date.now()}`;

            this.pendingUploads += 1;

            formData.append('file', file);
            Object.keys(this.upload_data).forEach(key => {
                formData.append(key, this.upload_data[key]);
            });

            fetch(this.upload_url, {
                method: 'POST',
                headers: this.requestHeaders,
                credentials: 'include',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.attachments) {
                    data.__uploadKey = uploadKey;
                    onSuccess(data);
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => {
                error.__uploadKey = uploadKey;
                onError(error);
            });
        },

        handleUploadSuccess(response, file) {
            this.error_message = '';
            const rawFile = file.raw || file;
            const uploadKey = response.__uploadKey;
            if (response.attachments && Array.isArray(response.attachments)) {
                response.attachments.forEach(attachmentHash => {
                    this.attachmentInfo[attachmentHash] = {
                        title: rawFile.name,
                        file_name: rawFile.name,
                        name: rawFile.name,
                        file_size: rawFile.size,
                        file_type: rawFile.type
                    };
                    this.attachments.push(attachmentHash);
                });
                this.showAttachmentList = true;
            }
            this.finishUpload(uploadKey);
        },

        handleUploadError(err, file) {
            this.finishUpload(err.__uploadKey);
            this.handleError(err, file.raw || file);
        },

        handleError(error, file) {
            let message = this.$t('File failed to upload');
            if (error.message) {
                try {
                    const errorData = JSON.parse(error.message);
                    message = this.convertToText(errorData) || message;
                } catch (e) {
                    message = error.message;
                }
            }
            this.error_message = `${file.name}: ${message}`;
        },

        finishUpload(uploadKey) {
            if (uploadKey && this.pendingUploads > 0) {
                this.pendingUploads -= 1;
            }
        },

        removeAttachment(index) {
            const hash = this.attachments[index];
            // Remove from local info map
            if (this.attachmentInfo[hash]) {
                delete this.attachmentInfo[hash];
            }
            this.attachments.splice(index, 1);
            this.$refs.uploadRef?.clearFiles();
            if (this.attachments.length === 0) {
                this.showAttachmentList = false;
            }
        },

        getAttachmentName(attachment) {
            // Handle both string (hash) and object formats
            if (typeof attachment === 'string') {
                const info = this.attachmentInfo[attachment];
                return info?.title || info?.file_name || info?.name || this.$t('Unknown File');
            }
            // Fallback for object format (backward compatibility)
            return attachment.title   ||
                   attachment.file_name ||
                   attachment.name ||
                   attachment.original_name ||
                   this.$t('Unknown File');
        },

        getFileExtension(filename) {
            if (!filename || filename === this.$t('Unknown File')) return 'FILE';
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toUpperCase() : 'FILE';
        },

        getFileTypeClass(filename) {
            if (!filename || filename === this.$t('Unknown File')) return 'fs_file_default';
            const parts = filename.split('.');
            if (parts.length <= 1) return 'fs_file_default';

            const ext = parts.pop().toLowerCase();
            const typeMap = {
                'pdf': 'fs_file_pdf',
                'jpg': 'fs_file_jpg', 'jpeg': 'fs_file_jpg',
                'png': 'fs_file_png',
                'gif': 'fs_file_gif',
                'doc': 'fs_file_doc', 'docx': 'fs_file_doc',
                'csv': 'fs_file_csv',
                'zip': 'fs_file_zip',
                'json': 'fs_file_json',
                'txt': 'fs_file_txt'
            };
            return typeMap[ext] || 'fs_file_default';
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 KB';
            const kb = bytes / 1024;
            return kb < 1024 ? `${Math.round(kb)} KB` : `${Math.round(kb / 1024)} MB`;
        },

        getAttachmentSize(attachment) {
            // Handle both string (hash) and object formats
            if (typeof attachment === 'string') {
                const info = this.attachmentInfo[attachment];
                return this.formatFileSize(info?.file_size || 0);
            }
            // Fallback for object format (backward compatibility)
            return this.formatFileSize(attachment.file_size || 0);
        },
        clearAllAttachments() {
            this.attachmentInfo = {};
            this.attachments.splice(0, this.attachments.length);
            this.$refs.uploadRef?.clearFiles();
            this.showAttachmentList = false;
        }
    }
}
</script>

<style scoped>
.fs_attachments_form {
    padding-left: 0;
}

.fs_attachment_upload {
    display: inline-block;
}

.fs_upload_dragger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.fs_upload_dragger img {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.fs_upload_dragger_text {
    margin: 0;
    font-size: 14px;
    color: #606266;
}

.fs_upload_dragger_text em {
    font-style: normal;
    color: var(--fs-text-primary, #0E121B);
    font-weight: 500;
}

.fs_attachment_header {
   padding-top: 10px;
}

.fs_attachment_header h4 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 500;
}

.fs_attachment_row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

.fs_attachment_actions {
    display: flex;
    gap: 12px;
    align-items: center;
}


.fs_attachment_count {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid #409eff;
    border-radius: 4px;
    background: #409eff;
    color: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    min-width: 50px;
    justify-content: center;
}

.fs_attachment_help {
    margin: 0;
    font-size: 12px;
    color: #909399;
    flex: 1;
}

.fs_attachment_list {
    margin-top: 12px;
    border: 1px solid #e4e7ed;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.fs_attachment_dropdown {
    min-width: 300px;
    max-width: 400px;
    padding: 0;
}

.fs_attachment_item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f5f7fa;
    margin: 0;
}

.fs_attachment_item:last-child {
    border-bottom: none;
}

.fs_attachment_item:hover {
    background-color: #f5f7fa;
}

.fs_attachment_icon {
    margin-right: 12px;
    flex-shrink: 0;
}

.fs_attachment_icon span {
    display: inline-block;
    width: 32px;
    height: 32px;
    line-height: 32px;
    text-align: center;
    border-radius: 4px;
    font-size: 9px;
    font-weight: bold;
    color: var(--fs-text-white, #FFFFFF);
}

.fs_file_pdf { background: #ff4757; }
.fs_file_jpg { background: #2ed573; }
.fs_file_png { background: #5352ed; }
.fs_file_gif { background: #a55eea; }
.fs_file_doc { background: #2f3542; }
.fs_file_csv { background: #ff6b35; }
.fs_file_zip { background: #ffa502; }
.fs_file_json { background: #3742fa; }
.fs_file_txt { background: #747d8c; }
.fs_file_default { background: #909399; }

.fs_attachment_details {
    flex: 1;
    min-width: 0;
}

.fs_attachment_name {
    font-size: 13px;
    font-weight: 500;
    color: #303133;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fs_attachment_size {
    font-size: 11px;
    color: #909399;
}

.fs_attachment_remove {
    padding: 4px;
    border: none;
    background: none;
    color: #c0c4cc;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.3s;
    flex-shrink: 0;
    margin-left: 8px;
}

.fs_attachment_remove:hover {
    background: #f56c6c;
    color: white;
}

.fs_error_message {
    color: #f56c6c;
    font-size: 12px;
    margin: 8px 0 0 0;
    cursor: pointer;
    padding: 8px 12px;
    background: #fef0f0;
    border-radius: 4px;
    border-left: 3px solid #f56c6c;
}

.fs_attachment_uploading {
    color: #8a6700;
    font-size: 12px;
    margin: 8px 0 0 0;
    padding: 8px 12px;
    background: #fff7e6;
    border-radius: 4px;
    border-left: 3px solid #e6a23c;
}

/* Responsive design */
@media (max-width: 768px) {
    .fs_attachment_header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .fs_attachment_actions {
        width: 100%;
        justify-content: flex-start;
    }

    .fs_attachment_help {
        width: 100%;
    }

    .fs_attachment_row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>
