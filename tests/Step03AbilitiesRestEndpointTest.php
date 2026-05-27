<?php
/**
 * Step 3 — Test Abilities REST API endpoint.
 *
 * The workshop step is "create an Application Password and curl the
 * abilities endpoint." We can't drive that interactive manual step in a
 * static test, but we CAN verify the live Studio site exposes the
 * /wp-abilities/v1/abilities route once steps 1 and 2 have been
 * applied AND the WordPress Abilities API plugin is active.
 *
 * The test is skipped (not failed) when the site URL is not reachable,
 * so it doesn't get in the way when running the suite offline.
 *
 * Override the target with WP_AI_WORKSHOP_DEMO_SITE_URL=https://your-site/ .
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step03AbilitiesRestEndpointTest extends WorkshopTestCase {

	private function siteUrl(): string {
		$url = getenv( 'WP_AI_WORKSHOP_DEMO_SITE_URL' );
		if ( ! is_string( $url ) || $url === '' ) {
			$url = 'https://wordpress.wp.local/';
		}
		return rtrim( $url, '/' ) . '/';
	}

	public function testAbilitiesRestRouteIsDiscoverable(): void {
		$indexUrl = $this->siteUrl() . 'wp-json/';

		$ctx     = stream_context_create( array(
			'http' => array(
				'timeout'       => 5,
				'ignore_errors' => true,
			),
			'ssl'  => array(
				'verify_peer'      => false,
				'verify_peer_name' => false,
			),
		) );
		$payload = @file_get_contents( $indexUrl, false, $ctx );

		if ( $payload === false ) {
			$this->markTestSkipped(
				"Studio site at {$indexUrl} is not reachable. Start the site with `studio site start` or override WP_AI_WORKSHOP_DEMO_SITE_URL."
			);
		}

		$index = json_decode( $payload, true );
		$this->assertIsArray( $index, 'WP REST API index did not return JSON.' );
		$this->assertArrayHasKey( 'routes', $index, 'WP REST API index has no routes key.' );

		$routes = array_keys( $index['routes'] );
		$abilitiesRoutes = array_values( array_filter(
			$routes,
			static fn( string $route ): bool => str_starts_with( $route, '/wp-abilities/' )
		) );

		$this->assertNotEmpty(
			$abilitiesRoutes,
			'The /wp-abilities/* namespace is not registered. Confirm the WordPress Abilities API plugin is active and that Step 1 + 2 have been applied.'
		);
	}
}
