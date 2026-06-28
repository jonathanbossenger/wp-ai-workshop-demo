# Workshop: Build a "Photo to Post" AI Plugin

In this workshop you will build a WordPress plugin that turns a **photo into a draft blog post**. Given an image URL, the plugin will:

1. **Look at the image** using an AI vision model and produce a text description.
2. **Write a blog post** (title + content) about the image using an AI text model.
3. **Create a draft post** with the photo set as the featured image.

The interesting part is *how* it is built: as three composable [WordPress Abilities](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/) that call the [WordPress AI Client](https://github.com/WordPress/php-ai-client):

| Ability | What it does | AI capability |
| --- | --- | --- |
| `wp-ai-workshop-demo/describe-image` | Image URL → text description | Vision (image input) |
| `wp-ai-workshop-demo/generate-post-from-description` | Description → post title + content | Text generation |
| `wp-ai-workshop-demo/create-post-from-photo` | Orchestrator: composes the two abilities above, then creates the post | — |

The orchestrator demonstrates **ability composition** — one ability calling other abilities via `WP_Ability::execute()`.

## How this workshop works

Each step below adds a piece of code, usually replacing a `// TODO:` comment. 

---

## 0. Prerequisites

1. Install and activate one of the AI Connector plugins: **OpenAI**, **Anthropic**, or **Google**.
2. Configure the connector with your API key.
   - **Important:** Photo to Post needs a **vision-capable** model (image input). All current Claude, GPT-4o/4.1, and Gemini models qualify. No *image generation* is required — the user supplies the photo.
3. Install dependencies:

```shell
cd /path/to/wp-content/plugins/wp-ai-workshop-demo
composer install
npm install
```

---

## 1. Ability Registration

Register the ability category and the three Photo to Post abilities.

**File:** `includes/abilities.php`

Replace the body of `wp_ai_workshop_demo_register_ability_categories()` with:

```php
	wp_register_ability_category(
		'wp-ai-workshop-demo',
		array(
			'label'       => __( 'WP AI Workshop Demo', 'wp-ai-workshop-demo' ),
			'description' => __( 'Abilities for the WP AI Workshop Demo.', 'wp-ai-workshop-demo' ),
		)
	);
```

Replace the body of `wp_ai_workshop_demo_register_describe_image_ability()` with:

```php
	wp_register_ability(
		'wp-ai-workshop-demo/describe-image',
		array(
			'label'               => __( 'Describe an image via AI', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given an image URL, use AI vision to produce a detailed text description of the image.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'image_url' => array(
						'type'        => 'string',
						'description' => 'The URL of the image to describe.',
					),
				),
				'required'   => array( 'image_url' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'description' => array(
						'type'        => 'string',
						'description' => 'A detailed description of the image.',
					),
				),
				'required'   => array( 'description' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_describe_image',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
```

Replace the body of `wp_ai_workshop_demo_register_generate_post_from_description_ability()` with:

```php
	wp_register_ability(
		'wp-ai-workshop-demo/generate-post-from-description',
		array(
			'label'               => __( 'Generate post copy from a description', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given a description (and optional tone/angle), generate a WordPress post title and body content.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'description' => array(
						'type'        => 'string',
						'description' => 'The source description to base the post on.',
					),
					'prompt'      => array(
						'type'        => 'string',
						'description' => 'Optional tone, angle, or extra guidance for the post.',
					),
				),
				'required'   => array( 'description' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'title'   => array(
						'type'        => 'string',
						'description' => 'The generated post title.',
					),
					'content' => array(
						'type'        => 'string',
						'description' => 'The generated post content in Block Editor markup.',
					),
				),
				'required'   => array( 'title', 'content' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_generate_post_from_description',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
```

Replace the body of `wp_ai_workshop_demo_register_create_post_from_photo_ability()` with:

```php
	wp_register_ability(
		'wp-ai-workshop-demo/create-post-from-photo',
		array(
			'label'               => __( 'Create a post from a photo via AI', 'wp-ai-workshop-demo' ),
			'description'         => __( 'Given an image URL, describe the image, write a post about it, and create a draft post using the image as the featured image.', 'wp-ai-workshop-demo' ),
			'category'            => 'wp-ai-workshop-demo',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'image_url' => array(
						'type'        => 'string',
						'description' => 'The URL of the image to turn into a post.',
					),
					'prompt'    => array(
						'type'        => 'string',
						'description' => 'Optional tone, angle, or extra guidance for the post.',
					),
				),
				'required'   => array( 'image_url' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'message' => array(
						'type'        => 'string',
						'description' => 'A status message describing the result.',
					),
					'post_id' => array(
						'type'        => 'integer',
						'description' => 'The ID of the newly created post.',
					),
				),
				'required'   => array( 'message' ),
			),
			'execute_callback'    => 'wp_ai_workshop_demo_create_post_from_photo',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
```

---

## 2. Ability Hook Registration

**File:** `wp-ai-workshop-demo.php`

Replace the `// TODO: Register the ability category and the three Photo to Post ability hooks.` comment with:

```php
add_action( 'wp_abilities_api_categories_init', 'wp_ai_workshop_demo_register_ability_categories' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_describe_image_ability' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_generate_post_from_description_ability' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_create_post_from_photo_ability' );
```

---

## 3. Test the Abilities REST API endpoint

The Abilities API exposes registered abilities over the REST API. Create an Application Password for your admin user, then:

```shell
curl -u 'USERNAME:APPLICATION_PASSWORD' https://yoursite.local/wp-json/wp-abilities/v1/abilities
```

You should see your three `wp-ai-workshop-demo/*` abilities listed.

Note: make sure your local WordPress site has Permalinks enabled (anything but "Plain") or the REST API endpoints won't work.

Tip: Use jq to auto-format the JSON response:

```shell
curl -u 'USERNAME:APPLICATION_PASSWORD' https://yoursite.local/wp-json/wp-abilities/v1/abilities | jq
```
---

## 4. Describe an image (Vision)

This is the first AI call: send the image to a vision-capable model and get back a description.

**File:** `includes/vision.php`

Replace the `// TODO: Implement AI image description (vision).` comment in `wp_ai_workshop_demo_describe_image()` with:

```php
	$image_url = $arguments['image_url'];

	// Vision-capable providers (e.g. Anthropic) require the image to be sent
	// inline (base64), not as a remote URL, so fetch it and build a data URI.
	// A data URI is also accepted by URL-friendly providers such as OpenAI,
	// which keeps this ability portable across providers.
	$data_uri = wp_ai_workshop_demo_image_url_to_data_uri( $image_url );
	if ( is_wp_error( $data_uri ) ) {
		return $data_uri;
	}

	$prompt  = 'Describe this image in detail for someone who cannot see it. ';
	$prompt .= 'Focus on the main subject, the setting, the mood, notable colours, ';
	$prompt .= 'and any details that would help someone write an engaging blog post about it.';

	// generate_text() returns a string on success, or a WP_Error on failure
	// (the AI Client's WordPress wrapper catches exceptions and returns WP_Error).
	$description = wp_ai_client_prompt()
		->with_text( $prompt )
		->with_file( $data_uri )
		->generate_text();

	if ( is_wp_error( $description ) ) {
		return $description;
	}

	return array(
		'description' => trim( $description ),
	);
```

Then add the helper that fetches a remote image and returns it as a base64 data URI:

```php
/**
 * Fetch a remote image and return it as a base64-encoded data URI.
 *
 * @param string $image_url The URL of the image to fetch.
 * @return string|WP_Error The data URI on success, or WP_Error on failure.
 */
function wp_ai_workshop_demo_image_url_to_data_uri( $image_url ) {
	$response = wp_remote_get(
		$image_url,
		array(
			'timeout'    => 30,
			'user-agent' => 'WP-AI-Workshop-Demo/1.0; ' . home_url( '/' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error(
			'image_fetch_failed',
			sprintf( 'Could not fetch the image (HTTP %d).', $code )
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return new WP_Error( 'image_fetch_empty', 'The fetched image was empty.' );
	}

	$mime = wp_remote_retrieve_header( $response, 'content-type' );
	if ( ! is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
		$filetype = wp_check_filetype( $image_url );
		$mime     = ! empty( $filetype['type'] ) ? $filetype['type'] : 'image/jpeg';
	}

	return 'data:' . $mime . ';base64,' . base64_encode( $body );
}
```

> **Key gotcha:** `wp_ai_client_prompt()` returns a builder whose `generate_text()` *returns* a `WP_Error` on failure (it does not throw), so always check `is_wp_error()`. And vision providers such as Anthropic require the image **inline as base64**, not as a remote URL — that is why we fetch it and build a data URI.

---

## 5. Generate post copy from the description

The second AI call: turn the description into a post title and body. We ask the model for a JSON object and parse it.

**File:** `includes/content.php`

Replace the `// TODO: Implement AI post copy generation.` comment in `wp_ai_workshop_demo_generate_post_from_description()` with:

```php
	$description = $arguments['description'];
	$guidance    = ! empty( $arguments['prompt'] ) ? trim( $arguments['prompt'] ) : '';

	$prompt = 'Write an engaging WordPress blog post inspired by the following image description.';
	if ( '' !== $guidance ) {
		$prompt .= ' Tone, angle, or extra guidance: ' . $guidance . '.';
	}
	$prompt .= ' Image description: ' . $description;
	$prompt .= ' Respond with a single JSON object containing exactly two keys: ';
	$prompt .= '"title" (a short, compelling post title as plain text) and ';
	$prompt .= '"content" (the full post body using valid WordPress Block Editor markup). ';
	$prompt .= 'Do not wrap the response in markdown code fences.';

	$text = wp_ai_client_prompt( $prompt )->generate_text();

	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$data = wp_ai_workshop_demo_decode_json_response( $text );

	if ( ! is_array( $data ) || empty( $data['title'] ) || empty( $data['content'] ) ) {
		return new WP_Error(
			'post_copy_parse_failed',
			'The AI response could not be parsed into a title and content.'
		);
	}

	return array(
		'title'   => trim( $data['title'] ),
		'content' => trim( $data['content'] ),
	);
```

Then add the JSON-decoding helper (tolerant of markdown code fences the model might add):

```php
/**
 * Decode a JSON object from an AI text response, tolerating markdown fences.
 *
 * @param string $text The raw text returned by the AI.
 * @return array|null Decoded associative array, or null if no JSON could be parsed.
 */
function wp_ai_workshop_demo_decode_json_response( $text ) {
	$text = trim( $text );

	$text = preg_replace( '/^\s*```(?:[a-z0-9_-]+)?\s*/i', '', $text );
	$text = preg_replace( '/\s*```\s*$/i', '', $text );
	$text = trim( $text );

	$data = json_decode( $text, true );
	if ( is_array( $data ) ) {
		return $data;
	}

	if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
		$data = json_decode( $matches[0], true );
		if ( is_array( $data ) ) {
			return $data;
		}
	}

	return null;
}
```

---

## 6. Create a post from a photo (Orchestration)

Now compose the two abilities and create the post. This is where one ability calls other abilities.

**File:** `includes/post.php`

Replace the `// TODO: Compose the describe-image and generate-post-from-description abilities.` comment in `wp_ai_workshop_demo_create_post_from_photo()` with:

