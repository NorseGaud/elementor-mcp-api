=== MCP API for Elementor ===
Contributors: norsegaud
Tags: elementor, rest-api, mcp, ai, api
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 3.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

REST API and MCP tools for AI-driven Elementor page building. Create, edit, and manage Elementor pages programmatically.

== Description ==

MCP API for Elementor exposes a WordPress REST API and optional Model Context Protocol (MCP) tools so AI agents and HTTP clients can build and edit Elementor pages safely.

This plugin is an independent add-on for Elementor. It is not affiliated with or endorsed by Elementor.

Features:

* Full CRUD on Elementor pages, elements, and templates
* Granular element editing without replacing the whole page tree
* Add, remove, duplicate, and move elements
* Global kit management (colors, fonts, and site-wide settings)
* Widget discovery and control schemas
* MCP support via the WordPress Abilities API and WordPress MCP Adapter
* get-instructions MCP tool returns always-current agent guidance from the plugin
* CSS cache flush after visual changes

Requirements:

* Elementor (free or Pro) — required
* WordPress 6.9+ (Abilities API in core; required for MCP tools and declared plugin minimum)
* WordPress MCP Adapter (https://github.com/wordpress/mcp-adapter) — optional; required only if you want the `/wp-json/mcp-api-for-elementor/mcp` endpoint

Authentication uses WordPress Application Passwords (recommended) or cookie auth. Authorization uses standard capabilities (`edit_pages`, `publish_pages`, `manage_options`).

== Installation ==

1. Install and activate Elementor.
2. Upload the plugin zip via Plugins → Add New → Upload Plugin, or install from the WordPress.org directory.
3. Activate MCP API for Elementor.
4. Create an Application Password under Users → Profile → Application Passwords.
5. (Optional) Install and activate the WordPress MCP Adapter plugin to enable MCP tools. Download from https://github.com/wordpress/mcp-adapter

REST base: `/wp-json/mcp-api-for-elementor/v1`
MCP endpoint: `/wp-json/mcp-api-for-elementor/mcp`

== Frequently Asked Questions ==

= Is this an official Elementor plugin? =

No. It is an independent plugin that works with Elementor. It is not affiliated with or endorsed by Elementor.

= Do I need the MCP Adapter? =

No. The REST API works without it. Install WordPress MCP Adapter only if you want MCP tools for AI clients.

= What capabilities are required? =

Page read/write requires `edit_pages` (plus per-page `edit_post`). Publishing requires `publish_pages`. Kit, templates, and CSS flush require `manage_options`.

= How do AI agents get building instructions? =

Call the MCP tool `mcp-api-for-elementor-get-instructions` first. It returns the latest workflow, tool catalog, layout patterns, and gotchas for the installed plugin version. No local skill file is required.

== Changelog ==


= 3.2.0 =

* release workflow fix
* hook lint
* hook lint
* hook lint
* hook lint
* * Add get/update page settings tools for Elementor document Body Style (`_elementor_page_settings`). * REST: GET/PATCH `/page/{id}/settings` with merge + optional `unset` key removal. * MCP: `mcp-api-for-elementor-get-page-settings` and `mcp-api-for-elementor-update-page-settings`.
* readme
* workflow fix

= 3.0.0 =

* Require WordPress 6.9+ (matches Abilities API usage checked by Plugin Check).
* Replace mt_rand/strip_tags/unlink with wp_rand/wp_strip_all_tags/wp_delete_file.
* Require PHP 8.0+ (matches runtime use of PHP 8 APIs).
* Update Tested up to WordPress 7.0.
* Include instructions-guidance.md in the release zip for get-instructions.
* Prefer uploads jail `mcp-api-for-elementor-import/` (legacy `elementor-mcp-import/` still accepted).
* Add `mcp-api-for-elementor-get-instructions` MCP tool (auto tool catalog + static guidance).
* Remove local `agent-skill` install path; instructions are served from the plugin.
* Rename plugin to MCP API for Elementor for WordPress.org trademark compliance.
* Hard cutover of REST namespace, MCP server ID, and ability IDs to `mcp-api-for-elementor`.
* Add WordPress.org `readme.txt` and plugin dependency header for Elementor.

== Upgrade Notice ==

= 3.0.0 =
Requires WordPress 6.9+ and PHP 8.0+. Breaking rename from elementor-mcp-api to mcp-api-for-elementor. Call get-instructions for agent guidance. Media import jail is uploads/mcp-api-for-elementor-import/.
