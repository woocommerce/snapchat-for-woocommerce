# Snapchat for WooCommerce

Seamlessly integrates your WooCommerce store with Snapchat's advertising platform, enabling you to reach potential customers through visual ads.

-   [WordPress.org plugin page](https://wordpress.org/plugins/snapchat-for-woocommerce/)

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests.

The best place to get support is the [WordPress.org Snapchat for WooCommerce forum](https://wordpress.org/support/plugin/snapchat-for-woocommerce/).

## Requirements

-   WordPress 6.8+
-   WooCommerce 10.2+
-   PHP 7.4+

## Plugin Setup and Configuration

**Install:** Install from [WordPress.org](https://wordpress.org/plugins/snapchat-for-woocommerce/) or via **Plugins → Add New** and search for "Snapchat for WooCommerce"; then activate.

**Configuration:** After activation, go to **WooCommerce → Marketing** and use the Snapchat channel, or open the setup flow at **WooCommerce → Marketing → Snapchat**.

## Development

### Prerequisites

-   [NVM](https://github.com/nvm-sh/nvm) (recommended) or [NPM](https://www.npmjs.com/) — use `nvm use` to match the Node version in [.nvmrc](.nvmrc) (Node 20)
-   [Composer](https://getcomposer.org/)
-   [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (requires [Docker](https://www.docker.com/))

### Install

```bash
nvm use
npm install
composer install
```

### Build

-   `npm run build` — production build
-   `npm run start` — development build with watch

### Local environment

```bash
npm run env:start
```

With the environment running, open the Snapchat setup page at:

-   **Setup:** http://localhost:8888/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup
-   **Settings:** http://localhost:8888/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsettings

You can also go to **WooCommerce → Marketing** and open the Snapchat channel from there.

## Testing

### Linting

-   `npm run lint` — run PHP, JavaScript, and CSS linting
-   `npm run lint:php` — PHP only
-   `npm run lint:js` — JavaScript only
-   `npm run lint:css` — CSS only

### E2E tests

E2E tests use [Playwright](https://playwright.dev/) and require Docker. After starting the environment:

```bash
npm run env:start
npm run test:e2e
```

The `env:start` script runs [tests/e2e/bin/initialize.sh](tests/e2e/bin/initialize.sh) after the environment is up.

## Helper Scripts

From [package.json](package.json):

-   `npm run build` — production build
-   `npm run start` — development build with watch
-   `npm run lint` — full lint (PHP + JS + CSS)
-   `npm run lint:php` — PHP code standards
-   `npm run lint:js` — ESLint
-   `npm run lint:css` — stylelint
-   `npm run format` — format code
-   `npm run env:start` — start wp-env
-   `npm run env:stop` — stop wp-env
-   `npm run env:clean` — clean wp-env
-   `npm run env:destroy` — destroy wp-env
-   `npm run test:e2e` — run Playwright E2E tests

---

Built with [Create Woo Extension](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/create-woo-extension/README.md).
