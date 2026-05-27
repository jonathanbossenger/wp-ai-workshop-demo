<?php
/**
 * Step 0 — Prerequisites.
 *
 * Verifies that the workshop attendee has run `composer install` and
 * `npm install` so the WP AI Client autoloader and JS build tooling
 * are available, that the wp-ai-workshop-demo plugin itself is active,
 * and that at least one of the supported AI Connector plugins (OpenAI,
 * Anthropic, or Google) is active with an API key configured.
 *
 * The live-site checks shell out to `studio wp` against the parent
 * Studio site. They are skipped (not failed) when the `studio` CLI is
 * unavailable, so the suite still runs offline.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step00PrerequisitesTest extends WorkshopTestCase {

	/**
	 * WP Core Connectors API provider IDs for the AI connectors the
	 * workshop is compatible with.
	 */
	private const AI_CONNECTOR_IDS = array( 'openai', 'anthropic', 'google' );

	public function testComposerDependenciesInstalled(): void {
		$autoload = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/vendor/autoload.php';
		$this->assertFileExists(
			$autoload,
			'Run `composer install` in the plugin directory to install PHP dependencies.'
		);
	}

	public function testWpAiClientPackageInstalled(): void {
		$wpAiClientAutoload = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/vendor/wordpress/wp-ai-client/autoload.php';
		$this->assertFileExists(
			$wpAiClientAutoload,
			'The wordpress/wp-ai-client package is missing — run `composer install`.'
		);
	}

	public function testNodeDependenciesInstalled(): void {
		$nodeModules = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/node_modules';
		$this->assertDirectoryExists(
			$nodeModules,
			'Run `npm install` in the plugin directory to install JavaScript dependencies.'
		);
	}

	public function testWorkshopDemoPluginActive(): void {
		if ( ! function_exists( 'shell_exec' ) ) {
			$this->markTestSkipped( '`shell_exec` is disabled — cannot query `studio wp` for active plugins.' );
		}

		$sitePath = dirname( WP_AI_WORKSHOP_DEMO_PLUGIN_DIR, 3 );
		$pathArg  = escapeshellarg( '--path=' . $sitePath );

		$activeJson = @shell_exec( "studio wp $pathArg option get active_plugins --format=json 2>/dev/null" );
		if ( ! is_string( $activeJson ) || trim( $activeJson ) === '' ) {
			$this->markTestSkipped(
				'Could not query active plugins via `studio wp`. Ensure the Studio CLI is installed (Studio app → Settings → Studio CLI for terminal) and the site is running.'
			);
		}

		$activePlugins = json_decode( trim( $activeJson ), true );
		$this->assertIsArray( $activePlugins, 'Unexpected response from `studio wp option get active_plugins`.' );

		$this->assertContains(
			'wp-ai-workshop-demo/wp-ai-workshop-demo.php',
			$activePlugins,
			'The wp-ai-workshop-demo plugin is not active. Activate it in wp-admin or run `studio wp plugin activate wp-ai-workshop-demo`.'
		);
	}

	public function testAiConnectorActiveWithApiKey(): void {
		if ( ! function_exists( 'shell_exec' ) ) {
			$this->markTestSkipped( '`shell_exec` is disabled — cannot query `studio wp` for connector state.' );
		}

		$sitePath = dirname( WP_AI_WORKSHOP_DEMO_PLUGIN_DIR, 3 );
		$pathArg  = escapeshellarg( '--path=' . $sitePath );

		// Single PHP snippet: enumerate the AI connectors registered in WP
		// Core's Connectors API, check each one's `is_active` callback, and
		// resolve the API key from env var, constant, or option (in that
		// order — matches core's `_wp_connectors_get_api_key_source`). Uses
		// only double-quoted strings so `escapeshellarg` can wrap the whole
		// thing in single quotes without any escaping headaches.
		$snippet = <<<'PHP'
if ( ! function_exists( "wp_get_connectors" ) ) { echo "MISSING_API"; exit; }
$ids = array( "openai", "anthropic", "google" );
$result = array();
foreach ( wp_get_connectors() as $id => $data ) {
    if ( ! in_array( $id, $ids, true ) ) { continue; }
    $auth = isset( $data["authentication"] ) && is_array( $data["authentication"] ) ? $data["authentication"] : array();
    $is_active_cb = $data["plugin"]["is_active"] ?? null;
    $is_active = is_callable( $is_active_cb ) ? (bool) $is_active_cb() : false;
    $source = "none";
    if ( ! empty( $auth["env_var_name"] ) && getenv( $auth["env_var_name"] ) ) {
        $source = "env";
    } elseif ( ! empty( $auth["constant_name"] ) && defined( $auth["constant_name"] ) && (string) constant( $auth["constant_name"] ) !== "" ) {
        $source = "constant";
    } elseif ( ! empty( $auth["setting_name"] ) && (string) get_option( $auth["setting_name"], "" ) !== "" ) {
        $source = "database";
    }
    $result[ $id ] = array( "active" => $is_active, "key_source" => $source );
}
echo wp_json_encode( $result );
PHP;

		$output = @shell_exec( "studio wp $pathArg eval " . escapeshellarg( $snippet ) . ' 2>/dev/null' );
		if ( ! is_string( $output ) || trim( $output ) === '' ) {
			$this->markTestSkipped(
				'Could not query AI Connectors via `studio wp eval`. Ensure the Studio CLI is installed (Studio app → Settings → Studio CLI for terminal) and the site is running.'
			);
		}

		$output = trim( $output );
		if ( $output === 'MISSING_API' ) {
			$this->fail(
				'WP Core Connectors API (`wp_get_connectors()`) is unavailable. The workshop requires WordPress 7.0 or later.'
			);
		}

		$connectors = json_decode( $output, true );
		$this->assertIsArray( $connectors, "Unexpected response from `studio wp eval`: $output" );

		$activeConnectorIds = array_keys( array_filter(
			$connectors,
			static fn( array $data ): bool => ! empty( $data['active'] )
		) );

		$this->assertNotEmpty(
			$activeConnectorIds,
			'No AI Connector plugin is active. Install and activate one of: AI Provider for OpenAI, AI Provider for Anthropic, or AI Provider for Google.'
		);

		$connectorsWithKey = array_keys( array_filter(
			$connectors,
			static fn( array $data ): bool => ! empty( $data['active'] )
				&& isset( $data['key_source'] )
				&& $data['key_source'] !== 'none'
		) );

		$this->assertNotEmpty(
			$connectorsWithKey,
			'No active AI Connector has an API key configured. Configure one at Settings → General → AI Connectors in wp-admin (active connectors: ' . implode( ', ', $activeConnectorIds ) . ').'
		);
	}
}
