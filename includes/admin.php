<?php
/**
 * Admin UI functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the WP AI Workshop Demo Tools submenu page.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_tools_submenu() {
	add_submenu_page(
		'tools.php',
		'WP AI Workshop Demo',
		'WP AI Workshop Demo',
		'manage_options',
		'wp-ai-workshop-demo-tools',
		'wp_ai_workshop_demo_tools_page_callback'
	);
}

/**
 * Render the WP AI client Demo Tools page.
 *
 * @return void
 */
function wp_ai_workshop_demo_tools_page_callback() {
	printf(
		'<div class="wrap" id="wp-ai-workshop-demo-app">%s</div>',
		esc_html__( 'Loading…', 'wp-ai-workshop-demo' )
	);
}

/**
 * Enqueue Editor assets.
 */
function wp_ai_workshop_demo_admin_enqueue_scripts() {
	$screen = get_current_screen();

	if ( 'tools_page_wp-ai-workshop-demo-tools' !== $screen->id ) {
		return;
	}

	$asset_file = include plugin_dir_path( __DIR__ ) . 'build/index.asset.php';

	// TODO: Enqueue the wp-ai-client and abilities scripts.

	// TODO: Update the plugin's script to be enqueued as a module, and add the abilities script module as a dependency
    wp_enqueue_script(
        'wp-ai-workshop-demo-script',
        plugins_url( 'build/index.js', __DIR__ ),
        $asset_file['dependencies'],
        $asset_file['version'],
        true
    );

	wp_enqueue_style(
		'wp-ai-workshop-demo-style',
		plugins_url( 'build/style-index.css', __DIR__ ),
		array(),
		$asset_file['version'],
	);
}
