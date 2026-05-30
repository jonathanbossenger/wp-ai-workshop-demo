<?php
/**
 * Ability registration functions for WP AI Workshop Demo.
 *
 * This plugin registers three abilities that work together to turn a photo into
 * a draft WordPress post:
 *
 *   1. describe-image                 — Vision: turn an image URL into a text description.
 *   2. generate-post-from-description — Text:   turn a description into a post title + body.
 *   3. create-post-from-photo         — Orchestrator: composes the two abilities above and
 *                                       then creates a draft post using the photo as the
 *                                       featured image.
 *
 * Each ability's execute_callback lives in a dedicated file so the "Core AI"
 * pieces can be extracted into self-contained workshop steps:
 *   - describe-image                 -> includes/vision.php
 *   - generate-post-from-description -> includes/content.php
 *   - create-post-from-photo         -> includes/post.php
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom ability category for the WP AI Workshop Demo plugin.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_ability_categories() {
	// TODO: Register the 'wp-ai-workshop-demo' ability category.
}

/**
 * Register the "describe an image" ability.
 *
 * Uses the AI Client's multimodal (vision) support to turn an image URL into a
 * detailed text description.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_describe_image_ability() {
	// TODO: Register the 'wp-ai-workshop-demo/describe-image' ability.
}

/**
 * Register the "generate post copy from a description" ability.
 *
 * Uses the AI Client's text generation to turn a description (plus optional
 * tone/angle guidance) into a post title and Block Editor content.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_generate_post_from_description_ability() {
	// TODO: Register the 'wp-ai-workshop-demo/generate-post-from-description' ability.
}

/**
 * Register the "create a post from a photo" orchestrator ability.
 *
 * This ability composes the describe-image and generate-post-from-description
 * abilities, then creates a draft post using the photo as the featured image.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_create_post_from_photo_ability() {
	// TODO: Register the 'wp-ai-workshop-demo/create-post-from-photo' ability.
}
