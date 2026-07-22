#!/usr/bin/env python3
"""Upload this plugin to a WordPress wp-content/plugins directory over SFTP.

Credentials and connection details come from environment variables (never hardcode them):

  SFTP_HOST     required  e.g. sftp.example.com
  SFTP_PORT     optional  default 22
  SFTP_USER     required
  SFTP_PASS     required
  SFTP_WORKERS  optional  parallel upload workers (default 8)

Example:

  SFTP_HOST=sftp.example.com SFTP_PORT=32022 \\
  SFTP_USER=myuser SFTP_PASS='secret' \\
  python3 upload-sftp.py
"""

from __future__ import annotations

import os
import stat
import sys
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed

try:
    import paramiko
except ImportError:
    print("paramiko is required: pip install paramiko", file=sys.stderr)
    sys.exit(1)

LOCAL_ROOT = os.path.dirname(os.path.abspath(__file__))
PLUGIN_SLUG = "mcp-api-for-elementor"
DEFAULT_WORKERS = 8
# Dev/docs/tooling only — not required for the plugin to run in WordPress.
SKIP_NAMES = {
    ".git",
    ".github",
    ".gitignore",
    ".DS_Store",
    ".cursor",
    "__pycache__",
    "upload-sftp.py",
    "README.md",
    "AGENTS.md",
    "docs",
    "tests",
    "vendor",
    "composer.json",
    "composer.lock",
    "phpcs.xml.dist",
    "phpunit.xml.dist",
}

_print_lock = threading.Lock()


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


def open_sftp(host: str, port: int, user: str, password: str) -> tuple[paramiko.Transport, paramiko.SFTPClient]:
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    if sftp is None:
        transport.close()
        raise RuntimeError("failed to open SFTP client")
    return transport, sftp


def close_sftp(transport: paramiko.Transport, sftp: paramiko.SFTPClient) -> None:
    sftp.close()
    transport.close()


def iter_runtime_files() -> list[tuple[str, str]]:
    """Return (local_path, relative_posix_path) for files needed at runtime."""
    runtime: list[tuple[str, str]] = []
    for root, dirs, files in os.walk(LOCAL_ROOT):
        dirs[:] = [d for d in dirs if d not in SKIP_NAMES]
        rel = os.path.relpath(root, LOCAL_ROOT)
        if any(part in SKIP_NAMES for part in rel.split(os.sep)):
            continue
        for fname in files:
            if fname in SKIP_NAMES:
                continue
            local_path = os.path.join(root, fname)
            rel_path = (
                fname
                if rel == "."
                else f"{rel.replace(os.sep, '/')}/{fname}"
            )
            runtime.append((local_path, rel_path))
    return runtime


def runtime_dirs(rel_paths: list[str]) -> list[str]:
    """Parent dirs needed for uploads, shallowest first."""
    dirs: set[str] = set()
    for rel in rel_paths:
        parts = rel.split("/")
        for i in range(1, len(parts)):
            dirs.add("/".join(parts[:i]))
    return sorted(dirs, key=lambda p: p.count("/"))


def remove_remote_tree(sftp: paramiko.SFTPClient, path: str) -> int:
    """Recursively delete a remote file or directory. Returns count removed."""
    removed = 0
    if is_dir(sftp, path):
        for name in sftp.listdir(path):
            removed += remove_remote_tree(sftp, f"{path}/{name}")
        sftp.rmdir(path)
        print("rmdir", path)
        removed += 1
    else:
        sftp.remove(path)
        print("rm", path)
        removed += 1
    return removed


