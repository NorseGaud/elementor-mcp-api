# MCP API for Elementor

WordPress plugin that exposes a REST API + MCP (Model Context Protocol) abilities for AI-driven Elementor page editing.

Build, edit, and manage Elementor pages programmatically — designed to be used by AI agents (Claude, GPT, etc.) or any HTTP client.

> **Breaking rename:** Formerly “Elementor MCP API” (`elementor-mcp-api`). Public REST/MCP paths, ability IDs, and tool names now use `mcp-api-for-elementor`. Update clients and `mcp.json` configs. The GitHub repository name is unchanged.

## Features

- **Full CRUD** on Elementor pages, elements, and templates
- **Granular element editing** — update a single widget's settings without touching the rest
- **Element operations** — add, remove, duplicate, move elements in the page tree
- **Global kit management** — read/write colors, fonts, and site-wide settings
- **Widget discovery** — list all available widgets and get their control schemas
- **MCP protocol support** — auto-registers 21 abilities via the core [Abilities API](https://developer.wordpress.org/apis/abilities-api/) when [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) is active
- **CSS cache management** — flush Elementor CSS after changes

## Requirements

- WordPress 6.0+ for the REST API; **WordPress 6.9+** for MCP (the [Abilities API](https://developer.wordpress.org/apis/abilities-api/) is in core — the standalone [abilities-api](https://github.com/WordPress/abilities-api) plugin repo is archived)
- PHP 7.4+
- Elementor (free or Pro)
- For MCP (Cursor / Claude / etc.): [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin (required — without it `/wp-json/mcp-api-for-elementor/mcp` will 404)
- Authentication: WordPress Application Passwords (recommended) or cookie auth
- Authorization: page read/write requires `edit_pages` (+ per-page `edit_post`); publish requires `publish_pages`; kit/templates/CSS flush require `manage_options`

## Installation

1. Download or clone this repository into `wp-content/plugins/` and use the plugin slug folder name:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/NorseGaud/elementor-mcp-api.git mcp-api-for-elementor
   ```
2. Activate the plugin in WordPress admin
3. Create an Application Password in **Users → Your Profile → Application Passwords**

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

## API & MCP Tools

REST base: `https://your-site.com/wp-json/mcp-api-for-elementor/v1`  
MCP tools use the names below exactly as shown in the client (ability `mcp-api-for-elementor/foo` → tool `mcp-api-for-elementor-foo`). See [MCP Integration](#mcp-integration).

### Pages

| Method | Endpoint | MCP Tool | Description |
|--------|----------|----------|-------------|
| GET | `/pages` | `mcp-api-for-elementor-list-pages` | List WordPress pages with Elementor status |
| GET | `/page/{id}/structure` | `mcp-api-for-elementor-get-page-structure` | Compact page tree (IDs, types, hints) |
| GET | `/page/{id}` | `mcp-api-for-elementor-get-page-data` | Full Elementor element tree |
| PUT | `/page/{id}` | `mcp-api-for-elementor-save-page-data` | Replace the full element tree |
| PATCH | `/page/{id}/meta` | `mcp-api-for-elementor-update-page-meta` | Update title, slug, excerpt, status, Yoast SEO |
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
3. Activate this plugin — it auto-registers 21 abilities and a dedicated MCP server (no extra WordPress config)
4. Create an Application Password for a user with the capabilities you need (admin recommended)
5. Verify the MCP route exists (must not 404):

   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" https://your-site.com/wp-json/mcp-api-for-elementor/mcp
   ```

   A working install returns something other than `404`. If you still get `404`, MCP Adapter is missing/inactive — the REST API under `/wp-json/mcp-api-for-elementor/v1` can work while MCP tools do not.
6. Add the server to your client `mcp.json` (see below), then reload MCP servers in the client

MCP endpoint: `https://your-site.com/wp-json/mcp-api-for-elementor/mcp`

This plugin registers its own MCP Adapter server on that path (hook: `mcp_adapter_init`). The adapter’s generic default server (`/wp-json/mcp/mcp-adapter-default-server`) is a useful smoke test that MCP Adapter itself is active, but is not required for these Elementor tools. The full REST ↔ MCP mapping is in [API & MCP Tools](#api--mcp-tools).

### Configure `mcp.json`

MCP clients (Cursor, Claude Desktop, VS Code, etc.) read a JSON config that registers servers. Use the HTTP bridge for remote sites, or STDIO + WP-CLI for a local WordPress install.

#### Remote site (HTTP) — recommended for hosted WordPress

Uses [`@automattic/mcp-wordpress-remote`](https://www.npmjs.com/package/@automattic/mcp-wordpress-remote) to proxy MCP over the WordPress REST API:

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

#### Local site (STDIO + WP-CLI)

When WordPress and WP-CLI are available on the same machine:

```json
{
  "mcpServers": {
    "mcp-api-for-elementor": {
      "command": "wp",
      "args": [
        "--path=/path/to/your/wordpress/site",
        "mcp-adapter",
        "serve",
        "--server=mcp-api-for-elementor",
        "--user=admin"
      ]
    }
  }
}
```

## Agent Skill

This repo includes a model-agnostic agent skill in `agent-skill/`. It teaches any AI coding agent (Claude Code, Cursor, GPT-based agents, etc.) how to use the API: workflows, element structures, widget settings, layout patterns, and design best practices.

### Install the skill

```bash
cd /path/to/this/repo
bash agent-skill/install.sh            # Claude Code + Cursor (default)
bash agent-skill/install.sh claude     # ~/.claude/skills/mcp-api-for-elementor/
bash agent-skill/install.sh cursor     # ~/.cursor/skills/mcp-api-for-elementor/
bash agent-skill/install.sh uninstall  # remove from Claude Code + Cursor
```

Or copy `agent-skill/SKILL.md` into your agent's skills directory manually. Restart the agent — then say "build an Elementor page" and it knows how.

### What the skill provides

- Full API workflow (discover → explore → edit → flush → verify)
- Elementor element JSON structure and common widget settings
- Reusable section patterns (hero, content rows, icon grids, contact forms, photo collages)
- Design best practices (zigzag layouts, background color alternation, responsive rules)
- Critical gotchas (race conditions, CSS cache, flex layout math)

## Important Notes

- **Sequential PATCH calls**: Never run multiple PATCH calls in parallel on the same page. Each PATCH loads, modifies, and saves the full page — parallel calls overwrite each other. Cross-page parallelism is safe.
- **Flush CSS**: Always call `/flush-css` after visual changes — Elementor caches CSS aggressively. Requires `manage_options`.
- **Element IDs**: Always provide valid 8-character hex IDs when creating elements.
- **PATCH merges settings**: Only send the settings you want to change, not the full settings object.
- **Default page status**: `POST /page` and `POST /build-page` default to `draft`. Publishing requires `publish_pages`.
- **Media import jail**: `POST /media/import` only accepts real image files under `wp-content/uploads/elementor-mcp-import/`.
- **MCP tools**: Exposed on `/wp-json/mcp-api-for-elementor/mcp` (authenticated Application Password session; dedicated admin password recommended).

## Publishing to WordPress.org

1. Run the GitHub **Release** workflow (or build `mcp-api-for-elementor-{version}.zip` locally with the same file set: bootstrap PHP, `includes/`, `readme.txt`, `LICENSE`).
2. Upload the zip at [Add your plugin](https://wordpress.org/plugins/developers/add/).
3. Wait for manual review (often 1–10 days).
4. After approval, commit the runtime files to WordPress.org SVN `trunk`, then copy to `tags/{version}` with matching `Stable tag` in `readme.txt`.

## License

GPL-3.0 — see [LICENSE](LICENSE)
