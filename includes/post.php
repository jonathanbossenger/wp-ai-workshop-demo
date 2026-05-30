<?php
/**
 * Post orchestration and creation functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrator: create a draft post from a photo.
 *
 * This is the execute callback for the `create-post-from-photo` ability. It
 * demonstrates composing other abilities: it executes the `describe-image` and
 * `generate-post-from-description` abilities in turn, then creates a draft post
 * and uses the photo as the featured image.
 *
 * @param array $arguments {
 *     Arguments validated against the ability input schema.
 *
 *     @type string $image_url The URL of the image.
 *     @type string $prompt    Optional tone/angle guidance.
 * }
 * @return array Result with a `message` and, on success, a `post_id`.
 */
function wp_ai_workshop_demo_create_post_from_photo( $arguments ) {
	// TODO: Compose the describe-image and generate-post-from-description abilities.
}
