<?php
/**
 * Step 2 — Ability Hook Registration.
 *
 * Verifies that the ability category and the three Photo to Post abilities
 * are wired into the WordPress Abilities API via the appropriate init hooks
 * in the main plugin file.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step02AbilityHooksTest extends WorkshopTestCase {

	private const FILE = 'wp-ai-workshop-demo.php';

	public function testTodoCommentRemoved(): void {
		$this->assertPluginFileNotContains(
			self::FILE,
			'// TODO: Register the ability category and the three Photo to Post ability hooks.'
		);
	}

	public function testCategoryHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_categories_init', 'wp_ai_workshop_demo_register_ability_categories' );"
		);
	}

	public function testDescribeImageHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_describe_image_ability' );"
		);
	}

	public function testGeneratePostFromDescriptionHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_generate_post_from_description_ability' );"
		);
	}

	public function testCreatePostFromPhotoHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_create_post_from_photo_ability' );"
		);
	}
}
