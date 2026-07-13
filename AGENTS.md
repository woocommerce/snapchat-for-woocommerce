# AGENTS.md — Snapchat for WooCommerce

Guidelines for AI coding agents working in this repository (the `snapchat-for-woocommerce` plugin). This plugin runs on live merchant stores and its public surface is consumed by other extensions, themes, and custom site code, so backward compatibility is a release-blocking concern.

- **Namespace root:** `SnapchatForWooCommerce\` (`includes/`)
- **Text domain / slug:** `snapchat-for-woocommerce`

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. A `private`-looking location or an "internal" name is not a guarantee that a symbol is safe to change: third-party code — other extensions, themes, or custom site code — implements and consumes some of these contracts in practice.

Treat a symbol as **externally exposed** when it is implemented or consumed outside this plugin, even if it looks internal. For this plugin that includes:

- **Custom hooks** — the actions and filters this plugin fires or registers (the `snapchat_for_woocommerce_` prefix, e.g. `snapchat_for_woocommerce_filter_tracking_data`, `snapchat_for_woocommerce_send_conversion_event`). Renaming a hook, changing its arguments, or dropping it breaks whatever is hooked into it.
- **REST API** — the `wc/sfw` routes, their request/response shapes, and their auth expectations.
- **Public PHP** — any `public` class, method, or function another plugin or theme can autoload and call, plus the `ServiceKey` constants other code resolves against, and the `Export/Contract/*Interface` contracts.
- **Front-end globals** — the `snapchatAds` JS globals and the pixel event contract that page scripts may read.

When in doubt, assume it is exposed and state the BC impact.

**Adding a method to an interface that external code can implement must be flagged explicitly.** It is a backward-incompatible change: existing implementers fatal on load because they no longer satisfy the contract. **Removing a required method from an interface is likewise breaking.** Prefer a non-breaking alternative — add the method to the concrete class rather than the interface, introduce a separate new interface, or supply a default implementation via an abstract base class.

**Deprecate, don't rename.** For existing public symbols (classes, interfaces, methods, constants, hooks), never rename or remove them in place. Mark the old symbol `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers have time to migrate.

> Why this matters: a signature change to a shared contract can take down live stores. WooCommerce 10.9.0 was reverted on WP Cloud after a PR added a required `get_entry_count(): int` method to `FeedInterface`, fataling older WooCommerce Stripe Gateway versions that implemented it. The same failure mode applies to any published WooCommerce extension.
