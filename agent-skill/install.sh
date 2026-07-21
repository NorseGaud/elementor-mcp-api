#!/bin/bash
# Install the Elementor Builder skill for AI coding agents
# Usage: bash agent-skill/install.sh [claude|cursor|all]
# Default: all

set -euo pipefail

TARGET="${1:-all}"
SRC="$(cd "$(dirname "$0")" && pwd)/SKILL.md"
NAME="elementor-builder"

install_to() {
  local dest_dir="$1"
  mkdir -p "$dest_dir"
  cp "$SRC" "$dest_dir/SKILL.md"
  # Back-compat for older Claude Code skill loaders that expect skill.md
  cp "$SRC" "$dest_dir/skill.md"
  echo "Installed to $dest_dir"
}

case "$TARGET" in
  claude)
    install_to "$HOME/.claude/skills/$NAME"
    echo "Restart Claude Code to use it."
    ;;
  cursor)
    install_to "$HOME/.cursor/skills/$NAME"
    echo "Restart Cursor (or start a new agent) to use it."
    ;;
  all)
    install_to "$HOME/.claude/skills/$NAME"
    install_to "$HOME/.cursor/skills/$NAME"
    echo "Restart your agent (Claude Code / Cursor / etc.) to use it."
    ;;
  *)
    echo "Usage: $0 [claude|cursor|all]" >&2
    exit 1
    ;;
esac
