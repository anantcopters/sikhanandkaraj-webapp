# SikhanandKaraj SEO Implementation, Operations and QA Guide

## 1. Purpose

This document is the long-term reference for SikhanandKaraj search-engine optimisation, Google Search Console, Google Tag Manager, Google Analytics 4, production verification, QA and future SEO work.

It should be reviewed whenever public routes, SEO landing pages, layouts, robots directives, sitemap generation, canonical URLs, analytics, registration flows or deployment behaviour changes.

The primary rule is that SEO must improve discovery of public SikhanandKaraj content without exposing member, authentication, account, administrative or other private application data.

---

## 2. SEO architecture and safety boundary

SikhanandKaraj uses an explicit public SEO surface rather than treating every application route as indexable.

Production is the only environment intended for search-engine indexing. Development and QA must remain non-indexable.

### Public SEO responsibilities

The application provides:

- page-specific document titles;
- meta descriptions;
- canonical URLs;
- robots directives;
- Open Graph metadata;
- Twitter metadata;
- structured data where applicable;
- public SEO landing pages;
- sitemap generation;
- robots handling;
- production-only indexing behaviour.

### Private application boundary

The following categories must not become search-indexable merely to increase the number of indexed pages:

- login/authentication flows;
- registration workflow pages that are not intentionally public landing pages;
- member dashboards;
- member profile/private profile routes;
- account settings;
- Aadhaar or identity-verification content;
- video introduction/private media;
- admin and super-admin routes;
- field-officer/private operational routes;
- private APIs or action endpoints;
- URLs containing sensitive member information.

Hiding a link from the UI is not an SEO/security control. Server-side authentication and authorisation remain mandatory.

---

## 3. Production prerequisites

Before Search Console submission or indexing checks, verify the current SEO release is deployed to production.

Production must use the intended deployment setting:

```ini
APP_DEPLOYMENT = production
```

Development and QA must not be configured as production simply to test SEO.

Verify the production versions of:

- `/`
- `/robots.txt`
- `/sitemap.xml`
- `/sikh-matrimony`
- other public SEO landing pages configured by the application.

For each public page verify:

- HTTP response is successful;
- canonical URL uses the production domain;
- robots directive is indexable only where intended;
- title is meaningful and unique;
- description is relevant and not duplicated unnecessarily;
- no member/private information is present in metadata or structured data;
- internal links resolve without redirect loops or 5xx errors.

---

## 4. Google Search Console setup

### 4.1 Create a Domain property

Use Google Search Console and create a **Domain** property for:

```text
sikhanandkaraj.com
```

A Domain property is preferred because it covers the domain across supported protocols and subdomains.

### 4.2 Verify ownership through DNS

Google provides a TXT record similar to:

```text
google-site-verification=<google-generated-value>
```

Add the exact value supplied by Google to the authoritative DNS provider and complete verification in Search Console.

Do not remove the verification TXT record after successful verification unless ownership is deliberately being changed.

### 4.3 Submit the sitemap

In Search Console:

```text
Indexing -> Sitemaps
```

Submit:

```text
sitemap.xml
```

Expected production URL:

```text
https://sikhanandkaraj.com/sitemap.xml
```

The target status is **Success**.

Do not repeatedly resubmit a successful sitemap. The sitemap is discovery information, not a command forcing Google to index every URL.

### 4.4 Inspect priority pages

Use Search Console URL Inspection for the important public pages, especially after the initial SEO launch or material page changes.

Examples include:

```text
https://sikhanandkaraj.com/
https://sikhanandkaraj.com/sikh-matrimony
https://sikhanandkaraj.com/how-it-works
https://sikhanandkaraj.com/verification-and-safety
https://sikhanandkaraj.com/membership-plans
```

Also inspect the important location landing pages currently present in the application's SEO catalogue.

For a priority URL:

1. Inspect the URL.
2. Use **Test Live URL** where necessary.
3. Confirm the page can be fetched.
4. Confirm indexing is allowed.
5. Use **Request Indexing** for important new or materially changed pages where appropriate.

