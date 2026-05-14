=== Snapchat for WooCommerce ===
Contributors: automattic, woocommerce
Tags: woocommerce, snapchat, product feed, ads
Tested up to: 7.0
Stable tag: 1.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrate your WooCommerce store with Snapchat Ads to track conversions and export products for advertising.

== Description ==

Snapchat for WooCommerce seamlessly integrates your WooCommerce store with Snapchat's powerful advertising platform, enabling you to reach millions of potential customers through engaging visual ads.

Connect your Snapchat Business account to automatically sync your product catalog, create targeted advertising campaigns, and track conversions directly from your WooCommerce dashboard. The plugin provides a streamlined setup process with step-by-step guidance to get your first campaign running quickly.

Key features include product catalog export for Snapchat's Dynamic Ads, Conversion tracking to measure campaign performance, and Pixel tracking to build custom audiences. The integration works through WooCommerce's Marketing menu, providing a familiar interface for managing your Snapchat advertising alongside other marketing channels.

Whether you're looking to increase brand awareness, drive sales, or re-engage existing customers, Snapchat for WooCommerce provides the tools you need to create effective advertising campaigns that convert visitors into customers.

== FAQ ==

= Does the plugin use any external services? =
Yes, it uses a [Jetpack](https://jetpack.com/) account to connect and communicate with the [Snapchat](https://www.snapchat.com) API.

== Changelog ==

= 1.0.3 - 2026-05-14 =
* Add - RTL Support.
* Add - Snapchat catalog persistence message.
* Dev - Bump WooCommerce "tested up to" version 10.8.
* Dev - Bump WooCommerce minimum supported version to 10.6.
* Dev - Bump WordPress "tested up to" version 7.0.
* Dev - Bump WordPress minimum supported version to 6.8.
* Fix - Ensure existing catalog ID is used on reconnect where possible.
* Fix - Load snapchat assets on any wp-admin route.
* Fix - Strip HTML from product description for the CSV export.
* Fix - Update integration value to include current plugin version.
* Update - Text of the "Disconnect Snapchat" modal.

= 1.0.2 - 2025-12-02 =
* Fix - Fatal error on plugin activation.

= 1.0.1 - 2025-12-02 =
* Add - Display an admin notice prompting users to uninstall the legacy Snapchat Pixel plugin if it is installed and active.
* Fix - Improve tracking by deduplicating events sent from Pixel and Conversion API.
* Tweak - WooCommerce 10.3 compatibility.
* Tweak - WordPress 6.9 compatibility.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/snapchat-for-woocommerce/trunk/changelog.txt).
