#!/bin/bash
# Install or uninstall the MCP API for Elementor skill for AI coding agents
# Usage: bash agent-skill/install.sh [claude|cursor|all]
#        bash agent-skill/install.sh uninstall [claude|cursor|all]
# Default target: all

set -euo pipefail

NAME="mcp-api-for-elementor"
# Previous install directory name — removed on install/uninstall
LEGACY_NAME="elementor-builder"
SRC="$(cd "$(dirname "$0")" && pwd)/SKILL.md"

ACTION="install"
TARGET="all"

if [[ "${1:-}" == "uninstall" ]]; then
  ACTION="uninstall"
  TARGET="${2:-all}"
else
  TARGET="${1:-all}"
fi

dest_dirs_for() {
  case "$1" in
    claude) echo "$HOME/.claude/skills" ;;
    cursor) echo "$HOME/.cursor/skills" ;;
    all)
      echo "$HOME/.claude/skills"
      echo "$HOME/.cursor/skills"
      ;;
    *)
      echo "Usage: $0 [claude|cursor|all]" >&2
      echo "       $0 uninstall [claude|cursor|all]" >&2
      exit 1
      ;;
  esac
}

remove_dir() {
  local dir="$1"
  if [[ -d "$dir" ]]; then
    rm -rf "$dir"
    echo "Removed $dir"
  fi
}

uninstall_from() {
  local skills_root="$1"
  remove_dir "$skills_root/$NAME"
  remove_dir "$skills_root/$LEGACY_NAME"
}

install_to() {
  local dest_dir="$1"
  # Drop the old skill name if present
  remove_dir "$(dirname "$dest_dir")/$LEGACY_NAME"
  mkdir -p "$dest_dir"
  cp "$SRC" "$dest_dir/SKILL.md"
  # Back-compat for older Claude Code skill loaders that expect skill.md
  cp "$SRC" "$dest_dir/skill.md"
  echo "Installed to $dest_dir"
}

restart_hint() {
  case "$1" in
    claude) echo "Restart Claude Code to use it." ;;
    cursor) echo "Restart Cursor (or start a new agent) to use it." ;;
    all) echo "Restart your agent (Claude Code / Cursor / etc.) to use it." ;;
  esac
}

while IFS= read -r skills_root; do
  if [[ "$ACTION" == "uninstall" ]]; then
    uninstall_from "$skills_root"
  else
    install_to "$skills_root/$NAME"
  fi
done < <(dest_dirs_for "$TARGET")

if [[ "$ACTION" == "uninstall" ]]; then
  echo "Uninstall complete."
else
  restart_hint "$TARGET"
fi
