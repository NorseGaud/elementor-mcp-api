<?php
/**
 * Plugin Name: MCP API for Elementor
 * Description: REST API + MCP tools for AI-driven Elementor page building. Exposes endpoints to create, read, update pages, elements, templates, and global settings programmatically. Compatible with WordPress MCP Adapter.
 * Version: 3.3.1
 * Author: Jérémy Christillin (bvisible) & Nathan Pierce (NorseGaud)
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: elementor
 * Text Domain: mcp-api-for-elementor
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) exit;

define('MCPAPFOE_VERSION', '3.3.1');
define('MCPAPFOE_PATH', plugin_dir_path(__FILE__));

// Load core includes
require_once MCPAPFOE_PATH . 'includes/class-permissions.php';
require_once MCPAPFOE_PATH . 'includes/class-element-factory.php';
require_once MCPAPFOE_PATH . 'includes/class-elementor-data.php';
require_once MCPAPFOE_PATH . 'includes/class-rest-controller.php';
require_once MCPAPFOE_PATH . 'includes/class-instructions-composer.php';

// ── REST API ────────────────────────────────────────────────
add_action('rest_api_init', 'mcpapfoe_register_rest_routes');

/**
 * Register REST routes once Elementor is loaded.
 */
function mcpapfoe_register_rest_routes(): void {
    if (!did_action('elementor/loaded')) {
        return;
    }

    $controller = new McpApiForElementor\REST_Controller();
    $controller->register_routes();
}

// ── MCP Abilities (auto-detected if Abilities API is active) ─
// These hooks only fire if the Abilities API plugin is active.
// No conditional check needed — WordPress ignores hooks for actions that never fire.
add_action('wp_abilities_api_categories_init', 'mcpapfoe_register_ability_category');

/**
 * Register the plugin ability category.
 */
function mcpapfoe_register_ability_category(): void {
    if (!did_action('elementor/loaded')) {
        return;
    }

    require_once MCPAPFOE_PATH . 'includes/class-abilities-provider.php';
    McpApiForElementor\Abilities_Provider::register_category();
}

add_action('wp_abilities_api_init', 'mcpapfoe_register_abilities');

/**
 * Register MCP abilities once Elementor is loaded.
 */
function mcpapfoe_register_abilities(): void {
    if (!did_action('elementor/loaded')) {
        return;
    }

    if (!class_exists('McpApiForElementor\\Abilities_Provider')) {
        require_once MCPAPFOE_PATH . 'includes/class-abilities-provider.php';
    }
    McpApiForElementor\Abilities_Provider::register();
}

// Dedicated MCP server: /wp-json/mcp-api-for-elementor/mcp
// Fires only when WordPress MCP Adapter is active.
add_action('mcp_adapter_init', 'mcpapfoe_register_mcp_server');

/**
 * Register the dedicated MCP Adapter server.
 *
 * @param object $adapter MCP Adapter instance.
 */
function mcpapfoe_register_mcp_server($adapter): void {
    require_once MCPAPFOE_PATH . 'includes/class-mcp-server.php';
    McpApiForElementor\Mcp_Server::register($adapter);
}

// ── Post Meta Registration ──────────────────────────────────
// Keep _elementor_data out of core REST; use mcp-api-for-elementor/v1 endpoints instead.
add_action('init', 'mcpapfoe_register_post_meta');

/**
 * Register Elementor data post meta with auth callback.
 */
function mcpapfoe_register_post_meta(): void {
    register_post_meta('page', '_elementor_data', [
        'show_in_rest'  => false,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => 'mcpapfoe_elementor_data_meta_auth',
    ]);
}

/**
 * Auth callback for _elementor_data post meta.
 */
function mcpapfoe_elementor_data_meta_auth(): bool {
    return current_user_can('edit_pages');
}
