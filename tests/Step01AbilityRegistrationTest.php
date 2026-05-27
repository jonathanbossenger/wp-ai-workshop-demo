<?php
/**
 * Step 1 — Ability Registration.
 *
 * Verifies includes/abilities.php has been updated to register the
 * 'wp-ai-workshop-demo' category and the 'wp-ai-workshop-demo/generate-post'
 * ability.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step01AbilityRegistrationTest extends WorkshopTestCase {

	private const FILE = 'includes/abilities.php';

	public function testCategoryTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			'wp_ai_workshop_demo_register_ability_categories',
			"TODO: Register the 'wp-ai-workshop-demo' ability category."
		);
	}

	public function testCategoryRegistered(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_ability_categories',
			'wp_register_ability_category('
		);
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_ability_categories',
			"'wp-ai-workshop-demo'"
		);
	}

	public function testGeneratePostAbilityTodoRemoved(): void {
		$this->assertFunctionBodyNotContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"TODO: Register the 'wp-ai-workshop-demo/generate-post' ability."
		);
	}

	public function testGeneratePostAbilityRegistered(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			'wp_register_ability('
		);
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"'wp-ai-workshop-demo/generate-post'"
		);
	}

	public function testGeneratePostAbilityDefinesInputSchema(): void {
		$body = $this->getFunctionBody(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability'
		);
		$this->assertStringContainsString( "'input_schema'", $body );
		$this->assertStringContainsString( "'title'", $body );
		$this->assertStringContainsString( "'prompt'", $body );
	}

	public function testGeneratePostAbilityDefinesOutputSchema(): void {
		$body = $this->getFunctionBody(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability'
		);
		$this->assertStringContainsString( "'output_schema'", $body );
		$this->assertStringContainsString( "'message'", $body );
		$this->assertStringContainsString( "'post_id'", $body );
	}

	public function testGeneratePostAbilityWiresExecuteCallback(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"'execute_callback'"
		);
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"'wp_ai_workshop_demo_generate_post'"
		);
	}

	public function testGeneratePostAbilityRequiresEditPostsCapability(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"current_user_can( 'edit_posts' )"
		);
	}

	public function testGeneratePostAbilityExposedInRest(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			'wp_ai_workshop_demo_register_generate_post_ability',
			"'show_in_rest' => true"
		);
	}
}
