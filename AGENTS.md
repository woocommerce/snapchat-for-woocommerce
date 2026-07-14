# AGENTS.md — Snapchat for WooCommerce

Guidelines for AI coding agents working in this repository (the `snapchat-for-woocommerce` plugin). This plugin runs on live merchant stores and its public surface is consumed by other extensions, themes, and custom site code, so backward compatibility is a release-blocking concern.

- **Namespace root:** `SnapchatForWooCommerce\` (`includes/`)
- **Text domain / slug:** `snapchat-for-woocommerce`

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. An internal-looking name or location is no guarantee a symbol is safe to change: other extensions, themes, and custom site code implement and consume these contracts in practice. When in doubt, assume it is exposed and state the BC impact.

For this plugin, externally exposed includes:

- **Custom hooks** — the actions and filters this plugin fires (the `snapchat_for_woocommerce_` prefix, e.g. `snapchat_for_woocommerce_filter_tracking_data`, `snapchat_for_woocommerce_send_conversion_event`). Renaming a hook, changing its arguments, or dropping it breaks whatever is hooked in.
- **REST API** — the `wc/sfw` routes, their request/response shapes, and their auth expectations.
- **Public PHP** — any `public` class, method, or function another plugin or theme can autoload and call, plus the `ServiceKey` constants other code resolves against, and the `Export/Contract/*Interface` contracts.
- **Front-end globals** — the `snapchatAds` JS globals and the pixel event contract that page scripts may read.

Rules:

- **Never add or remove a required method on an interface that external code can implement** — existing implementers fatal on load. Prefer adding the method to the concrete class, introducing a new interface, or supplying a default implementation in an abstract base class. If an interface change is unavoidable, flag it explicitly.
- **Deprecate, don't rename.** Never rename or remove an existing public symbol in place: mark it `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window.
- **Don't implement or type-hint WooCommerce core `Internal\` classes or interfaces** — core treats them as changeable in any release. If unavoidable, guard the dependency with `interface_exists()` / `method_exists()` checks so a core change doesn't fatal this plugin.

> Why: WooCommerce 10.9.0 was reverted on WP Cloud after woocommerce/woocommerce#64394 added a required method to core's internal `FeedInterface`, fataling older WooCommerce Stripe Gateway versions that implemented it (fixed in woocommerce/woocommerce#65965). The same failure mode applies to any published WooCommerce extension.
