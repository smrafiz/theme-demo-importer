<?php
/**
 * Demo Importer WooCommerce Handler
 *
 * This file contains WooCommerce-specific functionality.
 *
 * @package RT\Blusho\DemoImporter
 */

namespace RT\Blusho\DemoImporter\Handlers;

use RT\Blusho\DemoImporter\Utils;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Demo Importer WooCommerce Handler Class.
 */
class WooHandler {
	/**
	 * Theme configuration.
	 *
	 * @var array
	 */
	private $config = [];

	/**
	 * Utilities instance.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Class Constructor.
	 *
	 * @param array $config Theme configuration.
	 * @param Utils $utils  Utils instance.
	 */
	public function __construct( array $config, Utils $utils ) {
		$this->config = $config;
		$this->utils  = $utils;
	}

	/**
	 * Rebuild WooCommerce environment after import.
	 *
	 * @return void
	 */
	public function rebuild_environment(): void {
		$this->change_attribute_type();
		$this->change_site_visibility();
	}

	/**
	 * Remove default pages before import.
	 *
	 * @return void
	 */
	private function change_attribute_type(): void {
		if ( empty( $this->config['attribute_type'] ) ) {
			return;
		}

		global $wpdb;

		$product_attributes = $this->config['attribute_type'];

		foreach ( $product_attributes as $attribute_name => $attribute_data ) {
			$new_type   = $attribute_data['type'];
			$table_name = esc_sql( $wpdb->prefix . 'woocommerce_attribute_taxonomies' );
			$data       = [ 'attribute_type' => $new_type ];
			$where      = [ 'attribute_name' => $attribute_name ];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_name, $data, $where, [ '%s' ], [ '%s' ] );
			delete_transient( 'wc_attribute_taxonomies' );
		}
	}

	/**
	 * Change site visibility.
	 *
	 * @return void
	 */
	private function change_site_visibility(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
	}
}
