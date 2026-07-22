# Get-Instructions MCP Tool Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `mcp-api-for-elementor/get-instructions` that returns auto-generated tool docs plus static MCP guidance, and remove the local `agent-skill/` install path.

**Architecture:** `Instructions_Composer::build()` loads `includes/instructions-guidance.md`, appends a catalog from `wp_get_ability()` for each tool ability (except itself), and returns `{ markdown, plugin_version }`. Ability registered first in `get_tool_ability_names()`.

**Tech Stack:** PHP 7.4+, WordPress Abilities API (`WP_Ability`), existing `Abilities_Provider` / MCP Adapter wiring.

## Global Constraints

- MCP-first guidance only (no curl/REST skill).
- Delete entire `agent-skill/` directory.
- List `get-instructions` first in `get_tool_ability_names()`.
- Permission: `can_read`; meta: `meta_read()`.
- Bump version to `2.1.3` and update `readme.txt` changelog.
- No Co-authored-by trailers; no “cursor” in commit messages.
- Spec: `docs/superpowers/specs/2026-07-22-get-instructions-design.md`

## File map

| Path | Action |
|------|--------|
| `includes/class-instructions-composer.php` | Create |
| `includes/instructions-guidance.md` | Create (port from agent-skill, MCP-first) |
| `includes/class-abilities-provider.php` | Register ability; list first |
| `mcp-api-for-elementor.php` | require composer; version 2.1.3 |
| `tests/SmokeTest.php` | Assert composer + guidance file |
| `README.md` | Replace Agent Skill section |
| `readme.txt` | Stable tag + changelog |
| `upload-sftp.py` | Remove `agent-skill` skip |
| `agent-skill/*` | Delete |

---

### Task 1: Instructions_Composer + unit smoke coverage

**Files:**
- Create: `includes/class-instructions-composer.php`
- Create: `includes/instructions-guidance.md` (minimal stub first; full content in Task 2)
- Modify: `mcp-api-for-elementor.php` (require composer)
- Modify: `tests/SmokeTest.php`

**Interfaces:**
- Produces: `Instructions_Composer::build(): array` with keys `markdown` (string), `plugin_version` (string)
- Produces: `Instructions_Composer::ability_to_mcp_tool_name(string $ability_name): string`
- Consumes: `Abilities_Provider::get_tool_ability_names()`, `wp_get_ability()`, `MCP_API_FOR_ELEMENTOR_*` constants

- [ ] **Step 1:** Add failing smoke assertions for `Instructions_Composer` class and guidance file path.

- [ ] **Step 2:** Implement composer:
  - Header with plugin name + version
  - Load guidance markdown from `includes/instructions-guidance.md` (missing file → short notice, no fatal)
  - Catalog section: for each ability in `get_tool_ability_names()` except `mcp-api-for-elementor/get-instructions`, resolve via `wp_get_ability()`; if null/missing and `WP_DEBUG`, `error_log`; else emit label, MCP tool name (`str_replace('/', '-', $name)`), description, inputs table from `get_input_schema()`
  - Footer: flush-css + sequential writes reminder
  - Return `['markdown' => ..., 'plugin_version' => MCP_API_FOR_ELEMENTOR_VERSION]`
  - When `wp_get_ability` is unavailable (CLI smoke without WP abilities), catalog section says tools are listed at runtime / skip gracefully so `build()` still returns markdown

- [ ] **Step 3:** `require_once` composer from main plugin file; run `composer test` or `vendor/bin/phpunit` if available, else `phpunit`.

- [ ] **Step 4:** Commit: `Add Instructions_Composer for MCP get-instructions.`

---

### Task 2: Full MCP guidance markdown

**Files:**
- Modify: `includes/instructions-guidance.md`

**Interfaces:**
- Consumes: content themes from former `agent-skill/SKILL.md` (workflow, element structure, patterns, design, flex/v4, critical rules)
- Produces: static body inserted by composer (workflow + patterns + gotchas; no tool catalog)

- [ ] **Step 1:** Rewrite guidance MCP-first: tool names like `mcp-api-for-elementor-get-page-structure`; no curl; teach v4 widths via `_flex_size` + `_inline_size` + `width`; keep JSON patterns and design rules.

- [ ] **Step 2:** Spot-check file has no `curl` and no `/wp-json/mcp-api-for-elementor/v1`.

- [ ] **Step 3:** Commit: `Add MCP-first instructions guidance markdown.`

---

### Task 3: Register get-instructions ability

**Files:**
- Modify: `includes/class-abilities-provider.php`

**Interfaces:**
- Consumes: `Instructions_Composer::build()`
- Produces: ability `mcp-api-for-elementor/get-instructions` as first entry in `get_tool_ability_names()`

- [ ] **Step 1:** Prepend ability name to `get_tool_ability_names()`.

- [ ] **Step 2:** Register ability (label Get Instructions; strong “call first” description; empty input schema; output schema markdown + plugin_version; execute → `Instructions_Composer::build()`; `can_read`; `meta_read()`). Ensure composer class is loaded.

- [ ] **Step 3:** Commit: `Register get-instructions MCP ability.`

---

### Task 4: Remove agent-skill; docs + version bump

**Files:**
- Delete: `agent-skill/`
- Modify: `README.md`, `readme.txt`, `upload-sftp.py`, `mcp-api-for-elementor.php`
- Modify: `tests/SmokeTest.php` if version assertions need 2.1.3

- [ ] **Step 1:** Bump version to `2.1.3` in plugin header, constant, `readme.txt` Stable tag; add changelog entry for get-instructions + skill removal.

- [ ] **Step 2:** Replace README Agent Skill section with MCP “call get-instructions first”; update ability count (21 → 22).

- [ ] **Step 3:** Remove `agent-skill` from `upload-sftp.py` SKIP_NAMES; delete `agent-skill/` directory.

- [ ] **Step 4:** Run smoke tests; commit: `Serve instructions via MCP and remove local agent skill.`

---

### Task 5: Live verify (optional if site credentials available)

- [ ] Call `mcp-api-for-elementor-get-instructions` on a deployed site after upload, or document manual verify steps.
