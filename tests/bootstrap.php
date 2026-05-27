<?php
/**
 * PHPUnit bootstrap for workshop step verification tests.
 *
 * These tests perform static source inspection of plugin files to verify
 * that the workshop steps in WORKSHOP.md have been applied correctly.
 * No WordPress runtime is required.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

define( 'WP_AI_WORKSHOP_DEMO_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'WP_AI_WORKSHOP_DEMO_PLUGIN_FILE', WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/wp-ai-workshop-demo.php' );

require_once WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/tests/WorkshopTestCase.php';
