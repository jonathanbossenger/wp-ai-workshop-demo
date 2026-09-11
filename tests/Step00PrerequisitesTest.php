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
 * The live-site checks run through whichever WP-CLI can reach the local
 * site (auto-detected, or set via WP_AI_WORKSHOP_DEMO_WP_CLI), so any local
 * WordPress environment works. They are skipped (not failed) when no local
 * environment is reachable, so the suite still runs offline.
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
		$activeJson = $this->runWpCli( 'option', 'get', 'active_plugins', '--format=json' );
		if ( $activeJson === null ) {
			$this->markTestSkipped( $this->noEnvironmentMessage( 'query the active plugins' ) );
		}

		$activePlugins = $this->decodeJsonFromOutput( $activeJson );
		$this->assertIsArray( $activePlugins, "Unexpected response from `wp option get active_plugins`: $activeJson" );

		$pluginDirName = basename( WP_AI_WORKSHOP_DEMO_PLUGIN_DIR );
		$pluginFile    = $pluginDirName . '/wp-ai-workshop-demo.php';

		$this->assertContains(
			$pluginFile,
			$activePlugins,
			"The $pluginDirName plugin is not active. Activate it in wp-admin, or run `wp plugin activate $pluginDirName` with your environment's WP-CLI."
		);
	}

	public function testAiConnectorActiveWithApiKey(): void {
		// Single PHP snippet: enumerate the AI connectors registered in WP
		// Core's Connectors API, check each one's `is_active` callback, and
		// resolve the API key from env var, constant, or option (in that
		// order — matches core's `_wp_connectors_get_api_key_source`). Uses
		// only double-quoted strings so the snippet survives being passed
		// through the shell as a single quoted argument.
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

		$output = $this->runWpCli( 'eval', $snippet );
		if ( $output === null ) {
			$this->markTestSkipped( $this->noEnvironmentMessage( 'query the AI Connectors' ) );
		}

		$connectors = $this->decodeJsonFromOutput( $output );

		// The snippet echoes a bare MISSING_API line when the Connectors API
		// is absent. Match the line exactly: some runners echo the command
		// they ran (snippet included) alongside its output.
		if ( $connectors === null && in_array( 'MISSING_API', $this->outputLines( $output ), true ) ) {
			$this->fail(
				'WP Core Connectors API (`wp_get_connectors()`) is unavailable. The workshop requires WordPress 7.0 or later.'
			);
		}

		$this->assertIsArray( $connectors, "Unexpected response from `wp eval`: $output" );

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
