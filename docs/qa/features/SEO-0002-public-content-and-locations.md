# SEO-0002 — Public content and location landing pages

## Requirement

Implement SEO-2 and SEO-3 through the existing SEO-1 architecture:

- core Sikh Matrimony, How It Works, Verification and Safety, and FAQ pages;
- Delhi, Punjab, Chandigarh, Canada, Toronto and Vancouver landing pages;
- unique metadata, canonical URLs, breadcrumbs and internal links;
- sitemap and indexing-allowlist coverage;
- no public member data or duplicated doorway-page content.

## Architecture

- `SeoLandingPageController` remains a thin route-to-view coordinator.
- `SeoLandingPageCatalog` is the reviewed source of public content.
- `Pages/Seo/LandingPage` is the shared responsive presentation.
- `SeoMetadata` adds `BreadcrumbList` and visible-FAQ-matched `FAQPage`
  structured data.
- `Config\Seo` remains the indexing and sitemap source of truth.
- Existing Bootstrap, `app.css` and `custom.css` classes are reused; no CSS or
  JavaScript file was added.

## Privacy and content constraints

- Location pages do not query members or publish member counts.
- No member name, profile reference, photograph or contact detail is exposed.
- Pages describe regional considerations without claiming local inventory.
- Canada pages do not provide immigration or legal advice.
- Verification content states that badges are signals, not guarantees.

## QA review

| Area | Result | Evidence / outstanding work |
|---|---|---|
| Requirement QA | PASS | Four core and six location routes are implemented with unique content. |
| Code QA | PASS | One catalog, controller and shared view reuse SEO-1 metadata and layout architecture. |
| UI QA | NOT VERIFIED | Desktop, tablet and mobile browser rendering must be checked in the configured runtime. |
| Validation QA | NOT APPLICABLE | No user-submitted input was added. |
| Database QA | NOT APPLICABLE | No query, schema or migration was added. |
| Security QA | PASS | Static allowlisted content contains no member data and private routes remain unchanged. |
| Regression QA | NOT VERIFIED | Public header/footer, sitemap rendering and existing public pages require runtime regression checks. |

## Required runtime verification

1. Open all ten pages and verify HTTP 200, one H1 and responsive layout.
2. Confirm production pages emit `index,follow` and no noindex header.
3. Confirm QA/development pages remain noindex.
4. Verify unique title, description and canonical on every page.
5. Validate BreadcrumbList and FAQPage JSON-LD against visible content.
6. Confirm `/sitemap.xml` contains all ten URLs and no member URL.
7. Crawl the public site for broken internal links or orphaned pages.
8. Run Lighthouse on the Sikh Matrimony, FAQ, Canada and Toronto pages.

## QA Gate

**NOT VERIFIED** — code and content review are complete; PHP runtime and browser
verification have not been run in this environment.