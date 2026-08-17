# QA-0002 — International Country/State/City Locations

## Requirement

Enable Canada alongside India in prelaunch and live profile location flows,
preserve India as the default, validate the complete hierarchy server-side,
and expose Canadian states/cities to Search and Partner Preference. Mobile
country-code internationalisation is explicitly excluded.

## Affected areas

- Country/state/city master models and service
- Public prelaunch Basic Details
- Member Basic, Family and Sikh/Religious Details
- Search and Partner Preference location options
- Prelaunch-to-member migration compatibility
- Incremental database script `018_canada_matrimonial_locations.sql`

## QA status

| Area | Result | Evidence/status |
| --- | --- | --- |
| Requirement QA | NOT VERIFIED | Implementation inspection completed; runtime acceptance pending |
| Code QA | NOT VERIFIED | Static diff inspection completed; PHP runtime unavailable |
| UI QA | NOT VERIFIED | Desktop/mobile/browser checks required |
| Validation QA | NOT VERIFIED | Hierarchy rules inspected; tampered-request execution required |
| Database QA | NOT VERIFIED | SQL row generation validated; PostgreSQL execution required |
| Security QA | NOT VERIFIED | Direct endpoint and manipulated-ID tests required |
| Regression QA | NOT VERIFIED | `REG-MASTER-001` through `REG-MASTER-004` added |

## Required manual/runtime checks

1. Apply increment 018 twice in an isolated PostgreSQL copy and confirm the
   second run is idempotent.
2. Confirm India is selected on a new profile and only Indian states appear.
3. Select Canada and verify only its 13 provinces/territories appear.
4. Rapidly switch countries/states and confirm stale AJAX responses never win.
5. Submit mismatched country/state/city IDs and confirm rejection.
6. Migrate a Canadian prelaunch profile and edit all affected live sections.
7. Verify Canadian state/city filtering in Search and Partner Preference.
8. Verify keyboard, mobile and Choices.js loading/empty/error states.

## QA Gate

**NOT VERIFIED** — implementation is not production-ready until the runtime,
database and browser checks above pass.