def clean_remote_plugin(
    sftp: paramiko.SFTPClient,
    remote_plugin: str,
    keep_files: set[str],
) -> int:
    """Remove remote files/dirs not in the runtime keep set."""
    keep_dirs = {""}
    for rel in keep_files:
        parts = rel.split("/")
        for i in range(1, len(parts)):
            keep_dirs.add("/".join(parts[:i]))

    removed = 0

    def walk(remote_dir: str, rel_dir: str) -> None:
        nonlocal removed
        try:
            names = sftp.listdir(remote_dir)
        except FileNotFoundError:
            return
        for name in sorted(names):
            rel = name if not rel_dir else f"{rel_dir}/{name}"
            remote_path = f"{remote_dir}/{name}"
            if is_dir(sftp, remote_path):
                if rel in keep_dirs:
                    walk(remote_path, rel)
                else:
                    removed += remove_remote_tree(sftp, remote_path)
            elif rel not in keep_files:
                removed += remove_remote_tree(sftp, remote_path)

    walk(remote_plugin, "")
    return removed


def upload_files_parallel(
    host: str,
    port: int,
    user: str,
    password: str,
    remote_plugin: str,
    runtime_files: list[tuple[str, str]],
    workers: int,
) -> int:
    """Upload files concurrently; each worker uses its own SFTP connection."""
    worker_count = max(1, min(workers, len(runtime_files)))
    print(f"Uploading {len(runtime_files)} files with {worker_count} workers...")

    thread_local = threading.local()
    connections: list[tuple[paramiko.Transport, paramiko.SFTPClient]] = []
    connections_lock = threading.Lock()

    def get_worker_sftp() -> paramiko.SFTPClient:
        sftp = getattr(thread_local, "sftp", None)
        if sftp is not None:
            return sftp
        transport, sftp = open_sftp(host, port, user, password)
        thread_local.transport = transport
        thread_local.sftp = sftp
        with connections_lock:
            connections.append((transport, sftp))
        return sftp

    def put_one(local_path: str, rel_path: str) -> str:
        remote_path = f"{remote_plugin}/{rel_path}"
        get_worker_sftp().put(local_path, remote_path)
        with _print_lock:
            print("put", remote_path)
        return remote_path

    try:
        with ThreadPoolExecutor(max_workers=worker_count) as pool:
            futures = [
                pool.submit(put_one, local_path, rel_path)
                for local_path, rel_path in runtime_files
            ]
            for future in as_completed(futures):
                future.result()
    finally:
        for transport, sftp in connections:
            close_sftp(transport, sftp)

    return len(runtime_files)


def main() -> None:
    host = require_env("SFTP_HOST")
    user = require_env("SFTP_USER")
    password = require_env("SFTP_PASS")
    port = int(os.environ.get("SFTP_PORT", "22"))
    workers = int(os.environ.get("SFTP_WORKERS", str(DEFAULT_WORKERS)))

    print(f"Connecting to {host}:{port} as {user}...")
    transport, sftp = open_sftp(host, port, user, password)

    try:
        print("cwd:", sftp.normalize("."))
        list_dir(sftp, ".")

        plugins_dir = find_plugins_dir(sftp)
        if not plugins_dir:
            print("ERROR: could not find wp-content/plugins", file=sys.stderr)
            sys.exit(1)

        remote_plugin = f"{plugins_dir}/{PLUGIN_SLUG}"
        print("plugins_dir:", plugins_dir)
        ensure_dir(sftp, remote_plugin)

        runtime_files = iter_runtime_files()
        keep_files = {rel for _, rel in runtime_files}

        for rel_dir in runtime_dirs([rel for _, rel in runtime_files]):
            ensure_dir(sftp, f"{remote_plugin}/{rel_dir}")

        uploaded = upload_files_parallel(
            host, port, user, password, remote_plugin, runtime_files, workers
        )
        print(f"\nUploaded {uploaded} files to {remote_plugin}")

        cleaned = clean_remote_plugin(sftp, remote_plugin, keep_files)
        print(f"Removed {cleaned} unused remote path(s)")

        list_dir(sftp, remote_plugin)
        if is_dir(sftp, f"{remote_plugin}/includes"):
            list_dir(sftp, f"{remote_plugin}/includes")
    finally:
        close_sftp(transport, sftp)

    print("DONE")


if __name__ == "__main__":
    main()
