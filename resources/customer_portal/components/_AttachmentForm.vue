<template>
    <div class="upload-container">
        <el-upload
            class="fs-file-uploader upload-area"
            :action="upload_url"
            :on-remove="handleRemove"
            :on-success="handleUploadSuccess"
            :with-credentials="true"
            :on-error="handleError"
            multiple
            :drag="false"
            :headers="requestHeaders"
            :data="upload_data"
            :limit="parseInt(appVars.max_file_upload)"
            :on-exceed="handleExceed"
            :file-list="file_lists"
            list-type="picture"
        >
            <div class="fs-upload-content">
                <div class="fc-upload-text">
                    <p class="fs_upload_instruction">{{$t('Add Attachment')}}</p>
                        <el-button type="primary" class="fs_upload_button" >
                            <el-icon class="upload-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12.5274L15.8187 16.3452L14.5452 17.6187L12.9 15.9735V21H11.1V15.9717L9.45479 17.6187L8.18129 16.3452L12 12.5274ZM12 3C13.5453 3.00007 15.0366 3.568 16.1906 4.59581C17.3445 5.62361 18.0805 7.03962 18.2586 8.5746C19.3784 8.87998 20.3553 9.56919 21.0186 10.5218C21.6818 11.4744 21.9892 12.6297 21.887 13.786C21.7849 14.9422 21.2796 16.0257 20.4596 16.8472C19.6396 17.6687 18.5569 18.1759 17.4009 18.2802V16.4676C17.815 16.4085 18.2133 16.2674 18.5723 16.0527C18.9314 15.8379 19.244 15.5539 19.4921 15.217C19.7402 14.8801 19.9186 14.4972 20.0171 14.0906C20.1155 13.6839 20.132 13.2618 20.0655 12.8488C19.9991 12.4357 19.851 12.0401 19.6299 11.6849C19.4089 11.3297 19.1193 11.0221 18.7781 10.78C18.4369 10.538 18.0508 10.3663 17.6425 10.2751C17.2343 10.1838 16.8119 10.1748 16.4001 10.2486C16.541 9.5924 16.5334 8.91297 16.3778 8.2601C16.2222 7.60722 15.9224 6.99743 15.5006 6.47538C15.0788 5.95333 14.5455 5.53225 13.9399 5.24298C13.3343 4.9537 12.6716 4.80357 12.0004 4.80357C11.3293 4.80357 10.6666 4.9537 10.061 5.24298C9.45533 5.53225 8.92207 5.95333 8.50025 6.47538C8.07843 6.99743 7.77873 7.60722 7.62309 8.2601C7.46746 8.91297 7.45984 9.5924 7.60079 10.2486C6.77968 10.0944 5.93095 10.2727 5.2413 10.7443C4.55165 11.2159 4.07759 11.9421 3.92339 12.7632C3.76919 13.5843 3.9475 14.433 4.41908 15.1227C4.89065 15.8123 5.61688 16.2864 6.43799 16.4406L6.59999 16.4676V18.2802C5.4439 18.1761 4.36116 17.669 3.54101 16.8476C2.72087 16.0261 2.21548 14.9426 2.1132 13.7863C2.01091 12.6301 2.31822 11.4747 2.98142 10.522C3.64462 9.56934 4.62153 8.88005 5.74139 8.5746C5.91933 7.03954 6.65525 5.62342 7.80921 4.59558C8.96317 3.56774 10.4546 2.99988 12 3Z" fill="currentColor"/>
                                </svg>
                            </el-icon>
                            {{ $t('Browse Files') }}
                        </el-button>
                </div>
            </div>
            <template #tip>
                <p class="fs_upload_info">{{$t('allowed_files_and_size')}}</p>
            </template>
            <template #file="{ file }">
                <div class="fs-file-list">
                    <div class="fs-file-icon">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M30 39.25H10C7.10051 39.25 4.75 36.8995 4.75 34V6C4.75 3.10051 7.10051 0.75 10 0.75H20.5147C21.9071 0.75 23.2425 1.30312 24.227 2.28769L33.7123 11.773C34.6969 12.7575 35.25 14.0929 35.25 15.4853V34C35.25 36.8995 32.8995 39.25 30 39.25Z" fill="white" stroke="#CACFD8" stroke-width="1.5"/>
                        <path d="M23 1V9C23 11.2091 24.7909 13 27 13H35" stroke="#CACFD8" stroke-width="1.5"/>
                        <path d="M0 22C0 19.7909 1.79086 18 4 18H26C28.2091 18 30 19.7909 30 22V30C30 32.2091 28.2091 34 26 34H4C1.79086 34 0 32.2091 0 30V22Z" fill="#47C2FF"/>
                        <path d="M3.78906 30V22H8.91406V23.2148H5.23828V25.3867H8.5625V26.6016H5.23828V30H3.78906ZM11.9036 22V30H10.4544V22H11.9036ZM13.6929 30V22H15.1421V28.7852H18.6656V30H13.6929ZM20.1434 30V22H25.3465V23.2148H21.5926V25.3867H25.077V26.6016H21.5926V28.7852H25.3778V30H20.1434Z" fill="white"/>
                        </svg>
                    </div>
                    <div class="fs-file-infos">
                        <span class="fs-file-name">{{ truncateFileName(file.name) }}</span>
                        <span class="fs-file-size">{{ file.size }} <span class="fs-dot"></span>
                            <span class="fs-file-status" :class="file.status">
                                <template v-if="file.status == 'success'">
                                    <el-icon><CircleCheckFilled /></el-icon> {{ $t('Completed') }}
                                </template>
                                <template v-else-if="file.status == 'uploading'">
                                    <el-icon><Loading /></el-icon> {{ $t('Processing') }}
                                </template>
                                <template v-else>
                                    <el-icon><CircleCloseFilled /></el-icon> {{ $t('Failed') }}
                                </template>
                            </span>
                        </span>
                    </div>
                    <el-icon class="fs-file-delete" @click="handleRemove(file)">
                        <Delete />
                    </el-icon>
                </div>
            </template>
        </el-upload>
        <p v-if="error_message" class="error-message" @click="error_message = ''">
            {{ error_message }}
        </p>
    </div>
