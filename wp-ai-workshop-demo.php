<?php
/**
 * Plugin Name: WP AI Workshop Demo
 * Description: A demo plugin to showcase the integration of the WordPress AI Client.
 * Version: 1.0.0
 * Requires at least: 7.0
 * Author: Jonathan Bossenger
 * Plugin URI: https://github.com/jonathanbossenger/wp-ai-workshop-demo
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TODO: Include the WP AI Client Autoloader, so that we can use the AI Client.

// Include plugin files.
require_once __DIR__ . '/includes/ai-client.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/abilities.php';
require_once __DIR__ . '/includes/vision.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/post.php';

// Hook registrations.
add_action( 'init', 'wp_ai_workshop_demo_init' );
add_action( 'admin_menu', 'wp_ai_workshop_demo_register_tools_submenu' );
add_action( 'admin_enqueue_scripts', 'wp_ai_workshop_demo_admin_enqueue_scripts' );
// TODO: Register the ability category and the three Photo to Post ability hooks.

// Filters
add_filter( 'wp_ai_client_default_request_timeout', 'wp_ai_workshop_demo_set_request_timeout' );