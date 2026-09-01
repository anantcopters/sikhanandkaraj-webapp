# Architecture

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## Core request flow

```text
Browser / CLI
  → named route or CLI bootstrap
  → access filter / environment gate
  → thin controller or command runner
  → dedicated validation
  → application/domain service
  → CI4 model / provider adapter
  → PostgreSQL / S3 / CloudFront / SMS / email
  → result object/array
  → redirect / JSON / escaped view / CLI output
```

## Layer responsibilities

- **Routes** define URL, HTTP method, route name and access filter. Environment-specific public routing is controlled by `APP_DEPLOYMENT` rather than by `CI_ENVIRONMENT` alone.
- **Filters** enforce member, administrator, super-administrator and SAK Volunteer authentication boundaries.
- **Controllers** read expected input, invoke validation/services and own HTTP response decisions. Business SQL and multi-table rules do not belong in controllers.
- **Validation classes** are the authoritative server-side input contract. Browser validation mirrors these rules only for user experience.
- **Services** own business rules, transaction boundaries, locking/concurrency decisions, idempotency and provider orchestration.
- **Models** own table configuration and table-specific persistence/query helpers.
- **Support/result objects** carry domain state without HTTP concerns.
- **Views/components** render already-authorized/normalized data and never query the database.
- **Configuration** owns environment/provider settings and feature/deployment gates.

## Public SEO content architecture

Public search content extends the normal CodeIgniter web architecture rather
than using a separate application or layout:

```text
Named public route
  → SeoLandingPageController
  → SeoLandingPageCatalog
  → SeoMetadata
  → Pages/Seo/LandingPage
  → Layouts/Main

## Authentication contexts

### Member

Members have one authenticated session contract with two entry points:

1. verified contact + password; or
2. passwordless OTP to the verified mobile.

Registration is mobile-first. A new account stays `PENDING` until mobile OTP verification succeeds, then becomes `ACTIVE`. Password recovery may identify by registered mobile or verified email, but recovery OTP delivery is to the verified mobile.

### Administrator

Administrator authentication is isolated from member authentication. `SUPER_ADMIN` owns privileged administrator/SAK Volunteer management; operational `ADMIN` access remains constrained by route/service authorization.

### SAK Volunteer / Field Officer

The `/field-officer` portal is a third authentication boundary. Public routes cover login, OTP verification/resend/cancel and self-registration. Protected routes use `fieldOfficerAuth` and expose dashboard, submitted-profile listing and authorized profile/photo viewing.

SAK Volunteer login OTP state is stored separately from member verification state. Self-registered volunteers remain review-controlled and cannot become active while review is pending/rejected.

## Member profile architecture

Profile data is split by business section and coordinated through dedicated services/models:

- Basic Details;
- Education & Profession;
- Family Details;
- Lifestyle;
- About Me;
- Photos/Media;
- Partner Preferences.

Community is owned by Family Details. Sikh & Religious Details are not part of the current displayed member journey/completion calculation.

Family Details may optionally associate a verified SAK Volunteer. Once persisted, the volunteer ID/code pair is immutable and database-protected.

## Standard member presentation contexts

The member-facing product has exactly four presentation contexts. New UI must reuse these instead of creating another card system:

1. **Dashboard thumbnail** → `app/Views/Components/Member/ProfileThumbnail.php`.
2. **Search / Matches card** → `app/Views/Components/Member/ProfileCard.php`.
3. **Interest card** → `app/Views/Components/Member/ProfileInterestCard.php`.
4. **Full member profile** → authoritative detailed other-member profile screen.

Shared summary shaping for multi-profile contexts belongs in `App\Services\Matchmaking\MemberProfilePresentationService`. Search/match percentage, interest actions/status and pagination remain with their owning services.

## Matchmaking and interaction architecture

`app/Services/Matchmaking` separates concerns:

- `PartnerPreferenceMatchService` evaluates configured structured partner preferences and compulsory rules.
- `MemberMatchmakingService` builds match candidates/results.
- `MemberSearchService` owns explicit member search/filter/pagination behavior.
- `MemberProfilePresentationService` standardizes common list-card presentation data.
- `MemberProfileViewService` assembles and authorizes full other-member profiles and records profile-view activity.
- `MemberInterestService` owns sent/received interest status workflows.
- `MemberInteractionService` owns relationship operations such as member block/interactions.

Member-to-member blocking is a relationship/privacy action and is separate from administrator account suspension.

## Media architecture

```text
Authorized controller/service
  → AwsMediaService
      → ImageProcessorService
      → MediaPathService
      → S3Service
      → CloudFrontService (delivery only)
  → private S3 objects
  → short-lived signed CloudFront URL after viewer authorization
```

Core rules:

- S3 is private; direct object delivery is denied.
- The database stores object keys and metadata, never signed URLs.
- Normal profile upload creates original, medium and thumbnail variants.
- CloudFront URLs are signed only after ownership/viewer/visibility authorization.
- Other-member listings use authorized thumbnails; detailed profiles use authorized medium variants.
- `INTERESTED_MEMBERS` visibility requires an interest relationship in either direction.
- Controllers never call the AWS SDK directly.

`AwsMediaService` currently has a constructor dependency on `CloudFrontService`; therefore CLI workflows that only upload to S3 still instantiate the CloudFront signer and require its private key to be readable by the CLI process user.

## Prelaunch architecture

Production can redirect public home/login/register entry points to the prelaunch collection flow when `APP_DEPLOYMENT=production`. This routing choice is intentionally separate from `CI_ENVIRONMENT`.

Prelaunch profiles and staged photos remain operationally separate from live member data until approved migration. Migration performs idempotency/concurrency checks, creates live member/profile/contact rows, sends approved photos through the normal private-media pipeline, and compensates S3 objects when a later database step fails where possible.

## Development/QA profile loader

`scripts/load_development_profiles.php` is CLI-only and bootstraps CodeIgniter with `Boot::bootConsole()`.

`DevelopmentProfileLoaderService`:

- accepts numeric source folders under `public/assets/images/male/<n>` and `female/<n>`;
- deterministically maps folder numbers to QA names/mobile identities;
- creates the account/contact and current profile sections/preferences;
- uploads up to five source photos through the normal media-processing/S3 pipeline;
- inserts uploaded photos as approved, with the first photo primary;
- records source imports for rerun/idempotency protection;
- deletes uploaded S3 objects and the generated member if a later import step fails.

The loader is permitted only for explicit development/QA deployment configuration and must never become a browser route or production data-generation mechanism.

## Transaction and external-call rule

A service may hold a transaction only for the database decision that must be atomic. SMS, email and AWS/network calls occur after commit or through a retryable queue/outbox. Delivery failures must become explicit non-usable states such as `DELIVERY_FAILED`; a provider failure must not leave a usable OTP whose delivery is unknown.

## Operational boundaries

- CLI scripts reject browser execution.
- Queue/cleanup jobs use lock files to prevent overlap.
- Secrets and signing keys live outside source control.
- Database evolution uses the immutable baseline plus numbered SQL increments, not CI4 migrations.
- QA documentation under `docs/qa/` is the persistent QA knowledge base and must be updated when stable behavior changes.