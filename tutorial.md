# Build your first AI-Powered WordPress plugin

The last two major WordPress releases have included important new features for developers wanting to explore AI in the context of WordPress:

* **The [Abilities API](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/)** — a standard way to register a unit of functionality so that *anything* (REST, the block editor, an AI agent) can discover and run it.
* **The [WordPress AI Client](https://github.com/WordPress/php-ai-client)** — a provider-agnostic PHP library for talking to AI models, so your code doesn't care whether you're using OpenAI, Anthropic, or Google.
* **The [MCP Adapter](https://github.com/WordPress/mcp-adapter)** — which exposes abilities as MCP tools, so an AI agent in a desktop app can call them.

So far, you’ve probably read multiple tutorials on how to use each of these separately. We’ve even covered each of them on the WordPress developer blog:

* [Introducing the WordPress Abilities API](https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/)
* [From Abilities to AI Agents: Introducing the WordPress MCP Adapter](https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/)
* [How to build an image generation plugin with the WordPress AI Client](https://developer.wordpress.org/news/2026/05/how-to-build-an-image-generation-plugin-with-the-wordpress-ai-client/)

The reality is that while each of these is fun to explore, it's when you stitch them together that the magic really happens.

If you've been watching the Core AI projects land and wondering how the pieces fit together in a real plugin, then this post is for you. We're going to build one together, step by step, focusing on how to implement each of the three AI building blocks.

If this is your first time learning about any of these concepts, we recommend going back and working through the three posts linked above, and then coming back here.

*The contents of this tutorial were also presented at a workshop at WordCamp Europe 2026\. If you prefer to learn through video, you can [watch the recording on WordPress.tv](http://WordPress.tv).*

## What we're building

The plugin is called **Photo to Post**, and the idea is straightforward. You give it the URL of an image, and the plugin:

1. **Looks at the image** using an AI vision model and generates a text description.
2. **Creates a draft post** — title and content — based on that image description.
3. **Updates the draft post**, setting the original image as the post's featured image.

The interesting part isn't *what* it does, though. It's *how* it's built. Under the hood, Photo to Post is three composable [WordPress Abilities](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/) that call the [WordPress AI Client](https://github.com/WordPress/php-ai-client):

| Ability | What it does | AI capability |
| :---- | :---- | :---- |
| `describe-image` | Image URL → text description | Vision (image input) |
| `generate-post-from-description` | Description → post title \+ content | Text generation |
| `create-post-from-photo` | Orchestrates the two above, then creates the post | No AI in this one, but it orchestrates the others |

That third ability is the one I'm most excited about, because it demonstrates **ability composition** — one ability calling other abilities via `WP_Ability::execute()`. More on that later.

## Before you start

To be able to run the code for this tutorial, you’re going to need a few things on your local computer:

* A local WordPress environment running version 7.0 or later on PHP 8.1 or higher
* The latest version of [Composer](https://getcomposer.org/) and [Node.js](https://nodejs.org/en) is installed
* API credentials for an AI provider that supports a vision-capable model (more on this later)

To make life easier (and this tutorial shorter), you’ll be building on top of a starter plugin available here: [https://github.com/wptrainingteam/wp-ai-workshop-demo/releases](https://github.com/wptrainingteam/wp-ai-workshop-demo/releases).

Download the latest version of the plugin (1.1.1), install and activate it in your local WordPress development environment.

Once it’s activated, the plugin adds a new admin page under **Tools** called **WP AI Workshop Demo**, which displays a form for managing the Photo to Post process.

![][image1]

It uses the [WordPress DataForm package](https://developer.wordpress.org/news/2026/01/how-to-use-dataform-to-create-plugin-settings-pages/) to render the form, but currently doesn’t provide any functionality.

Since we're going to be building out the functionality of this tutorial, it'll be helpful to install the composer and npm dependencies now. Inside the plugin directory, run:

```bash
composer install
npm install
```

Open up the main plugin file, `wp-ai-workshop-demo.php`, and you’ll see a couple of comments marked as TODO, like this one on line 29:

```
// TODO: Register the ability category and the three Photo to Post ability hooks.
```

This indicates the different areas around the plugin where you’ll build out the functionality. Every time this tutorial asks you to add some code, look for the specific TODO to replace.

## Connecting your site to your AI provider

In order to make it possible for your WordPress site to make use of generative AI functionality via the AI Client, in this case, the process of reading the photo, generating its description, and then generating the post content, you need to connect it to an AI provider.

Since WordPress 7.0, this is possible via the [new Connectors screen](https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/#ai-connectors-screen). Powered by the [Connectors API](https://make.wordpress.org/core/2026/03/18/introducing-the-connectors-api-in-wordpress-7-0/), the Connectors screen provides a WordPress site admin with a single interface for storing external API keys.

Previously, any plugin that added an external connection to WordPress required a place for the WordPress site admin to store the API key to enable the connection. Some of the more common examples of this include:

* Akismet for anti-spam
* Mailchimp for WordPress for adding Mailchimp email support
* Site Kit by Google for Google Analytics information
* WooCommerce payment gateway extensions (e.g., Stripe, PayPal, [Authorize.net](http://Authorize.net))

Plugin developers typically had to manage storing the API key themselves, usually on a Settings page. So if you had 4 different plugins that stored API keys, that meant you had 4 different places to manage them.

The Connectors API and screen bring all that information into one place. Developers can register a Connector for the service their plugin provides, and the Connector appears on the Connectors page, where users can enter and store their API key.

In the context of AI providers, plugins exist to support the three major frontier providers: **OpenAI**, **Anthropic**, and **Google.** From the Connectors page, you can install the plugin for the provider of your choice

Once installed, you can enter and save the API key to enable the AI Client functionality.

If you prefer to use AI models other than those from OpenAI, Anthropic, or Google, plugins exist for several other providers, including [Ollama](https://wordpress.org/plugins/ai-provider-for-ollama/), [OpenRouter](https://wordpress.org/plugins/ai-provider-for-openrouter/), and [Mistral](https://wordpress.org/plugins/ai-provider-for-mistral/). Just make sure your provider uses a **vision-capable** model for the functionality you’ll build in this tutorial**.**

## Building the plugin functionality

With your AI provider connected, you can now start building out all the plugin features.

## Step 1: Register the abilities

An ability is just a registered description of *something your plugin can do*: a label, a description, an input schema, an output schema, a permission check, and a callback that does the actual work. You’ll perform all your Abilities registration inside the `includes/abilities.php`. file.

First, you need to register a category to group your abilities using the `wp_register_ability_category` function. Inside the `wp_ai_workshop_demo_register_ability_categories` function, register the category:

```php
wp_register_ability_category(
	'wp-ai-workshop-demo',
	array(
		'label'       => __( 'WP AI Workshop Demo', 'wp-ai-workshop-demo' ),
		'description' => __( 'Abilities for the WP AI Workshop Demo.', 'wp-ai-workshop-demo' ),
	)
);
```

Then you can register the first ability, `describe-image`, using the `wp_register_ability` function. Notice how much of this is just *describing* the ability — the input and output schemas tell anything that consumes the ability exactly what to send in and what to expect back. Add this to the `wp_ai_workshop_demo_register_describe_image_ability` function:

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

The `generate-post-from-description` and `create-post-from-photo` abilities follow exactly the same pattern — only the schemas and callbacks change.

First, the `wp_ai_workshop_demo_register_generate_post_from_description_ability` function:

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

Followed by the `wp_ai_workshop_demo_register_create_post_from_photo_ability` function:

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

The key thing to internalize here is the Ability shape: **label, description, schemas, permission, callback.** Once it clicks, you'll be registering abilities in your sleep.

So why bother describing everything so formally? Because the `label`, `description`, `input_schema,` and `output_schema` are what let the REST API, other developers, and AI agents all understand your ability without you writing any other code. You describe it once; everything that wants to use it instantly understands what it’s called, what it does, what inputs it expects, and what outputs it will return.

## Step 2: Hook the abilities in

Ability registration is happens via dedicated action hooks, specifically `wp_abilities_api_categories_init` and `wp_abilities_api_init`.

In the main plugin file, `wp-ai-workshop-demo.php`, wire up the functions from the `includes/abilities.php` file to the relevant hook:

```php
add_action( 'wp_abilities_api_categories_init', 'wp_ai_workshop_demo_register_ability_categories' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_describe_image_ability' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_generate_post_from_description_ability' );
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_demo_register_create_post_from_photo_ability' );
```

## Step 3: Check your work over REST

Here's one of my favorite things about the Abilities API — the moment you register an ability with `'show_in_rest' => true`, it's exposed over the REST API to authenticated users, no extra code needed. Make sure your local site has Permalinks set to anything *other* than "Plain", or the REST endpoints won't resolve.

To test this, create an [Application Password](https://developer.wordpress.org/advanced-administration/security/application-passwords/) for your admin user, then test it using something like `curl` in the terminal:

```shell
curl -u 'USERNAME:APPLICATION_PASSWORD' https://yoursite.local/wp-json/wp-abilities/v1/abilities | jq
```

You should see a JSON response which includes the three core WordPress abilities, followed by your three `wp-ai-workshop-demo/*` abilities.

This is incredibly reassuring as a checkpoint. You haven't written any AI generation code for these Abilities yet, but you already have three discoverable, schema-described endpoints. That's the foundation on which everything else is built.

## Step 4: Describe the image (your first AI call)

Now for the fun part. You will need to implement the functionality for all Ability execute callback functions, which is where you’ll connect to and use the AI providers.

Start with `wp_ai_workshop_demo_describe_image`, which executes the `wp-ai-workshop-demo/describe-image` Ability. This happens in the `includes/vision.php`. File, and it’s where you make your first call to the WordPress AI Client.

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

Look at how readable that AI Client call is. `wp_ai_client_prompt()` gives you a fluent builder: add your prompt text, attach a file, ask for text back. And because the AI Client is provider-agnostic, this exact code works whether the user configured OpenAI, Anthropic, or Google.

You'll also need to add the helper, `wp_ai_workshop_demo_image_url_to_data_uri()` function. This fetches the remote image with `wp_remote_get()` and base64-encodes it into a data URI to send to the AI model.

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

There are two things to note here:

* `wp_ai_client_prompt()->generate_text()` *returns* a `WP_Error` on failure — it doesn't throw an exception. The AI Client's WordPress wrapper catches exceptions for you and hands back a `WP_Error`. So always check for `is_wp_error()`.
* Vision providers such as Anthropic want the image sent **inline as base64**, not as a remote URL. That's why you fetch it and build a data URI. Sending a URL works on some providers and fails on others — the data URI makes it the most portable.

## Step 5: Generate the post copy

The next Ability execute callback to update, `wp_ai_workshop_demo_generate_post_from_description`, is inside `includes/content.php`. This function makes a second AI call that turns the description into a post title and body. Notice how the prompt asks the AI to return a specific JSON object, making it easier to return the title and content.

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

You'll also add a `wp_ai_workshop_demo_decode_json_response()` helper that deliberately strips any markdown code fences the model might wrap around the JSON before decoding.

````php
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
````

You might wonder why you’re adding this when the prompt explicitly says not to. Here's the thing about asking a language model for clean JSON: it'll mostly comply, until it doesn’t. AI models are not deterministic, meaning they don’t follow a fixed set of rules. Rather, they recognize patterns and calculate the mathematical likelihood of the next output. Because of this, asking an AI the exact same question can produce different responses each time. So, adding a defensive check here and parsing the AI's data ensures you get the correct response type every time.

## Step 6: Compose the abilities (the good bit)

Now for the really interesting part. Inside the `includes/post.php` file is the `wp_ai_workshop_demo_create_post_from_photo`, which is the core of the plugin functionality.  Here, this orchestrator function can call the other two abilities.

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

Do you see what's happening? `wp_get_ability()` fetches an ability by name, and `->execute()` runs it. Your orchestrator never touches the AI Client directly; it just composes two abilities that already know how to do their jobs, then hands off to a `wp_ai_workshop_demo_create_post()` helper that calls `wp_insert_post()` to create the post and sideloads the featured image with `media_sideload_image()`. Now would be a good time to add these helper functions.

```php
/**
 * Create a draft post and set its featured image from an image URL.
 *
 * @param string $title The Post title.
 * @param string $content The Post content.
 * @param string $image_url The external image url.
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
 *
 * @param string $post_id ID of the Post.
 * @param string $image_url URL of the external image.
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

This is one of the greatest things about Abilities. Once your functionality is expressed as abilities, you and other developers extending your functionality can build *new* functionality by combining existing abilities — like LEGO bricks. It's the same instinct that made hooks and filters so powerful, applied to a new generation of features.

## Step 7: Update the Admin Interface

At this point, you’ve completed most of the core plugin functionality. Now we just need to make it available to the site owner. As mentioned earlier, the plugin ships with a settings page, so we need to connect it to the Abilities we just registered.

The first step is to enqueue the two scripts the form depends on in `includes/admin.php`:

```php
wp_enqueue_script( 'wp-ai-client' );
wp_enqueue_script_module( '@wordpress/core-abilities' );
```

* The wp-ai-client script enables the AI Client’s JavaScript API. You’ll use this in the form to show an AI-generated message to the user.
* The `@wordpress/core-abilities` enqueues the Abilities JavaScript API. You’ll use this to find and execute the Abilities you registered in PHP.

Next, you need to update the plugin’s own script enqueuing to load it as a script module, which depends on `@wordpress/core-abilities`:

```javascript
   wp_enqueue_script_module(
        'wp-ai-workshop-demo-script',
        plugins_url( 'build/index.js', __DIR__ ),
        array( '@wordpress/core-abilities' ),
        $asset_file['version'],
    );
```


Now open the file `src/components/settings-page.jsx`, where you’ll update the settings page interface.

Before implementing the photo-to-post functionality, update the welcome message to use the AI Client to greet the user with a short, AI-generated message when the settings page loads. You’ll implement this via a useEffect hook.

First, add \`useEffect\` to the \`@wordpress/element\` import:

```javascript
import { useState, useEffect, useCallback } from '@wordpress/element';
```

Then, inside the `SettingsPage` component, add the `useEffect` after the variable declarations:

```javascript
   useEffect( () => {
        async function loadInstructionsMessage() {
            let prompt = '';
            prompt += 'A simple sentence encouraging the user to create a WordPress Post from a photo using AI. ';
            prompt += 'The user can paste an image URL and (optionally) an angle/tone to generate the post. ';
            prompt += 'Only return the actual sentence. Do not include any additional text or formatting.';
            const text = await wp.aiClient.prompt( prompt ).generateText();
            setNoticeMessage( text );
        }
        loadInstructionsMessage();
    }, [] );
```

Now we can move on to the core plugin functionality.

Step one is to import the `getAbility` and `executeAbility` functions from the `@wordpress/abilities` package. This allows you to get and execute your registered Abilities. At the same time, you can create a constant that holds the name of the `create-post-from-photo` Ability.

```javascript
const { getAbility, executeAbility } = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const ABILITY = 'wp-ai-workshop-demo/create-post-from-photo';
```

**Heads up:** that `/* webpackIgnore: true */` comment matters. It tells `wp-scripts` not to try to bundle the import, because WordPress provides it at runtime. There's an [open PR in Gutenberg](https://github.com/WordPress/gutenberg/pull/76397) to smooth this over, but for now, the comment is what keeps the build happy.

Then, inside the `generateFromInput` function, ese the Abilities JavaScript API to execute the `create-post-from-photo` ability.

```javascript
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

The key detail to focus on here is that, just like in PHP, you get an Ability by its name, check that it’s valid, and then execute it with the required inputs. No additional code is needed to perform the same action in both PHP and JavaScript.

In your terminal, run `npm run build` to rebuild the assets.

Then head to **Tools → WP AI Workshop Demo**, paste in an image URL, and click **Generate Post**. The *same ability* you tested over `curl` is now driving a dashboard UI. Write once, use everywhere.

### One practical tweak: the timeout

Photo to Post makes two AI calls in a row and then sideloads an image, so a single request can easily run longer than WordPress's default HTTP timeout. Fortunately, the AI Client exposes a filter for increasing the timeout value:

```php
function wp_ai_workshop_demo_set_request_timeout() {
	return 120;
}
add_filter( 'wp_ai_client_default_request_timeout', 'wp_ai_workshop_demo_set_request_timeout' );
```

This is one of those things you don't think about until your first real request times out at the worst possible moment.

## Step 8: Hand it to an AI agent (MCP)

Here's where it gets a little bit magical. We've got abilities that work over REST and in the dashboard. With one small change, they can also be driven by an AI agent in a desktop app.

Inside the `wp-ai-workshop-demo/create-post-from-photo` Ability registration, update `mcp` meta array to add support for MCP.

```php
'meta' => array(
	'show_in_rest' => true,
	'mcp'          => array(
		'public' => true, // Expose this ability via MCP.
	),
),
```

Now download, install, and activate the [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases) plugin on your site. This will create a default MCP server on the WordPress site.

Using the same Application Password you created earlier, you can configure your AI agent with the default WordPress MCP server details. Below is an example of how you would do this inside the Claude `~/.config/Claude/claude_desktop_config.json` file :

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

(If you're a VS Code user, the MCP block is named `servers` rather than `mcpServers`.)

Now ask your MCP client to *"create a post from this photo"* with an image URL — and watch it discover and call your `create-post-from-photo` ability all on its own. The same ability. Three different front doors: REST, the dashboard, and an AI agent. We never wrote separate code for any of them.

## So what did we actually learn?

When I think back to that question I asked at the start — *what does it take to build a genuinely useful AI feature into WordPress?* — the answer turned out to be more reassuring than I expected.

You don't need to reinvent anything. You register **abilities** that describe what your plugin can do. You let the **AI Client** handle the messy provider-specific details. You **compose** small abilities into bigger ones. And then REST, the block editor, and MCP agents all come along for the ride, for free.

That's a pattern I think we'll be using for years. It feels a lot like the first time I really *got* hooks and filters — a small set of ideas that quietly change how you build everything afterwards.

If you want to build along, the full plugin and a step-by-step `WORKSHOP.md` live at [`wptrainingteam/wp-ai-workshop-demo`](https://github.com/wptrainingteam/wp-ai-workshop-demo). Clone it, fill in the TODOs, and turn one of your photos into a post.

And if you build something with it — or get gloriously stuck — I'd love to hear about it in the comments. That's the best part of putting these things out into the open. Now go and build your first AI-powered plugin. 🚀
