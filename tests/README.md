# Workshop Step Verification Tests

These PHPUnit tests verify that each step in [`WORKSHOP.md`](../WORKSHOP.md) has been applied correctly. Most steps are checked by static source inspection of the plugin files; two steps (0 and 3) additionally check the running site.

The suite is **not tied to any particular local WordPress environment** — WordPress Studio, `wp-env`, Local, DDEV, Lando, Valet, MAMP, a Docker stack or a plain LAMP install all work.

## Requirements

- **PHP 8.1+** to run PHPUnit itself. This is all the static source-inspection tests (Steps 1, 2, 4–10) need — they run anywhere the plugin is checked out.
- For the runtime checks (**Steps 0 and 3**): a running local site with the plugin installed, and a WP-CLI that can reach it. The suite auto-detects, in order, `wp` on your `PATH`, `studio` (WordPress Studio), `wp-env`, `ddev` and `lando`, probing each until one answers. It finds the WordPress installation by walking up from the plugin directory looking for `wp-load.php`, so any directory layout works.

The runtime checks are *skipped* (not failed) when no local environment is reachable, so the suite stays green offline — but an offline run only partially verifies the workshop.

### Pointing the tests at your environment

If auto-detection doesn't cover your setup, set any of these as environment variables (or in `phpunit.xml.dist`, where they are listed commented out):

| Variable | Purpose |
| --- | --- |
| `WP_AI_WORKSHOP_DEMO_WP_CLI` | The command that runs WP-CLI against your site, used verbatim as a prefix. E.g. `"docker compose exec -T wordpress wp"`, `"ddev wp"`, `"wp --ssh=vagrant"`. |
| `WP_AI_WORKSHOP_DEMO_SITE_URL` | The URL your local site is served on. Only needed if it can't be read from the site via WP-CLI. |
| `WP_AI_WORKSHOP_DEMO_SITE_PATH` | Path to the WordPress installation, if the plugin doesn't live under it. |

For example:

```shell
WP_AI_WORKSHOP_DEMO_WP_CLI="ddev wp" composer test
```

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
| `Step03AbilitiesRestEndpointTest.php` | Step 3 — `/wp-abilities/*` REST namespace is reachable on the live site (skipped offline) |
| `Step04DescribeImageTest.php` | Step 4 — `wp_ai_workshop_demo_describe_image()` fetches the image as a data URI and uses `wp_ai_client_prompt()->with_file()->generate_text()` (vision) |
| `Step05GeneratePostCopyTest.php` | Step 5 — `wp_ai_workshop_demo_generate_post_from_description()` uses `wp_ai_client_prompt()` and parses a JSON `{title, content}` response |
| `Step06CreatePostFromPhotoTest.php` | Step 6 — `wp_ai_workshop_demo_create_post_from_photo()` composes the two abilities via `WP_Ability::execute()`, then creates a draft post and sets the featured image |
| `Step07EnqueueScriptsTest.php` | Step 7 — the `@wordpress/core-abilities` script module is enqueued, and the plugin script is enqueued as a script module declaring it as a dependency |
| `Step08SettingsPageTest.php` | Step 8 — `src/components/settings-page.jsx` has the abilities import, image URL form, and `create-post-from-photo` ability call, AND `build/index.js` has been re-built |
| `Step09RequestTimeoutTest.php` | Step 9 — `wp_ai_workshop_demo_set_request_timeout()` returns an integer and is hooked into the `wp_ai_client_default_request_timeout` filter |
| `Step10McpAdapterTest.php` | Step 10 — Each ability's meta config exposes it via MCP (`'mcp' => array( 'public' => true )`) |

## Notes

- **Step 0** runtime checks (plugin active, AI Connector configured) run `option get active_plugins` and a small `eval` snippet through the detected WP-CLI. They are *skipped* (not failed) when no WP-CLI can reach a site, so the suite still runs in environments without one.
- **Step 3** probes `wp-json/` over HTTP. The site URL comes from `WP_AI_WORKSHOP_DEMO_SITE_URL` when set, and otherwise from `wp option get siteurl`. The test is *skipped* (not failed) when no URL can be determined or the site is unreachable, so the suite stays green when running offline.
- **Step 10 only checks the code change.** Installing the [MCP Adapter](https://github.com/WordPress/mcp-adapter) and configuring an MCP client are out of scope for an automated test.
