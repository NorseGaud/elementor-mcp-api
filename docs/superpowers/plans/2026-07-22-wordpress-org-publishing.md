# WordPress.org Publishing Preparation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the plugin to trademark-safe **MCP API for Elementor** (`mcp-api-for-elementor`), hard-cutover all public identifiers, and produce a WordPress.org-ready runtime zip + `readme.txt`.

**Architecture:** In-repo full rename. Bootstrap, PHP namespace, constants, REST/MCP/ability IDs, docs, CI/release, and SFTP deploy target all move to the new slug. Release zip ships only runtime files.

**Tech Stack:** WordPress plugin PHP, GitHub Actions, Composer/PHPUnit/PHPCS, WordPress.org `readme.txt`

## Global Constraints

- Display name: `MCP API for Elementor`
- Slug / text domain: `mcp-api-for-elementor`
- PHP namespace: `McpApiForElementor`
- Constants: `MCP_API_FOR_ELEMENTOR_VERSION`, `MCP_API_FOR_ELEMENTOR_PATH`
- Hard cutover: no `elementor-mcp-api` aliases
- Zip includes only: bootstrap PHP, `includes/*.php`, `readme.txt`, `LICENSE`
- Keep media jail `elementor-mcp-import/` and Elementor-product filenames (e.g. `class-elementor-data.php`)
- Do not rename the GitHub repo directory
- No auto-commit unless user asks (except this plan/spec already committed)

## File map

| Path | Role |
|------|------|
| `mcp-api-for-elementor.php` | New bootstrap (replaces `elementor-mcp-api.php`) |
| `includes/*.php` | Namespace + ID string updates |
| `readme.txt` | WordPress.org directory readme |
| `README.md` | GitHub docs with breaking rename note |
| `.github/workflows/release.yml` | New slug/constants/zip contents |
| `.github/workflows/ci.yml` | Paths if bootstrap referenced |
| `upload-sftp.py` | `PLUGIN_SLUG` |
| `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`, `tests/*` | Branding/paths/assertions |
| `agent-skill/*` | Path/name references only |

---

### Task 1: Bootstrap + PHP namespace/constants rename

**Files:**
- Create: `mcp-api-for-elementor.php` (from `elementor-mcp-api.php`)
- Delete: `elementor-mcp-api.php`
- Modify: all `includes/*.php`, `tests/SmokeTest.php`, `tests/bootstrap.php`

- [ ] Replace namespace `ElementorMcpApi` → `McpApiForElementor`
- [ ] Replace constants `ELEMENTOR_MCP_API_*` → `MCP_API_FOR_ELEMENTOR_*`
- [ ] Update plugin headers (name, text domain, license URI, `Requires Plugins: elementor`)
- [ ] Point smoke tests at new bootstrap/constants/classes
- [ ] Run `composer test` — expect PASS

### Task 2: Public REST/MCP/ability hard cutover

**Files:**
- Modify: `includes/class-rest-controller.php`, `includes/class-mcp-server.php`, `includes/class-abilities-provider.php`

- [ ] REST namespace → `mcp-api-for-elementor/v1`
- [ ] MCP `SERVER_ID` / `ROUTE_NAMESPACE` → `mcp-api-for-elementor`
- [ ] Ability category + all ability IDs → `mcp-api-for-elementor/...`
- [ ] Labels/strings that say “Elementor MCP API” → “MCP API for Elementor”
- [ ] Run `composer lint && composer test` — expect PASS

### Task 3: `readme.txt` + tooling/docs packaging

**Files:**
- Create: `readme.txt`
- Modify: `README.md`, `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`, `upload-sftp.py`, `.github/workflows/ci.yml`, `.github/workflows/release.yml`, `agent-skill/SKILL.md` (and install script if it references old slug)

- [ ] Add WordPress.org `readme.txt` (tags: `elementor, rest-api, mcp, ai, api`; Contributors = real .org usernames if known, else `norsegaud`)
- [ ] Update release zip to `mcp-api-for-elementor-{version}.zip` including `readme.txt` + `LICENSE`
- [ ] Update version-bump regexes for new bootstrap/constant names
- [ ] Set `upload-sftp.py` `PLUGIN_SLUG = "mcp-api-for-elementor"`
- [ ] Rewrite README for new paths; note breaking rename
- [ ] Dry-run local zip build; verify zip listing

### Task 4: Verify

- [ ] `composer lint && composer test`
- [ ] Confirm no leftover public IDs: `rg 'elementor-mcp-api|ElementorMcpApi|ELEMENTOR_MCP_API|Elementor MCP API' --glob '!docs/**' --glob '!vendor/**'`
- [ ] Accept remaining hits only for media jail `elementor-mcp-import` and Elementor product terms in prose where appropriate
