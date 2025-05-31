<?php

namespace SnapchatForWoocommerce\Config;

interface AdPartnerConfigInterface {
	public function get_service_slug(): string;
	public function get_rest_namespace(): string;
	public function get_return_url(): string;
}
