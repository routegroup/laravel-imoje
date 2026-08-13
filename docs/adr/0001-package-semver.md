# Package SemVer is ours, not imoje's or Laravel's

`v1.4.0` mirrored imoje OpenAPI `info.version`. That label is a document deploy badge, not product SemVer — bump.sh later showed `1.6.2` then `1.0.0` — so we version `Routegroup\Imoje\Payment\*` ourselves. Binding the package major to Laravel would spend Composer `^` on the framework while this package already supports several Laravel majors in one tag.

**Considered options.** Mirror imoje document versions (rejected: non-monotonic). Package major = Laravel major (rejected: one tag already serves `^11||^12||^13`; a PHP breaking change would have no free major). Own SemVer (accepted).

**Consequences.** Alignment with imoje is dates and guids in the changelog, not a second number in PHP. Tags are the only published version; `composer.json` has no `version` field. `v1.4.0` stays a historical accident. The unpublished API catch-up from #46 is a minor (`1.5.0`) when that release is cut.
