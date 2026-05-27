# Workshop Step Verification Tests

These PHPUnit tests verify that each step in [`WORKSHOP.md`](../WORKSHOP.md) has been applied correctly. They perform static source inspection of the plugin files — no WordPress runtime is required, so they run quickly with any PHP 8.1+ install on the workshop attendee's machine.

## Running the tests

From the plugin directory:

```shell
composer install
composer test
```

Or with the `testdox` formatter (groups assertions by workshop step):

```shell
composer test:workshop
```

To run a single step:

```shell
./vendor/bin/phpunit --filter Step05
```

## What each test verifies

| Test file | Workshop step |
| --- | --- |
| `Step00PrerequisitesTest.php` | Step 0 — `composer install` + `npm install` have been run, and at least one AI Connector (OpenAI / Anthropic / Google) is active with an API key configured |
| `Step01AbilityRegistrationTest.php` | Step 1 — Ability category + generate-post ability are registered in `includes/abilities.php` |
| `Step02AbilityHooksTest.php` | Step 2 — Ability hooks added to `wp-ai-workshop-demo.php` |
| `Step03AbilitiesRestEndpointTest.php` | Step 3 — `/wp-abilities/*` REST namespace is reachable on the live Studio site (skipped offline) |
| `Step04AiClientAutoloaderTest.php` | Step 4 (first) — wp-ai-client autoloader is required from the main plugin file |
| `Step04EnqueueScriptsTest.php` | Step 4 (second) — `wp-ai-client` and `@wordpress/core-abilities` scripts are enqueued, and the plugin script declares the dependency |
| `Step05ContentGenerationTest.php` | Step 5 — `wp_ai_workshop_generate_content()` uses `wp_ai_client_prompt()` |
| `Step06ImageGenerationTest.php` | Step 6 — `wp_ai_workshop_demo_create_image()` uses `wp_ai_client_prompt()` |
| `Step07SettingsPageTest.php` | Step 7 — `src/components/settings-page.jsx` has the abilities import, welcome message, and ability call, AND `build/index.js` has been re-built |
| `Step08McpAdapterTest.php` | Step 8 — Ability meta config exposes the ability via MCP (`'mcp' => array( 'public' => true )`) |

## Notes

- **Step 0** AI Connector check shells out to `studio wp` to read the active plugins list and the `wp_ai_client_provider_credentials` option. It is *skipped* (not failed) when the `studio` CLI is unavailable, so the suite still runs in environments without WordPress Studio.
- **Step 3** requires a local development environment url. Set this url in the `phpunit.xml.dist` file under `<php><env name="WP_AI_WORKSHOP_DEMO_SITE_URL" .../></php>`). To use a different URL: edit `phpunit.xml.dist`, or export `WP_AI_WORKSHOP_DEMO_SITE_URL=https://your-site/` in your shell — the shell value takes precedence (`force="false"`). The test is *skipped* (not failed) when the site is unreachable, so the suite stays green when running offline.
- **Step 8 only checks the code change.** Installing the [MCP Adapter](https://github.com/WordPress/mcp-adapter) and configuring an MCP client are out of scope for an automated test.
