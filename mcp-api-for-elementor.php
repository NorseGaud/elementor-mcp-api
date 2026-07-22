<?php
/**
 * Plugin Name: MCP API for Elementor
 * Description: REST API + MCP tools for AI-driven Elementor page building. Exposes endpoints to create, read, update pages, elements, templates, and global settings programmatically. Compatible with WordPress MCP Adapter.
 * Version: 3.2.0
 * Author: Jérémy Christillin (bvisible) & Nathan Pierce (NorseGaud)
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: elementor
 * Text Domain: mcp-api-for-elementor
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) exit;

define('MCP_API_FOR_ELEMENTOR_VERSION', '3.2.0');
define('MCP_API_FOR_ELEMENTOR_PATH', plugin_dir_path(__FILE__));

// Load core includes
require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-permissions.php';
require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-element-factory.php';
require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-elementor-data.php';
require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-rest-controller.php';
require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-instructions-composer.php';

// ── REST API ────────────────────────────────────────────────
add_action('rest_api_init', function () {
    if (!did_action('elementor/loaded')) return;

    $controller = new McpApiForElementor\REST_Controller();
    $controller->register_routes();
});

// ── MCP Abilities (auto-detected if Abilities API is active) ─
// These hooks only fire if the Abilities API plugin is active.
// No conditional check needed — WordPress ignores hooks for actions that never fire.
add_action('wp_abilities_api_categories_init', function () {
    if (!did_action('elementor/loaded')) return;

    require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-abilities-provider.php';
    McpApiForElementor\Abilities_Provider::register_category();
});

add_action('wp_abilities_api_init', function () {
    if (!did_action('elementor/loaded')) return;

    if (!class_exists('McpApiForElementor\\Abilities_Provider')) {
        require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-abilities-provider.php';
    }
    McpApiForElementor\Abilities_Provider::register();
});

// Dedicated MCP server: /wp-json/mcp-api-for-elementor/mcp
// Fires only when WordPress MCP Adapter is active.
add_action('mcp_adapter_init', function ($adapter) {
    require_once MCP_API_FOR_ELEMENTOR_PATH . 'includes/class-mcp-server.php';
    McpApiForElementor\Mcp_Server::register($adapter);
});

// ── Post Meta Registration ──────────────────────────────────
// Keep _elementor_data out of core REST; use mcp-api-for-elementor/v1 endpoints instead.
add_action('init', function () {
    register_post_meta('page', '_elementor_data', [
        'show_in_rest' => false,
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => function () {
            return current_user_can('edit_pages');
        },
    ]);
});
