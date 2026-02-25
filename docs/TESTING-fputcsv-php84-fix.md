# Testing the fputcsv() PHP 8.4 deprecation fix

Step-by-step instructions to verify the CSV export writer change (escape parameter) in Snapchat for WooCommerce.

---

## 1. Run PHPCS (no test env required)

From the plugin root:

```bash
cd /Users/jamesmorrison/Sites/woo/public/wp-content/plugins/snapchat-for-woocommerce

./vendor/bin/phpcs includes/Admin/Export/Writer/CsvExportWriter.php tests/Unit/Admin/Export/Writer/CsvExportWriterTest.php
```

**Expected:** Exit code 0. Any WooCommerce sniff deprecation warning is unrelated to this change.

---

## 2. Run unit tests (CsvExportWriter)

Unit tests need the WordPress test library and WooCommerce. One-time setup then run.

### 2a. One-time: install WordPress test environment

From the plugin root, run the install script with your local MySQL credentials:

```bash
cd /Users/jamesmorrison/Sites/woo/public/wp-content/plugins/snapchat-for-woocommerce

./bin/install-wp-tests.sh <db_name> <db_user> <db_password> [db_host] [wp_version] [wc_version]
```

Examples:

- **Minimal:** `./bin/install-wp-tests.sh snapchat_test root mypassword`
- **With host:** `./bin/install-wp-tests.sh snapchat_test root mypassword 127.0.0.1`
- **Specific versions:** `./bin/install-wp-tests.sh snapchat_test root mypassword localhost 6.4 8.5`

Use a **dedicated test database** (e.g. `snapchat_test`); the script can create/drop it.

This will:

- Download WordPress and the test suite into your system temp directory (e.g. `/var/folders/.../wordpress` and `.../wordpress-tests-lib`).
- Install WooCommerce into that WordPress’ `wp-content/plugins/woocommerce`.
- Configure the test DB in `wordpress-tests-lib/wp-tests-config.php`.

### 2b. Run the CsvExportWriter tests

```bash
cd /Users/jamesmorrison/Sites/woo/public/wp-content/plugins/snapchat-for-woocommerce

./vendor/bin/phpunit tests/Unit/Admin/Export/Writer/CsvExportWriterTest.php
```

**Expected:** All tests pass (e.g. 3 tests, 3 assertions). No deprecation notices.

To run with PHP 8.4 if you have it:

```bash
php8.4 /path/to/vendor/bin/phpunit tests/Unit/Admin/Export/Writer/CsvExportWriterTest.php
```

(Adjust `php8.4` to your PHP 8.4 binary if different.)

---

## 3. WP Admin (manual test on PHP 8.4)

Use a site with WooCommerce and Snapchat for WooCommerce on **PHP 8.4**. Then:

1. **Enable debug logging**  
   In `wp-config.php` add or set:
   - `define( 'WP_DEBUG', true );`
   - `define( 'WP_DEBUG_LOG', true );`
   - `define( 'WP_DEBUG_DISPLAY', false );`

2. **Open the site and trigger the product catalog export**
   - Log in to **WP Admin**.
   - Go to **WooCommerce → Marketing → Snapchat**.
   - Open the **Product catalog** (or **Settings**) section.
   - Click **Generate CSV** or **Regenerate CSV**. That starts the export (it runs in the background via Action Scheduler; wait a few seconds for it to finish).
   - **Alternative (WP-CLI):** From the site root, run:
     ```bash
     wp action-scheduler run --hook=snapchat_for_woocommerce_export_product_catalog
     ```
     Run it repeatedly until no more actions are run (export is batched). Or run the recurring hook once to start a full export:
     ```bash
     wp eval "do_action( 'snapchat_for_woocommerce_recurring_catalog_export' );"
     ```

3. **Verify no error in logs**
   - Open `wp-content/debug.log`.
   - Confirm there is **no** line containing:
     - `PHP Deprecated: fputcsv(): the $escape parameter must be provided`
   - If that line appears, the fix is not in place or the export did not use the updated code.

---

## Summary

| Step | Command / action | Requirement |
|------|------------------|-------------|
| 1    | `./vendor/bin/phpcs` on the two files | Composer deps only |
| 2a   | `./bin/install-wp-tests.sh ...`       | One-time; MySQL, network |
| 2b   | `./vendor/bin/phpunit tests/Unit/Admin/Export/Writer/CsvExportWriterTest.php` | After 2a |
| 3    | WP Admin: enable debug log → trigger export → check `debug.log` for no fputcsv deprecation | Site on PHP 8.4 |
