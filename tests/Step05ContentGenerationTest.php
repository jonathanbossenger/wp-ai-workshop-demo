<?php
/**
 * Step 5 — AI Content Generation.
 *
 * Verifies includes/content.php has been updated to call the WP AI Client
 * to generate text content for the post body.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step05ContentGenerationTest extends WorkshopTestCase {

	private const FILE = 'includes/content.php';
	private const FN   = 'wp_ai_workshop_generate_content';

	public function testTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::FN,
			'TODO: Implement AI content generation.'
		);
	}

	public function testPlaceholderReturnRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::FN,
			"return '';"
		);
	}

	public function testUsesAiClientPrompt(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'wp_ai_client_prompt('
		);
	}

	public function testGeneratesText(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'generate_text('
		);
	}

	public function testAppendsBlockEditorMarkupInstruction(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			'WordPress Block Editor markup'
		);
	}

	public function testHandlesExceptionAsWpError(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );
		$this->assertStringContainsString( 'try', $body );
		$this->assertStringContainsString( 'catch', $body );
		$this->assertStringContainsString( 'WP_Error', $body );
	}
}
