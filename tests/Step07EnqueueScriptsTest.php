<?php
/**
 * Step 7 — Enqueue WP AI Client and Abilities Scripts.
 *
 * Verifies includes/admin.php enqueues the wp-ai-client script and the
 * @wordpress/core-abilities script module, and that the plugin's own
 * script module declares @wordpress/core-abilities as a dependency.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step07EnqueueScriptsTest extends WorkshopTestCase {

	private const FILE = 'includes/admin.php';
	private const FN   = 'wp_ai_workshop_demo_admin_enqueue_scripts';

	public function testTodoCommentRemoved(): void {
		$this->assertPluginFileNotContains(
			self::FILE,
			'// TODO: Enqueue the wp-ai-client and abilities scripts.'
		);
	}

	public function testWpAiClientScriptEnqueued(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			"wp_enqueue_script( 'wp-ai-client' );"
		);
	}

	public function testCoreAbilitiesModuleEnqueued(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			"wp_enqueue_script_module( '@wordpress/core-abilities' );"
		);
	}

	public function testPluginScriptDeclaresCoreAbilitiesDependency(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );

		// Find the wp_enqueue_script_module call that registers our plugin script.
		$pattern = '/wp_enqueue_script_module\s*\(\s*[\'"]wp-ai-workshop-demo-script[\'"](?<args>.*?)\)\s*;/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$body,
			"Could not locate the wp_enqueue_script_module() call for 'wp-ai-workshop-demo-script'."
		);

		preg_match( $pattern, $body, $matches );
		$this->assertStringContainsString(
			"'@wordpress/core-abilities'",
			$matches['args'],
			"Expected the 'wp-ai-workshop-demo-script' module to declare '@wordpress/core-abilities' as a dependency."
		);
	}
}
