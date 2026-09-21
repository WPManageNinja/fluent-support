const {Fragment, useState} = wp.element;
const {
    SelectControl,
    TextControl,
    Button,
    Spinner,
    SearchControl,
    ToggleControl
} = wp.components;
const {__} = wp.i18n;

import './view_ticket.scss';
import ViewTicketInspectorControls, { generateStyles } from './InspectorSettings';

export const ViewTicketBlock = props => {
    const {attributes: blockAttributes, setAttributes, showSection} = props;

    const [activeTab, setActiveTab] = useState('Visual');
    const [details, setDetails] = useState('');

    const {
        blockStyles,
        primaryButtonStyles,
        secondaryButtonStyles,
        avatarStyle
    } = generateStyles(blockAttributes);

    return (
        <Fragment>
            <ViewTicketInspectorControls attributes={blockAttributes} setAttributes={setAttributes} />

            <div className="fs_block_back_nav">
                <button className="fs_block_back_button" onClick={() => showSection('allTickets')}>
                    <svg width="6" height="10" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M2.20437 4.9992L5.91687 8.7117L4.85637 9.7722L0.083374 4.9992L4.85637 0.226196L5.91687 1.2867L2.20437 4.9992Z"
                            fill="#0E121B"
                        />
                    </svg>
                    <span>Back to All Tickets</span>
                </button>
            </div>

            <div className="fs_block_editor_view_ticket" style={blockStyles}>
                <div className="fs_block_editor_view_ticket_header">
                    <h2 className="fs_block_ticket_title">#2053 Premium Access Not Working</h2>
                    <div className="fs_block_ticket_status active">Active</div>
                </div>

                <div className="fs_block_ticket_actions">
                    <button className="fs_block_refresh_button" style={secondaryButtonStyles}>
                        <span className="dashicons dashicons-update-alt"></span> Refresh
                    </button>
                    <button className="fs_block_close_ticket_button" style={secondaryButtonStyles}>
                        Close Ticket
                    </button>
                </div>

                <div className="fs_block_ticket_privacy_notice">
                    <span className="fs_block_privacy_icon">i</span>
                    <p className="fs_block_privacy_text">This ticket is private—only you and official support agents can view
                        it.</p>
                    <button className="fs_block_close_notice_button">×</button>
                </div>

                <div className="fs_block_reply_container">
                    <label className="fs_block_reply_header">Write a reply</label>
                    <div className="fs_block_ticket_details_container">
                        <div className="fs_block_ticket_details_tabs">
                            <div className="fs_block_tabs_wrapper">
                                <button
                                    className={`fs_block_tab_button ${activeTab === 'Visual' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('Visual')}
                                >
                                    Visual
                                </button>
                                <button
                                    className={`fs_block_tab_button ${activeTab === 'Text' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('Text')}
                                >
                                    Text
                                </button>
                            </div>
                        </div>
                        <div className="fs_block_ticket_details_editor">
                            <div className="fs_block_editor_toolbar">
                                <select className="fs_block_paragraph_dropdown">
                                    <option>Paragraph</option>
                                </select>
                                <button className="fs_block_toolbar_button fs_block_bold"><span
                                    className="dashicons dashicons-editor-bold"></span></button>
                                <button className="fs_block_toolbar_button fs_block_italic"><span
                                    className="dashicons dashicons-editor-italic"></span></button>
                                <button className="fs_block_toolbar_button fs_block_list_bullets"><span
                                    className="dashicons dashicons-editor-ul"></span></button>
                                <button className="fs_block_toolbar_button fs_block_list_numbers"><span
                                    className="dashicons dashicons-editor-ol"></span></button>
                                <button className="fs_block_toolbar_button fs_block_link"><span
                                    className="dashicons dashicons-admin-links"></span></button>
                                <button className="fs_block_toolbar_button fs_block_quote"><span
                                    className="dashicons dashicons-editor-quote"></span></button>
                                <button className="fs_block_toolbar_button fs_block_align_left"><span
                                    className="dashicons dashicons-editor-alignleft"></span></button>
                                <button className="fs_block_toolbar_button fs_block_align_center"><span
                                    className="dashicons dashicons-editor-aligncenter"></span></button>
                                <button className="fs_block_toolbar_button fs_block_align_right"><span
                                    className="dashicons dashicons-editor-alignright"></span></button>
                                <button className="fs_block_toolbar_button fs_block_align_justify"><span
                                    className="dashicons dashicons-editor-justify"></span></button>
                                <button className="fs_block_toolbar_button fs_block_font"><span
                                    className="dashicons dashicons-editor-textcolor"></span></button>
                                <button className="fs_block_toolbar_button fs_block_undo"><span
                                    className="dashicons dashicons-undo"></span></button>
                                <button className="fs_block_toolbar_button fs_block_redo"><span
                                    className="dashicons dashicons-redo"></span></button>
                            </div>
                            <textarea
                                className="fs_block_editor_textarea"
                                placeholder="Enter ticket details here..."
                                value={details}
                                onChange={(e) => setDetails(e.target.value)}
                            ></textarea>
                        </div>
                    </div>
                    <div className="fs_block_attachment_section">
                        <h4 className="fs_block_attachment_header">Add Attachment</h4>
                        <button className="fs_block_browse_file_button" style={secondaryButtonStyles}>
                            <svg width="20" height="18" viewBox="0 0 24 24" fill="currentColor"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 12.5274L15.8187 16.3452L14.5452 17.6187L12.9 15.9735V21H11.1V15.9717L9.45479 17.6187L8.18129 16.3452L12 12.5274ZM12 3C13.5453 3.00007 15.0366 3.568 16.1906 4.59581C17.3445 5.62361 18.0805 7.03962 18.2586 8.5746C19.3784 8.87998 20.3553 9.56919 21.0186 10.5218C21.6818 11.4744 21.9892 12.6297 21.887 13.786C21.7849 14.9422 21.2796 16.0257 20.4596 16.8472C19.6396 17.6687 18.5569 18.1759 17.4009 18.2802V16.4676C17.815 16.4085 18.2133 16.2674 18.5723 16.0527C18.9314 15.8379 19.244 15.5539 19.4921 15.217C19.7402 14.8801 19.9186 14.4972 20.0171 14.0906C20.1155 13.6839 20.132 13.2618 20.0655 12.8488C19.9991 12.4357 19.851 12.0401 19.6299 11.6849C19.4089 11.3297 19.1193 11.0221 18.7781 10.78C18.4369 10.538 18.0508 10.3663 17.6425 10.2751C17.2343 10.1838 16.8119 10.1748 16.4001 10.2486C16.541 9.5924 16.5334 8.91297 16.3778 8.2601C16.2222 7.60722 15.9224 6.99743 15.5006 6.47538C15.0788 5.95333 14.5455 5.53225 13.9399 5.24298C13.3343 4.9537 12.6716 4.80357 12.0004 4.80357C11.3293 4.80357 10.6666 4.9537 10.061 5.24298C9.45533 5.53225 8.92207 5.95333 8.50025 6.47538C8.07843 6.99743 7.77873 7.60722 7.62309 8.2601C7.46746 8.91297 7.45984 9.5924 7.60079 10.2486C6.77968 10.0944 5.93095 10.2727 5.2413 10.7443C4.55165 11.2159 4.07759 11.9421 3.92339 12.7632C3.76919 13.5843 3.9475 14.433 4.41908 15.1227C4.89065 15.8123 5.61688 16.2864 6.43799 16.4406L6.59999 16.4676V18.2802C5.4439 18.1761 4.36116 17.669 3.54101 16.8476C2.72087 16.0261 2.21548 14.9426 2.1132 13.7863C2.01091 12.6301 2.31822 11.4747 2.98142 10.522C3.64462 9.56934 4.62153 8.88005 5.74139 8.5746C5.91933 7.03954 6.65525 5.62342 7.80921 4.59558C8.96317 3.56774 10.4546 2.99988 12 3Z"
                                    fill="currentColor"/>
                            </svg>
                            <span>Browse File</span>
                        </button>
                        <p className="fs_block_attachment_info">
                            (Supported Types: Photos, CSV, PDF/Docs, Zip, JSON and max file size: 2.0MB)
                        </p>
                    </div>

                    <div className="fs_block_reply_actions">
                        <a href="#" className="fs_block_reply_and_close_link">
                            Reply and Close
                        </a>
                        <button
                            className="fs_block_reply_button"
                            style={primaryButtonStyles}
                        >
                            <span>Reply</span>
                            <svg width="16" height="12" viewBox="0 0 16 12" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.75 12L15.25 6L7.75 0V3.75C3.60775 3.75 0.25 7.10775 0.25 11.25C0.25 11.4548 0.2575 11.6572 0.274 11.8575C1.37125 9.777 3.5215 8.33925 6.01525 8.25375L6.25 8.25H7.75V12ZM9.25 6.75H6.2245L5.96425 6.75525C5.0005 6.7875 4.07125 6.98775 3.20725 7.32975C4.3075 6.05625 5.935 5.25 7.75 5.25H9.25V3.12075L12.8485 6L9.25 8.87925V6.75Z"
                                    fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div className="fs_block_conversation_container">
                    <div className="fs_block_conversation_message fs_block_agent_message">
                        <div className="fs_block_message_avatar" style={avatarStyle}>

                        </div>
                        <div className="fs_block_message_content">
                        <div className="fs_block_message_header">
                                <div className="fs_block_message_author">
                                    <span className="fs_block_author_name">Support Agent</span>
                                    <span className="fs_block_author_role">Support Team</span>
                                </div>
                            </div>
                            <div className="fs_block_message_text">
                                <p>Hi! Thanks for reaching out. I’ve checked your account, and there was a delay in
                                    syncing your subscription. I’ve manually updated it, and you should now have full
                                    access. Please refresh your page and let me know if everything works!</p>
                            </div>
                            <div className="fs_block_message_footer">
                                <span className="fs_block_message_timestamp">1 day ago</span>
                            </div>
                        </div>
                    </div>
                    <div className="fs_block_conversation_message fs_block_thread_start">
                        <div className="fs_block_message_avatar" style={avatarStyle}>

                        </div>
                        <div className="fs_block_message_content">
                            <div className="fs_block_message_header">
                                <div className="fs_block_message_author">
                                    <span className="fs_block_author_name">You</span>
                                    <span className="fs_block_author_info">started the conversation</span>
                                </div>
                            </div>
                            <div className="fs_block_message_text">
                                <p>Hi, I upgraded to the premium plan, but my account still shows the free plan
                                    restrictions. Can you check?</p>
                            </div>
                            <div className="fs_block_message_footer">
                                <span className="fs_block_message_timestamp">2 days ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Fragment>
    );
};

export default ViewTicketBlock;
