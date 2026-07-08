<?php
/**
 * Step 10 — Install and try the MCP Adapter.
 *
 * The workshop step has two parts:
 *   1. Each ability's meta exposes it via MCP ('mcp' => array( 'public' => true )).
 *   2. Install and configure the MCP Adapter plugin and a client.
 *
 * Part 1 is a code change we can statically verify here. Part 2 is external
 * configuration (a separate WordPress plugin + a client app) and is out of
 * scope for an automated test.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step10McpAdapterTest extends WorkshopTestCase {

	private const FILE = 'includes/abilities.php';

	/**
	 * Every registered ability should be exposed via MCP.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function abilityRegistrationFunctions(): array {
		return array(
			'describe-image'                 => array( 'wp_ai_workshop_demo_register_describe_image_ability' ),
			'generate-post-from-description' => array( 'wp_ai_workshop_demo_register_generate_post_from_description_ability' ),
			'create-post-from-photo'         => array( 'wp_ai_workshop_demo_register_create_post_from_photo_ability' ),
		);
	}

	/**
	 * @dataProvider abilityRegistrationFunctions
	 */
	public function testMetaDeclaresMcpKey( string $functionName ): void {
		$this->assertFunctionBodyContains( self::FILE, $functionName, "'mcp'" );
	}

	/**
	 * @dataProvider abilityRegistrationFunctions
	 */
	public function testMcpAbilityIsPublic( string $functionName ): void {
		$body = $this->getFunctionBody( self::FILE, $functionName );

		// Match: 'mcp' => array( ... 'public' => true ... )
		$pattern = '/[\'"]mcp[\'"]\s*=>\s*(?:array\s*\(|\[)(?<inner>.*?)(?:\)|])\s*[,)]/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$body,
			"Could not locate an 'mcp' => array( ... ) block inside the meta config of $functionName."
		);

		preg_match( $pattern, $body, $matches );
		$this->assertMatchesRegularExpression(
			'/[\'"]public[\'"]\s*=>\s*true/',
			$matches['inner'],
			"Expected the 'mcp' meta block in $functionName to set 'public' => true so MCP clients can see this ability."
		);
	}
}
