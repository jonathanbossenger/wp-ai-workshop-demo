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
	 * Cached WP-CLI command prefix, resolved once per process.
	 *
	 * `null` = not yet resolved, `false` = no working WP-CLI found.
	 *
	 * @var string|false|null
	 */
	private static $wpCliCommand = null;

	/**
	 * Cached site URL, resolved once per process.
	 *
	 * @var string|false|null
	 */
	private static $siteUrl = null;

	/**
	 * Locate the WordPress installation this plugin lives in.
	 *
	 * Walks up from the plugin directory looking for `wp-load.php`, so it
	 * works for any local environment that keeps the site files on the host
	 * (Studio, Local, Valet, MAMP, DDEV, Lando, a plain LAMP stack, ...),
	 * regardless of how deeply the plugin is nested.
	 *
	 * Returns null when the plugin is checked out standalone, or when the
	 * site files only exist inside a container (wp-env, Docker).
	 */
	protected function findWordPressRoot(): ?string {
		$override = getenv( 'WP_AI_WORKSHOP_DEMO_SITE_PATH' );
		if ( is_string( $override ) && $override !== '' ) {
			return rtrim( $override, '/' );
		}

		$dir = WP_AI_WORKSHOP_DEMO_PLUGIN_DIR;
		for ( $i = 0; $i < 8; $i++ ) {
			if ( file_exists( $dir . '/wp-load.php' ) ) {
				return $dir;
			}
			$parent = dirname( $dir );
			if ( $parent === $dir ) {
				break;
			}
			$dir = $parent;
		}

		return null;
	}

	/**
	 * Resolve a WP-CLI command prefix that can talk to the local site.
	 *
	 * Resolution order:
	 *
	 * 1. The `WP_AI_WORKSHOP_DEMO_WP_CLI` environment variable, used verbatim
	 *    as a command prefix. This is the escape hatch for any environment not
	 *    auto-detected below, e.g.
	 *    `WP_AI_WORKSHOP_DEMO_WP_CLI="docker compose exec -T wordpress wp"`.
	 * 2. Auto-detection of the common local environments, in order: a plain
	 *    `wp` on PATH, `studio wp` (WordPress Studio), `wp-env run cli wp`,
	 *    `ddev wp`, `lando wp`.
	 *
	 * Each candidate is probed with `option get siteurl`; the first one that
	 * answers wins. Returns null when nothing works, so callers can skip.
	 */
	protected function wpCliCommand(): ?string {
		if ( self::$wpCliCommand !== null ) {
			return self::$wpCliCommand === false ? null : self::$wpCliCommand;
		}

		self::$wpCliCommand = false;

		if ( ! function_exists( 'shell_exec' ) ) {
			return null;
		}

		$override = getenv( 'WP_AI_WORKSHOP_DEMO_WP_CLI' );
		if ( is_string( $override ) && trim( $override ) !== '' ) {
			$candidates = array( trim( $override ) );
		} else {
			$root       = $this->findWordPressRoot();
			$pathArg    = $root !== null ? ' ' . escapeshellarg( '--path=' . $root ) : '';
			$candidates = array();

			// Host-based runners: only useful when we can point them at a site.
			if ( $root !== null && $this->commandExists( 'wp' ) ) {
				$candidates[] = 'wp' . $pathArg;
			}
			if ( $root !== null && $this->commandExists( 'studio' ) ) {
				$candidates[] = 'studio wp' . $pathArg;
			}

			// Container-based runners: they resolve the site path themselves.
			if ( $this->commandExists( 'wp-env' ) ) {
				$candidates[] = 'wp-env run cli wp';
			}
			if ( $this->commandExists( 'ddev' ) ) {
				$candidates[] = 'ddev wp';
			}
			if ( $this->commandExists( 'lando' ) ) {
				$candidates[] = 'lando wp';
			}

			// A `wp` on PATH may still be able to find the site on its own
			// (e.g. run from inside the site directory, or via wp-cli.yml).
			if ( $root === null && $this->commandExists( 'wp' ) ) {
				$candidates[] = 'wp';
			}
		}

		foreach ( $candidates as $candidate ) {
			$output = @shell_exec( $candidate . ' option get siteurl 2>/dev/null' );
			foreach ( $this->outputLines( $output ) as $line ) {
				if ( filter_var( $line, FILTER_VALIDATE_URL ) !== false ) {
					self::$wpCliCommand = $candidate;
					self::$siteUrl      = $line;
					return $candidate;
				}
			}
		}

		return null;
	}

	/**
	 * Run a WP-CLI subcommand against the local site.
	 *
	 * Arguments are escaped individually. Returns the trimmed output, or null
	 * when no WP-CLI is available or the command produced nothing.
	 */
	protected function runWpCli( string ...$args ): ?string {
		$command = $this->wpCliCommand();
		if ( $command === null ) {
			return null;
		}

		$escaped = array_map( 'escapeshellarg', $args );
		$output  = @shell_exec( $command . ' ' . implode( ' ', $escaped ) . ' 2>/dev/null' );

		if ( ! is_string( $output ) || trim( $output ) === '' ) {
			return null;
		}

		return trim( $output );
	}

	/**
	 * Pull a JSON array out of WP-CLI output.
	 *
	 * Container-based runners (wp-env, ddev, lando) wrap command output in
	 * their own progress chatter, so rather than assuming the payload is the
	 * whole output, look for the first line that decodes to an array.
	 */
	protected function decodeJsonFromOutput( string $output ): ?array {
		foreach ( array_merge( array( $output ), $this->outputLines( $output ) ) as $candidate ) {
			$decoded = json_decode( $candidate, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return null;
	}

	/**
	 * The URL of the local site under test.
	 *
	 * Uses `WP_AI_WORKSHOP_DEMO_SITE_URL` when set, otherwise asks WP-CLI.
	 * Returns null when neither is available.
	 */
	protected function siteUrl(): ?string {
		$override = getenv( 'WP_AI_WORKSHOP_DEMO_SITE_URL' );
		if ( is_string( $override ) && trim( $override ) !== '' ) {
			return rtrim( trim( $override ), '/' ) . '/';
		}

		if ( self::$siteUrl === null ) {
			// Resolving the CLI populates self::$siteUrl as a side effect.
			$this->wpCliCommand();
			if ( self::$siteUrl === null ) {
				self::$siteUrl = false;
			}
		}

		return self::$siteUrl === false ? null : rtrim( self::$siteUrl, '/' ) . '/';
	}

	/**
	 * Message shown when no local WordPress environment can be reached.
	 */
	protected function noEnvironmentMessage( string $what ): string {
		return "Could not $what: no local WordPress environment detected. "
			. 'Start your site and make its WP-CLI available (wp, studio, wp-env, ddev or lando), '
			. 'or set WP_AI_WORKSHOP_DEMO_WP_CLI to the command that runs WP-CLI against your site '
			. '(e.g. WP_AI_WORKSHOP_DEMO_WP_CLI="docker compose exec -T wordpress wp").';
	}

	/**
	 * Whether a command is available on PATH.
	 */
	private function commandExists( string $command ): bool {
		$found = @shell_exec( 'command -v ' . escapeshellarg( $command ) . ' 2>/dev/null' );

		return is_string( $found ) && trim( $found ) !== '';
	}

	/**
	 * Non-empty, trimmed lines of a command's output.
	 *
	 * @return string[]
	 */
	protected function outputLines( ?string $output ): array {
		if ( ! is_string( $output ) ) {
			return array();
		}

		return array_values( array_filter(
			array_map( 'trim', preg_split( '/\R/', $output ) ?: array() ),
			static fn( string $line ): bool => $line !== ''
		) );
	}

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
