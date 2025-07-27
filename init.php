<?php
/**
 * Demo Importer Initialization
 *
 * This file initializes the easy demo importer.
 *
 * @package RT\CLClassified\DemoImporter
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

if ( ! defined( 'CL_CLASSIFIED_DEMO_IMPORTER_PATH' ) ) {
	define( 'CL_CLASSIFIED_DEMO_IMPORTER_PATH', __DIR__ );
}

if ( ! defined( 'CL_CLASSIFIED_DEMO_IMPORTER_URL' ) ) {
	define( 'CL_CLASSIFIED_DEMO_IMPORTER_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * PSR-4 Autoloader for Demo Importer
 */
spl_autoload_register(
	function ( $class ) {
		$namespaces = [
			'RT\\CLClassified\\DemoImporter\\' => __DIR__ . '/src/',
		];

		foreach ( $namespaces as $namespace => $base_dir ) {
			$len = strlen( $namespace );

			if ( strncmp( $namespace, $class, $len ) !== 0 ) {
				continue;
			}

			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
);

// Load configuration.
$config_file = __DIR__ . '/config.php';

if ( ! file_exists( $config_file ) ) {
	wp_die( 'Demo Importer Configuration file not found: ' . esc_html( $config_file ) );
}

$demo_config = require $config_file;

if ( ! is_array( $demo_config ) ) {
	wp_die( 'Invalid demo configuration. Configuration must return an array.' );
}

// Get current theme information.
$current_theme = wp_get_theme();
$theme_slug    = $current_theme->get_stylesheet();
$theme_name    = $current_theme->get( 'Name' );

// Inject current theme information.
$demo_config = array_merge(
	[
		'theme_name'       => $theme_name,
		'theme_slug'       => $theme_slug,
		'demo_content_dir' => CL_CLASSIFIED_DEMO_IMPORTER_PATH,
		'demo_content_url' => CL_CLASSIFIED_DEMO_IMPORTER_URL,
		'commenter_email'  => get_option( 'admin_email' ),
		/**
		'user_importer'    => 'UserImporter.php',
		'user_class'       => 'RT\\CLClassified\\DemoImporter\\UserImporter',
		*/
	],
	$demo_config
);

// Initialize the demo importer.
try {
	new RT\CLClassified\DemoImporter\Core( $demo_config );
} catch ( Exception $e ) {
	wp_die( 'Failed to initialize Demo Importer: ' . esc_html( $e->getMessage() ) );
}
