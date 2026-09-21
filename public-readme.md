# Fluent Support - WordPress Helpdesk & Customer Support System

A feature-rich, self-hosted customer support and helpdesk plugin for WordPress that rivals premium SaaS solutions like Zendesk, Help Scout, and Freshdesk - all within your WordPress site without any growth-tax or monthly subscriptions.

## 🚀 What is Fluent Support?

Fluent Support is a complete customer support ticketing solution built specifically for WordPress. It provides businesses with a powerful, self-hosted helpdesk system that scales with your needs while keeping you in full control of your data and customer interactions.

### ✨ Key Features

**🎯 Complete Support Solution**
- Multi-channel support ticketing system (contact forms, direct submission, email)
- Unlimited tickets, agents, customers, and products
- Customer support portal with secure public access
- 360° customer view with purchase history, membership details, and previous tickets

**⚡ Built for Performance**
- Single Page Application (SPA) built with VueJS and REST API
- Lightning-fast interface with no page reloads
- Separate database tables - won't slow down your WordPress site
- GitHub-style activity heatmaps and comprehensive reporting

**🔧 Productivity Tools**
- Automated workflows and manual bulk actions
- Saved replies for common responses
- Internal notes and agent mentions
- Time tracking and agent permissions
- Advanced filtering and ticket segmentation
- AI-powered responses with OpenAI integration (Pro)

**🔌 Seamless Integrations**
- **E-commerce**: WooCommerce, Easy Digital Downloads
- **Membership**: Restrict Content Pro, Paid Membership Pro, MemberPress
- **LMS**: LearnDash, LifterLMS, TutorLMS
- **Community**: BuddyPress, BuddyBoss, Fluent Community
- **Marketing**: FluentCRM, Fluent Forms
- **Storage**: Google Drive, Dropbox (Pro)

**🛡️ Privacy & Security**
- 100% self-hosted - your data stays on your server
- GDPR compliant out of the box
- No external dependencies or SaaS connections required
- Complete GPL codebase for unlimited customization

## 🎬 See It In Action

- [5-Minute Getting Started Guide](https://fluentsupport.com/fluent-support-101/)
- [Official Website](https://fluentsupport.com/)
- [Facebook Community](https://facebook.com/groups/fluentsupport)

## 📊 Migration Support

Easily migrate from existing support systems:
- Awesome Support
- SupportCandy
- Help Scout (Pro)
- Freshdesk (Pro)
- Zendesk (Pro)
- JS Help Desk

## 🏗️ For Contributors

### Development Stack
- **Frontend**: VueJS 2.x, REST API, Single Page Application
- **Backend**: PHP 7.4+, WordPress 5.6+
- **Build Tools**: Laravel Mix, NPM/Node.js
- **Database**: Custom MySQL tables for optimal performance

### Quick Start

```bash
# Clone the repository
git clone [repository-url]
cd fluent-support

# Install dependencies
npm install

# Development build with file watching
npx mix watch
# or
npm run watch

# Production build
npx mix --production
```

### Build for Release
```bash
sh ./build.sh --node-build --with-pro
```

### Development Guidelines
- Follow WordPress coding standards
- Maintain backward compatibility
- Ensure translation strings are properly handled

### Database Schema
Recent additions:
- `waiting_since` column added to `fs_tickets` table

## 🤝 Contributing

We welcome contributions! Whether you're fixing bugs, adding features, or improving documentation, your help is appreciated.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Ensure all functionality works as expected
5. Submit a pull request

## 📄 License

GPL v2 or later.

## 🔗 Links

- **Official Website**: [fluentsupport.com](https://fluentsupport.com/)
- **Community**: [Facebook Group](https://facebook.com/groups/fluentsupport)
- **Documentation**: [Getting Started Guide](https://fluentsupport.com/fluent-support-101/)
- **WordPress Plugin Directory**: [Fluent Support](https://wordpress.org/plugins/fluent-support/)

---

**Built with ❤️ by the team behind FluentCRM, Fluent Forms, and Ninja Tables**