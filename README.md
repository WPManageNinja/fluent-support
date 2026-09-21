# Fluent Support

WordPress helpdesk and support ticket plugin.

## Development Setup

```bash
npm install --legacy-peer-deps
```

### Dev mode (Vite HMR on port 5175)
```bash
npm run dev
```
This starts the Vite dev server with hot module replacement. PHP serves assets from `http://localhost:5175` when the server is running, and falls back to built assets when it's not.

### Production build
```bash
npm run build
```
Builds both the admin app (code-split with vendor chunks) and the customer portal (standalone bundle).

### Individual builds
```bash
npm run build:admin    # Admin app only
npm run build:portal   # Customer portal only
npm run build:block    # Gutenberg block (React, separate from Vite)
```

### RTL CSS
```bash
npm run rtl
```
Generates `-rtl.css` variants from all built CSS files.

### Build for release
```bash
sh build.sh --node-build --rtl_css --with_pro
```

### Publish to the public repo
```bash
npm -C dev run public:status              # what's pending in either direction
npm -C dev run public:import              # bring in merged public PRs (if any)
npm -C dev run public:up                  # publish develop to the public repo
npm -C dev run public:release -- v2.4.1   # tag + GitHub release
```
Publishes a snapshot of `develop` (minus everything in `dev/mirror/mirrorignore`) to
`WPManageNinja/fluent-support` without force-pushing, keeping contributor commits
intact. Full guide: `dev/mirror/README.md`.

The list of files included in the plugin zip is managed in `dev/whitelist.sh`.

## Build Architecture

The frontend uses **Vite** with two separate build configs:

- **Admin app** (`vite.config.mjs`) — Vue 3 SPA with Element Plus, code-split into `start.js` + shared vendor chunks. Entry: `resources/admin/start.js`
- **Customer portal** (`vite.config.portal.mjs`) — Vue 3 SPA, standalone single-file bundle with all dependencies inlined. Entry: `resources/customer_portal/portal.js`
- **Block editor** — React/JSX Gutenberg block, built separately via `@wordpress/scripts`

### Output structure
```
assets/
  admin/
    start.js              # Admin Vue app
    global_admin.js        # Admin menu interactions
    global_summary.js      # Admin bar widget
    css/
      alpha-admin.css      # Admin styles
      style.css            # Merged vendor/component CSS
      all_public.css       # Login/signup styles
  customer_portal/
    portal.js              # Standalone portal app
    login_helper.js        # Login form handler
  portal/css/
    app.css                # Portal styles
  vendor.js                # Shared vendor libs (admin only)
  vendor-element-plus.js   # Element Plus (admin only)
  block-editor/js/
    fs_block.js            # Gutenberg block
```

### Vite dev/prod mode
The `config/app.php` `env` key controls mode (`dev` or `production`). The `npm run dev` and `npm run build` scripts set this automatically via `vite-mode.js`. In dev mode, the `Vite.php` helper checks if the dev server is running and proxies assets from it; if the server is down, it falls back to built assets.

## DB Changelog

- `waiting_since` in fs_tickets has been added

## Run the tests

If you don't have a testing database then please run the following command from your project root:
```bash
bin/install-wp-tests.sh db_name db_user db_pass db_host
```

You may need to install `phpunit` package as dev deps. To run the tests,
just run `phpunit` command in the terminal from project root. To get more
details on `phpunit` type `phpunit --help` on your terminal. You can test
both free and pro version at the same time. All test file names should start
with `test-`. Make sure you have all your test file in the `tests` folder.
You can include or exclude your files in `phpunit.xml.dist` file.
