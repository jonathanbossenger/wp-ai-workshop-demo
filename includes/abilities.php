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
	wp_register_ability_category(
		'wp-ai-workshop-demo',
		array(
			'label'       => __( 'WP AI Workshop Demo', 'wp-ai-workshop-demo' ),
			'description' => __( 'Abilities for the WP AI Workshop Demo.', 'wp-ai-workshop-demo' ),
		)
	);
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
	wp_register_ability(
		'wp-ai-workshop-demo/describe-image',
		array(
			'label'               => __( 'Describe an image via AI', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given an image URL, use AI vision to produce a detailed text description of the image.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'image_url' => array(
						'type'        => 'string',
						'description' => 'The URL of the image to describe.',
					),
				),
				'required'   => array( 'image_url' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'description' => array(
						'type'        => 'string',
						'description' => 'A detailed description of the image.',
					),
				),
				'required'   => array( 'description' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_describe_image',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
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
	wp_register_ability(
		'wp-ai-workshop-demo/generate-post-from-description',
		array(
			'label'               => __( 'Generate post copy from a description', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given a description (and optional tone/angle), generate a WordPress post title and body content.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'description' => array(
						'type'        => 'string',
						'description' => 'The source description to base the post on.',
					),
					'prompt'      => array(
						'type'        => 'string',
						'description' => 'Optional tone, angle, or extra guidance for the post.',
					),
				),
				'required'   => array( 'description' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'title'   => array(
						'type'        => 'string',
						'description' => 'The generated post title.',
					),
					'content' => array(
						'type'        => 'string',
						'description' => 'The generated post content in Block Editor markup.',
					),
				),
				'required'   => array( 'title', 'content' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_generate_post_from_description',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
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
	wp_register_ability(
		'wp-ai-workshop-demo/create-post-from-photo',
		array(
			'label'               => __( 'Create a post from a photo via AI', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given an image URL, describe the image, write a post about it, and create a draft post using the image as the featured image.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'image_url' => array(
						'type'        => 'string',
						'description' => 'The URL of the image to turn into a post.',
					),
					'prompt'    => array(
						'type'        => 'string',
						'description' => 'Optional tone, angle, or extra guidance for the post.',
					),
				),
				'required'   => array( 'image_url' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'message' => array(
						'type'        => 'string',
						'description' => 'A status message describing the result.',
					),
					'post_id' => array(
						'type'        => 'integer',
						'description' => 'The ID of the newly created post.',
					),
				),
				'required'   => array( 'message' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_create_post_from_photo',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true, // Expose this ability via MCP.
				),
			),
		)
	);
}
