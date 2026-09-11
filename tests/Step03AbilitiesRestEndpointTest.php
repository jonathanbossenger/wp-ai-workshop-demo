<?php
/**
 * Step 3 — Test Abilities REST API endpoint.
 *
 * The workshop step is "create an Application Password and curl the
 * abilities endpoint." We can't drive that interactive manual step in a
 * static test, but we CAN verify the local site exposes the
 * /wp-abilities/v1/abilities route once steps 1 and 2 have been
 * applied AND the WordPress Abilities API plugin is active.
 *
 * The site URL is taken from WP_AI_WORKSHOP_DEMO_SITE_URL when set, and
 * otherwise read from the site itself via WP-CLI, so this works with any
 * local WordPress environment. The test is skipped (not failed) when no
 * site URL can be determined or the site is not reachable, so it doesn't
 * get in the way when running the suite offline.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

final class Step03AbilitiesRestEndpointTest extends WorkshopTestCase {

	public function testAbilitiesRestRouteIsDiscoverable(): void {
		$siteUrl = $this->siteUrl();
		if ( $siteUrl === null ) {
			$this->markTestSkipped(
				$this->noEnvironmentMessage( 'determine the site URL' )
					. ' Alternatively set WP_AI_WORKSHOP_DEMO_SITE_URL to your local site URL.'
			);
		}

		$indexUrl = $siteUrl . 'wp-json/';

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
				"The site at {$indexUrl} is not reachable. Start your local WordPress environment, or set WP_AI_WORKSHOP_DEMO_SITE_URL to the URL it is served on."
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
