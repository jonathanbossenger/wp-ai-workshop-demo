<?php
/**
 * Base test case with helpers for inspecting workshop plugin source files.
 *
 * @package wp-ai-workshop-demo
 */

declare( strict_types=1 );

namespace WpAiWorkshopDemo\Tests;

use PHPUnit\Framework\TestCase;

abstract class WorkshopTestCase extends TestCase {

	/**
	 * Read a file relative to the plugin root.
	 */
	protected function readPluginFile( string $relativePath ): string {
		$path = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR . '/' . ltrim( $relativePath, '/' );
		$this->assertFileExists( $path, "Expected plugin file '$relativePath' to exist." );
		$contents = file_get_contents( $path );
		$this->assertNotFalse( $contents, "Failed to read '$relativePath'." );
		return $contents;
	}

	/**
	 * Assert a needle is present in the contents of a plugin file.
	 */
	protected function assertPluginFileContains( string $relativePath, string $needle, string $message = '' ): void {
		$contents = $this->readPluginFile( $relativePath );
		$this->assertStringContainsString(
			$needle,
			$contents,
			$message !== '' ? $message : "Expected '$relativePath' to contain: $needle"
		);
	}

	/**
	 * Assert a needle is NOT present in the contents of a plugin file.
	 * Use this to confirm TODO comments and placeholder code have been removed.
	 */
	protected function assertPluginFileNotContains( string $relativePath, string $needle, string $message = '' ): void {
		$contents = $this->readPluginFile( $relativePath );
		$this->assertStringNotContainsString(
			$needle,
			$contents,
			$message !== '' ? $message : "Expected '$relativePath' NOT to contain: $needle"
		);
	}

	/**
	 * Extract the body of a top-level PHP function definition from a file.
	 *
	 * Returns the source between the function's opening "{" and matching "}".
	 * Useful for asserting that the body of a specific function has been
	 * replaced rather than relying on top-level string matching that could
	 * pick up text from elsewhere in the file.
	 */
	protected function getFunctionBody( string $relativePath, string $functionName ): string {
		$contents = $this->readPluginFile( $relativePath );

		$pattern = '/function\s+' . preg_quote( $functionName, '/' ) . '\s*\([^)]*\)\s*\{/';
		if ( ! preg_match( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			$this->fail( "Could not locate function '$functionName' in '$relativePath'." );
		}

		$start  = $matches[0][1] + strlen( $matches[0][0] );
		$depth  = 1;
		$length = strlen( $contents );

		for ( $i = $start; $i < $length; $i++ ) {
			$ch = $contents[ $i ];
			if ( $ch === '{' ) {
				$depth++;
			} elseif ( $ch === '}' ) {
				$depth--;
				if ( $depth === 0 ) {
					return substr( $contents, $start, $i - $start );
				}
			}
		}

		$this->fail( "Could not find closing brace for function '$functionName' in '$relativePath'." );
	}

	/**
	 * Assert a needle is present in the body of a named function.
	 */
	protected function assertFunctionBodyContains(
		string $relativePath,
		string $functionName,
		string $needle,
		string $message = ''
	): void {
		$body = $this->getFunctionBody( $relativePath, $functionName );
		$this->assertStringContainsString(
			$needle,
			$body,
			$message !== '' ? $message : "Expected body of '$functionName' in '$relativePath' to contain: $needle"
		);
	}

	/**
	 * Assert a needle is NOT present in the body of a named function.
	 */
	protected function assertFunctionBodyNotContains(
		string $relativePath,
		string $functionName,
		string $needle,
		string $message = ''
	): void {
		$body = $this->getFunctionBody( $relativePath, $functionName );
		$this->assertStringNotContainsString(
			$needle,
			$body,
			$message !== '' ? $message : "Expected body of '$functionName' in '$relativePath' NOT to contain: $needle"
		);
	}
}