</template>

<script>

export default {
    name: 'AttachmentForm',
    components: {},
    props: {
        ticket: Object,
        attachments: Array,
        is_agent: Boolean,
    },
    data() {
        return {
            upload_url: this.appVars.rest.url + '/ticket_file_upload',
            file_lists: [],
            error_message: '',
            upload_data: {
                ticket_id: this.ticket.serial_number || this.ticket.id,
                intended_ticket_hash: this.appVars.intended_ticket_hash || '',
                is_agent: this.is_agent
            },
            requestHeaders: {
                'X-WP-Nonce': this.appVars.rest.nonce
            },
        };
    },
    methods: {
        truncateFileName(name) {
            const maxLength = 15;
            if (name.length > maxLength) {
                return name.substring(0, maxLength) + '...';
            }
            return name;
        },
        handleRemove(file) {
            this.error_message = '';
            const fileIndex = this.file_lists.findIndex(f => f.uid === file.uid);

            if (fileIndex !== -1) {
                this.file_lists.splice(fileIndex, 1);
            }

            if (file.response && file.response.attachments) {
                const attachmentIndex = this.attachments.findIndex(attachment => attachment.id === file.response.attachments[0].id);

                if (attachmentIndex !== -1) {
                    this.attachments.splice(attachmentIndex, 1);
                }
            }

            this.file_lists = [...this.file_lists];
        },

        handleExceed(files, fileList) {
            this.error_message = `You can upload a maximum of ${parseInt(this.appVars.max_file_upload)} files`;
        },
        handleError(err, file, fileList) {
            let message = 'File failed to upload';
            try {
                let errorResponse = JSON.parse(err.message);
                message = errorResponse.file?.mimetypes || message;
            } catch (e) {
                // console.error("Upload error:", err);
            }
            this.error_message = `${file.name}: ${message}`;
        },
        handleUploadSuccess(response, file, fileList) {
            this.error_message = '';
            this.attachments.push(...response.attachments);
            this.file_lists.push(file);
            if (file.raw.type.includes('application') || file.raw.type.includes('text')) {
                file.url = this.appVars.asset_url
                    ? this.appVars.asset_url + 'images/icons/file.svg'
                    : this.appVars.fallback_image;
            }
        }
    }
};
</script>
