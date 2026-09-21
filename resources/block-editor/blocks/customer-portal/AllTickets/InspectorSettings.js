import React from 'react';
const { InspectorControls } = wp.blockEditor;
const { PanelBody, PanelRow } = wp.components;
const { __ } = wp.i18n;

import { GeneralInspectorSettings, generateStyles as generalGenerateStyles } from '../GeneralInspectorSettings';

export const generateStyles = (attributes) => {
    const generalStyles = generalGenerateStyles(attributes);

    return {
        ...generalStyles,
        showLogout: {
            showLogoutButton: attributes.showLogoutButton || false
        },
    };
};

const TicketsInspectorControls = ({ attributes, setAttributes }) => {
    return (
        <InspectorControls>
            <PanelBody title={__('Custom Page Style')} initialOpen={true}>
                <PanelRow>
                    <div className="fs_block_inspector_widget">
                        <h3 className="label">{__('Log Out Button')}</h3>
                        <select
                            value={attributes.showLogoutButton ? 'true' : 'false'} // Use 'true' or 'false' for display
                            onChange={(event) => setAttributes({ showLogoutButton: event.target.value === 'true' })} // Convert to boolean
                        >
                            <option value="true">{__('Show')}</option>
                            <option value="false">{__('Hide')}</option>
                        </select>
                    </div>
                </PanelRow>
            </PanelBody>
            <GeneralInspectorSettings
                attributes={attributes}
                setAttributes={setAttributes}
            />
        </InspectorControls>
    );
};

export default TicketsInspectorControls;
