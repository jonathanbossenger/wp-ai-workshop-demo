<?php
/**
 * Step 6 — Create a Post from a Photo (Orchestration).
 *
 * Verifies includes/post.php implements the `create-post-from-photo`
 * orchestrator callback. It demonstrates ability composition: it executes
 * the describe-image and generate-post-from-description abilities via
 * WP_Ability::execute(), then creates a draft post and sets the supplied
 * photo as the featured image.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step06CreatePostFromPhotoTest extends WorkshopTestCase {

	private const FILE          = 'includes/post.php';
	private const ORCHESTRATOR  = 'wp_ai_workshop_demo_create_post_from_photo';
	private const CREATE_FN     = 'wp_ai_workshop_demo_create_post';
	private const FEATURED_FN   = 'wp_ai_workshop_demo_set_featured_image_from_url';

	public function testTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::ORCHESTRATOR,
			'TODO: Compose the describe-image and generate-post-from-description abilities.'
		);
	}

	public function testComposesDescribeImageAbility(): void {
		$body = $this->getFunctionBody( self::FILE, self::ORCHESTRATOR );
		$this->assertStringContainsString(
			"wp_get_ability( 'wp-ai-workshop-demo/describe-image' )",
			$body
		);
	}

	public function testComposesGeneratePostAbility(): void {
		$body = $this->getFunctionBody( self::FILE, self::ORCHESTRATOR );
		$this->assertStringContainsString(
			"wp_get_ability( 'wp-ai-workshop-demo/generate-post-from-description' )",
			$body
		);
	}

	public function testExecutesComposedAbilities(): void {
		$body = $this->getFunctionBody( self::FILE, self::ORCHESTRATOR );
		$this->assertStringContainsString( '->execute(', $body );
		$this->assertStringContainsString( 'wp_ai_workshop_demo_create_post(', $body );
	}

	public function testCreatesDraftPost(): void {
		$body = $this->getFunctionBody( self::FILE, self::CREATE_FN );
		$this->assertStringContainsString( 'wp_insert_post(', $body );
		$this->assertStringContainsString( "'draft'", $body );
		$this->assertStringContainsString( 'wp_ai_workshop_demo_set_featured_image_from_url(', $body );
	}

	public function testSetsFeaturedImageFromUrl(): void {
		$body = $this->getFunctionBody( self::FILE, self::FEATURED_FN );
		$this->assertStringContainsString( 'media_sideload_image(', $body );
		$this->assertStringContainsString( 'set_post_thumbnail(', $body );
	}
}
