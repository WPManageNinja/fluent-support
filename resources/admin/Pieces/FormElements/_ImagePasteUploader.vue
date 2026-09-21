<template>
    <div>

    </div>
  </template>

  <script>
  export default {
    name: 'ImagePasteUploader',
    // components: {notify},
    data() {
        return {
            upload_url: this.appVars.rest.url+'/ticket_file_upload',
            upload_data: {
                ticket_id: '',
                intended_ticket_hash: this.appVars.intended_ticket_hash,
                is_agent: ''
            },
            file_lists: [],
            requestHeaders: {
                'X-WP-Nonce': this.appVars.rest.nonce
            },
            error_message: '',
            dialogImageUrl: '',
            dialogImageTitle: '',
            dialogVisible: false,
        }
    },
    methods: {
      handleImagePaste(event, ticketId, is_agent) {
        this.upload_data.ticket_id =  ticketId;
        this.upload_data.is_agent = is_agent;

        const clipboardData = event.clipboardData || window.clipboardData;
        if (clipboardData && clipboardData.items) {
            for (let i = 0; i < clipboardData.items.length; i++) {
                const item = clipboardData.items[i];
                if (item.kind === 'file' && item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    if (file) {
                        const newFile = new File([file], file.name, { type: file.type });
                        this.uploadImage(newFile);
                    } else {
                        console.error("Failed to extract image file from clipboard.");
                    }
                    event.preventDefault();
                }else{
                  this.$notify({
                      message: this.$t('Invalid image'),
                      type: 'error'
                  });
                }
            }
        }
      },

      uploadImage(file) {
          const formData = new FormData();
          if (!(file instanceof Blob || file instanceof File)) {
              console.error("Provided file is not valid.");
              return;
          }

          formData.append('file', file);
          formData.append('ticket_id', this.upload_data.ticket_id);
          formData.append('is_agent', this.upload_data.is_agent);
          formData.append('intended_ticket_hash', this.appVars.intended_ticket_hash);
          formData.append('type', 'direct_paste');

          fetch(this.upload_url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-WP-Nonce': this.appVars.rest.nonce,
                }
            })
            .then(response => {
              if (!response.ok) {
                  return response.json().then(err => {
                      throw new Error(err.file.mimetypes || 'Upload failed');
                  });
              }
              // If everything is okay, parse the response data
              return response.json();
            })
            .then(data => {
              this.$notify({
                  message: this.$t('Image successfully uploaded.'),
                  type: 'success'
              });
              this.$emit('imagePath', data.attachments);
            })
            .catch((error) => {
              this.$notify({
                  message: error,
                  type: 'error'
              });
            });
      }
    }
  };
  </script>

  <style scoped>
  /* No specific styles needed */
  </style>
