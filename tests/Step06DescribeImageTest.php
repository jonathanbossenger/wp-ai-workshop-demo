<?php
/**
 * Step 6 — Describe an Image (Vision).
 *
 * Verifies includes/vision.php implements the `describe-image` ability
 * callback using the WP AI Client's multimodal (vision) support: it fetches
 * the image as a base64 data URI and sends it to the model with a text
 * instruction, returning the description (or a WP_Error).
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step06DescribeImageTest extends WorkshopTestCase {

	private const FILE      = 'includes/vision.php';
	private const FN        = 'wp_ai_workshop_demo_describe_image';
	private const HELPER_FN = 'wp_ai_workshop_demo_image_url_to_data_uri';

	public function testTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::FN,
			'TODO: Implement AI image description (vision).'
		);
	}

	public function testFetchesImageAsDataUri(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'wp_ai_workshop_demo_image_url_to_data_uri('
		);
	}

	public function testUsesAiClientVisionPrompt(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'wp_ai_client_prompt(', $body );
		$this->assertStringContainsString( '->with_text(', $body );
		$this->assertStringContainsString( '->with_file(', $body );
		$this->assertStringContainsString( 'generate_text(', $body );
	}

	public function testHandlesWpErrorAndReturnsDescription(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'is_wp_error(', $body );
		$this->assertStringContainsString( "'description'", $body );
	}

	public function testDataUriHelperFetchesAndEncodes(): void {
		$body = $this->getFunctionBody( self::FILE, self::HELPER_FN );
		$this->assertStringContainsString( 'wp_remote_get(', $body );
		$this->assertStringContainsString( 'base64_encode(', $body );
		$this->assertStringContainsString( "'data:'", $body );
	}
}
