<?php
/**
 * Ability registration functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom ability categories for the WP AI Workshop Demo plugin.
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
 * Register a custom ability to generate a post via AI.
 *
 * @return void
 */
function wp_ai_workshop_demo_register_generate_post_ability() {
	wp_register_ability(
		'wp-ai-workshop-demo/generate-post',
		array(
			'label'               => __( 'Generate a post via AI', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Based on a title and prompt, create an AI generated WordPress post.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'title'  => array(
						'type'        => 'string',
						'description' => 'The title of the post to be generated.',
					),
					'prompt'  => array(
						'type'        => 'string',
						'description' => 'The prompt to guide the post generation.',
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'message' => array(
						'type'        => 'string',
						'description' => 'A status message if the post was created successfully or not.',
					),
					'post_id' => array(
						'type'        => 'integer',
						'description' => 'The ID of the newly created post.',
					),
				),
				'required'   => array( 'message' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_generate_post',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
				),
			),
		)
	);
}

