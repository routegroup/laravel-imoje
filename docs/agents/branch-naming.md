# Branch naming

Prefer:

```text
{issue_number}-{short-slug-from-issue-title}
```

Example: issue `#71` titled "Residual domain cleanup" → `71-residual-domain-cleanup`.

## Slug rules

- Lowercase ASCII
- Words separated by hyphens
- Drop filler words if needed; keep it short
- Derive the slug from the issue title (or a clear shortening of it)

## No issue

Happy path is issue-first. If there truly is no issue (e.g. tiny docs hotfix):

- Branch must be `agent/{slug}` (short descriptive slug, no leading number)
- The PR **Linked issue** section must be `n/a — <one-line justification>`
