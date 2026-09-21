const {Fragment, useState, useEffect} = wp.element;
const {useBlockProps} = wp.blockEditor;
const { apiFetch } = wp;
import AllTickets from './AllTickets/LandingPage';
import CreateTicket from './CreateTicket/LandingPage';
import ViewTicket from "./ViewTicket/LandingPage";

export default function Edit({attributes, setAttributes}) {

    const [showSection, setShowSection] = useState('allTickets');

    return (
        <Fragment>
            <div {...useBlockProps()}>
                {/* Render different components based on the showSection state */}
                {showSection === 'allTickets' && (
                    <AllTickets
                        attributes={attributes}
                        setAttributes={setAttributes}
                        showSection={setShowSection}
                    />
                )}
                {showSection === 'createTicket' && (
                    <CreateTicket
                        attributes={attributes}
                        setAttributes={setAttributes}
                        showSection={setShowSection}
                    />
                )}
                {showSection === 'viewTicket' && (
                    <ViewTicket
                        attributes={attributes}
                        setAttributes={setAttributes}
                        showSection={setShowSection}
                    />
                )}
            </div>
        </Fragment>
    );
}