```php
	$image_url = $arguments['image_url'];
	$prompt    = isset( $arguments['prompt'] ) ? $arguments['prompt'] : '';

	// Step 1: Describe the image (ability composition).
	$describe_ability = wp_get_ability( 'wp-ai-workshop-demo/describe-image' );
	if ( ! $describe_ability instanceof WP_Ability ) {
		return array( 'message' => 'Post creation failed: the describe-image ability is not available.' );
	}
	$description_result = $describe_ability->execute( array( 'image_url' => $image_url ) );
	if ( is_wp_error( $description_result ) ) {
		return array( 'message' => 'Post creation failed while describing the image: ' . $description_result->get_error_message() );
	}

	// Step 2: Generate the post title and content (ability composition).
	$generate_ability = wp_get_ability( 'wp-ai-workshop-demo/generate-post-from-description' );
	if ( ! $generate_ability instanceof WP_Ability ) {
		return array( 'message' => 'Post creation failed: the generate-post-from-description ability is not available.' );
	}
	$copy_result = $generate_ability->execute(
		array(
			'description' => $description_result['description'],
			'prompt'      => $prompt,
		)
	);
	if ( is_wp_error( $copy_result ) ) {
		return array( 'message' => 'Post creation failed while writing the post: ' . $copy_result->get_error_message() );
	}

	// Step 3: Create the draft post and set the featured image.
	return wp_ai_workshop_demo_create_post(
		$copy_result['title'],
		$copy_result['content'],
		$image_url
	);
```

