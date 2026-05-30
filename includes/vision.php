<?php
/**
 * Vision (image understanding) functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describe an image using the AI Client's multimodal (vision) support.
 *
 * This is the execute callback for the `describe-image` ability. It sends a text
 * instruction together with the image to a vision-capable model and returns the
 * model's text description.
 *
 * @param array $arguments {
 *     Arguments validated against the ability input schema.
 *
 *     @type string $image_url The URL of the image to describe.
 * }
 * @return array|WP_Error Array with a `description` key, or WP_Error on failure.
 */
function wp_ai_workshop_demo_describe_image( $arguments ) {
	// TODO: Implement AI image description (vision).
}