Do not manually request indexing for every application URL.

### 4.5 Monitor indexing

Review Search Console's indexing reports after deployment.

Important states include:

- Indexed;
- Discovered - currently not indexed;
- Crawled - currently not indexed;
- Duplicate without user-selected canonical;
- Blocked by robots.txt;
- Excluded by noindex;
- Redirect error;
- Server error (5xx).

Not every excluded URL is a defect. Private/member/admin routes are expected to remain outside the public index.

Do not respond to normal crawl/indexing delays by changing titles and content every day. Establish a stable baseline and investigate persistent patterns.

---

## 5. Google Analytics 4 setup

Create a GA4 property for the production website and a Web Data Stream for:

```text
https://sikhanandkaraj.com
```

Recommended naming:

```text
Account: SikhanandKaraj
Property: SikhanandKaraj Website
Stream: SikhanandKaraj Production
Time zone: India
Currency: INR
```

Keep Enhanced Measurement enabled initially unless a later privacy/measurement review requires specific changes.

The GA4 web stream supplies a Measurement ID in this format:

```text
G-XXXXXXXXXX
```

GA4 is deployed through Google Tag Manager rather than by separately installing a direct `gtag.js` implementation in the application layout.

This avoids maintaining two competing analytics installation paths.

---

## 6. Google Tag Manager setup

The production web container is:

```text
GTM-N9C3FRL2
```

The application should load GTM centrally from the common layout and only under the intended production deployment configuration.

The production environment contains the GTM configuration required by the application. Development and QA should not accidentally send routine developer/test activity into production analytics.

GTM uses two installation portions:

- the main GTM script as high in `<head>` as practical;
- the GTM `noscript` iframe immediately after `<body>`.

Do not duplicate the GTM snippets across individual pages.

### 6.1 GA4 inside GTM

Within GTM create/configure the Google tag using the GA4 Measurement ID and an all-pages trigger appropriate to the current Google Tag Manager interface.

The intended flow is:

```text
SikhanandKaraj production website
        -> Google Tag Manager
        -> Google Analytics 4
```

Do not independently add another direct GA4 installation to the common layout unless the analytics architecture is deliberately changed and duplicate collection has been ruled out.

### 6.2 Preview and publish

Before publishing a GTM workspace change:

1. Use GTM Preview/Tag Assistant.
2. Connect to the production website or the specifically intended test target.
3. Confirm the Google/GA4 tag fires where expected.
4. Confirm it does not fire multiple times unexpectedly.
5. Submit and publish the GTM container version.
6. Give the version a descriptive name.

### 6.3 Verify collection

Use GA4 Realtime after publishing.

Browse several production public pages and confirm traffic appears. Verify at least page/location/device information is arriving before proceeding to custom business-event work.

---

## 7. Link Search Console and GA4

After Search Console and GA4 are both operational:

```text
GA4 -> Admin -> Product links -> Search Console Links
```

Link:

- the verified `sikhanandkaraj.com` Search Console property; and
- the production GA4 web stream.

Where required by the current GA4 interface, publish the Search Console report collection from the Reports Library.

This provides a useful combined view of organic acquisition and onsite behaviour. Search Console and GA4 metrics have different collection models, so their numbers should not be expected to match exactly.

---

## 8. Analytics event strategy

Basic GA4 page measurement is only the first stage. SEO needs to answer whether organic visitors become useful SikhanandKaraj users.

Initial event candidates are:

| Event | Meaning | Initial classification |
|---|---|---|
| `sign_up` | Registration successfully completed | Key event |
| `login` | Member successfully logged in | Normal event |
| `search` | Member performs an applicable profile search | Normal event |
| `generate_lead` | Quick/prelaunch profile successfully submitted | Key event |
| `membership_plan_viewed` | Membership plans viewed | Normal event |

Use Google's recommended event names where they accurately represent the application action.

Future application-specific events may include:

- `profile_completed`;
- `interest_sent`;
- `interest_accepted`;
- `membership_activated`.

