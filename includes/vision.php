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
	$image_url = $arguments['image_url'];

	// Vision-capable providers require inline image data, so convert the URL to a data URI.
	$data_uri = wp_ai_workshop_demo_image_url_to_data_uri( $image_url );
	if ( is_wp_error( $data_uri ) ) {
		return $data_uri;
	}

	$prompt  = 'Describe this image in detail for someone who cannot see it. ';
	$prompt .= 'Focus on the main subject, the setting, the mood, notable colours, ';
	$prompt .= 'and any details that would help someone write an engaging blog post about it.';

	$description = wp_ai_client_prompt()
		->with_text( $prompt )
		->with_file( $data_uri )
		->generate_text();

	if ( is_wp_error( $description ) ) {
		return $description;
	}

	return array(
		'description' => trim( $description ),
	);
}

/**
 * Fetch a remote image and return it as a base64-encoded data URI.
 *
 * @param string $image_url The URL of the image to fetch.
 * @return string|WP_Error The data URI on success, or WP_Error on failure.
 */
function wp_ai_workshop_demo_image_url_to_data_uri( $image_url ) {
	$response = wp_remote_get(
		$image_url,
		array(
			'timeout'    => 30,
			'user-agent' => 'WP-AI-Workshop-Demo/1.0; ' . home_url( '/' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error(
			'image_fetch_failed',
			sprintf( 'Could not fetch the image (HTTP %d).', $code )
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return new WP_Error( 'image_fetch_empty', 'The fetched image was empty.' );
	}

	$mime = wp_remote_retrieve_header( $response, 'content-type' );
	if ( ! is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
		$filetype = wp_check_filetype( $image_url );
		$mime     = ! empty( $filetype['type'] ) ? $filetype['type'] : 'image/jpeg';
	}

	return 'data:' . $mime . ';base64,' . base64_encode( $body );
}
