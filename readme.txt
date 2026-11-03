=== Snapchat for WooCommerce ===
Contributors: automattic, woocommerce
Tags: woocommerce, woo, snapchat, product feed, ads
Tested up to: 7.1
Stable tag: 1.0.8
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

= 1.0.8 - 2026-11-03 =
* Dev - Tested with PHP 8.5.
* Fix - Retry Purchase conversions that fail to send, and stop counting them as sent until Snapchat accepts them.
* Fix - Send the store currency with Pixel ADD_CART events.
* Tweak - Update the WordPress.com logo on the account connection card.

= 1.0.7 - 2026-10-08 =
* Tweak - Bump WooCommerce "tested up to" version to 11.2.

= 1.0.6 - 2026-09-29 =
* Add - Sandbox mode to preview settings without connecting to Snapchat.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/snapchat-for-woocommerce/trunk/changelog.txt).