Do not create large numbers of events without a measurement question they answer.

### 8.1 Measure successful outcomes, not button clicks

Do not treat a Register button click as a completed registration.

Correct measurement should follow the real application outcome:

```text
Registration started
    -> validation
    -> OTP / required verification
    -> successful member creation
    -> analytics success event
```

The same rule applies to leads, payments, interests and other transactional actions.

Where reliable measurement requires knowledge of a server-confirmed success, add a small application `dataLayer` event at the successful state and let GTM map that event to GA4.

### 8.2 Key events

Initially keep key events focused on high-value outcomes, for example:

- successful registration (`sign_up`);
- successful prelaunch/quick-registration lead (`generate_lead`).

Do not mark routine events such as page views, scrolling or login as key events simply to increase conversion counts.

---

## 9. Analytics privacy rules

SikhanandKaraj is a matrimonial platform and analytics must be deliberately privacy-minimising.

Do not send personally identifiable or private member data to GA4/GTM, including:

- member name;
- email address;
- mobile number;
- parent mobile number;
- exact date of birth;
- Aadhaar information;
- member/profile identifiers where they can identify a person;
- candidate/interest-recipient identity;
- private photo or video URLs;
- messages;
- authentication tokens;
- private contact details.

Avoid transmitting matrimonial attributes/preferences unless a later privacy-reviewed analytics requirement explicitly justifies an aggregated/non-identifying design.

Analytics should measure behaviour and outcomes, not export member profiles.

---

## 10. SEO measurement funnel

The initial organic funnel to monitor is:

```text
Google Search impression
    -> Google Search click
    -> SEO/public landing page
    -> registration/quick-registration intent
    -> successful registration or lead
```

Search Console answers questions such as:

- which queries generate impressions;
- which pages appear in Google;
- clicks;
- click-through rate;
- average position;
- device/country search performance.

GA4 answers questions such as:

- landing-page traffic;
- engagement;
- acquisition source/medium;
- registrations/leads;
- key events;
- onsite journey after landing.

Evaluate SEO using both systems rather than ranking alone.

---

## 11. QA checklist - every SEO-affecting release

QA should perform this checklist whenever routes, public pages, layouts, SEO metadata, sitemap/robots handling, redirects, analytics or deployment configuration changes.

### Environment and indexing

- [ ] Development remains non-indexable.
- [ ] QA remains non-indexable.
- [ ] Production public SEO pages have intended index/follow behaviour.
- [ ] Private/member/admin pages remain non-indexable.
- [ ] Production domain is used in canonical URLs.

### Metadata

- [ ] Exactly one meaningful document title is rendered.
- [ ] Meta description is present where intended.
- [ ] Canonical is correct and absolute.
- [ ] Open Graph title/description/URL are correct.
- [ ] Structured data is valid and contains no private/member data.
- [ ] No environment/local/QA URLs appear in production metadata.

### Routes and HTTP behaviour

- [ ] Public SEO routes return expected 2xx responses.
- [ ] Removed/renamed routes have intentional redirect/404 behaviour.
- [ ] No redirect loops.
- [ ] No unexpected 5xx responses.
- [ ] Authentication boundaries cannot be bypassed through direct URLs.
- [ ] SEO changes have not made private member resources public.

### Sitemap and robots

- [ ] `/robots.txt` is accessible in production.
- [ ] `/sitemap.xml` is accessible in production.
- [ ] Sitemap contains only intended canonical public URLs.
- [ ] Sitemap does not contain login, account, member, admin or private-action URLs.
- [ ] Sitemap URLs return valid responses.
- [ ] Robots rules do not accidentally block intended public SEO pages.

### GTM and GA4

- [ ] GTM container is present in production when configured.
- [ ] GTM is not unintentionally collecting development/QA traffic.
- [ ] Tag Assistant Preview reports expected tag firing.
- [ ] GA4 Realtime receives production traffic.
- [ ] GA4 is not installed twice.
- [ ] No PII/private member data is visible in analytics event parameters.
- [ ] Key events fire only after successful outcomes.
- [ ] Events do not double-fire after refresh/back navigation unless expected.

