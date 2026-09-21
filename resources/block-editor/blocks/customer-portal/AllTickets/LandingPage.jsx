const {Fragment, useState} = wp.element;
const {__} = wp.i18n;

import './all-tickets.scss';
import TicketsInspectorControls, {generateStyles} from './InspectorSettings';

export const TicketsLandingBlock = props => {
    const {attributes: blockAttributes, setAttributes, showSection} = props;

    const [loading, setLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [currentPage, setCurrentPage] = useState(2);
    const [currentFilter, setCurrentFilter] = useState('all');
    const [selectedProduct, setSelectedProduct] = useState('All Products');
    const totalPages = 16;

    const ticketSubjects = [
        "Unable to log in after password reset",
        "Payment processed but service not activated",
        "Delayed email notifications for support tickets",
        "Request for account data export under GDPR",
        "File upload failing with server error"
    ];

    const ticketDescriptions = [
        "After resetting my password, I’m unable to log in. The new credentials are not being recognized.",
        "I was charged for my subscription renewal, but my account still shows inactive. Please check.",
        "I’m not receiving email notifications when a support ticket is updated. This issue started recently.",
        "I need to export my account data as per GDPR compliance. Can you provide a downloadable report?",
        "When attempting to upload a file, I receive a ‘500 Server Error’. This happens with different file types."
    ];

    const statuses = ["open", "pending", "resolved", "in-progress", "escalated"];

    const sampleTickets = Array.from({ length: 5 }, (_, index) => ({
        id: index + 1,
        title: ticketSubjects[index],
        description: ticketDescriptions[index],
        date: `${10 + index} Mar, 2024`,
        status: "active",
        count: Math.floor(Math.random() * 5) + 1
    }));

    const {
        blockStyles,
        primaryButtonStyles,
        showLogout
    } = generateStyles(blockAttributes);

    const [isDropdownOpen, setDropdownOpen] = useState(false);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const handleFilterChange = (filter) => {
        setCurrentFilter(filter);
    };

    const toggleDropdown = () => {
        setDropdownOpen(!isDropdownOpen);
    };

    return (
        <Fragment>
            <TicketsInspectorControls
                attributes={blockAttributes}
                setAttributes={setAttributes}
            />
            <div className="fs_block_tickets_block_container" style={blockStyles}>
                <div className="fs_block_tickets_header">
                    <label className="fs_block_tickets_title">
                        {'All Tickets'}
                    </label>
                    <div className="fs_block_header_actions">
                        <button
                            className="fs_block_create_ticket_button"
                            style={primaryButtonStyles}
                            onClick={() => showSection('createTicket')}
                        >
                            <span className="fs_block_plus_icon">+</span>
                            {'Create Ticket'}
                        </button>

                        {(showLogout.showLogoutButton) && (
                            <div className="fs_block_logout_container">
                                <div className="fs_block_logout_button">
                                    <div className="fs_block_three_dot_dropdown">
                                        <button
                                            className="fs_block_three_dot_button"
                                            onClick={toggleDropdown}
                                        >
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M10 3.25C9.38125 3.25 8.875 3.75625 8.875 4.375C8.875 4.99375 9.38125 5.5 10 5.5C10.6187 5.5 11.125 4.99375 11.125 4.375C11.125 3.75625 10.6187 3.25 10 3.25ZM10 14.5C9.38125 14.5 8.875 15.0063 8.875 15.625C8.875 16.2437 9.38125 16.75 10 16.75C10.6187 16.75 11.125 16.2437 11.125 15.625C11.125 15.0063 10.6187 14.5 10 14.5ZM10 8.875C9.38125 8.875 8.875 9.38125 8.875 10C8.875 10.6188 9.38125 11.125 10 11.125C10.6187 11.125 11.125 10.6188 11.125 10C11.125 9.38125 10.6187 8.875 10 8.875Z"
                                                    fill="#525866"/>
                                            </svg>
                                        </button>
                                        {isDropdownOpen && (
                                            <ul className="fs_block_logout_dropdown_menu">
                                                <li className="fs_block_logout_option">
                                                    <button
                                                        className="fs_block_logout_btn"
                                                        onClick={() => handleLogout()}
                                                    >
                                                        <svg className="fs_logout_icon" width="20" height="20"
                                                             viewBox="0 0 20 20" fill="none"
                                                             xmlns="http://www.w3.org/2000/svg">
                                                            <path
                                                                d="M4.75 17.5C4.55109 17.5 4.36032 17.421 4.21967 17.2803C4.07902 17.1397 4 16.9489 4 16.75V3.25C4 3.05109 4.07902 2.86032 4.21967 2.71967C4.36032 2.57902 4.55109 2.5 4.75 2.5H15.25C15.4489 2.5 15.6397 2.57902 15.7803 2.71967C15.921 2.86032 16 3.05109 16 3.25V5.5H14.5V4H5.5V16H14.5V14.5H16V16.75C16 16.9489 15.921 17.1397 15.7803 17.2803C15.6397 17.421 15.4489 17.5 15.25 17.5H4.75ZM14.5 13V10.75H9.25V9.25H14.5V7L18.25 10L14.5 13Z"
                                                                fill="#525866"/>
                                                        </svg>
                                                        <span className="fs_block_logout_text">Log Out</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="fs_block_tickets_filters">
                    <div className="fs_block_filters_left">
                        <div className={'fs_block_status_filter'}>
                            <button
                                className={`fs_block_filter_button ${currentFilter === 'all' ? 'active' : ''}`}
                                onClick={() => handleFilterChange('all')}
                            >
                                All
                            </button>
                            <button
                                className={`fs_block_filter_button ${currentFilter === 'open' ? 'active' : ''}`}
                                onClick={() => handleFilterChange('open')}
                            >
                                Open
                            </button>
                            <button
                                className={`fs_block_filter_button ${currentFilter === 'closed' ? 'active' : ''}`}
                                onClick={() => handleFilterChange('closed')}
                            >
                                Closed
                            </button>
                        </div>
                        <div className="fs_block_product_dropdown">
                            <select
                                value={selectedProduct}
                                onChange={(e) => setSelectedProduct(e.target.value)}
                            >
                                <option value="All Products">All Products</option>
                                <option value="Product 1">Product 1</option>
                                <option value="Product 2">Product 2</option>
                            </select>
                        </div>

                        <button className="fs_block_filter_toggle_button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 4H21V6H3V4ZM5 11H19V13H5V11ZM7 18H17V20H7V18Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>

                    <div className="fs_block_filters_right">
                        <div className="fs_block_search_wrapper">
                            <input
                                type="text"
                                placeholder="Search..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="fs_block_search_input"
                            />
                        </div>
                    </div>
                </div>

                {/* Table Header */}
                <div className={'fs_block_ticket_table'}>
                    <div className="fs_block_tickets_table_header">
                        <div className="fs_block_header_conversation">Conversation</div>
                        <div className="fs_block_header_date">
                            Date
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 10l5 5 5-5z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div className="fs_block_header_status">Status</div>
                    </div>

                    {/* Tickets List */}
                    <div className="fs_block_tickets_list">
                        {sampleTickets.map(ticket => (
                            <div key={ticket.id} className="fs_block_ticket_item"
                                 onClick={() => showSection('viewTicket')}
                                 style={{cursor: 'pointer'}}>
                                <div className="fs_block_ticket_conversation">
                                    <div className="fs_block_ticket_title">
                                        {ticket.title}
                                        <span className="fs_block_ticket_count">{ticket.count}</span>
                                    </div>
                                    <div className="fs_block_ticket_description">
                                        {ticket.description}
                                    </div>
                                </div>
                                <div className="fs_block_ticket_date">
                                    {ticket.date}
                                </div>
                                <span className="fs_block_status_badge">
                                    <span className="fs_block_status_dot"></span>
                                    {ticket.status}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Pagination */}
                <div className="fs_block_tickets_pagination">
                    <div className={'fs_block_pagination_left'}>
                        <div className="fs_block_pagination_info">
                            Page {currentPage} of {totalPages}
                        </div>

                        <div className="fs_block_pagination_per_page">
                            <select value={`${blockAttributes.perPage || 10} / page`}>
                                <option value="10 / page">10 / page</option>
                                <option value="20 / page">20 / page</option>
                                <option value="30 / page">30 / page</option>
                            </select>
                        </div>
                    </div>
                    <div className={'fs_block_pagination_right'}>
                        <div className="fs_block_pagination_controls">
                            <button className="fs_block_pagination_button"
                                    onClick={() => handlePageChange(Math.max(1, currentPage - 1))}>‹
                            </button>

                            <button
                                className={`fs_block_pagination_button ${currentPage === 1 ? 'active' : ''}`}
                                onClick={() => handlePageChange(1)}>1
                            </button>
                            <button
                                className={`fs_block_pagination_button ${currentPage === 2 ? 'active' : ''}`}
                                onClick={() => handlePageChange(2)}>2
                            </button>
                            <button
                                className={`fs_block_pagination_button ${currentPage === 3 ? 'active' : ''}`}
                                onClick={() => handlePageChange(3)}>3
                            </button>
                            <button
                                className={`fs_block_pagination_button ${currentPage === 4 ? 'active' : ''}`}
                                onClick={() => handlePageChange(4)}>4
                            </button>
                            <button
                                className={`fs_block_pagination_button ${currentPage === 5 ? 'active' : ''}`}
                                onClick={() => handlePageChange(5)}>5
                            </button>

                            <span className="pagination-ellipsis">...</span>

                            <button
                                className={`fs_block_pagination_button ${currentPage === totalPages ? 'active' : ''}`}
                                onClick={() => handlePageChange(totalPages)}>
                                {totalPages}
                            </button>

                            <button className="fs_block_pagination_button"
                                    onClick={() => handlePageChange(Math.min(totalPages, currentPage + 1))}>›
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Fragment>
    );
};

export default TicketsLandingBlock;
