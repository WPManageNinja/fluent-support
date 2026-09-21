import React from 'react';
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, PanelRow } = wp.components;
const { __ } = wp.i18n;

import { GeneralInspectorSettings, generateStyles as generalGenerateStyles } from '../GeneralInspectorSettings';

export const generateStyles = (attributes) => {
    const generalStyles = generalGenerateStyles(attributes);

    return {
        ...generalStyles,
        avatarStyle: {
            borderRadius: attributes.avatarBorderRadius || '50%'
        },
    };
};

export const ViewTicketInspectorControls = ({ attributes, setAttributes }) => {
    return (
        <InspectorControls>
            <PanelBody title={__('Custom Page Style')} initialOpen={true}>
                <PanelRow>
                    <div className="fs_block_inspector_widget">
                        <h3 className="label">{__('Avatar Style')}</h3>
                        <select
                            value={attributes.avatarBorderRadius}
                            onChange={(event) => setAttributes({avatarBorderRadius: event.target.value})}
                        >
                            <option value="8px">{__('Square')}</option>
                            <option value="50%">{__('Rounded')}</option>
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

export default ViewTicketInspectorControls;
