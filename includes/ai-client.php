<?php
/**
 * AI Client functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize any plugin functionality
 *
 * @return void
 */
function wp_ai_workshop_demo_init() {
    if ( class_exists( 'WordPress\AI_Client\AI_Client' ) ) {
        \WordPress\AI_Client\AI_Client::init();
    }
}

// TODO: Add the wp_ai_workshop_demo_set_request_timeout() function to increase the AI Client request timeout.
