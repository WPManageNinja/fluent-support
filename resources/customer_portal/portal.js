import { createApp } from 'vue';
import Application from './Application';
import { createWebHashHistory, createRouter } from 'vue-router'
import routes from "./routes";
import {ElNotification, ElLoading, ElMessageBox} from 'element-plus'
import Rest from './Rest.js';

import {
    Refresh,
    Search,
    Paperclip,
    ArrowUpBold,
    ArrowDownBold,
    Sort,
    ArrowLeft,
    List,
    UploadFilled,
    Upload,
    Delete,
    Loading,
    CircleCloseFilled,
    CircleCheckFilled
} from '@element-plus/icons-vue/dist';

const app = createApp(Application);

app.use(ElLoading);

app.config.globalProperties.$notify = ElNotification;
app.config.globalProperties.$confirm = ElMessageBox.confirm;
app.config.globalProperties.$prompt = ElMessageBox.prompt;
// Backward compatibility for legacy references.
app.config.globalProperties.$promt = ElMessageBox.prompt;
app.config.globalProperties.$messageBox = ElMessageBox;

const router = createRouter({
    history: createWebHashHistory(),
    routes
});

const components = [
    // Icon component
    Refresh,
    Paperclip,
    Search,
    ArrowUpBold,
    ArrowDownBold,
    Sort,
    List,
    ArrowLeft,
    UploadFilled,
    Upload,
    Delete,
    Loading,
    CircleCloseFilled,
    CircleCheckFilled
];

components.forEach(component => {
    app.component(component.name, component)
})

app.config.globalProperties.appVars = window.fs_customer_portal;

app.mixin({
    data() {
        return {
        }
    },
    methods: {
        $get: Rest.get,
        $post: Rest.post,
        $del: Rest.delete,
        $put: Rest.put,
        $patch: Rest.patch,
        $uploadFile: Rest.uploadFile,
        $handleError(response) {
            let errorMessage = '';
            if (typeof response === 'string') {
                errorMessage = response;
            } else if (response && response.message) {
                errorMessage = response.message;
            } else {
                errorMessage = this.convertToText(response);
            }
            if (!errorMessage) {
                errorMessage = 'Something is wrong!';
            }

            if (typeof errorMessage != 'string') {
                errorMessage = this.convertToText(errorMessage);
            }

            this.$notify({
                type: 'error',
                title: 'Error',
                message: errorMessage,
                dangerouslyUseHTMLString: true
            });
        },
        convertToText(obj) {
            const string = [];
            if (typeof (obj) === 'object' && (obj.join === undefined)) {
                for (const prop in obj) {
                    string.push(this.convertToText(obj[prop]));
                }
            } else if (typeof (obj) === 'object' && !(obj.join === undefined)) {
                for (const prop in obj) {
                    string.push(this.convertToText(obj[prop]));
                }
            } else if (typeof (obj) === 'function') {

            } else if (typeof (obj) === 'string') {
                string.push(obj)
            }
            return string.join('<br />')
        },
        $t(string) {
            return window.window.fs_customer_portal.i18n[string] || string;
        }
    }
});

app.use(router).mount('#fluent_support_client_app');

jQuery(document).ajaxSuccess((event, xhr, settings) => {
    const nonce = xhr.getResponseHeader('X-WP-Nonce');
    if (nonce) {
        window.fs_customer_portal.rest.nonce = nonce;
    }
});
