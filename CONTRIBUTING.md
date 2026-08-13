# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/evilnet/dotpay).

## Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - Check the code style with ``$ composer format``.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Follow the versioning policy** — see [Versioning](#versioning) below.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Versioning

Tags follow [SemVer v2.0.0](https://semver.org/) of **this** package's PHP API. The contract is the entire namespace `Routegroup\Imoje\Payment\*` — clients, DTOs, enums, exceptions, factories, `BaseDto`, `Utils`, `Url`, and the service provider. Nothing in that namespace is free to break in a minor. The number is not imoje's OpenAPI `info.version` and not the Laravel major. Why: [ADR 0001](docs/adr/0001-package-semver.md).

Stay on the `1.x` line. Do not cut a major to wash `v1.4.0` (that tag mirrored an imoje document label). The unpublished API catch-up from #46 is a minor (`1.5.0`) when that GitHub Release is cut.

The published version is the git tag `vX.Y.Z`. Packagist reads tags. Do not put a `version` field in `composer.json`. Feature PRs do not bump the number; a GitHub Release / tag does, and updates `CHANGELOG.md`.

**Major** — documented working consumer code stops working: public symbol removed or signature/type changed; the same documented inputs yield a different documented result; a documented exception disappears or appears on a previously succeeding documented path (e.g. `InvalidSignatureException` from signature verification). Dropping a PHP or Laravel major is also major.

**Minor** — new public symbol; optional field or new enum case; new Laravel major in `illuminate/contracts` with no PHP signature change; loosening undocumented throw-on-incomplete-payload behaviour (e.g. omitted response enum hydrating to `null`).

**Patch** — bugfix that leaves the documented contract unchanged.

Laravel support lives in Composer constraints. One tag may support several Laravel majors.

Each GitHub Release body lists:

1. Bump kind (patch / minor / major) and why
2. imoje changelog entries applied (date and guid or bump.sh link)
3. Alignment as dates/guids — not “package x.y.z equals imoje document version”

Upstream-watch issue titles stay date-based (`Adjust package: imoje API (2026-07-02)`).

## Running Tests

``` bash
composer test
```

or with coverage

``` bash
composer test-coverage
```

## Running Static Analyse

``` bash
composer analyse
```

**Happy coding**!