### Search Console after release

- [ ] Priority changed URLs can be fetched using URL Inspection.
- [ ] Indexing is allowed on intended public URLs.
- [ ] Sitemap remains successful.
- [ ] New 5xx/redirect/canonical/noindex problems are investigated.

---

## 12. Routine SEO operating schedule

### Weekly during initial growth

Review Search Console for:

- indexing errors;
- new queries;
- pages gaining impressions;
- pages with impressions but weak CTR;
- unexpected drops;
- 5xx/redirect/canonical issues.

Review GA4 for:

- organic landing pages;
- engagement;
- registrations/leads from organic traffic;
- device differences;
- unexpected tracking changes.

### Monthly

Compare:

- organic impressions;
- clicks;
- CTR;
- query/page position trends;
- organic sessions/users;
- registrations/leads/key events;
- highest-value landing pages;
- pages with impressions but insufficient content/CTR;
- technical/Core Web Vitals trends.

Do not judge SEO solely from a single day's ranking movement.

---

## 13. Future SEO phases

The following phases should be implemented based on Search Console/GA4 evidence rather than creating large amounts of content blindly.

### Phase A - Complete conversion measurement

Implement reliable application-driven `dataLayer` events for successful registration, quick registration/prelaunch lead and selected downstream business actions.

Objectives:

- attribute registrations/leads to organic landing pages;
- identify high-converting search topics;
- distinguish traffic growth from useful-user growth.

### Phase B - Content expansion based on search demand

Use Search Console query data to identify real Sikh matrimonial search demand.

Expand public content only where the site can provide useful, unique information. Candidate categories may include:

- high-value Indian state/city Sikh matrimonial landing pages;
- Anand Karaj guidance;
- Sikh matrimonial process/guides;
- trust, verification and safety education;
- profile creation guidance;
- family-focused matrimonial guidance;
- genuine FAQs derived from user/search needs.

Avoid thin programmatic pages where only a city/state word changes. Each indexable landing page should have meaningful unique value.

### Phase C - Internal linking and information architecture

Strengthen contextual links between:

- the home page;
- Sikh matrimony hub;
- location pages;
- how-it-works;
- verification/safety;
- membership information;
- future guides/resources.

Use descriptive anchors. Avoid excessive keyword-stuffed footer links.

### Phase D - Structured data review

Maintain only schema that accurately represents visible page content.

Potential structured-data work should be evaluated page-by-page. Never manufacture ratings/reviews or schema content that users cannot see.

Validate material schema changes using Google's current rich-results/schema testing tools where applicable.

### Phase E - Core Web Vitals and performance

Use Search Console Core Web Vitals plus field/lab performance tools to prioritise real bottlenecks.

Likely areas to monitor include:

- LCP/hero image delivery;
- image dimensions/compression;
- unnecessary JavaScript;
- render-blocking assets;
- caching/compression;
- layout shift;
- font loading;
- mobile performance;
- server response time.

Optimise from measured evidence rather than adding a new frontend build architecture solely for SEO.

### Phase F - CTR optimisation

Once pages have meaningful impressions, identify pages with good visibility but weak CTR.

Test improvements to:

- title clarity;
- search-intent alignment;
- descriptions;
- visible page proposition;
- freshness where genuinely relevant.

Do not rewrite titles repeatedly without enough data to evaluate the result.

### Phase G - Authority and trust

Improve legitimate authority through:

- useful Sikh/Anand Karaj resources;
- transparent safety/verification information;
- organisation/contact transparency;
- high-quality community references and partnerships;
- earned editorial links and mentions.

Do not buy bulk backlinks, participate in link schemes or create doorway pages.

### Phase H - Location expansion

Expand beyond current target locations only when there is sufficient user/search value and genuinely useful localised content.

For each proposed location verify:

- search demand;
- ability to provide unique content;
- correct canonical/indexing behaviour;
- sitemap inclusion;
- internal links;
- conversion performance after launch.

