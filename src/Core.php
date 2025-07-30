<?php
/**
 * Demo Importer Core Class
 *
 * This file contains the core functionality of the demo importer.
 *
 * @package RT\Blusho\DemoImporter
 */

namespace RT\Blusho\DemoImporter;

use RT\Blusho\DemoImporter\Utils;
use RT\Blusho\DemoImporter\Handlers\WooHandler;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Demo Importer Core Class.
 */
class Core {
	/**
	 * Theme configuration.
	 *
	 * @var array
	 */
	private $config = [];

	/**
	 * Configuration cache.
	 *
	 * @var array
	 */
	private $config_cache = null;

	/**
	 * Utilities instance.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Class Constructor.
	 *
	 * @param array $config Theme configuration array.
	 */
	public function __construct( array $config = [] ) {
		$this->config = $config;
		$this->utils  = new Utils( $config );

		if ( $this->is_feature_enabled( 'woo_support' ) ) {
			$this->woo = new WooHandler( $config, $this->utils );
		}

		$this->setup_hooks();
	}

	/**
	 * Check if a feature is enabled.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	private function is_feature_enabled( string $feature ): bool {
		return isset( $this->config['features'][ $feature ] ) && $this->config['features'][ $feature ];
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		/**
		 * Filters.
		 */
		add_filter( 'sd/edi/importer/config', [ $this, 'import_config' ] );

		/**
		 * Actions.
		 */
		add_action( 'sd/edi/after_plugins_activation', [ $this, 'before_import_actions' ] );
		add_action( 'sd/edi/before_widgets_import', [ $this, 'remove_default_widgets' ] );
		add_action( 'sd/edi/after_import', [ $this, 'after_import_actions' ] );
	}

	/**
	 * Generate import configuration from theme config.
	 *
	 * @return array
	 */
	public function import_config(): array {
		if ( null !== $this->config_cache ) {
			return $this->config_cache;
		}

		$this->config_cache = [
			'themeName'             => $this->config['theme_name'],
			'themeSlug'             => $this->config['theme_slug'],
			'multipleZip'           => false,
			'demoZip'               => $this->utils->get_demo_asset_url( $this->config['demo_content_zip'] ),
			'blogSlug'              => $this->config['blog_slug'],
			'urlToReplace'          => $this->config['demo_url'],
			'replaceCommenterEmail' => $this->config['commenter_email'] ?? '',
			'elementor_data_fix'    => $this->is_feature_enabled( 'elementor_fixes' ) ? $this->config['elementor_fixes'] : [],
			'settingsJson'          => $this->config['settings_json'],
			'menus'                 => $this->config['menus'],
			'demoData'              => $this->build_demo_data(),
			'plugins'               => $this->build_plugins_config(),
		];

		if ( ! empty( $this->config['fluent_forms_json'] ) ) {
			$this->config_cache['fluentFormsJson'] = $this->config['fluent_forms_json'];
		}

		return $this->config_cache;
	}

	/**
	 * Build demo data configuration.
	 *
	 * @return array
	 */
	private function build_demo_data(): array {
		return array_map(
			function ( $variant ) {
				return [
					'name'         => $variant['name'],
					'previewImage' => $this->utils->get_demo_asset_url( $variant['preview'] ),
					'previewUrl'   => $this->config['demo_url'] . $variant['url'],
				];
			},
			$this->config['demo_variants']
		);
	}

	/**
	 * Build plugins configuration.
	 *
	 * @return array
	 */
	private function build_plugins_config(): array {
		$plugins = array_merge(
			$this->build_wp_plugins(),
			$this->build_bundled_plugins()
		);

		uasort(
			$plugins,
			function ( $a, $b ) {
				return strcasecmp( $a['name'] ?? '', $b['name'] ?? '' );
			}
		);

		return $plugins;
	}

	/**
	 * Build WordPress repository plugins configuration.
	 *
	 * @return array
	 */
	private function build_wp_plugins(): array {
		$plugins = [];

		foreach ( $this->config['wp_plugins'] as $slug => $plugin ) {
			$name     = is_array( $plugin ) ? $plugin['name'] : $plugin;
			$filePath = is_array( $plugin ) && ! empty( $plugin['file'] ) ? $plugin['file'] : $slug . '/' . $slug . '.php';

			$plugins[ $slug ] = [
				'name'     => $name,
				'source'   => 'wordpress',
				'filePath' => $filePath,
			];
		}

		return $plugins;
	}

	/**
	 * Build bundled plugins configuration.
	 *
	 * @return array
	 */
	private function build_bundled_plugins(): array {
		$plugins = [];

		foreach ( $this->config['bundled_plugins'] as $slug => $plugin ) {
			$plugins[ $slug ] = [
				'name'     => $plugin['name'],
				'source'   => 'bundled',
				'filePath' => $slug . '/' . $slug . '.php',
				'location' => get_parent_theme_file_uri( $plugin['file'] ),
			];
		}

		return $plugins;
	}

	/**
	 * Execute before import actions.
	 *
	 * @return void
	 */
	public function before_import_actions(): void {
		$this->set_import_options();
	}

	/**
	 * Remove default widgets.
	 *
	 * @return void
	 */
	public function remove_default_widgets(): void {
		delete_option( 'sidebars_widgets' );
	}

	/**
	 * Execute after import actions.
	 *
	 * @return void
	 */
	public function after_import_actions(): void {
		if ( $this->woo ) {
			$this->woo->rebuild_environment();
		}

		$this->cleanup_after_import();
	}

	/**
	 * Set import-specific options.
	 *
	 * @return void
	 */
	private function set_import_options(): void {
		foreach ( $this->config['pre_import_options'] as $option => $value ) {
			update_option( $option, $value );
		}
	}

	/**
	 * Cleanup after import completion.
	 *
	 * @return void
	 */
	private function cleanup_after_import(): void {
		foreach ( $this->config['post_import_options'] as $option => $value ) {
			update_option( $option, $value );
		}

		flush_rewrite_rules();
	}
}
