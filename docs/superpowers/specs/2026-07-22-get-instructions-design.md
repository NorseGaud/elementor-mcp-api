# Design: MCP `get-instructions` Tool

**Date:** 2026-07-22  
**Status:** Approved for implementation planning  
**Product:** MCP API for Elementor

## Problem

Local agent skills (`agent-skill/SKILL.md`) go stale when the plugin or guidance changes and the user does not reinstall. Agents connected over MCP should load the latest instructions from the site itself.

## Goals

- Expose a read-only MCP tool that returns current building instructions.
- Keep the **tool catalog** in sync with registered abilities (no hand-maintained endpoint tables).
- Keep **workflows, Elementor patterns, and gotchas** as curated static markdown (MCP-oriented).
- Remove the local skill install path entirely.

## Non-goals

- MCP resources or prompts.
- Auto-generating section patterns or design rules from code.
- Preserving a parallel REST/`curl` skill document.
- Documenting REST-only helpers (bulk patch, column-width, find) as primary paths.

## Decisions

| Decision | Choice |
|----------|--------|
| Delivery | MCP tool only (no local skill) |
| Catalog | Generated at runtime from Abilities API |
| Guidance | Static `includes/instructions-guidance.md` |
| Local skill | Delete `agent-skill/` |
| Content tone | MCP-first (tool names, not REST) |

## Architecture

```
get-instructions (ability)
        │
        ▼
Instructions_Composer::build()
        │
        ├── Header (name, version, “use MCP tools”)
        ├── Static workflow section (from guidance file or embedded prefix)
        ├── Generated tool catalog (wp_get_ability / ability objects)
        ├── Static guidance (patterns, design, gotchas)
        └── Footer (flush, sequential writes)
        │
        ▼
{ markdown, plugin_version }
```

### New files

| Path | Role |
|------|------|
| `includes/class-instructions-composer.php` | Builds markdown from abilities + static file |
| `includes/instructions-guidance.md` | Hand-written MCP workflows, patterns, gotchas |

### Modified files

| Path | Change |
|------|--------|
| `includes/class-abilities-provider.php` | Register `get-instructions`; add to `get_tool_ability_names()`; load composer |
| `mcp-api-for-elementor.php` | `require_once` composer if needed (or lazy-load from provider) |
| `README.md` | Remove Agent Skill install; document calling `get-instructions` first |
| `readme.txt` | Note tool / changelog entry if version bump accompanies ship |
| `upload-sftp.py` | Drop `agent-skill` from `SKIP_NAMES` (directory removed) |

### Removed

- `agent-skill/SKILL.md`
- `agent-skill/install.sh`
- Entire `agent-skill/` directory

## Tool contract

### Ability

- **Name:** `mcp-api-for-elementor/get-instructions`
- **MCP tool name:** `mcp-api-for-elementor-get-instructions` (Adapter: `/` → `-`)
- **Category:** `mcp-api-for-elementor`
- **Label:** Get Instructions
- **Description (discovery):** Must strongly instruct agents to call this tool before creating or editing Elementor pages with this server. Example intent: “Return the latest Elementor page-building instructions for this plugin. Call this first before any Elementor page work.”
- **Permission:** `Abilities_Provider::can_read` (authenticated editor+)
- **Meta:** `meta_read()` (readonly, non-destructive, idempotent, `mcp.public: false`)

### Input

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

No parameters required.

### Output

```json
{
  "type": "object",
  "properties": {
    "markdown": {
      "type": "string",
      "description": "Full instructions document in Markdown."
    },
    "plugin_version": {
      "type": "string",
      "description": "Plugin version that produced the document."
    }
  },
  "required": ["markdown", "plugin_version"]
}
```

## Generated tool catalog

### Source of truth

For each ability name in `Abilities_Provider::get_tool_ability_names()` **except** `mcp-api-for-elementor/get-instructions`:

1. Resolve the ability via WordPress Abilities API (`wp_get_ability()` or equivalent available API at runtime).
2. Emit a markdown section:

```markdown
### {label}

- **Tool:** `{mcp-tool-name}`
- **Ability:** `{ability-name}`

{description}

**Inputs**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| ... | ... | yes/no | ... |
```

### MCP tool name mapping

`mcp-api-for-elementor/list-pages` → `mcp-api-for-elementor-list-pages`  
(replace `/` with `-`, matching Adapter convention already documented in README).

### Schema rendering rules

- Read `input_schema.properties` and `input_schema.required`.
- Property `type` may be string or array (JSON Schema); render as comma-separated or primary type.
- Nested `items` / deep objects: one-line summary (type + short description); do not dump full nested trees.
- If an ability cannot be resolved at runtime, omit it and continue (do not fail the whole document). Log when `WP_DEBUG` is on.
- Order: same order as `get_tool_ability_names()`.

### Why exclude `get-instructions` from the catalog body

