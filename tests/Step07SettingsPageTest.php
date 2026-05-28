<?php
/**
 * Step 7 — Settings Page (Abilities and AI Welcome Message).
 *
 * Verifies src/components/settings-page.jsx imports the right modules,
 * adds the AI welcome message effect, and wires the Generate button to
 * execute the 'wp-ai-workshop-demo/generate-post' ability.
 *
 * Also verifies the JS bundle (build/index.js) has been re-built so the
 * compiled output contains the new ability call. This catches the
 * "forgot to run `npm run build`" mistake.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step07SettingsPageTest extends WorkshopTestCase {

	private const SRC_FILE   = 'src/components/settings-page.jsx';
	private const BUILD_FILE = 'build/index.js';

	// 7a — Abilities import.

	public function testUseEffectIsImported(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );

		$pattern = '/import\s*\{[^}]*\buseEffect\b[^}]*\}\s*from\s*[\'"]@wordpress\/element[\'"]/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$contents,
			'Expected useEffect to be imported from @wordpress/element.'
		);
	}

	public function testAbilitiesDynamicImportPresent(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( '@wordpress/abilities', $contents );
		$this->assertStringContainsString( 'getAbility', $contents );
		$this->assertStringContainsString( 'executeAbility', $contents );
	}

	// 7b — AI Welcome Message.

	public function testWelcomeMessageEffectAdded(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( 'useEffect(', $contents );
		$this->assertStringContainsString( 'wp.aiClient.prompt', $contents );
		$this->assertStringContainsString( 'generateText', $contents );
		$this->assertStringContainsString( 'setNoticeMessage', $contents );
	}

	// 7c — Generate Post via Ability.

	public function testTodoRemovedFromGenerateFromInput(): void {
		$this->assertPluginFileNotContains(
			self::SRC_FILE,
			"TODO: Use the Abilities API to execute the 'wp-ai-workshop-demo/generate-post' ability."
		);
	}

	public function testPlaceholderNoticeRemoved(): void {
		$this->assertPluginFileNotContains(
			self::SRC_FILE,
			'Post generation is not yet implemented.'
		);
	}

	public function testGenerateFromInputCallsAbility(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString(
			"getAbility( 'wp-ai-workshop-demo/generate-post' )",
			$contents
		);
		$this->assertStringContainsString(
			"executeAbility( 'wp-ai-workshop-demo/generate-post'",
			$contents
		);
	}

	// 7d — Build artifact reflects the new source.

	public function testBuildOutputHasBeenRegenerated(): void {
		$buildPath = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/' . self::BUILD_FILE;
		$this->assertFileExists(
			$buildPath,
			'build/index.js is missing — run `npm install && npm run build`.'
		);

		$built = file_get_contents( $buildPath );
		$this->assertNotFalse( $built );

		$this->assertStringContainsString(
			'wp-ai-workshop-demo/generate-post',
			$built,
			'The build/index.js bundle does not reference the generate-post ability — re-run `npm run build` after applying the Step 7 changes.'
		);
	}
}
