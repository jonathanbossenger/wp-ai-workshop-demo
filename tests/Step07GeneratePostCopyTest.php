<?php
/**
 * Step 7 — Generate Post Copy from a Description.
 *
 * Verifies includes/content.php implements the
 * `generate-post-from-description` ability callback: it calls the WP AI
 * Client for text generation, asks for a JSON object, and parses it into a
 * post title and Block Editor content (or returns a WP_Error).
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step07GeneratePostCopyTest extends WorkshopTestCase {

	private const FILE      = 'includes/content.php';
	private const FN        = 'wp_ai_workshop_demo_generate_post_from_description';
	private const HELPER_FN = 'wp_ai_workshop_demo_decode_json_response';

	public function testTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::FN,
			'TODO: Implement AI post copy generation.'
		);
	}

	public function testUsesAiClientTextGeneration(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'wp_ai_client_prompt(', $body );
		$this->assertStringContainsString( 'generate_text(', $body );
	}

	public function testHandlesWpError(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'is_wp_error('
		);
	}

	public function testParsesJsonIntoTitleAndContent(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'wp_ai_workshop_demo_decode_json_response(', $body );
		$this->assertStringContainsString( "'title'", $body );
		$this->assertStringContainsString( "'content'", $body );
	}

	public function testJsonDecodeHelperUsesJsonDecode(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::HELPER_FN,
			'json_decode('
		);
	}
}
