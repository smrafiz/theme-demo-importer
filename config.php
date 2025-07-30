<?php
/**
 * Theme Demo Configuration File
 *
 * This file contains the configuration for the demo importer.
 *
 * @package @package Radiustheme\Blusho
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

return [
	// Basic theme information.
	'blog_slug'           => 'blogs',
	'demo_url'            => 'https://www.radiustheme.com/demo/wordpress/themes/blusho/',
	'menus'               => [ 'primary' => 'Main Menu' ],

	// File paths.
	'demo_content_zip'    => 'demo-files/demo-content.zip',

	// Demo variants.
	'demo_variants'       => [
		'home-01' => [
			'name'    => 'Home 1',
			'preview' => 'screenshots/home-1.jpg',
			'url'     => '',
		],
		'home-02' => [
			'name'    => 'Home 2',
			'preview' => 'screenshots/home-2.jpg',
			'url'     => 'home-02/',
		],
		'home-03' => [
			'name'    => 'Home 3',
			'preview' => 'screenshots/home-3.jpg',
			'url'     => 'home-03/',
		],
		'home-04' => [
			'name'    => 'Home 4',
			'preview' => 'screenshots/home-4.jpg',
			'url'     => 'home-04/',
		],
		'home-05' => [
			'name'    => 'Home 5',
			'preview' => 'screenshots/home-5.jpg',
			'url'     => 'home-05/',
		],
		'home-06' => [
			'name'    => 'Home 6',
			'preview' => 'screenshots/home-6.jpg',
			'url'     => 'home-06/',
		],
		'home-07' => [
			'name'    => 'Home 7',
			'preview' => 'screenshots/home-7.jpg',
			'url'     => 'home-07/',
		],
	],

	// Additional settings.
	'settings_json'       => [
		'_fluentform_global_form_settings',
		'bcn_options',
		'rtsb_extra_settings',
		'rtsb_settings',
		'rtsb_template_settings',
		'rtwpvg',
		'rtwpvs',
	],
	'fluent_forms_json'   => 'fluentform',

	// WordPress repository plugins.
	'wp_plugins'          => [
		'breadcrumb-navxt'               => 'Breadcrumb NavXT',
		'elementor'                      => 'Elementor Page Builder',
		'fluentform'                     => 'WP Fluent Forms',
		'shopbuilder'                    => 'ShopBuilder - Elementor WooCommerce Builder Addons',
		'woocommerce'                    => 'WooCommerce',
		'woo-product-variation-swatches' => 'Variation Swatches for WooCommerce',
		'woo-product-variation-gallery'  => 'Variation Images Gallery for WooCommerce',
	],

	// Bundled/Premium plugins.
	'bundled_plugins'     => [
		'rt-framework'                       => [
			'name' => 'RT Framework',
			'file' => 'plugin-bundle/rt-framework.zip',
		],
		'shopbuilder-pro'                    => [
			'name' => 'ShopBuilder Pro',
			'file' => 'plugin-bundle/shopbuilder-pro.zip',
		],
		'woo-product-variation-swatches-pro' => [
			'name' => 'WooCommerce Variation Swatches Pro',
			'file' => 'plugin-bundle/woo-product-variation-swatches-pro.zip',
		],
		'woo-product-variation-gallery-pro'  => [
			'name' => 'WooCommerce Variation Images Gallery Pro',
			'file' => 'plugin-bundle/woo-product-variation-gallery-pro.zip',
		],
	],

	// Enable/disable import features.
	'features'            => [
		'woo_support' => true,
	],

	// WooCommerce attribute type.
	'attribute_type'      => [
		'color' => [
			'type' => 'color',
		],
		'size'  => [
			'type' => 'button',
		],
		'brand' => [
			'type' => 'button',
		],
	],

	// Pre-import options.
	'pre_import_options'  => [
		'elementor_experiment-e_font_icon_svg' => 'inactive',
	],

	// Post-import options.
	'post_import_options' => [
		'elementor_unfiltered_files_upload' => true,
		'posts_per_page'                    => 8,
	],
];
