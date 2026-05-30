<?php
/**
 * Step 1 — Ability Registration.
 *
 * Verifies includes/abilities.php registers the 'wp-ai-workshop-demo'
 * category and the three Photo to Post abilities:
 *
 *   - wp-ai-workshop-demo/describe-image
 *   - wp-ai-workshop-demo/generate-post-from-description
 *   - wp-ai-workshop-demo/create-post-from-photo
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step01AbilityRegistrationTest extends WorkshopTestCase {

	private const FILE = 'includes/abilities.php';

	private const CATEGORY_FN = 'wp_ai_workshop_demo_register_ability_categories';
	private const DESCRIBE_FN = 'wp_ai_workshop_demo_register_describe_image_ability';
	private const GENERATE_FN = 'wp_ai_workshop_demo_register_generate_post_from_description_ability';
	private const PHOTO_FN    = 'wp_ai_workshop_demo_register_create_post_from_photo_ability';

	// --- Category. ---

	public function testCategoryTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::CATEGORY_FN,
			"TODO: Register the 'wp-ai-workshop-demo' ability category."
		);
	}

	public function testCategoryRegistered(): void {
		$this->assertFunctionBodyContains( self::FILE, self::CATEGORY_FN, 'wp_register_ability_category(' );
		$this->assertFunctionBodyContains( self::FILE, self::CATEGORY_FN, "'wp-ai-workshop-demo'" );
	}

	// --- describe-image ability. ---

	public function testDescribeImageTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::DESCRIBE_FN,
			"TODO: Register the 'wp-ai-workshop-demo/describe-image' ability."
		);
	}

	public function testDescribeImageRegistered(): void {
		$this->assertFunctionBodyContains( self::FILE, self::DESCRIBE_FN, 'wp_register_ability(' );
		$this->assertFunctionBodyContains( self::FILE, self::DESCRIBE_FN, "'wp-ai-workshop-demo/describe-image'" );
	}

	public function testDescribeImageSchemaAndCallback(): void {
		$body = $this->getFunctionBody( self::FILE, self::DESCRIBE_FN );
		$this->assertStringContainsString( "'input_schema'", $body );
		$this->assertStringContainsString( "'image_url'", $body );
		$this->assertStringContainsString( "'output_schema'", $body );
		$this->assertStringContainsString( "'description'", $body );
		$this->assertStringContainsString( "'execute_callback'", $body );
		$this->assertStringContainsString( "'wp_ai_workshop_demo_describe_image'", $body );
		$this->assertStringContainsString( "current_user_can( 'edit_posts' )", $body );
		$this->assertStringContainsString( "'show_in_rest' => true", $body );
	}

	// --- generate-post-from-description ability. ---

	public function testGeneratePostFromDescriptionTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::GENERATE_FN,
			"TODO: Register the 'wp-ai-workshop-demo/generate-post-from-description' ability."
		);
	}

	public function testGeneratePostFromDescriptionRegistered(): void {
		$this->assertFunctionBodyContains( self::FILE, self::GENERATE_FN, 'wp_register_ability(' );
		$this->assertFunctionBodyContains( self::FILE, self::GENERATE_FN, "'wp-ai-workshop-demo/generate-post-from-description'" );
	}

	public function testGeneratePostFromDescriptionSchemaAndCallback(): void {
		$body = $this->getFunctionBody( self::FILE, self::GENERATE_FN );
		$this->assertStringContainsString( "'input_schema'", $body );
		$this->assertStringContainsString( "'description'", $body );
		$this->assertStringContainsString( "'output_schema'", $body );
		$this->assertStringContainsString( "'title'", $body );
		$this->assertStringContainsString( "'content'", $body );
		$this->assertStringContainsString( "'execute_callback'", $body );
		$this->assertStringContainsString( "'wp_ai_workshop_demo_generate_post_from_description'", $body );
	}

	// --- create-post-from-photo orchestrator ability. ---

	public function testCreatePostFromPhotoTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			self::PHOTO_FN,
			"TODO: Register the 'wp-ai-workshop-demo/create-post-from-photo' ability."
		);
	}

	public function testCreatePostFromPhotoRegistered(): void {
		$this->assertFunctionBodyContains( self::FILE, self::PHOTO_FN, 'wp_register_ability(' );
		$this->assertFunctionBodyContains( self::FILE, self::PHOTO_FN, "'wp-ai-workshop-demo/create-post-from-photo'" );
	}

	public function testCreatePostFromPhotoSchemaAndCallback(): void {
		$body = $this->getFunctionBody( self::FILE, self::PHOTO_FN );
		$this->assertStringContainsString( "'input_schema'", $body );
		$this->assertStringContainsString( "'image_url'", $body );
		$this->assertStringContainsString( "'output_schema'", $body );
		$this->assertStringContainsString( "'message'", $body );
		$this->assertStringContainsString( "'post_id'", $body );
		$this->assertStringContainsString( "'execute_callback'", $body );
		$this->assertStringContainsString( "'wp_ai_workshop_demo_create_post_from_photo'", $body );
		$this->assertStringContainsString( "current_user_can( 'edit_posts' )", $body );
	}
}