Then add the post-creation and featured-image helpers:

```php
/**
 * Create a draft post and set its featured image from an image URL.
 */
function wp_ai_workshop_demo_create_post( $title, $content, $image_url ) {
	$post_id = wp_insert_post(
		array(
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_type'    => 'post',
		)
	);

	if ( is_wp_error( $post_id ) || 0 === $post_id ) {
		return array( 'message' => 'Post creation failed.' );
	}

	$attachment_id = wp_ai_workshop_demo_set_featured_image_from_url( $post_id, $image_url );
	if ( is_wp_error( $attachment_id ) ) {
		return array(
			'message' => 'Post created, but the featured image could not be added: ' . $attachment_id->get_error_message(),
			'post_id' => $post_id,
		);
	}

	return array(
		'message' => 'Post created successfully with featured image.',
		'post_id' => $post_id,
	);
}

/**
 * Sideload an image from a URL into the media library and set it as the
 * featured image for a post.
 */
function wp_ai_workshop_demo_set_featured_image_from_url( $post_id, $image_url ) {
    // Verify that the image_url is a valid url, just to be safe.
    if ( ! wp_http_validate_url( $image_url ) ) {
        return new WP_Error( 'invalid_url', 'Image URL failed validation.' );
    }
      
    // The media_sideload_image() function is typically only used in the admin context
    // This ensures that it can be used outside the admin context, such as in a REST API request or MCP tool.
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	set_post_thumbnail( $post_id, $attachment_id );

	return $attachment_id;
}
```

