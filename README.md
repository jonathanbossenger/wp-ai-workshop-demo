# WP AI Workshop Demo

A demonstration plugin showcasing the integration and capabilities of the WordPress AI Client library. This plugin demonstrates how to build AI-powered features in WordPress using both PHP and JavaScript.

## Description

WP AI Workshop Demo provides a practical example of integrating AI capabilities into WordPress. Its core feature — **Photo to Post** — takes an image URL, describes the image with an AI vision model, writes a blog post about it, and creates a draft post with the photo as the featured image. It demonstrates:

- AI vision (image understanding) using the WP AI Client
- AI text generation for post title and Block Editor content
- Composing multiple WordPress Abilities (one ability calling others via `WP_Ability::execute()`)
- Exposing abilities over REST and MCP
- An admin interface (React + DataViews) that drives an ability from the browser

See [`WORKSHOP.md`](WORKSHOP.md) to build this plugin step by step.

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Composer
- Node.js and npm (for building JavaScript assets)
- API credentials for an AI provider (configured through the WP AI Client)

## Installation

1. Clone or download this plugin to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone git@github.com:jonathanbossenger/wp-ai-workshop-demo.git
   ```

2. Install PHP dependencies:
   ```bash
   cd wp-ai-workshop-demo
   composer install
   ```

3. Install JavaScript dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```

4. Activate the plugin through the WordPress admin interface or via WP-CLI:
   ```bash
   wp plugin activate wp-ai-workshop-demo
   ```

5. Configure your AI provider credentials as required by the WP AI Client library

## Usage

### Admin Interface

Access the demo tools page from the WordPress admin:

1. Navigate to **Tools > WP AI Workshop Demo** in your WordPress admin
2. Use the interface to test AI capabilities

## License

GPL-2.0-or-later

## Author

Jonathan Bossenger
