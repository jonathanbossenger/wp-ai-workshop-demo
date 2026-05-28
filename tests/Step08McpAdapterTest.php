<?php
/**
 * Step 8 — Install and try the MCP Adapter.
 *
 * The workshop step has two parts:
 *   1. Update the generate-post ability metadata so it is exposed via MCP.
 *   2. Install and configure the MCP Adapter plugin and a client.
 *
 * Part 1 is a code change we can statically verify here. Part 2 is
 * external configuration (a separate WordPress plugin + a client app)
 * and is out of scope for an automated test.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step08McpAdapterTest extends WorkshopTestCase {

	private const FILE = 'includes/abilities.php';
	private const FN   = 'wp_ai_workshop_demo_register_generate_post_ability';

	public function testMetaDeclaresMcpKey(): void {
		$this->assertFunctionBodyContains(
			self::FILE,
			self::FN,
			"'mcp'"
		);
	}

	public function testMcpAbilityIsPublic(): void {
		$body = $this->getFunctionBody( self::FILE, self::FN );

		// Match: 'mcp' => array( ... 'public' => true ... )
		$pattern = '/[\'"]mcp[\'"]\s*=>\s*(?:array\s*\(|\[)(?<inner>.*?)(?:\)|])\s*[,)]/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$body,
			"Could not locate an 'mcp' => array( ... ) block inside the meta config."
		);

		preg_match( $pattern, $body, $matches );
		$this->assertMatchesRegularExpression(
			'/[\'"]public[\'"]\s*=>\s*true/',
			$matches['inner'],
			"Expected the 'mcp' meta block to set 'public' => true so MCP clients can see this ability."
		);
	}
}
