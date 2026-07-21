# Elementor MCP API

WordPress plugin that exposes a REST API + MCP (Model Context Protocol) abilities for AI-driven Elementor page editing.

Build, edit, and manage Elementor pages programmatically — designed to be used by AI agents (Claude, GPT, etc.) or any HTTP client.

## Features

- **Full CRUD** on Elementor pages, elements, and templates
- **Granular element editing** — update a single widget's settings without touching the rest
- **Element operations** — add, remove, duplicate, move elements in the page tree
- **Global kit management** — read/write colors, fonts, and site-wide settings
- **Widget discovery** — list all available widgets and get their control schemas
- **MCP protocol support** — auto-registers 20 abilities when used with [WordPress Abilities API](https://github.com/bvisible/wordpress-abilities-api) + [WordPress MCP Adapter](https://github.com/bvisible/wordpress-mcp-adapter)
- **CSS cache management** — flush Elementor CSS after changes

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor (free or Pro)
- Authentication: WordPress Application Passwords (recommended) or cookie auth
- Authorization: page read/write requires `edit_pages` (+ per-page `edit_post`); publish requires `publish_pages`; kit/templates/CSS flush require `manage_options`

## Installation

1. Download or clone this repository into `wp-content/plugins/`:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/bvisible/elementor-mcp-api.git
   ```
2. Activate the plugin in WordPress admin
3. Create an Application Password in **Users → Your Profile → Application Passwords**

## API Endpoints

Base URL: `https://your-site.com/wp-json/elementor-mcp-api/v1`

### Pages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/pages` | List all Elementor pages |
| GET | `/page/{id}` | Full page data (elements tree) |
| GET | `/page/{id}/structure` | Lightweight structure (IDs, types, hints) |
| PUT | `/page/{id}` | Replace all page data |
| POST | `/page` | Create a new page |
| POST | `/build-page` | Create or update a full page |

### Elements

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/page/{id}/element/{eid}` | Get single element data |
| PATCH | `/page/{id}/element/{eid}` | Update element settings (merge) |
| POST | `/page/{id}/element` | Add new element |
| DELETE | `/page/{id}/element/{eid}` | Remove element |
| POST | `/page/{id}/element/{eid}/duplicate` | Duplicate element |
| POST | `/page/{id}/element/{eid}/move` | Move element to new position |

### Templates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/templates` | List all templates |
| POST | `/template` | Create template (header, footer, etc.) |

### Global Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/kit` | Get global kit settings |
| PUT | `/kit` | Update global kit settings |

### Widgets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/widgets` | List all registered widgets |
| GET | `/widget/{name}/schema` | Get widget control schema |
| GET | `/widget/{name}/defaults` | Ready-to-use element JSON with defaults |

### Cache

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/flush-css` | Flush Elementor CSS cache |

## Quick Start

```bash
API="https://your-site.com/wp-json/elementor-mcp-api/v1"
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

1. Install [WordPress Abilities API](https://github.com/bvisible/wordpress-abilities-api)
2. Install [WordPress MCP Adapter](https://github.com/bvisible/wordpress-mcp-adapter)
3. The plugin auto-registers 20 abilities — no configuration needed

MCP endpoint: `https://your-site.com/wp-json/mcp/mcp-adapter-default-server`

## Agent Skill

This repo includes a model-agnostic agent skill in `agent-skill/`. It teaches any AI coding agent (Claude Code, Cursor, GPT-based agents, etc.) how to use the API: workflows, element structures, widget settings, layout patterns, and design best practices.

### Install the skill

```bash
cd elementor-mcp-api/
bash agent-skill/install.sh          # Claude Code + Cursor (default)
bash agent-skill/install.sh claude   # ~/.claude/skills/elementor-builder/
bash agent-skill/install.sh cursor   # ~/.cursor/skills/elementor-builder/
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
- **MCP tools**: Registered with `public: false` — use an authenticated MCP adapter session (dedicated admin Application Password recommended).

## License

GPL-3.0 — see [LICENSE](LICENSE)
