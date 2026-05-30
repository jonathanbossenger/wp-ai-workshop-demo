<?php
/**
 * Content generation functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate a post title and body from a description using the AI Client.
 *
 * This is the execute callback for the `generate-post-from-description` ability.
 * The model is asked to return a single JSON object so we can extract both the
 * title and the Block Editor content from one text generation request.
 *
 * @param array $arguments {
 *     Arguments validated against the ability input schema.
 *
 *     @type string $description The source description.
 *     @type string $prompt      Optional tone/angle guidance.
 * }
 * @return array|WP_Error Array with `title` and `content`, or WP_Error on failure.
 */
function wp_ai_workshop_demo_generate_post_from_description( $arguments ) {
	// TODO: Implement AI post copy generation.
}
