<?php
/**
 * Step 9 — Settings Page (Image URL form + Ability call).
 *
 * Verifies src/components/settings-page.jsx imports the Abilities API,
 * adds the AI welcome message effect, renders an image URL form, and wires
 * the Generate button to execute the
 * 'wp-ai-workshop-demo/create-post-from-photo' ability.
 *
 * Also verifies the JS bundle (build/index.js) has been re-built so the
 * compiled output references the ability. This catches the "forgot to run
 * `npm run build`" mistake.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step09SettingsPageTest extends WorkshopTestCase {

	private const SRC_FILE   = 'src/components/settings-page.jsx';
	private const BUILD_FILE = 'build/index.js';

	private const ABILITY = 'wp-ai-workshop-demo/create-post-from-photo';

	// 9a — Abilities import.

	public function testAbilitiesImportPresent(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( '@wordpress/abilities', $contents );
		$this->assertStringContainsString( 'getAbility', $contents );
		$this->assertStringContainsString( 'executeAbility', $contents );
	}

	// 9b — AI Welcome Message.

	public function testWelcomeMessageEffectAdded(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( 'useEffect(', $contents );
		$this->assertStringContainsString( 'wp.aiClient.prompt', $contents );
		$this->assertStringContainsString( 'generateText', $contents );
		$this->assertStringContainsString( 'setNoticeMessage', $contents );
	}

	// 9c — Image URL form.

	public function testFormUsesImageUrlField(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( 'image_url', $contents );
	}

	// 9d — Generate via the create-post-from-photo ability.

	public function testTodoRemovedFromGenerateFromInput(): void {
		$this->assertPluginFileNotContains(
			self::SRC_FILE,
			"TODO: Use the Abilities API to execute the 'wp-ai-workshop-demo/create-post-from-photo' ability."
		);
	}

	public function testGenerateFromInputReferencesAbility(): void {
		$contents = $this->readPluginFile( self::SRC_FILE );
		$this->assertStringContainsString( "'" . self::ABILITY . "'", $contents );
		$this->assertStringContainsString( 'getAbility(', $contents );
		$this->assertStringContainsString( 'executeAbility(', $contents );
	}

	// 9e — Build artifact reflects the new source.

	public function testBuildOutputHasBeenRegenerated(): void {
		$buildPath = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/' . self::BUILD_FILE;
		$this->assertFileExists(
			$buildPath,
			'build/index.js is missing — run `npm install && npm run build`.'
		);

		$built = file_get_contents( $buildPath );
		$this->assertNotFalse( $built );

		$this->assertStringContainsString(
			self::ABILITY,
			$built,
			'The build/index.js bundle does not reference the create-post-from-photo ability — re-run `npm run build` after applying the Step 9 changes.'
		);
	}
}
