<?php
/**
 * Step 2 — Ability Hook Registration.
 *
 * Verifies that the ability category and generate-post ability are wired
 * into the WordPress Abilities API via the appropriate init hooks.
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
			'// TODO: Register ability category and generate post ability hooks.'
		);
	}

	public function testCategoryHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_categories_init', 'wp_ai_workshop_demo_register_ability_categories' );"
		);
	}

	public function testAbilityHookRegistered(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_generate_post_ability' );"
		);
	}
}
