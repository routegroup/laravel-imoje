# Conventional Commits

Every git commit in this repo MUST follow [Conventional Commits](https://www.conventionalcommits.org/).

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

## Types

| Type | Use when |
| --- | --- |
| `feat` | A new user-facing capability |
| `fix` | A bug fix |
| `docs` | Documentation only |
| `style` | Formatting, no logic change |
| `refactor` | Restructure without behaviour change |
| `perf` | Performance improvement |
| `test` | Adding or correcting tests |
| `build` | Build system or dependencies |
| `ci` | CI configuration |
| `chore` | Maintenance that does not fit elsewhere |
| `revert` | Reverts a previous commit |

## Rules

- Description is imperative, lowercase, no trailing period: `feat: add BLIK payment method`
- Scope is optional kebab-case: `fix(webhook): validate signatures`
- Breaking changes: `feat!: drop PHP 8.1` or a `BREAKING CHANGE:` footer
- Subject line ≤ 72 characters
- Body (when present) explains why, not what
- Do not mention Cursor, Copilot, or that an agent wrote the commit

```
# ❌ BAD
Add BLIK support
Fixed webhook signatures.
feat: Added a new payment method.

# ✅ GOOD
feat: add BLIK payment method
fix(webhook): validate signatures
build(deps): bump actions/checkout from 6 to 7
feat!: drop PHP 8.1 support
```
