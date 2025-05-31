<?php

namespace SnapchatForWoocommerce\Config;

class AdPartnerConfig implements AdPartnerConfigInterface {
	public function get_service_slug(): string {
		return 'snapchat-ads';
	}

	public function get_rest_namespace(): string {
		return 'snapchat-ads/v1';
	}

	public function get_return_url(): string {
		return admin_url( 'admin.php?page=wc-snapchat' );
	}
}