## 7. Enqueue the WP AI Client and Abilities scripts

https://make.wordpress.org/core/2026/03/24/client-side-abilities-api-in-wordpress-7-0/

**File:** `includes/admin.php`

The plugin already enqueues its own script module (`wp-ai-workshop-demo-script`) so the settings form renders from the start. In this step you add the two scripts the form depends on — the **WP AI Client** and the **`@wordpress/core-abilities`** module — and wire `@wordpress/core-abilities` in as a dependency of the plugin's script module.

First, replace the `// TODO: Enqueue the wp-ai-client and abilities scripts.` comment in `wp_ai_workshop_demo_admin_enqueue_scripts()` with:

```php
    wp_enqueue_script( 'wp-ai-client' );

    wp_enqueue_script_module( '@wordpress/core-abilities' );
```

Then update the existing `wp_enqueue_script_module( 'wp-ai-workshop-demo-script', … )` call to declare `@wordpress/core-abilities` as a dependency — change its empty dependency array from `array()` to `array( '@wordpress/core-abilities' )`:

```php
    wp_enqueue_script_module(
        'wp-ai-workshop-demo-script',
        plugins_url( 'build/index.js', __DIR__ ),
        array( '@wordpress/core-abilities' ),
        $asset_file['version'],
    );
```

---

---

## 8. Build the Settings Page form

**File:** `src/components/settings-page.jsx`

Import the Abilities API near the top of the file:

```js
const { getAbility, executeAbility } = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const ABILITY = 'wp-ai-workshop-demo/create-post-from-photo';
```

> **Key point:** The `webpackIgnore: true` comment tells webpack/wp-scripts not to try to bundle this import, due to the way it's provided by WordPress. There's an open issue to fix this issue in wp-scripts here: https://github.com/WordPress/gutenberg/pull/76397

### Add an AI welcome message

Use the AI Client to greet the user with a short, AI-generated message when the settings page loads.

First, add `useEffect` to the `@wordpress/element` import:

```js
import { useState, useEffect, useCallback } from '@wordpress/element';
```

Then, inside the `SettingsPage` component, add this `useEffect` after the `useState` declarations (after the `input` state). On mount it asks the AI Client for a single encouraging sentence and shows it in the notice:

