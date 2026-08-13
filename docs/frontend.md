# Frontend, CSS and JavaScript

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## Layout contexts

- Public/member pages use the established member/public layout family (`Layouts/Main` where applicable).
- Administrator pages use `Admin/Layouts/Main`.
- Prelaunch pages retain their dedicated prelaunch layout/flow.
- SAK Volunteer pages are a separate authenticated portal and must reuse the existing project visual language rather than introduce another design system.

## Styling rules

Use Bootstrap utilities and existing `public/assets/css/app.css` first. Use `custom.css` only for genuinely missing project-specific behavior.

Do not create a new CSS class when Bootstrap, `app.css`, `custom.css` or an existing shared component already solves the requirement. Do not duplicate existing cards, buttons, forms, badges, tables, avatars, menus, validation states or responsive rules.

All member/admin/volunteer screens must preserve responsive behavior across desktop, tablet and mobile.

## View contract

Every PHP view must:

- document controller/service supplied variables in its opening PHPDoc;
- normalize supplied values into safe local view variables before markup;
- escape user-controlled output using the existing conventions;
- contain meaningful comments for major UI regions/non-obvious conditions;
- avoid database queries, authorization decisions and reusable business transformations.

## JavaScript ownership

- Reusable behavior belongs under `public/assets/js/components`.
- Page-specific behavior belongs under `public/assets/js/pages`.
- Controllers/layouts should include page scripts once; do not double-initialize components.
- Reuse existing form-validation, OTP, password-visibility and Choices.js patterns.
- Client validation mirrors server rules but is never the only enforcement point.
- Save/action buttons must prevent accidental duplicate requests where the operation is not safely repeatable.

## Current member navigation

Authenticated member navigation includes Home, Matches, Interests and Messages, with the notification UI/count handled separately. Notifications have their own listing/read actions.

## Standard member profile presentations

There are exactly four supported presentation contexts:

1. **Dashboard thumbnail** — `app/Views/Components/Member/ProfileThumbnail.php`.
2. **Search / Matches card** — `app/Views/Components/Member/ProfileCard.php`.
3. **Interest card** — `app/Views/Components/Member/ProfileInterestCard.php`.
4. **Full profile** — authoritative detailed member profile view.

Search and Matches share the standard ProfileCard; Interest uses its own card because status/actions are domain-specific. Common summary data for the list contexts is shaped by `MemberProfilePresentationService`.

Do not add a fifth independent member-card implementation without an explicit requirement proving these contexts cannot be reused.

## Photo fallback and privacy

Multi-profile views use authorized thumbnail delivery. If no authorized member thumbnail is available, render the approved gender-based placeholder asset rather than member initials.

The UI must never construct S3 URLs or treat an object key as publicly accessible. The controller/service supplies authorized signed media or the placeholder state.

## Search / Matches

Search and Matches intentionally share the standard member card. Search owns filtering, pagination and search-state UI; Matches owns preference match percentage/eligibility. Presentation-only fields stay centralized rather than duplicated across controllers/views.

Search forms must keep client constraints aligned with server rules (for example valid age and height ranges). Multi-select `Any` behavior must remain selectable and must not silently disable legitimate choices.

## Interest UI

Interest Received and Interest Sent expose All/Pending/Accepted/Declined states/counts. Pending received Interests expose Accept/Decline actions. Mutating action buttons must show an in-progress state, prevent duplicate submission and render safe success/error feedback.

## Forms and master data

- Use current master-data services/endpoints/options rather than hardcoded copies.
- `Any` in search/preferences means no restriction for that criterion; it is not a disabled placeholder.
- Dependent state/city and similar selects must preserve loading, empty and error states.
- Required/optional UI labels and constraints must agree with server validation.

## Accessibility

Every interactive control needs an accessible label/name, keyboard behavior and associated validation feedback. Use semantic buttons/links, `aria-describedby`, conditional `aria-invalid`, descriptive modal labels and adequate touch targets.