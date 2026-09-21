<template>
    <div class="fs_global_form_builder">
        <el-form @submit.prevent.native="nativeSave" :data="formData" :label-position="label_position">
            <slot name="form_start"></slot>
            <template v-for="(field,fieldIndex) in fields" :key="fieldIndex">
                <with-label v-if="dependancyPass(field)" :field="field">
                    <div :class="`fs_${field.type}_wrapper`">
                        <component :is="field.type" v-model="formData[fieldIndex]" :field="field" v-bind="field.props"/>
                        <div v-if="field.inline_help && field.type == 'inline-checkbox'" class="fs_field_description">
                            <span v-html="field.inline_help"></span>
                            <a
                                v-if="fieldIndex === 'keyboard_shortcuts' && field.shortcut_modal"
                                href="#"
                                class="fs_doc_link fs_keyboard_shortcuts_link"
                                @click.prevent="showKeyboardShortcutsModal = true"
                            >{{ field.shortcut_modal.title }}</a>
                        </div>
                    </div>
                </with-label>
            </template>
            <slot name="form_end"></slot>
        </el-form>

        <keyboard-shortcuts-modal
            v-model="showKeyboardShortcutsModal"
            :shortcut-modal-data="shortcutModalData"
        />
    </div>
</template>

<script type="text/babel">
import WithLabel from './_WithLabel';
import InputText from './_InputText';
import WpEditor from '../_wp_editor'
import WpEditorField from './_WpEditorField'
import ImageRadio from './_ImageRadio'
import InputRadio from './_InputRadio'
import InputOptions from './_InputOptions'
import InlineCheckbox from './_InlineCheckbox'
import VerifiedEmailInput from './_VerifiedEmailInput'
import CheckboxGroup from './_InputCheckboxes'
import MultiSelect from './_MultiSelect'
import HtmlViewer from './_HtmlViewer'
import AgentSelectors from './_AgentSelectors'
import TagSelectors from './_TagSelectors'
import CountrySelector from './_CountrySelector'
import ObjectTabularInput from './_ObjectTabularInput'
import KeyboardShortcutsModal from './_KeyboardShortcutsModal.vue'

export default {
    name: 'global_form_builder',
    components: {
        WithLabel,
        InputText,
        WpEditor,
        WpEditorField,
        ImageRadio,
        InputRadio,
        InputOptions,
        InlineCheckbox,
        VerifiedEmailInput,
        CheckboxGroup,
        MultiSelect,
        HtmlViewer,
        AgentSelectors,
        TagSelectors,
        CountrySelector,
        ObjectTabularInput,
        KeyboardShortcutsModal
    },
    data() {
        return {
            showKeyboardShortcutsModal: false
        };
    },
    computed: {
        shortcutModalData() {
            return this.fields && this.fields.keyboard_shortcuts && this.fields.keyboard_shortcuts.shortcut_modal
                ? this.fields.keyboard_shortcuts.shortcut_modal
                : null;
        }
    },
    props: {
        formData: {
            type: Object,
            required: false,
            default() {
                return {}
            }
        },
        label_position: {
            required: false,
            type: String,
            default() {
                return 'top';
            }
        },
        fields: {
            required: true,
            type: Object
        }
    },
    methods: {
        nativeSave() {
            this.$emit('nativeSave', this.formData);
        },
        /**
         * Helper function for show/hide dependent elements
         & @return {Boolean}
         */
        compare(operand1, operator, operand2) {
            switch (operator) {
                case '=':
                    return operand1 === operand2
                case '!=':
                    return operand1 !== operand2
                case 'contains':
                    return Array.isArray(operand2) && operand2.includes(operand1);
                case 'not_contains':
                    return !Array.isArray(operand2) || !operand2.includes(operand1);
            }
        },

        /**
         * Checks if a prop is dependent on another
         * @param listItem
         * @return {boolean}
         */
        dependancyPass(listItem) {
            if (listItem && listItem.dependency) {
                const optionPaths = listItem.dependency.depends_on.split('/');

                const dependencyVal = optionPaths.reduce((obj, prop) => {
                    return obj[prop]
                }, this.formData);

                if (this.compare(listItem.dependency.value, listItem.dependency.operator, dependencyVal)) {
                    return true;
                }
                return false;
            }
            return true;
        }
    }
}
</script>
