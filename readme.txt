=== Snapchat for WooCommerce ===
Contributors: automattic, woocommerce
Tags: woocommerce, woo, snapchat, product feed, ads
Tested up to: 7.1
Stable tag: 1.0.4
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

= 1.0.4 - 2026-08-19 =
* Fix – Resolved potential transaction ID collisions in purchase event deduplication by using order ID as the event_id instead.
* Fix - Corrected invalid nested HTML in Conversions API settings.
* Dev - Update WPCS to 3.4.1 to fix CVE-2026-45293, an arbitrary code execution vulnerability in WordPress Coding Standards.
* Tweak - Bump WordPress "Tested up to" version to 7.1
* Tweak - Bump WordPress "Requires at least" version to 6.9

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

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/snapchat-for-woocommerce/trunk/changelog.txt).