```js
    useEffect( () => {
        async function loadInstructionsMessage() {
            let prompt = '';
            prompt += 'A simple sentence encouraging the user to create a WordPress Post from a photo using AI. ';
            prompt += 'Only return the actual sentence. Do not include any additional text or formatting.';
            const text = await wp.aiClient.prompt( prompt ).generateText();
            setNoticeMessage( text );
        }
        loadInstructionsMessage();
    }, [] );
```

### Generate a post via the Ability

The component already renders an **Image URL** field and an optional **angle/tone** field. Replace the `// TODO: Use the Abilities API to execute the 'wp-ai-workshop-demo/create-post-from-photo' ability.` comment in `generateFromInput` with the ability call:

```js
        const ability = getAbility( ABILITY );
        if ( ! ability ) {
            updateNotice( __( 'Whoops, the create-post-from-photo Ability was not found.', 'wp-ai-workshop-demo' ), 'error' );
            return;
        }

        setIsBusy( true );
        updateNotice( __( 'Looking at your image and writing a post… this can take a moment.', 'wp-ai-workshop-demo' ), 'info' );

        try {
            const result = await executeAbility( ABILITY, {
                image_url: input.image_url,
                prompt: input.prompt,
            } );

            if ( result && result.post_id ) {
                const editUrl = `post.php?post=${ result.post_id }&action=edit`;
                updateNotice(
                    <span>
                        { ( result.message || __( 'Post created.', 'wp-ai-workshop-demo' ) ) + ' ' }
                        <a href={ editUrl }>{ __( 'Edit the draft post.', 'wp-ai-workshop-demo' ) }</a>
                    </span>,
                    'success'
                );
            } else {
                updateNotice( result && result.message ? result.message : __( 'Done.', 'wp-ai-workshop-demo' ), 'error' );
            }
        } catch ( err ) {
            updateNotice( __( 'Error during post generation. Check the console for details.', 'wp-ai-workshop-demo' ), 'error' );
            console.error( err );
        } finally {
            setIsBusy( false );
        }
```

Rebuild the JavaScript bundle:

```shell
npm run build
```

Now open **Tools → WP AI Workshop Demo**, paste an image URL, and click **Generate Post**.

---

## 9. Increase the AI Client request timeout

Photo to Post makes two AI calls in a row (vision, then text generation) and also sideloads an image, so a single request can easily run longer than WordPress's default HTTP timeout. The AI Client exposes a `wp_ai_client_default_request_timeout` filter so you can raise it.

**File:** `includes/ai-client.php`

Replace the `// TODO: Add the wp_ai_workshop_demo_set_request_timeout() function to increase the AI Client request timeout.` comment with:

```php
/**
 * Set a custom request timeout for the AI Client.
 *
 * @return int
 */
function wp_ai_workshop_demo_set_request_timeout() {
    return 120;
}
```

**File:** `wp-ai-workshop-demo.php`

Replace the `// TODO: Hook wp_ai_workshop_demo_set_request_timeout into the wp_ai_client_default_request_timeout filter.` comment with:

```php
add_filter( 'wp_ai_client_default_request_timeout', 'wp_ai_workshop_demo_set_request_timeout' );
```

---

## 10. Expose the abilities via the MCP Adapter

Update each Ability's registration to include the `mcp` meta, which exposes it to the MCP Adapter plugin:

```php
'meta' => array(
    'show_in_rest' => true,
    'mcp'          => array(
        'public' => true, // Expose this ability via MCP.
    ),
),
```

These Abilities can now be adapted to MCP Tools, and be driven by an MCP client (e.g. an AI agent) once the MCP Adapter is installed:

- Install the [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases) plugin.
- Create an Application Password for an admin user.
- Configure your MCP client:

```json
{
  "mcpServers": {
    "wordpress-ai-demo": {
      "command": "npx",
      "args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
      "env": {
        "WP_API_URL": "https://yoursite.local/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "your-application-password"
      }
    }
  }
}
```

> **VS Code users:** In VS Code the MCP server block is named simply "servers".

Then ask your MCP client to "create a post from this photo" with an image URL — it will discover and call your `create-post-from-photo` ability.

MCP remote troubleshooting: https://github.com/Automattic/mcp-wordpress-remote/blob/trunk/Docs/troubleshooting.md

---

## Done
