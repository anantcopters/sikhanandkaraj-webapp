# SEO-0001 — Technical SEO foundation

## Requirement

Implement SEO-1 without creating a parallel public application architecture:

- centralized public metadata and canonical URLs;
- fail-closed indexing boundaries for private and operational routes;
- XML sitemap and deliberate robots policy;
- Organization and WebSite structured data;
- correct noindex behavior for private layouts and error responses;
- focused homepage image and asset-loading improvements;
- consistent authentication filters on member-only Account Settings and Video
  Introduction routes.

## Implementation scope

- `Config\Seo` is the single allowlist for indexable paths and sitemap routes.
- `SeoRobotsFilter` applies `X-Robots-Tag` to every non-allowlisted response.
- Non-production deployments remain noindex even when they render public pages.
- `SeoMetadata` supplies normalized metadata to the existing public layout.
- `SitemapController` returns only named, allowlisted public routes.
- Existing Admin, Field Officer and Prelaunch layouts explicitly remain noindex.
- Private member media and member profiles remain outside the sitemap.
- The homepage hero is served as WebP and the rendered logo has intrinsic
  dimensions.

## QA review

| Area | Result | Evidence / outstanding work |
|---|---|---|
| Requirement QA | PASS | Code inspection covers the stated SEO-1 scope. |
| Code QA | PASS | Existing routes, filters, controllers and layouts are extended; no parallel public architecture or third-party dependency was introduced. |
| UI QA | NOT VERIFIED | Browser rendering and responsive checks require the configured application runtime. |
| Validation QA | NOT APPLICABLE | SEO-1 adds no user-submitted fields. |
| Database QA | NOT APPLICABLE | No schema or data change. |
| Security QA | PASS | Indexing is fail-closed; member-only route filters were added and the duplicate unfiltered report route was removed. |
| Regression QA | NOT VERIFIED | Member login, Account Settings, Video Introduction, public registration and production Apache redirects require runtime verification. |

## Required runtime verification

1. Confirm production uses `APP_DEPLOYMENT=production` and
   `app.baseURL='https://sikhanandkaraj.com/'`.
2. Confirm `/`, `/about-us` and `/membership-plans` emit indexable metadata and
   do not emit `X-Robots-Tag: noindex` in production.
3. Confirm the same pages emit noindex in development and QA.
4. Confirm login, member, admin, field-officer, prelaunch, API, PDF and media
   responses emit `X-Robots-Tag: noindex, nofollow, noarchive`.
5. Validate `/sitemap.xml` and confirm it contains no private URL.
6. Validate `robots.txt` and Organization/WebSite JSON-LD.
7. Test HTTP/www/trailing-slash redirects through the production proxy and
   Apache path.
8. Run Lighthouse mobile and desktop before and after deployment.

## QA Gate

**NOT VERIFIED** — static implementation review is complete, but runtime and
browser checks have not been executed in this environment.
