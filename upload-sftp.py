#!/usr/bin/env python3
"""Upload this plugin to a WordPress wp-content/plugins directory over SFTP.

Credentials and connection details come from environment variables (never hardcode them):

  SFTP_HOST   required  e.g. sftp.example.com
  SFTP_PORT   optional  default 22
  SFTP_USER   required
  SFTP_PASS   required

Example:

  SFTP_HOST=sftp.example.com SFTP_PORT=32022 \\
  SFTP_USER=myuser SFTP_PASS='secret' \\
  python3 upload-sftp.py
"""

from __future__ import annotations

import os
import stat
import sys

try:
    import paramiko
except ImportError:
    print("paramiko is required: pip install paramiko", file=sys.stderr)
    sys.exit(1)

LOCAL_ROOT = os.path.dirname(os.path.abspath(__file__))
PLUGIN_SLUG = "elementor-mcp-api"
# Dev/docs/tooling only — not required for the plugin to run in WordPress.
SKIP_NAMES = {
    ".git",
    ".gitignore",
    ".DS_Store",
    ".cursor",
    "__pycache__",
    "upload-sftp.py",
    "README.md",
    "AGENTS.md",
    "LICENSE",
    "agent-skill",
}


def require_env(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        print(f"Missing required environment variable: {name}", file=sys.stderr)
        print(__doc__, file=sys.stderr)
        sys.exit(1)
    return value


def is_dir(sftp: paramiko.SFTPClient, path: str) -> bool:
    try:
        return stat.S_ISDIR(sftp.stat(path).st_mode)
    except FileNotFoundError:
        return False


def list_dir(sftp: paramiko.SFTPClient, path: str) -> None:
    print(f"=== {path} ===")
    for name in sorted(sftp.listdir(path)):
        full = f"{path.rstrip('/')}/{name}" if path != "." else name
        print(f"  {name}{'/' if is_dir(sftp, full) else ''}")


def ensure_dir(sftp: paramiko.SFTPClient, path: str) -> None:
    cur = ""
    for part in path.strip("/").split("/"):
        cur = f"{cur}/{part}" if cur else part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)
            print("mkdir", cur)


def find_plugins_dir(sftp: paramiko.SFTPClient) -> str | None:
    candidates = [
        "wp-content/plugins",
        "wordpress/wp-content/plugins",
        "public_html/wp-content/plugins",
        "httpdocs/wp-content/plugins",
        "www/wp-content/plugins",
        "html/wp-content/plugins",
        "site/wp-content/plugins",
    ]
    for cand in candidates:
        if is_dir(sftp, cand):
            return cand

    for name in sftp.listdir("."):
        if not is_dir(sftp, name):
            continue
        for sub in ("wp-content/plugins", "plugins"):
            path = f"{name}/{sub}"
            if is_dir(sftp, path):
                return path
    return None


def main() -> None:
    host = require_env("SFTP_HOST")
    user = require_env("SFTP_USER")
    password = require_env("SFTP_PASS")
    port = int(os.environ.get("SFTP_PORT", "22"))

    print(f"Connecting to {host}:{port} as {user}...")
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    print("cwd:", sftp.normalize("."))
    list_dir(sftp, ".")

    plugins_dir = find_plugins_dir(sftp)
    if not plugins_dir:
        print("ERROR: could not find wp-content/plugins", file=sys.stderr)
        sftp.close()
        transport.close()
        sys.exit(1)

    remote_plugin = f"{plugins_dir}/{PLUGIN_SLUG}"
    print("plugins_dir:", plugins_dir)
    ensure_dir(sftp, remote_plugin)

    uploaded = 0
    for root, dirs, files in os.walk(LOCAL_ROOT):
        dirs[:] = [d for d in dirs if d not in SKIP_NAMES]
        rel = os.path.relpath(root, LOCAL_ROOT)
        if any(part in SKIP_NAMES for part in rel.split(os.sep)):
            continue
        remote_dir = (
            remote_plugin
            if rel == "."
            else f"{remote_plugin}/{rel.replace(os.sep, '/')}"
        )
        if rel != ".":
            ensure_dir(sftp, remote_dir)
        for fname in files:
            if fname in SKIP_NAMES:
                continue
            local_path = os.path.join(root, fname)
            remote_path = f"{remote_dir}/{fname}"
            sftp.put(local_path, remote_path)
            print("put", remote_path)
            uploaded += 1

    print(f"\nUploaded {uploaded} files to {remote_plugin}")
    list_dir(sftp, remote_plugin)
    if is_dir(sftp, f"{remote_plugin}/includes"):
        list_dir(sftp, f"{remote_plugin}/includes")

    sftp.close()
    transport.close()
    print("DONE")


if __name__ == "__main__":
    main()
