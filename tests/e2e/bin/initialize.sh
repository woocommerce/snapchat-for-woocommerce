#!/bin/bash

echo "Initializing Snapchat for WooCommerce"

# Enable pretty permalinks.
wp-env run tests-wordpress chmod -c ugo+w /var/www/html
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

# Log plugins
wp-env run tests-cli wp plugin list

# Use storefront theme.
wp-env run tests-cli wp theme activate storefront
wp-env run tests-cli wp option update storefront_nux_dismissed 1

# Activate and setup WooCommerce.
wp-env run tests-cli wp wc tool run install_pages --user=1

wp-env run tests-cli wp option update woocommerce_currency "USD"
wp-env run tests-cli wp option update woocommerce_default_country "US:CA"
wp-env run tests-cli wp option set woocommerce_allow_tracking "no"
wp-env run tests-cli wp option set woocommerce_coming_soon "no"
wp-env run tests-cli wp wc payment_gateway update cod --enabled=1 --user=1

wp-env run tests-cli wp user create customer customer@bulkstockmgmttestsuite.com --user_pass=password --role=customer

wp-env run tests-cli wp wc product create --user=1 \
  --name="Product One" \
  --slug="product-one" \
  --type="simple" \
  --regular_price="10"

wp-env run tests-cli wp wc product create --user=1 \
  --name="Product Two" \
  --slug="product-two" \
  --type="simple" \
  --regular_price="15"

wp-env run tests-cli wp wc product create --user=1 \
  --name="Product Three" \
  --slug="product-three" \
  --type="simple" \
  --regular_price="10"

wp-env run tests-cli wp wc product create --user=1 \
  --name="Product Four" \
  --slug="product-four" \
  --type="simple" \
  --regular_price="10"

wp-env run tests-cli wp wc product create --user=1 \
  --name="Product Five" \
  --slug="product-five" \
  --type="simple" \
  --regular_price="10"

PRODUCT_ID=$(wp-env run tests-cli wp wc product create --user=1 \
  --type=variable \
  --name="Variable Product One" \
  --slug="variable-product-one" \
  --attributes='[
    {
      "name": "Color",
      "slug": "pa_color",
      "visible": true,
      "variation": true,
      "options": ["Red", "Blue", "Green"]
    }
  ]' \
  --porcelain)

wp-env run tests-cli wp wc product_variation create $PRODUCT_ID --user=1 \
  --regular_price=12 \
  --attributes='[
    {
      "name": "Color",
      "option": "Red"
    }
  ]' \
  --porcelain

wp-env run tests-cli wp wc product_variation create $PRODUCT_ID --user=1 \
  --regular_price=13 \
  --attributes='[
    {
      "name": "Color",
      "option": "Blue"
    }
  ]' \
  --porcelain

wp-env run tests-cli wp wc product_variation create $PRODUCT_ID --user=1 \
  --regular_price=13 \
  --attributes='[
    {
      "name": "Color",
      "option": "Green"
    }
  ]' \
  --porcelain
