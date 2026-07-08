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
| `Step01AbilityRegistrationTest.php` | Step 1 — Ability category + the three Photo to Post abilities (`describe-image`, `generate-post-from-description`, `create-post-from-photo`) are registered in `includes/abilities.php` |
| `Step02AbilityHooksTest.php` | Step 2 — Ability category + three ability hooks added to `wp-ai-workshop-demo.php` |
| `Step03AbilitiesRestEndpointTest.php` | Step 3 — `/wp-abilities/*` REST namespace is reachable on the live Studio site (skipped offline) |
| `Step04DescribeImageTest.php` | Step 4 — `wp_ai_workshop_demo_describe_image()` fetches the image as a data URI and uses `wp_ai_client_prompt()->with_file()->generate_text()` (vision) |
| `Step05GeneratePostCopyTest.php` | Step 5 — `wp_ai_workshop_demo_generate_post_from_description()` uses `wp_ai_client_prompt()` and parses a JSON `{title, content}` response |
| `Step06CreatePostFromPhotoTest.php` | Step 6 — `wp_ai_workshop_demo_create_post_from_photo()` composes the two abilities via `WP_Ability::execute()`, then creates a draft post and sets the featured image |
| `Step07EnqueueScriptsTest.php` | Step 7 — `wp-ai-client` and `@wordpress/core-abilities` scripts are enqueued, and the plugin script declares the dependency |
| `Step08SettingsPageTest.php` | Step 8 — `src/components/settings-page.jsx` has the abilities import, AI welcome message, image URL form, and `create-post-from-photo` ability call, AND `build/index.js` has been re-built |
| `Step09RequestTimeoutTest.php` | Step 9 — `wp_ai_workshop_demo_set_request_timeout()` returns an integer and is hooked into the `wp_ai_client_default_request_timeout` filter |
| `Step10McpAdapterTest.php` | Step 10 — Each ability's meta config exposes it via MCP (`'mcp' => array( 'public' => true )`) |

## Notes

- **Step 0** AI Connector check shells out to `studio wp` to read the active plugins list and the `wp_ai_client_provider_credentials` option. It is *skipped* (not failed) when the `studio` CLI is unavailable, so the suite still runs in environments without WordPress Studio.
- **Step 3** requires a local development environment url. Set this url in the `phpunit.xml.dist` file under `<php><env name="WP_AI_WORKSHOP_DEMO_SITE_URL" .../></php>`). To use a different URL: edit `phpunit.xml.dist`, or export `WP_AI_WORKSHOP_DEMO_SITE_URL=https://your-site/` in your shell — the shell value takes precedence (`force="false"`). The test is *skipped* (not failed) when the site is unreachable, so the suite stays green when running offline.
- **Step 10 only checks the code change.** Installing the [MCP Adapter](https://github.com/WordPress/mcp-adapter) and configuring an MCP client are out of scope for an automated test.
