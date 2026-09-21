import React from 'react';
const { InspectorControls } = wp.blockEditor;
const { PanelBody, RangeControl } = wp.components;
const { __ } = wp.i18n;

import { GeneralInspectorSettings, generateStyles as generalGenerateStyles } from '../GeneralInspectorSettings';

export const generateStyles = (attributes) => {

    const generalStyles = generalGenerateStyles(attributes);

    return {
        ...generalStyles,
        inputFieldStyle: {
            borderRadius: `${attributes.ticketInputBorderRadius || 0}px`,
        }
    };
};

const CreateTicketInspectorControls = ({ attributes, setAttributes }) => {
    return (
        <InspectorControls>
            <PanelBody title={__('Custom Page Style')} initialOpen={true}>
                <RangeControl
                    label={__('Input Field Border Radius')}
                    value={attributes.ticketInputBorderRadius || 0}
                    onChange={value => setAttributes({ticketInputBorderRadius: value})}
                    min={0}
                    max={20}
                />
            </PanelBody>
            <GeneralInspectorSettings
                attributes={attributes}
                setAttributes={setAttributes}
            />
        </InspectorControls>
    );
};

export default CreateTicketInspectorControls;
