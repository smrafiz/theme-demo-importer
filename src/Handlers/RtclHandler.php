<?php
/**
 * Demo Importer RTCL Handler
 *
 * This file contains RTCL-specific functionality.
 *
 * @package RT\CLClassified\DemoImporter
 */

namespace RT\CLClassified\DemoImporter\Handlers;

use Rtcl\Models\Form\Form;
use RtclStore\Controllers\Hooks\Init;
use RT\CLClassified\DemoImporter\Utils;
use SigmaDevs\EasyDemoImporter\Common\Functions\Helpers;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Demo Importer RTCL Handler Class.
 */
class RtclHandler {
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
	 * Prepare RTCL environment for import.
	 *
	 * @return void
	 */
	public function prepare_environment(): void {
		$this->update_rtcl_options();
		$this->remove_default_pages();
	}

	/**
	 * Rebuild RTCL environment after import.
	 *
	 * @return void
	 */
	public function rebuild_environment(): void {
		$this->update_rtcl_options();
		$this->clear_forms();
		$this->import_forms();
		$this->update_pages();
		$this->set_listing_types();
	}

	/**
	 * Remove default pages before import.
	 *
	 * @return void
	 */
	private function remove_default_pages(): void {
		if ( empty( $this->config['rtcl_pages_to_remove'] ) ) {
			return;
		}

		foreach ( $this->config['rtcl_pages_to_remove'] as $page_title ) {
			$page = Helpers::getPageByTitle( $page_title );

			if ( $page ) {
				wp_delete_post( $page->ID, true );
			}
		}
	}

	/**
	 * Clear RTCL forms table.
	 *
	 * @return void
	 */
	private function clear_forms(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'rtcl_forms';

		$wpdb->query( "TRUNCATE TABLE $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Update RTCL membership settings.
	 *
	 * @return void
	 */
	private function update_rtcl_options(): void {
		$file_path = $this->utils->get_demo_content_path() . $this->config['rtcl_custom_files']['rtcl_options'];
		$data      = $this->utils->load_json_file( $file_path );

		if ( is_array( $data ) && ! empty( $data['settings'] ) ) {
			$admin_email = get_option( 'admin_email' );

			foreach ( $data['settings'] as $setting ) {
				$key   = $setting['key'];
				$value = $setting['value'];

				// Handle nested array for rtcl_email_settings.
				if ( 'rtcl_email_settings' === $key && is_array( $value ) ) {
					foreach ( $value as $sub_key => $sub_value ) {
						if ( in_array( $sub_key, [ 'from_email', 'admin_notice_emails' ], true ) ) {
							$value[ $sub_key ] = is_array( $sub_value ) ? [ $admin_email ] : $admin_email;
						}
					}
				}

				update_option( $key, $value );
			}
		}

		update_option( 'rtcl_importing_demo', 'yes' );
	}

	/**
	 * Import RTCL forms from JSON file.
	 *
	 * @return void
	 */
	private function import_forms(): void {
		if ( ! class_exists( '\Rtcl\Models\Form\Form' ) ) {
			return;
		}

		$file_path = $this->utils->get_demo_content_path() . $this->config['rtcl_custom_files']['rtcl_forms'];
		$forms     = $this->utils->load_json_file( $file_path );

		if ( $forms && is_array( $forms ) ) {
			foreach ( $forms as $form_item ) {
				Form::query()->insert( $form_item );
			}
		}
	}

	/**
	 * Update RTCL page options.
	 *
	 * @return void
	 */
	private function update_pages(): void {
		$pages = [];

		foreach ( $this->config['rtcl_pages'] as $key => $title ) {
			$page = Helpers::getPageByTitle( $title );

			if ( $page ) {
				$pages[ $key ] = $page->ID;
			}
		}

		if ( empty( $pages ) ) {
			return;
		}

		$settings         = array_merge( [ 'permalink' => 'listing' ], $pages );
		$defaults         = get_option( 'rtcl_advanced_settings', [] );
		$updated_settings = wp_parse_args( $settings, $defaults );

		update_option( 'rtcl_advanced_settings', $updated_settings );
	}

	/**
	 * Set RTCL listing types.
	 *
	 * @return void
	 */
	private function set_listing_types(): void {
		if ( empty( $this->config['rtcl_listing_types'] ) ) {
			return;
		}

		update_option( 'rtcl_listing_types', $this->config['rtcl_listing_types'] );
	}
}