### Phase I - SEO content governance

Before adding a new indexable page, answer:

1. What search/user problem does this page solve?
2. Is it materially different from an existing page?
3. Should Google index it?
4. What is its canonical URL?
5. Where will users discover it through internal links?
6. Does it expose any private/member data?
7. Should it be in the sitemap?
8. How will its performance/conversion be measured?

If these questions do not have clear answers, the page should not automatically be added to the SEO surface.

---

## 14. SEO regression rules for developers

When changing routes, controllers or layouts:

- do not remove/change an indexed URL without considering redirect impact;
- do not make authenticated/member data public for SEO;
- do not hardcode development/QA domains in metadata;
- do not create a second independent SEO metadata architecture;
- do not add direct GA4 installation while GTM owns GA4 deployment;
- do not add private URLs to the sitemap;
- do not assume `robots.txt` protects confidential information;
- do not rely on frontend hiding for access control;
- preserve canonical consistency;
- verify production-only indexing behaviour after deployment.

When adding a new public SEO page, update the existing SEO catalogue/configuration and sitemap flow rather than building a parallel mechanism.

---

## 15. Incident / troubleshooting reference

### Public page not appearing in Google

Check, in order:

1. Production URL returns a successful response.
2. Page is not authentication-protected.
3. Robots meta permits indexing.
4. `robots.txt` does not unintentionally block crawling.
5. Canonical points to the intended production URL.
6. URL is discoverable through internal links and/or sitemap.
7. Search Console URL Inspection result.
8. Whether Google has crawled but chosen not to index the page.
9. Page uniqueness and usefulness compared with existing pages.

### GA4 Realtime shows no traffic

Check:

1. Production GTM configuration is present.
2. GTM container version is published.
3. Tag Assistant sees the production container.
4. Google/GA4 tag fires.
5. Correct GA4 Measurement ID is configured.
6. Browser/ad-blocker/consent behaviour is considered during testing.
7. No duplicate/competing analytics installation exists.

### Analytics numbers appear too high

Check for:

- duplicate GTM installation;
- direct GA4 plus GTM GA4 running together;
- duplicate triggers;
- events firing on both click and success;
- refresh/back-navigation event replay;
- development/QA traffic contaminating production.

### Search Console reports private URLs

Discovery does not necessarily mean indexing. Verify:

- authentication/authorisation remains intact;
- robots meta is non-indexable;
- private URLs are absent from sitemap;
- public pages are not unnecessarily linking to crawlable private parameter URLs;
- no sensitive information is returned to unauthorised requests.

---

## 16. Current milestone status

At the time this document was introduced, the project had completed the core application SEO implementation and the initial Google measurement setup process, including:

- public SEO architecture and metadata work;
- production indexing controls;
- sitemap/robots work;
- public SEO landing-page work;
- Google Search Console setup process;
- GA4 property/web-stream setup process;
- Google Tag Manager container `GTM-N9C3FRL2` integration process;
- GA4 configured through GTM;
- GTM Preview verification;
- GTM container publication;
- GA4 Realtime traffic verification.

The next operational priorities are:

1. maintain/verify Search Console sitemap and indexing health;
2. link Search Console with GA4 if not already linked;
3. establish a baseline before frequent SEO changes;
4. implement privacy-safe, success-based registration/lead analytics events;
5. use Search Console + GA4 evidence to choose the next content and technical SEO work.

---

## 17. External platform note

Google Search Console, Google Analytics and Google Tag Manager interfaces change over time. Menu labels in this document describe the workflow used at the time of implementation, but QA/operations should use the current Google interface and official Google documentation when labels move.

The architectural rules in this document remain the source of truth for SikhanandKaraj:

- production-only indexing;
- explicit public SEO surface;
- private member data protection;
- canonical consistency;
- sitemap limited to intended public URLs;
- GTM as the GA4 deployment layer;
- privacy-safe analytics;
- measurement based on successful business outcomes;
- future SEO decisions driven by observed search and conversion data.
