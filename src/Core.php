<?php
/**
 * Demo Importer Core Class
 *
 * This file contains the core functionality of the demo importer.
 *
 * @package RT\CLClassified\DemoImporter
 */

namespace RT\CLClassified\DemoImporter;

use RT\CLClassified\DemoImporter\Utils;
use RT\CLClassified\DemoImporter\Handlers\RtclHandler;

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
	 * RTCL handler instance.
	 *
	 * @var RtclHandler|null
	 */
	private $rtcl = null;

	/**
	 * Class Constructor.
	 *
	 * @param array $config Theme configuration array.
	 */
	public function __construct( array $config = [] ) {
		$this->config = $config;
		$this->utils  = new Utils( $config );

		if ( $this->is_feature_enabled( 'rtcl_support' ) ) {
			$this->rtcl = new RtclHandler( $config, $this->utils );
		}

		$this->load_user_importer();
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
	 * Load the user importer file if it exists.
	 *
	 * @return void
	 */
	private function load_user_importer(): void {
		if ( ! $this->is_feature_enabled( 'user_import' ) ) {
			return;
		}

		$user_importer_path = $this->config['core_base_dir'] . $this->config['user_importer'];

		if ( file_exists( $user_importer_path ) ) {
			require_once $user_importer_path;
		}
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
		add_filter( 'sd/edi/activated_plugin_actions', [ $this, 'plugin_actions' ] );

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
	 * Run plugin activation actions after activation.
	 *
	 * @param array $actions Existing plugin activation actions.
	 *
	 * @return array
	 */
	public function plugin_actions( array $actions ): array {
		$actions['classified-listing/classified-listing.php'] = [
			'class'  => '\Rtcl\Helpers\Installer',
			'action' => 'install',
		];

		$actions['classified-listing-pro/classified-listing-pro.php'] = [
			'class'  => '\RtclPro\Helpers\Installer',
			'action' => 'install',
		];

		$actions['classified-listing-store/classified-listing-store.php'] = [
			'class'  => '\RtclStore\Helpers\Install',
			'action' => 'install',
		];

		return $actions;
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
		return array_merge(
			$this->build_wp_plugins(),
			$this->build_bundled_plugins()
		);
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
		if ( $this->rtcl ) {
			$this->rtcl->prepare_environment();
			flush_rewrite_rules();
		}

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
		if ( $this->is_feature_enabled( 'user_import' ) ) {
			$this->import_users();
		}

		if ( $this->rtcl ) {
			$this->rtcl->rebuild_environment();
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
	 * Import users and update post authors.
	 *
	 * @return void
	 */
	private function import_users(): void {
		global $wpdb;

		$current_user_id = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_author = %d",
				$current_user_id
			)
		);

		if ( class_exists( $this->config['user_class'] ) ) {
			$class_name = $this->config['user_class'];
			new $class_name();
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
