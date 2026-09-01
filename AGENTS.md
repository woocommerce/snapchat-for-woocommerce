# AGENTS.md — Snapchat for WooCommerce

Guidelines for AI coding agents working in this repository (the `snapchat-for-woocommerce` plugin). This plugin runs on live merchant stores and its public surface is consumed by other extensions, themes, and custom site code, so backward compatibility is a release-blocking concern.

-   **Namespace root:** `SnapchatForWooCommerce\` (`includes/`)
-   **Text domain / slug:** `snapchat-for-woocommerce`

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. An internal-looking name or location is not by itself a guarantee that a symbol is safe to change: other extensions, themes, and custom site code implement and consume some of these contracts in practice. See the exposed-surface list for what counts and the **Scope** note for what does not; when a symbol is genuinely reachable and useful to outside code, err toward treating it as exposed.

**Externally exposed surface** — treat changes here as high-risk:

-   **Custom hooks** — the actions and filters this plugin fires (the `snapchat_for_woocommerce_` prefix, e.g. `snapchat_for_woocommerce_filter_tracking_data`, `snapchat_for_woocommerce_send_conversion_event`). These are documented integration points for merchant site code and other extensions. Renaming a hook, changing or reordering its arguments, or dropping it breaks whatever is hooked in; to retire one, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window.
-   **REST API** — the `wc/sfw` routes, their request/response shapes, and their auth expectations.
-   **Public PHP** — any `public` class, method, or function another plugin or theme actually autoloads and calls, plus the `Export/Contract/*Interface` contracts and any symbol explicitly documented as extensible.
-   **Front-end globals** — the `snapchatAds` JS globals and the pixel event contract that page scripts may read.

**Scope — what is _not_ a third-party contract:** the static `ServiceContainer` and its `ServiceKey` constants are internal dependency-injection wiring, not an API other extensions build on. An ordinary signature change to a service that exists only so the container can construct and connect objects does **not** require a BC statement. When you are unsure whether a symbol is a contract, check the exposed surface above rather than assuming every `public` method is one.

Rules:

-   **Never add or remove a required method on an interface that external code can implement** — existing implementers fatal on load. Prefer adding the method to the concrete class, introducing a new interface, or supplying a default implementation in an abstract base class. If an interface change is unavoidable, flag it explicitly.
-   **Deprecate, don't rename.** Never rename or remove an existing public symbol in place: mark it `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window.
-   **Never trust data that flows through hooks.** Keep hook callback parameters untyped and validate or coerce the value before passing it to strictly typed code, since any callback can receive a value another one produced. And when firing a filter, validate the final return value before using it, since any callback in the chain can return the wrong thing.
-   **Don't implement or type-hint WooCommerce core `Internal\` classes or interfaces** — core treats them as changeable in any release. If unavoidable, guard the dependency with `class_exists()` / `interface_exists()` / `method_exists()` checks so a core change doesn't cause a fatal error in this plugin.

> Why: WooCommerce 10.9.0 was reverted on WP Cloud after woocommerce/woocommerce#64394 added a required method to core's internal `FeedInterface`, causing fatal errors in older WooCommerce Stripe Gateway versions that implemented it (fixed in woocommerce/woocommerce#65965). The same failure mode applies to any published WooCommerce extension.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. A change to any of the following is equally high-risk and needs the same backward-compatibility impact statement in the PR.

-   **Overridable classes, including which internal methods get called.** Site code and extensions subclass exposed classes and override individual methods. Adding a fast path or skip that avoids calling an overridable method silently disables those overrides even though no signature changed: the subclass's code simply stops running. When optimizing such a class, ensure overridable methods are still invoked on every code path, or treat the change as breaking.
-   **Script and style handles.** Registered handles (the `snapchat_` asset handle prefix from `Config::ASSET_HANDLE_PREFIX`) are public contracts: third-party code enqueues them and lists them as dependencies, including handles only ever registered incidentally. Renaming a handle breaks those consumers. To rename with a compatibility window, register the legacy handle as an alias that depends on the new one (the same pattern WordPress core uses for `jquery` → `jquery-core`); do not register the same file under both handles, or pages with mixed consumers will load it twice.
-   **Global state.** Code runs in admin, REST, CLI, cron, webhook, and front-end contexts, and not all set the globals a front-end request does (`$post`, `$wp_query`, an initialized session or cart). A new read of a global — or of `WC()->…` state — in a path reachable outside a standard request fatals or silently misbehaves where it isn't set. Guard the exact dependency (`function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle) and verify `WC()` and the component are initialized before dereferencing.
-   **Multisite.** Site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, capabilities, and upload paths all differ under multisite. A change that reads or writes site state must state whether it behaves correctly under multisite, or say it wasn't tested there.
-   **Install layout.** WordPress can run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind `home_url()` vs `site_url()`).

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, hook, script/style handle, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers — you cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone may consume it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it for review.
