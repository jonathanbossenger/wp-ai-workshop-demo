<?php
/**
 * Image handling functions for WP AI Workshop Demo.
 *
 * @package wp-ai-workshop-demo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate an image using the AI Client based on the provided title.
 *
 * @param string $title The post title to guide image generation.
 *
 * @return mixed
 */
function wp_ai_workshop_demo_create_image( $title ) {
	$prompt = 'Create a relevant featured image for a blog post with the following title: ' . $title . '.';
	$image_builder = wp_ai_client_prompt( $prompt );
    if ( ! $image_builder->is_supported_for_image_generation() ){
        return null;
    }
    try {
        return $image_builder->generate_image();
    }catch ( Exception $e ) {
        return new WP_Error( 'image_creation_error', 'Error message', $e->getMessage() );
    }
}

/**
 * Convert an AI generated image to a WordPress media attachment.
 *
 * @param object $image AI generated image object.
 * @param string $filename Optional. Desired filename for the image.
 * @param int    $post_id Optional. Post ID to attach the media to.
 *
 * @return int|WP_Error
 */
function wp_ai_workshop_demo_image_to_media( $image, $filename = null, $post_id = 0 ) {
	$base64_string = $image->getBase64Data();
	$mime          = $image->getMimeType();

	$data = base64_decode( $base64_string ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( false === $data ) {
		return new WP_Error( 'invalid_base64', 'Base64 decode failed.' );
	}

	$mime_to_ext = array(
		'image/jpeg' => '.jpg',
		'image/png'  => '.png',
		'image/gif'  => '.gif',
		'image/webp' => '.webp',
	);
	$ext         = $mime_to_ext[ $mime ] ?? '.png';

	if ( ! $filename ) {
		$filename = 'image-' . time() . $ext;
	} elseif ( pathinfo( $filename, PATHINFO_EXTENSION ) === '' ) {
		$filename .= $ext;
	}

	$upload = wp_upload_dir();
	if ( wp_mkdir_p( $upload['path'] ) ) {
		$file_path = $upload['path'] . '/' . $filename;
	} else {
		$file_path = $upload['basedir'] . '/' . $filename;
	}

	if ( file_put_contents( $file_path, $data ) === false ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return new WP_Error( 'cannot_write_file', 'Failed to write file to disk.' );
	}

	$filetype = wp_check_filetype( $filename, null );

	$attachment = array(
		'guid'           => $upload['url'] . '/' . basename( $file_path ),
		'post_mime_type' => $filetype['type'] ?: ( $mime ?: 'image/png' ), //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
		'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
	if ( is_wp_error( $attach_id ) ) {
		return $attach_id;
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	return $attach_id;
}
