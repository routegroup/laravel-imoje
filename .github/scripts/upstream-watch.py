#!/usr/bin/env python3
"""Open package-adjustment issues when imoje API or Laravel major moves.

Spec: https://github.com/routegroup/laravel-imoje/issues/37
"""

from __future__ import annotations

import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime
from html.parser import HTMLParser
from typing import Any

RSS_URL = "https://bump.sh/pgw/doc/imoje-api/changes.rss"
PACKAGIST_URL = "https://repo.packagist.org/p2/laravel/framework.json"
USER_AGENT = "routegroup/laravel-imoje upstream-watch"
TRACKING_TITLE = "Upstream watcher RSS baseline"
TRACKING_MARKER = "watcher-baseline:imoje-rss"
IMOJE_MARKER_PREFIX = "imoje-api:"
LARAVEL_MARKER_PREFIX = "laravel-major:"
LABEL_PACKAGE_ADJUSTMENT = "package-adjustment"
LABEL_ENHANCEMENT = "enhancement"
GUID_RE = re.compile(
    r"[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}",
    re.IGNORECASE,
)
PRERELEASE_RE = re.compile(r"(^dev-)|(-dev)|alpha|beta|rc|snapshot", re.IGNORECASE)


class _TextExtractor(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self._chunks: list[str] = []

    def handle_data(self, data: str) -> None:
        self._chunks.append(data)

    def text(self) -> str:
        return re.sub(r"\n{3,}", "\n\n", "".join(self._chunks)).strip()


def html_to_text(raw: str) -> str:
    parser = _TextExtractor()
    parser.feed(raw)
    parser.close()
    return parser.text()


def fetch(url: str) -> bytes:
    request = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            if response.status != 200:
                raise RuntimeError(f"{url} returned HTTP {response.status}")
            return response.read()
    except urllib.error.URLError as error:
        raise RuntimeError(f"failed to fetch {url}: {error}") from error


def parse_rss(xml_bytes: bytes) -> list[dict[str, str]]:
    root = ET.fromstring(xml_bytes)
    items: list[dict[str, str]] = []
    for item in root.findall("./channel/item"):
        guid = (item.findtext("guid") or "").strip()
        if not guid:
            continue
        pub_raw = item.findtext("pubDate") or ""
        try:
            published = parsedate_to_datetime(pub_raw)
        except (TypeError, ValueError):
            published = datetime.now(timezone.utc)
        items.append(
            {
                "guid": guid,
                "title": (item.findtext("title") or "").strip(),
                "link": (item.findtext("link") or "").strip(),
                "description": item.findtext("description") or "",
                "date": published.date().isoformat(),
            }
        )
    return items


def is_stable_version(version: str) -> bool:
    return PRERELEASE_RE.search(version) is None


def _version_key(numeric: str) -> tuple[int, ...]:
    parts: list[int] = []
    for piece in numeric.split("."):
        try:
            parts.append(int(piece))
        except ValueError:
            break
    return tuple(parts)


def latest_stable_laravel_major(payload: dict[str, Any]) -> tuple[int, str]:
    packages = payload.get("packages", {}).get("laravel/framework") or []
    best_major = -1
    best_version = ""
    for package in packages:
        version = str(package.get("version") or "")
        if not is_stable_version(version):
            continue
        numeric = version.lstrip("v")
        try:
            major = int(numeric.split(".")[0])
        except (TypeError, ValueError):
            continue
        if major > best_major or (major == best_major and _version_key(numeric) > _version_key(best_version.lstrip("v"))):
            best_major = major
            best_version = version
    if best_major < 0:
        raise RuntimeError("no stable laravel/framework version on Packagist")
    return best_major, best_version


def repo() -> str:
    env = os.environ.get("GH_REPO", "").strip()
    if env:
        return env
    value = subprocess.check_output(
        ["gh", "repo", "view", "--json", "nameWithOwner", "--jq", ".nameWithOwner"],
        text=True,
    ).strip()
    if not value:
        raise RuntimeError("could not determine GH_REPO")
    return value


def gh_api(path: str, method: str = "GET", data: dict[str, Any] | None = None) -> Any:
    command = ["gh", "api", "--method", method, path]
    if method == "GET":
        command.insert(2, "--paginate")
    if data is None:
        completed = subprocess.run(command, check=False, capture_output=True, text=True)
    else:
        completed = subprocess.run(
            command + ["--input", "-"],
            check=False,
            capture_output=True,
            text=True,
            input=json.dumps(data),
        )
    if completed.returncode != 0:
        raise RuntimeError(
            f"gh api {method} {path} failed ({completed.returncode}): {completed.stderr.strip()}"
        )
    if not completed.stdout.strip():
        return None
    parsed = json.loads(completed.stdout)
    if isinstance(parsed, list) and parsed and isinstance(parsed[0], list):
        flat: list[Any] = []
        for page in parsed:
            flat.extend(page)
        return flat
    return parsed


def list_issues(repository: str) -> list[dict[str, Any]]:
    raw = gh_api(f"repos/{repository}/issues?state=all&per_page=100") or []
    if not isinstance(raw, list):
        raise RuntimeError("unexpected issues list payload")
    return [issue for issue in raw if "pull_request" not in issue]


def issue_markers(issues: list[dict[str, Any]]) -> tuple[set[str], set[int]]:
    imoje: set[str] = set()
    laravel: set[int] = set()
    for issue in issues:
        body = issue.get("body") or ""
        for match in re.finditer(rf"{re.escape(IMOJE_MARKER_PREFIX)}({GUID_RE.pattern})", body, re.I):
            imoje.add(match.group(1).lower())
        for match in re.finditer(rf"{re.escape(LARAVEL_MARKER_PREFIX)}(\d+)", body):
            laravel.add(int(match.group(1)))
    return imoje, laravel


def find_tracking_issue(issues: list[dict[str, Any]]) -> dict[str, Any] | None:
    matches = [issue for issue in issues if TRACKING_MARKER in (issue.get("body") or "")]
    if len(matches) > 1:
        raise RuntimeError("multiple RSS baseline tracking issues found")
    return matches[0] if matches else None


def tracking_guids(body: str) -> set[str]:
    return {match.group(0).lower() for match in GUID_RE.finditer(body)}


def ensure_label(repository: str, name: str, color: str, description: str) -> None:
    encoded = urllib.request.quote(name)
    command = ["gh", "api", f"repos/{repository}/labels/{encoded}"]
    completed = subprocess.run(command, check=False, capture_output=True, text=True)
    if completed.returncode == 0:
        return
    if "404" not in completed.stderr and "Not Found" not in completed.stderr:
        raise RuntimeError(f"failed to read label {name}: {completed.stderr.strip()}")
    gh_api(
        f"repos/{repository}/labels",
        method="POST",
        data={"name": name, "color": color, "description": description[:100]},
    )


def create_issue(
    repository: str,
    title: str,
    body: str,
    labels: list[str] | None = None,
) -> dict[str, Any]:
    payload: dict[str, Any] = {"title": title, "body": body}
    if labels:
        payload["labels"] = labels
    created = gh_api(f"repos/{repository}/issues", method="POST", data=payload)
    if not isinstance(created, dict) or "number" not in created:
        raise RuntimeError(f"issue create returned unexpected payload: {created}")
    print(f"opened #{created['number']}: {title}", file=sys.stderr)
    return created


def close_issue(repository: str, number: int) -> None:
    gh_api(f"repos/{repository}/issues/{number}", method="PATCH", data={"state": "closed"})


def tracking_body(guids: list[str]) -> str:
    lines = "\n".join(f"- `{guid}`" for guid in guids)
    return (
        "Operational store for the upstream watcher. Do not delete. "
        "Do not edit the guid list.\n\n"
        f"{TRACKING_MARKER}\n\n"
        "## Seen RSS guids (first-run snapshot)\n\n"
        f"{lines}\n"
    )


def imoje_issue_title(item: dict[str, str]) -> str:
    breaking = "breaking" in item["title"].lower()
    kind = "imoje API breaking" if breaking else "imoje API"
    return f"Adjust package: {kind} ({item['date']})"


def imoje_issue_body(item: dict[str, str]) -> str:
    summary = html_to_text(item["description"]) or "(no summary in RSS)"
    return (
        f"New imoje API changelog entry.\n\n"
        f"- [Changelog entry]({item['link']})\n"
        f"- RSS guid: `{item['guid']}`\n\n"
        f"```\n{summary}\n```\n\n"
        f"{IMOJE_MARKER_PREFIX}{item['guid']}\n"
    )


def laravel_issue_body(major: int, version: str) -> str:
    tag = version if version.startswith("v") else f"v{version}"
    return (
        f"A new stable Laravel major is on Packagist.\n\n"
        f"- Latest stable of this major: `{version}`\n"
        f"- [Packagist laravel/framework](https://packagist.org/packages/laravel/framework)\n"
        f"- [GitHub release {tag}](https://github.com/laravel/framework/releases/tag/{tag})\n\n"
        f"{LARAVEL_MARKER_PREFIX}{major}\n"
    )


def main() -> int:
    rss_items = parse_rss(fetch(RSS_URL))
    if not rss_items:
        raise RuntimeError(f"no items in {RSS_URL}")
    packagist = json.loads(fetch(PACKAGIST_URL))
    laravel_major, laravel_version = latest_stable_laravel_major(packagist)

    repository = repo()
    issues = list_issues(repository)
    imoje_seen, laravel_seen = issue_markers(issues)
    tracking = find_tracking_issue(issues)
    has_watcher_markers = bool(imoje_seen or laravel_seen)

    ensure_label(
        repository,
        LABEL_PACKAGE_ADJUSTMENT,
        "1d76db",
        "Upstream watcher: imoje API or Laravel major may need a package change",
    )
    ensure_label(
        repository,
        LABEL_ENHANCEMENT,
        "a2eeef",
        "New feature or request",
    )

    if tracking is None:
        if has_watcher_markers:
            raise RuntimeError(
                "RSS baseline tracking issue is missing, but watcher markers already exist"
            )
        created = create_issue(
            repository,
            TRACKING_TITLE,
            tracking_body([item["guid"] for item in rss_items]),
        )
        close_issue(repository, int(created["number"]))
        if laravel_major not in laravel_seen:
            create_issue(
                repository,
                f"Adjust package: Laravel {laravel_major}",
                laravel_issue_body(laravel_major, laravel_version),
                labels=[LABEL_PACKAGE_ADJUSTMENT, LABEL_ENHANCEMENT],
            )
        return 0

    body = tracking.get("body") or ""
    if TRACKING_MARKER not in body:
        raise RuntimeError("tracking issue is missing watcher-baseline:imoje-rss")
    baseline = tracking_guids(body)
    if not baseline:
        raise RuntimeError("tracking issue has no RSS guids")

    for item in rss_items:
        guid = item["guid"].lower()
        if guid in baseline or guid in imoje_seen:
            continue
        create_issue(
            repository,
            imoje_issue_title(item),
            imoje_issue_body(item),
            labels=[LABEL_PACKAGE_ADJUSTMENT, LABEL_ENHANCEMENT],
        )

    if laravel_major not in laravel_seen:
        create_issue(
            repository,
            f"Adjust package: Laravel {laravel_major}",
            laravel_issue_body(laravel_major, laravel_version),
            labels=[LABEL_PACKAGE_ADJUSTMENT, LABEL_ENHANCEMENT],
        )

    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as error:  # noqa: BLE001 — fail the Actions job
        print(error, file=sys.stderr)
        raise SystemExit(1)
