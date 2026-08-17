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
- Incremental database scripts `018_canada_matrimonial_locations.sql` and
  `019_country_location_integrity.sql`

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
7. Verify Country = Canada with no state/city returns Canadian profiles in
   Search and matches Canadian candidates in Partner Preference.
8. Verify a selected Canadian province rejects an Indian city, and a selected
   India country rejects a Canadian province in both flows.
9. Save a legacy-style state/city preference with no country selection and
   confirm its existing match behaviour is unchanged.
10. Run increment 019 against a deliberately mismatched copy and confirm it
    aborts before adding composite foreign keys; repair the row and rerun.
11. Verify keyboard, mobile and Choices.js loading/empty/error states.

## QA Gate

**NOT VERIFIED** — implementation is not production-ready until the runtime,
database and browser checks above pass.
