# WordPress.org Publishing Preparation Design

**Date:** 2026-07-22  
**Status:** Approved (pending final user review of this doc)  
**Repo:** elementor-mcp-api (WordPress plugin)  
**Submission target:** https://wordpress.org/plugins/developers/add/

## Goal

Prepare this plugin for WordPress.org Plugin Directory submission: trademark-safe naming, complete directory packaging (`readme.txt` + headers + release zip), and a hard cutover of public API identifiers. Scope is submission-ready packaging, not deep security/i18n hardening or directory marketing assets.

## Decisions

| Topic | Choice |
|-------|--------|
| Trademark naming | Rename for compliance (guideline 17) |
| Display name | `MCP API for Elementor` |
| Slug / text domain | `mcp-api-for-elementor` |
| REST/MCP URL migration | Hard cutover — no dual aliases for old `elementor-mcp-api` paths |
| Rename depth | Full hard cutover in code (bootstrap, namespace, constants, abilities, MCP IDs, docs, release) |
| WordPress.org zip contents | Runtime only |
| Scope | Submission-ready package (not reviewer-hardening pass, not banners/icons) |
| Implementation approach | In-repo full rename + packaging |
| GitHub repo directory name | Keep `elementor-mcp-api` for now; zip folder uses new slug |

## Approach

Perform an in-repo rename so the source that developers edit matches the WordPress.org artifact. Update the existing GitHub Release zip builder so the downloadable zip is what gets uploaded to the directory add form. Document the human review/SVN steps; do not automate SVN in this pass.

## Identity & public API

| Item | New value |
|------|-----------|
| Display name | `MCP API for Elementor` |
| Text domain / slug | `mcp-api-for-elementor` |
| Bootstrap file | `mcp-api-for-elementor.php` |
| PHP namespace | `McpApiForElementor` |
| Version / path constants | `MCP_API_FOR_ELEMENTOR_VERSION`, `MCP_API_FOR_ELEMENTOR_PATH` |
| REST namespace | `mcp-api-for-elementor/v1` |
| MCP route | `/wp-json/mcp-api-for-elementor/mcp` |
| MCP server ID (STDIO) | `mcp-api-for-elementor` |
| Ability category | `mcp-api-for-elementor` |
| Ability IDs | `mcp-api-for-elementor/{action}` |
| MCP tool names | `mcp-api-for-elementor-{action}` |

**Breaking change:** Existing clients, `mcp.json` configs, and docs that use `elementor-mcp-api` must be updated. No compatibility aliases.

## WordPress.org package & headers

### `readme.txt`

Add a WordPress.org-format `readme.txt` (directory page source of truth; keep GitHub `README.md` separately):

- Headers: `Contributors`, `Tags` (max 5), `Requires at least`, `Tested up to`, `Requires PHP`, `Stable tag`, `License`, `License URI`
- `Contributors` must be WordPress.org usernames (not display names). Fill with the authors’ real .org accounts at implementation time; do not invent handles.
- Tags (exactly these five): `elementor`, `rest-api`, `mcp`, `ai`, `api`
- Sections: short description, Description, Installation, FAQ, Changelog
- Document Elementor as required; WordPress MCP Adapter as optional for MCP
- Trademark-safe wording (“for Elementor”; do not imply Elementor authorship)

### Plugin headers (`mcp-api-for-elementor.php`)

- `Plugin Name: MCP API for Elementor`
- `Description`, `Version`, `Author` (preserve current authors)
- `License: GPL-3.0` (existing LICENSE file stays GPL-3)
- `License URI:` https://www.gnu.org/licenses/gpl-3.0.html
- `Text Domain: mcp-api-for-elementor`
- `Requires at least: 6.0`
- `Requires PHP: 7.4`
- `Requires Plugins: elementor`

Optional Elementor headers may be added later (`Elementor tested up to`); not required for this pass.

### Release zip

Update `.github/workflows/release.yml`:

- Top-level folder: `mcp-api-for-elementor/`
- Include only:
  - `mcp-api-for-elementor.php`
  - `includes/*.php`
  - `readme.txt`
  - `LICENSE`
- Exclude: `tests/`, `vendor/`, `.github/`, `upload-sftp.py`, `agent-skill/`, Composer files, `AGENTS.md`, `docs/`, etc.
- Artifact name: `mcp-api-for-elementor-{version}.zip`
- Version bump logic must target the new bootstrap filename and `MCP_API_FOR_ELEMENTOR_VERSION`

This zip is the file uploaded at https://wordpress.org/plugins/developers/add/

## Code & tooling changes

1. Rename bootstrap `elementor-mcp-api.php` → `mcp-api-for-elementor.php`
2. Rename PHP namespace `ElementorMcpApi` → `McpApiForElementor` in `includes/` and tests
3. Update constants, REST namespace, MCP server ID/route, ability category + all ability IDs
4. Update `upload-sftp.py` remote plugin folder to `mcp-api-for-elementor/` (per `AGENTS.md`)
5. Update CI/release workflows for new filenames, constants, zip slug/contents
6. Update `composer.json` package name to `norsegaud/mcp-api-for-elementor`
7. Update PHPCS/PHPUnit paths if they reference the old bootstrap filename

**Keep as-is (not part of public plugin slug):**
- Internal PHP filenames that describe Elementor data (e.g. `class-elementor-data.php`) — they refer to the Elementor product, not our plugin brand
- Media import jail directory `wp-content/uploads/elementor-mcp-import/` — filesystem path; renaming it is out of scope for this packaging pass

## Documentation

- Add `readme.txt` as above
- Rewrite GitHub `README.md` for new name/paths; prominently note the breaking rename
- Update `agent-skill/` path/name references so local skill docs stay accurate (skill remains excluded from the WordPress.org zip)

## Verification

Before calling the work complete:

1. `php -l` on plugin PHP sources
2. `composer lint` and `composer test` pass after rename
3. Spot-check release zip layout: only runtime files under `mcp-api-for-elementor/`

## Human submission checklist (documented, not automated)

1. Build/download `mcp-api-for-elementor-{version}.zip` via the Release workflow (or equivalent local zip)
2. Log in and upload at https://wordpress.org/plugins/developers/add/
3. Wait for manual review (typically 1–10 days; common delays: escaping, sanitizing, nonces, guideline issues)
4. After approval: commit runtime files to WordPress.org SVN `trunk`, then copy to `tags/{version}` with matching `Stable tag` in `readme.txt`

SVN deploy automation is out of scope for this prep pass.

## Non-goals

- Dual-route / dual-ability aliases for `elementor-mcp-api`
- Directory banner, icon, or screenshot assets
- Deep PHPCS/security/i18n hardening beyond what is required to keep the rename green
- Renaming the GitHub repository itself
- Automating WordPress.org SVN commits from CI

## Success criteria

- Plugin display name and slug are trademark-safe for directory review
- `readme.txt` and plugin headers meet directory submission expectations
- Release zip is a complete, minimal, installable plugin named `mcp-api-for-elementor`
- Public REST/MCP identifiers consistently use `mcp-api-for-elementor`
- Docs and deploy helper (`upload-sftp.py`) match the new slug
- A clear checklist exists for uploading to WordPress.org and post-approval SVN
