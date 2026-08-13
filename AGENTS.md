## Agent skills

### Issue tracker

GitHub Issues (`gh` CLI); external PRs are not a triage surface. See `docs/agents/issue-tracker.md`.

### Triage labels

Canonical names: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.

### Versioning

Package SemVer of `Routegroup\Imoje\Payment\*` (not imoje document version, not Laravel major). Rules: `CONTRIBUTING.md`. Why: `docs/adr/0001-package-semver.md`. Source of truth is the git tag `vX.Y.Z`.

### Usage docs

Files directly in `docs/` (`api.md`, `paywall.md`, `notifications.md`) are consumer usage exclusively. Agent process: `docs/agents/`. Domain: `CONTEXT.md` and `docs/adr/`.

### Commits

Always use [Conventional Commits](https://www.conventionalcommits.org/). See `docs/agents/conventional-commits.md`.

### Branch naming

Prefer `{issue_number}-{short-slug-from-issue-title}`. No issue: `agent/{slug}`. See `docs/agents/branch-naming.md`.
