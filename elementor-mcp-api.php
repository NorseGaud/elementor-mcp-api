<?php
/**
 * Plugin Name: Elementor MCP API
 * Description: REST API + MCP tools for AI-driven Elementor page building. Exposes endpoints to create, read, update pages, elements, templates, and global settings programmatically. Compatible with WordPress MCP Adapter.
 * Version: 2.0.1
 * Author: Jérémy Christillin (bvisible) & Nathan Pierce (NorseGaud)
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-3.0
 */

if (!defined('ABSPATH')) exit;

define('ELEMENTOR_MCP_API_VERSION', '2.0.1');
define('ELEMENTOR_MCP_API_PATH', plugin_dir_path(__FILE__));

// Load core includes
require_once ELEMENTOR_MCP_API_PATH . 'includes/class-permissions.php';
require_once ELEMENTOR_MCP_API_PATH . 'includes/class-element-factory.php';
require_once ELEMENTOR_MCP_API_PATH . 'includes/class-elementor-data.php';
require_once ELEMENTOR_MCP_API_PATH . 'includes/class-rest-controller.php';

// ── REST API ────────────────────────────────────────────────
add_action('rest_api_init', function () {
    if (!did_action('elementor/loaded')) return;

    $controller = new ElementorMcpApi\REST_Controller();
    $controller->register_routes();
});

// ── MCP Abilities (auto-detected if Abilities API is active) ─
// These hooks only fire if the Abilities API plugin is active.
// No conditional check needed — WordPress ignores hooks for actions that never fire.
add_action('wp_abilities_api_categories_init', function () {
    if (!did_action('elementor/loaded')) return;

    require_once ELEMENTOR_MCP_API_PATH . 'includes/class-abilities-provider.php';
    ElementorMcpApi\Abilities_Provider::register_category();
});

add_action('wp_abilities_api_init', function () {
    if (!did_action('elementor/loaded')) return;

    if (!class_exists('ElementorMcpApi\\Abilities_Provider')) {
        require_once ELEMENTOR_MCP_API_PATH . 'includes/class-abilities-provider.php';
    }
    ElementorMcpApi\Abilities_Provider::register();
});

// Dedicated MCP server: /wp-json/elementor-mcp-api/mcp
// Fires only when WordPress MCP Adapter is active.
add_action('mcp_adapter_init', function ($adapter) {
    require_once ELEMENTOR_MCP_API_PATH . 'includes/class-mcp-server.php';
    ElementorMcpApi\Mcp_Server::register($adapter);
});

// ── Post Meta Registration ──────────────────────────────────
// Keep _elementor_data out of core REST; use elementor-mcp-api/v1 endpoints instead.
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
