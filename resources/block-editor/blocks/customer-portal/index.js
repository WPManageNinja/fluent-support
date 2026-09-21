const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
import Edit from './edit';
import icons from '../utils/icons';
registerBlockType('fluent-support/customer-portal', {
    apiVersion: 3,
    title: __('Customer Portal'),
    description: __('Customer Portal Page'),
    category: 'design',
    icon: icons.fluentSupport,
    keywords: [__('fluent'), __('fluent support'), __('support'), __('tickets')],
    supports: {
        html: true
    },
    /**
     * @see ./attributes.js
     */
    /**
     * @see ./edit.js
     */
    edit: function (props) {
        const { attributes, setAttributes } = props;
        return (
            <Edit
                attributes={attributes}
                setAttributes={setAttributes}
            />
        );
    },
    save: function (props) {
        return null;
    },
} );

