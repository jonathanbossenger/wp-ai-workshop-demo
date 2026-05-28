<?php
/**
 * Step 6 — AI Image Generation (optional).
 *
 * Verifies includes/image.php has been updated to call the WP AI Client
 * to generate a featured image based on the post title.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step06ImageGenerationTest extends WorkshopTestCase {

	private const FILE = 'includes/image.php';
	private const FN   = 'wp_ai_workshop_demo_create_image';

	public function testTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::FN,
			'TODO: Implement AI image generation.'
		);
	}

	public function testBuildsImagePromptFromTitle(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( '$title', $body );
		$this->assertStringContainsString( 'featured image', $body );
	}

	public function testUsesAiClientPrompt(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'wp_ai_client_prompt('
		);
	}

	public function testChecksImageGenerationSupport(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'is_supported_for_image_generation('
		);
	}

	public function testGeneratesImage(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'generate_image('
		);
	}

	public function testHandlesExceptionAsWpError(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'try', $body );
		$this->assertStringContainsString( 'catch', $body );
		$this->assertStringContainsString( 'WP_Error', $body );
	}
}
