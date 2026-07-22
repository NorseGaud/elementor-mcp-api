=== MCP API for Elementor ===
Contributors: norsegaud
Tags: elementor, rest-api, mcp, ai, api
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.1.5
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
* WordPress MCP Adapter — optional; required only if you want the `/wp-json/mcp-api-for-elementor/mcp` endpoint

Authentication uses WordPress Application Passwords (recommended) or cookie auth. Authorization uses standard capabilities (`edit_pages`, `publish_pages`, `manage_options`).

== Installation ==

1. Install and activate Elementor.
2. Upload the plugin zip via Plugins → Add New → Upload Plugin, or install from the WordPress.org directory.
3. Activate MCP API for Elementor.
4. Create an Application Password under Users → Profile → Application Passwords.
5. (Optional) Install and activate the WordPress MCP Adapter plugin to enable MCP tools.

REST base: `/wp-json/mcp-api-for-elementor/v1`
MCP endpoint: `/wp-json/mcp-api-for-elementor/mcp`

== Frequently Asked Questions ==

= Is this an official Elementor plugin? =

No. It is an independent plugin that works with Elementor. It is not affiliated with or endorsed by Elementor.

= Do I need the MCP Adapter? =

No. The REST API works without it. Install WordPress MCP Adapter only if you want MCP tools for AI clients.

= What capabilities are required? =

Page read/write requires `edit_pages` (plus per-page `edit_post`). Publishing requires `publish_pages`. Kit, templates, and CSS flush require `manage_options`.

= Will my old elementor-mcp-api URLs still work? =

No. Version 2.1.1+ uses the `mcp-api-for-elementor` namespace and tool names only. Update clients and MCP configs.

= How do AI agents get building instructions? =

Call the MCP tool `mcp-api-for-elementor-get-instructions` first. It returns the latest workflow, tool catalog, layout patterns, and gotchas for the installed plugin version. No local skill file is required.

== Changelog ==

= 2.1.5 =
* Require WordPress 6.9+ (matches Abilities API usage checked by Plugin Check).
* Replace mt_rand/strip_tags/unlink with wp_rand/wp_strip_all_tags/wp_delete_file.

= 2.1.4 =
* Require PHP 8.0+ (matches runtime use of PHP 8 APIs).
* Update Tested up to WordPress 7.0.
* Include instructions-guidance.md in the release zip for get-instructions.
* Prefer uploads jail `mcp-api-for-elementor-import/` (legacy `elementor-mcp-import/` still accepted).

= 2.1.3 =
* Add `mcp-api-for-elementor-get-instructions` MCP tool (auto tool catalog + static guidance).
* Remove local `agent-skill` install path; instructions are served from the plugin.

= 2.1.2 =
* Rename plugin to MCP API for Elementor for WordPress.org trademark compliance.
* Hard cutover of REST namespace, MCP server ID, and ability IDs to `mcp-api-for-elementor`.
* Add WordPress.org `readme.txt` and plugin dependency header for Elementor.

== Upgrade Notice ==

= 2.1.5 =
Requires WordPress 6.9 or later.

= 2.1.4 =
Requires PHP 8.0+. Media import jail path is now `uploads/mcp-api-for-elementor-import/` (legacy path still works).

= 2.1.3 =
New MCP tool `get-instructions` replaces the local agent skill. Call it before Elementor page work.

= 2.1.2 =
Breaking rename: update REST/MCP URLs and tool names from `elementor-mcp-api` to `mcp-api-for-elementor`.
