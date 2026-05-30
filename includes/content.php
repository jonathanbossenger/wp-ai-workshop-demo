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
	$description = $arguments['description'];
	$guidance    = ! empty( $arguments['prompt'] ) ? trim( $arguments['prompt'] ) : '';

	$prompt = 'Write an engaging WordPress blog post inspired by the following image description.';
	if ( '' !== $guidance ) {
		$prompt .= ' Tone, angle, or extra guidance: ' . $guidance . '.';
	}
	$prompt .= ' Image description: ' . $description;
	$prompt .= ' Respond with a single JSON object containing exactly two keys: ';
	$prompt .= '"title" (a short, compelling post title as plain text) and ';
	$prompt .= '"content" (the full post body using valid WordPress Block Editor markup). ';
	$prompt .= 'Do not wrap the response in markdown code fences.';

	// generate_text() returns a string on success, or a WP_Error on failure
	// (the AI Client's WordPress wrapper catches exceptions and returns WP_Error).
	$text = wp_ai_client_prompt( $prompt )->generate_text();

	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$data = wp_ai_workshop_demo_decode_json_response( $text );

	if ( ! is_array( $data ) || empty( $data['title'] ) || empty( $data['content'] ) ) {
		return new WP_Error(
			'post_copy_parse_failed',
			'The AI response could not be parsed into a title and content.'
		);
	}

	return array(
		'title'   => trim( $data['title'] ),
		'content' => trim( $data['content'] ),
	);
}

/**
 * Decode a JSON object from an AI text response, tolerating markdown fences.
 *
 * @param string $text The raw text returned by the AI.
 * @return array|null Decoded associative array, or null if no JSON could be parsed.
 */
function wp_ai_workshop_demo_decode_json_response( $text ) {
	$text = trim( $text );

	// Strip leading/trailing markdown code fences if the model added them.
	$text = preg_replace( '/^\s*```(?:[a-z0-9_-]+)?\s*/i', '', $text );
	$text = preg_replace( '/\s*```\s*$/i', '', $text );
	$text = trim( $text );

	$data = json_decode( $text, true );
	if ( is_array( $data ) ) {
		return $data;
	}

	// Fall back to extracting the first {...} block from the response.
	if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
		$data = json_decode( $matches[0], true );
		if ( is_array( $data ) ) {
			return $data;
		}
	}

	return null;
}