Avoid pointless self-reference in the catalog. The tool still exists for discovery via the MCP tools list; the markdown header can mention “you already called get-instructions.”

## Static guidance content

File: `includes/instructions-guidance.md`

Port and rewrite content from the former `agent-skill/SKILL.md`, MCP-first:

1. **Workflow** — discover (`list-pages`, `list-widgets`, `get-kit`) → explore (`get-page-structure`, then `get-element` / `get-page-data`) → edit (update/add/move/remove/duplicate/`generate-element`/`build-page`) → `flush-css` → visual verify.
2. **Element JSON structure** — `id`, `elType`, `widgetType`, `settings`, `elements`; 8-char hex IDs.
3. **Section patterns** — hero, zigzag content row, icon row, photo collage, contact (JSON examples kept; tool-oriented surrounding prose).
4. **Design best practices** — backgrounds, zigzag, icons, forms, footer, images, responsive, sticky header.
5. **Flex / Elementor v4 gotchas** — width math; require `_flex_size` + `_inline_size` + `width` together (no REST `column-width` helper).
6. **Critical rules** — never parallelize write tools on the same page; sequential updates; merge semantics of `update-element`; `position` / `parent_id` rules; flush after visual changes.

### Explicitly drop from guidance

- curl examples and `$API` / `$AUTH` setup
- REST endpoint tables
- REST-only: patch-bulk, column-width, find, widget defaults HTTP routes
- Local skill install instructions
- WP-CLI menu commands (optional one-line “add new pages to nav in WP admin” is fine)

## Composer implementation notes

- Namespace: `McpApiForElementor`
- Public API: `Instructions_Composer::build(): array` returning `['markdown' => string, 'plugin_version' => string]`
- Load guidance with `file_get_contents( MCP_API_FOR_ELEMENTOR_PATH . 'includes/instructions-guidance.md' )`
- If guidance file missing: return catalog + short error note in markdown (still HTTP-success from ability); do not fatal
- No caching required for v1 (document is ~tens of KB; generated per call). Optional transient cache keyed by `MCP_API_FOR_ELEMENTOR_VERSION` can be a follow-up
- Ability `execute_callback` calls `Instructions_Composer::build()` and returns its array

## Bootstrap / wiring

1. Ensure composer class is loaded before ability registration (from provider or main plugin file).
2. Append `'mcp-api-for-elementor/get-instructions'` to `get_tool_ability_names()` (recommended: first entry so it sorts near the top of client tool lists when order is preserved).
3. Register ability in `register_utility_abilities()` (or a dedicated `register_instructions_ability()` called from `register()`).
4. Dedicated MCP server already passes `get_tool_ability_names()` into `create_server` — no `Mcp_Server` change if the name list is updated.

## Documentation updates

### README

- Remove “Agent Skill” install/uninstall section.
- Under MCP Integration: instruct agents/users that **`mcp-api-for-elementor-get-instructions` should be called first** before page building.
- Keep REST docs as-is for human/API consumers; instructions tool remains MCP-oriented.

### readme.txt

- Changelog bullet when this ships with a version bump.
- Optional “AI / MCP” FAQ: instructions are served by the plugin tool, not a local skill file.

## Migration

| Actor | Action |
|-------|--------|
| Repo | Delete `agent-skill/`; stop documenting install |
| Existing users with local skill | Harmless leftover; may delete `~/.claude/skills/mcp-api-for-elementor` and `~/.cursor/skills/mcp-api-for-elementor` manually. No plugin-side uninstall. |
| Agents | Discover new tool after MCP reload / plugin update |

## Testing

1. With MCP Adapter + auth: list tools includes `mcp-api-for-elementor-get-instructions`.
2. Call tool: response has non-empty `markdown`, correct `plugin_version`.
3. Markdown contains each other tool’s MCP name from `get_tool_ability_names()`.
4. Markdown contains a distinctive phrase from `instructions-guidance.md`.
5. Markdown does not contain `curl` or `/wp-json/mcp-api-for-elementor/v1` as a primary workflow (spot-check).
6. Unauthenticated / insufficient cap: permission denied.
7. Plugin zip / SFTP upload includes `includes/instructions-guidance.md`.

## Risks and mitigations

| Risk | Mitigation |
|------|------------|
| Agents never call the tool | Strong tool description; README callout |
| Abilities API shape differs across WP versions | Defensive property access; omit broken abilities |
| Guidance drifts from tools | Catalog is generated; only patterns/gotchas are manual |
| Large payload | Acceptable (~SKILL size); no images |

## Open items for implementation plan

- Exact Abilities API getters available in supported WP / abilities package versions (confirm `wp_get_ability` + schema accessors during implementation).
- Version bump policy (ship in next patch vs fold into larger release).

## Resolved during design

- List `get-instructions` **first** in `get_tool_ability_names()`.
