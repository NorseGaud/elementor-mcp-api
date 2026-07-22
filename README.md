# MCP API for Elementor

WordPress plugin that exposes a REST API + MCP (Model Context Protocol) abilities for AI-driven Elementor page editing.

Build, edit, and manage Elementor pages programmatically — designed to be used by AI agents (Claude, GPT, etc.) or any HTTP client.

> **Breaking rename:** Formerly “Elementor MCP API” (`elementor-mcp-api`). Public REST/MCP paths, ability IDs, and tool names now use `mcp-api-for-elementor`. Update clients and `mcp.json` configs. The GitHub repository is `mcp-api-for-elementor`.

Official WordPress Marketplace Listing: [https://wordpress.org/plugins/mcp-api-for-elementor/](https://wordpress.org/plugins/mcp-api-for-elementor/)


## Features

- **Full CRUD** on Elementor pages, elements, and templates
- **Granular element editing** — update a single widget's settings without touching the rest
- **Element operations** — add, remove, duplicate, move elements in the page tree
- **Global kit management** — read/write colors, fonts, and site-wide settings
- **Widget discovery** — list all available widgets and get their control schemas
- **MCP protocol support** — auto-registers 24 abilities via the core [Abilities API](https://developer.wordpress.org/apis/abilities-api/) when [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) is active, including `get-instructions` for always-current agent guidance
- **CSS cache management** — flush Elementor CSS after changes

## Requirements

- **WordPress 6.9+** (the [Abilities API](https://developer.wordpress.org/apis/abilities-api/) is in core — the standalone [abilities-api](https://github.com/WordPress/abilities-api) plugin repo is archived)
- PHP 8.0+
- Elementor (free or Pro)
- For MCP (Cursor / Claude / etc.): [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin (required — without it `/wp-json/mcp-api-for-elementor/mcp` will 404)
- Authentication: WordPress Application Passwords (recommended) or cookie auth
- Authorization: page read/write requires `edit_pages` (+ per-page `edit_post`); publish requires `publish_pages`; kit/templates/CSS flush require `manage_options`

## API & MCP Tools

REST base: `https://your-site.com/wp-json/mcp-api-for-elementor/v1`  
MCP tools use the names below exactly as shown in the client (ability `mcp-api-for-elementor/foo` → tool `mcp-api-for-elementor-foo`). See [MCP Integration](#mcp-integration).

### Pages

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| — | — | `mcp-api-for-elementor-get-instructions` | Latest agent instructions (workflow, tool catalog, patterns, gotchas) |
| GET | `/pages` | `mcp-api-for-elementor-list-pages` | List WordPress pages with Elementor status |
| GET | `/page/{id}/structure` | `mcp-api-for-elementor-get-page-structure` | Compact page tree (IDs, types, hints) |
| GET | `/page/{id}` | `mcp-api-for-elementor-get-page-data` | Full Elementor element tree |
| PUT | `/page/{id}` | `mcp-api-for-elementor-save-page-data` | Replace the full element tree |
| PATCH | `/page/{id}/meta` | `mcp-api-for-elementor-update-page-meta` | Update title, slug, excerpt, status, Yoast SEO |
| GET | `/page/{id}/settings` | `mcp-api-for-elementor-get-page-settings` | Get Elementor page/document settings (Body Style) |
| PATCH | `/page/{id}/settings` | `mcp-api-for-elementor-update-page-settings` | Merge page settings; optional `unset` keys; flushes post CSS |
| POST | `/page` | `mcp-api-for-elementor-create-page` | Create a page with optional Elementor content |
| POST | `/build-page` | `mcp-api-for-elementor-build-page` | Create or update a full page (optional image import) |

### Elements

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| GET | `/page/{id}/element/{eid}` | `mcp-api-for-elementor-get-element` | Get one element’s full data |
| PATCH | `/page/{id}/element/{eid}` | `mcp-api-for-elementor-update-element` | Merge settings into an element |
| POST | `/page/{id}/element` | `mcp-api-for-elementor-add-element` | Add a container or widget |
| DELETE | `/page/{id}/element/{eid}` | `mcp-api-for-elementor-remove-element` | Remove an element (and children) |
| POST | `/page/{id}/element/{eid}/duplicate` | `mcp-api-for-elementor-duplicate-element` | Clone an element with new IDs |
| POST | `/page/{id}/element/{eid}/move` | `mcp-api-for-elementor-move-element` | Move an element to a new parent/position |
| — | — | `mcp-api-for-elementor-generate-element` | Build well-formed element JSON via the Element Factory (MCP only) |

### Templates

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| GET | `/templates` | `mcp-api-for-elementor-list-templates` | List Theme Builder templates and conditions |
| POST | `/template` | `mcp-api-for-elementor-create-template` | Create a header/footer/single/archive/etc. template |

### Global Settings

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| GET | `/kit` | `mcp-api-for-elementor-get-kit` | Read global kit settings |
| PUT | `/kit` | `mcp-api-for-elementor-update-kit` | Merge global kit settings (flushes CSS) |

### Widgets

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| GET | `/widgets` | `mcp-api-for-elementor-list-widgets` | List registered Elementor widgets |
| GET | `/widget/{name}/schema` | `mcp-api-for-elementor-get-widget-schema` | Control schema for a widget type |
| GET | `/widget/{name}/defaults` | — | Ready-to-use element JSON with defaults (REST only) |

### Cache & media

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| POST | `/flush-css` | `mcp-api-for-elementor-flush-css` | Flush Elementor CSS cache |
| POST | `/media/import` | — | Import an image from the upload jail (REST only; also via `mcp-api-for-elementor-build-page`) |

## Quick Start

```bash
API="https://your-site.com/wp-json/mcp-api-for-elementor/v1"
AUTH="username:your-application-password"

# List pages
curl -s -u "$AUTH" "$API/pages"

# Get page structure (always start here)
curl -s -u "$AUTH" "$API/page/8/structure"

# Update an element's title color
curl -s -X PATCH -u "$AUTH" -H "Content-Type: application/json" \
  -d '{"settings":{"title_color":"#333333"}}' \
  "$API/page/8/element/f8703b57"

# Flush CSS after changes
curl -s -X POST -u "$AUTH" "$API/flush-css"
```

## MCP Integration

This plugin can expose its capabilities via the Model Context Protocol for direct AI agent integration:

1. Use **WordPress 6.9+** — the [Abilities API](https://developer.wordpress.org/apis/abilities-api/) ships in core ([standalone plugin archived](https://github.com/WordPress/abilities-api/issues/160); no separate Abilities install needed)
2. Install and activate the official [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin  
   Download the latest `mcp-adapter.zip` from [Releases](https://github.com/WordPress/mcp-adapter/releases) → Plugins → Add New → Upload Plugin → Activate
3. Activate this plugin — it auto-registers 24 abilities and a dedicated MCP server (no extra WordPress config)
4. Create an Application Password for a user with the capabilities you need (admin recommended)
5. Verify the MCP route exists (must not 404):

   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" https://your-site.com/wp-json/mcp-api-for-elementor/mcp
   ```

   A working install returns something other than `404`. If you still get `404`, MCP Adapter is missing/inactive — the REST API under `/wp-json/mcp-api-for-elementor/v1` can work while MCP tools do not.
6. Add the server to your client `mcp.json` (see below), then reload MCP servers in the client
7. Call **`mcp-api-for-elementor-get-instructions` first** before page work — it returns the latest workflow, tool catalog, patterns, and gotchas for this plugin version (no local skill file required)

MCP endpoint: `https://your-site.com/wp-json/mcp-api-for-elementor/mcp`

This plugin registers its own MCP Adapter server on that path (hook: `mcp_adapter_init`). The adapter’s generic default server (`/wp-json/mcp/mcp-adapter-default-server`) is a useful smoke test that MCP Adapter itself is active, but is not required for these Elementor tools. The full REST ↔ MCP mapping is in [API & MCP Tools](#api--mcp-tools).

### Configure `mcp.json`

MCP clients (Cursor, Claude Desktop, VS Code, etc.) read a JSON config that registers servers. Use the HTTP bridge with [`@automattic/mcp-wordpress-remote`](https://www.npmjs.com/package/@automattic/mcp-wordpress-remote):

```json
{
  "mcpServers": {
    "mcp-api-for-elementor": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://your-site.com/wp-json/mcp-api-for-elementor/mcp",
        "WP_API_USERNAME": "your-wp-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx",
        "OAUTH_ENABLED": "false"
      }
    }
  }
}
```

Replace:

- `WP_API_URL` — your site’s Elementor MCP endpoint (`/wp-json/mcp-api-for-elementor/mcp`) — must be the real hostname, not the `your-site.com` placeholder
- `WP_API_USERNAME` — WordPress username
- `WP_API_PASSWORD` — Application Password (spaces are fine)
- `OAUTH_ENABLED` — set to `"false"` when using Application Passwords (required by `@automattic/mcp-wordpress-remote`)

If Cursor shows a green status but **“No tools, prompts, or resources”**, the proxy likely cached a failed init (common right after installing MCP Adapter). Toggle the server off/on or reload MCP servers so it reconnects against the live `/mcp` route.

---

## Important Notes

- **Sequential PATCH calls**: Never run multiple PATCH calls in parallel on the same page. Each PATCH loads, modifies, and saves the full page — parallel calls overwrite each other. Cross-page parallelism is safe.
- **Flush CSS**: Always call `/flush-css` after visual changes — Elementor caches CSS aggressively. Requires `manage_options`.
- **Element IDs**: Always provide valid 8-character hex IDs when creating elements.
- **PATCH merges settings**: Only send the settings you want to change, not the full settings object.
- **Default page status**: `POST /page` and `POST /build-page` default to `draft`. Publishing requires `publish_pages`.
- **Media import jail**: `POST /media/import` only accepts real image files under `wp-content/uploads/mcp-api-for-elementor-import/` (legacy `elementor-mcp-import/` still accepted).
- **MCP tools**: Exposed on `/wp-json/mcp-api-for-elementor/mcp` (authenticated Application Password session; dedicated admin password recommended).

## License

GPL-3.0 — see [LICENSE](LICENSE)

---

## Installation

### WordPress Marketplace

[https://wordpress.org/plugins/mcp-api-for-elementor/](https://wordpress.org/plugins/mcp-api-for-elementor/)

### Manual Installation

1. Download or clone this repository into `wp-content/plugins/` and use the plugin slug folder name:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/NorseGaud/mcp-api-for-elementor.git
   ```
2. Activate the plugin in WordPress admin
3. Create an Application Password in **Users → Your Profile → Application Passwords**

## Development

### Requirements

| Tool | Version | Notes |
|------|---------|--------|
| PHP | 8.0+ (8.1 recommended) | Same floor as the plugin; CI uses 8.1 |
| [Composer](https://getcomposer.org/) | 2.x | Dev deps: PHPCS, WPCS, PHPUnit |
| Git | any recent | Pre-commit hook lives in `.githooks/` |
| Python 3 | optional | Only for `upload-sftp.py` (`pip install paramiko`) |

Runtime stack for local testing is unchanged: WordPress 6.9+, Elementor, and [MCP Adapter](https://github.com/WordPress/mcp-adapter) if you exercise MCP.

### Setup

```bash
git clone https://github.com/NorseGaud/mcp-api-for-elementor.git
cd mcp-api-for-elementor
composer install
git config core.hooksPath .githooks
```

`composer install` creates `vendor/` (gitignored), including `vendor/bin/phpcs`. Without it, the pre-commit hook fails with a clear error.

Point `core.hooksPath` at `.githooks` once per clone so PHPCS runs on staged plugin PHP before each commit.

### Lint and tests

```bash
composer lint        # PHPCS (WordPress Coding Standards via phpcs.xml.dist)
composer lint:fix   # Auto-fix what PHPCS can
composer test        # PHPUnit (phpunit.xml.dist)
```

CI on the `edge` branch also runs PHP syntax checks, PHPCS, PHPUnit, and WordPress Plugin Check (strict) against a release-shaped package.

### Pre-commit

`.githooks/pre-commit` lints staged files under `mcp-api-for-elementor.php` and `includes/**` with the same ruleset as CI. Install deps and enable the hooks path (see [Setup](#setup)) before committing.

### Deploy over SFTP

Use `upload-sftp.py` from the repo root to push the plugin into a remote WordPress `wp-content/plugins/` directory. Requires [paramiko](https://www.paramiko.org/) (`pip install paramiko`).

Credentials are read from environment variables (nothing is stored in the script):

| Variable | Required | Description |
|----------|----------|-------------|
| `SFTP_HOST` | yes | SFTP hostname |
| `SFTP_USER` | yes | SFTP username |
| `SFTP_PASS` | yes | SFTP password |
| `SFTP_PORT` | no | Port (default `22`) |

```bash
SFTP_HOST=sftp.example.com SFTP_PORT=32022 \
SFTP_USER=myuser SFTP_PASS='secret' \
python3 upload-sftp.py
```

The script locates `wp-content/plugins`, uploads into `mcp-api-for-elementor/`, and overwrites matching remote files on re-run. It skips `.git`, `.gitignore`, and the upload script itself. Remote files that no longer exist locally are not deleted.