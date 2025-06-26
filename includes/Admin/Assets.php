<?php
namespace SnapchatForWooCommerce\Admin;
use SnapchatForWooCommerce\Utils\AssetLoader;

class Assets {
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		AssetLoader::enqueue_script( 'index', 'index' );
		AssetLoader::enqueue_style( 'index', 'index' );
	}
}
