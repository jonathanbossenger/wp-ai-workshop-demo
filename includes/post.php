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
	$image_url = $arguments['image_url'];
	$prompt    = isset( $arguments['prompt'] ) ? $arguments['prompt'] : '';

	// Step 1: Describe the image (ability composition).
	$describe_ability = wp_get_ability( 'wp-ai-workshop-demo/describe-image' );
	if ( ! $describe_ability instanceof WP_Ability ) {
		return array( 'message' => 'Post creation failed: the describe-image ability is not available.' );
	}
	$description_result = $describe_ability->execute( array( 'image_url' => $image_url ) );
	if ( is_wp_error( $description_result ) ) {
		return array( 'message' => 'Post creation failed while describing the image: ' . $description_result->get_error_message() );
	}

	// Step 2: Generate the post title and content (ability composition).
	$generate_ability = wp_get_ability( 'wp-ai-workshop-demo/generate-post-from-description' );
	if ( ! $generate_ability instanceof WP_Ability ) {
		return array( 'message' => 'Post creation failed: the generate-post-from-description ability is not available.' );
	}
	$copy_result = $generate_ability->execute(
		array(
			'description' => $description_result['description'],
			'prompt'      => $prompt,
		)
	);
	if ( is_wp_error( $copy_result ) ) {
		return array( 'message' => 'Post creation failed while writing the post: ' . $copy_result->get_error_message() );
	}

	// Step 3: Create the draft post and set the featured image.
	return wp_ai_workshop_demo_create_post(
		$copy_result['title'],
		$copy_result['content'],
		$image_url
	);
}

/**
 * Create a draft post and set its featured image from an image URL.
 *
 * @param string $title     The post title.
 * @param string $content   The post content (Block Editor markup).
 * @param string $image_url The URL of the image to sideload as the featured image.
 * @return array Result with a `message` and, on success, a `post_id`.
 */
function wp_ai_workshop_demo_create_post( $title, $content, $image_url ) {
	$post_id = wp_insert_post(
		array(
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_type'    => 'post',
		)
	);

	if ( is_wp_error( $post_id ) || 0 === $post_id ) {
		return array( 'message' => 'Post creation failed.' );
	}

	$attachment_id = wp_ai_workshop_demo_set_featured_image_from_url( $post_id, $image_url );
	if ( is_wp_error( $attachment_id ) ) {
		return array(
			'message' => 'Post created, but the featured image could not be added: ' . $attachment_id->get_error_message(),
			'post_id' => $post_id,
		);
	}

	return array(
		'message' => 'Post created successfully with featured image.',
		'post_id' => $post_id,
	);
}

/**
 * Sideload an image from a URL into the media library and set it as the featured
 * image for a post.
 *
 * @param int    $post_id   The post to attach the image to.
 * @param string $image_url The image URL to sideload.
 * @return int|WP_Error The attachment ID on success, or WP_Error on failure.
 */
function wp_ai_workshop_demo_set_featured_image_from_url( $post_id, $image_url ) {
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	set_post_thumbnail( $post_id, $attachment_id );

	return $attachment_id;
}
