<?php
/**
 * Demo Importer Utilities
 *
 * This file contains utility functions.
 *
 * @package RT\CLClassified\DemoImporter
 */

namespace RT\CLClassified\DemoImporter;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Demo Importer Utilities Class.
 */
class Utils {
	/**
	 * Theme configuration.
	 *
	 * @var array
	 */
	private $config = [];

	/**
	 * Demo content directory path.
	 *
	 * @var string
	 */
	private $demo_content_dir;

	/**
	 * Demo base URL.
	 *
	 * @var string
	 */
	private $demo_base_url;

	/**
	 * Class Constructor.
	 *
	 * @param array $config Theme configuration.
	 */
	public function __construct( array $config ) {
		$this->config           = $config;
		$this->demo_content_dir = trailingslashit( $config['demo_content_dir'] );
		$this->demo_base_url    = trailingslashit( $config['demo_content_url'] );
	}

	/**
	 * Get the demo content directory path.
	 *
	 * @return string
	 */
	public function get_demo_content_path(): string {
		return $this->demo_content_dir;
	}

	/**
	 * Get demo asset URL.
	 *
	 * @param string $file File name.
	 * @return string
	 */
	public function get_demo_asset_url( string $file ): string {
		return esc_url( $this->demo_base_url . $file );
	}

	/**
	 * Check if the file exists and is readable.
	 *
	 * @param string $file_path the file path to check.
	 * @return bool
	 */
	public function is_valid_file( string $file_path ): bool {
		return file_exists( $file_path ) && is_readable( $file_path );
	}

	/**
	 * Load and decode JSON file.
	 *
	 * @param string $file_path File-path to load.
	 * @return mixed|null
	 */
	public function load_json_file( string $file_path ) {
		if ( ! $this->is_valid_file( $file_path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json_data = file_get_contents( $file_path );

		if ( false === $json_data ) {
			return null;
		}

		$decoded = json_decode( $json_data, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->log_error( 'JSON Decode Error: ' . json_last_error_msg() . ' in file: ' . $file_path );
			return null;
		}

		return $decoded;
	}

	/**
	 * Get configuration value.
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_config( string $key, $default = null ) {
		return $this->config[ $key ] ?? $default;
	}
}
