<?php
/**
 * Step 4 (first of two "4"s in WORKSHOP.md) — Add WP AI Client autoloader.
 *
 * Verifies the main plugin file includes the wp-ai-client autoloader so
 * the wp-ai-client JavaScript can be enqueued.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step04AiClientAutoloaderTest extends WorkshopTestCase {

	private const FILE = 'wp-ai-workshop-demo.php';

	public function testTodoCommentRemoved(): void {
		$this->assertPluginFileNotContains(
			self::FILE,
			'// TODO: Include the WP AI Client Autoloader'
		);
	}

	public function testAutoloadGuardIsPresent(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"file_exists( __DIR__ . '/vendor/wordpress/wp-ai-client/autoload.php' )"
		);
	}

	public function testAutoloadFileRequired(): void {
		$this->assertPluginFileContains(
			self::FILE,
			"require_once __DIR__ . '/vendor/wordpress/wp-ai-client/autoload.php';"
		);
	}
}
