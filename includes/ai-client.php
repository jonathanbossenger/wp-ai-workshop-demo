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
 * Set a custom request timeout for the AI Client.
 *
 * @return int
 */
function wp_ai_workshop_demo_set_request_timeout() {
    return 120;
}
