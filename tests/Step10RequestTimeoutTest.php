<?php
/**
 * Step 10 — Increase the AI Client request timeout.
 *
 * Verifies includes/ai-client.php defines wp_ai_workshop_demo_set_request_timeout()
 * returning an integer timeout, and that wp-ai-workshop-demo.php hooks it into the
 * wp_ai_client_default_request_timeout filter.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step10RequestTimeoutTest extends WorkshopTestCase {

	private const CLIENT_FILE = 'includes/ai-client.php';
	private const PLUGIN_FILE = 'wp-ai-workshop-demo.php';
	private const FN          = 'wp_ai_workshop_demo_set_request_timeout';

	public function testClientTodoCommentRemoved(): void {
		$this->assertPluginFileNotContains(
			self::CLIENT_FILE,
			'// TODO: Add the wp_ai_workshop_demo_set_request_timeout() function to increase the AI Client request timeout.'
		);
	}

	public function testPluginTodoCommentRemoved(): void {
		$this->assertPluginFileNotContains(
			self::PLUGIN_FILE,
			'// TODO: Hook wp_ai_workshop_demo_set_request_timeout into the wp_ai_client_default_request_timeout filter.'
		);
	}

	public function testTimeoutFunctionReturnsAnInteger(): void {
		$body = $this->getFunctionBody( self::CLIENT_FILE, self::FN );
		$this->assertMatchesRegularExpression(
			'/return\s+\d+\s*;/',
			$body,
			"Expected " . self::FN . "() to return an integer timeout value."
		);
	}

	public function testFilterIsHooked(): void {
		$contents = $this->readPluginFile( self::PLUGIN_FILE );

		$pattern = '/add_filter\s*\(\s*[\'"]wp_ai_client_default_request_timeout[\'"]\s*,\s*[\'"]' . preg_quote( self::FN, '/' ) . '[\'"]/';
		$this->assertMatchesRegularExpression(
			$pattern,
			$contents,
			"Expected " . self::FN . "() to be hooked into the 'wp_ai_client_default_request_timeout' filter."
		);
	}
}
