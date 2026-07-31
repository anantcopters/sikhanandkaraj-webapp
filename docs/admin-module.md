# Administrator Module

## Roles

- `SUPER_ADMIN`: dashboard, KPIs, administrator management, field-officer management and privileged review functions.
- `ADMIN`: operational dashboard and permitted profile/photo review only. An `ADMIN` must not view or create other administrators and must not see super-admin-only KPIs.

## Account states

- `PENDING`: invitation issued, activation incomplete.
- `VERIFIED`: invitation accepted and login allowed.
- `SUSPENDED`: login blocked.

## Invitation flow

A super administrator creates an admin account. The service stores a hashed one-time invitation with expiry, queues email after the database decision, and records an audit event. Acceptance locks the invitation and administrator rows, creates the password, verifies email, activates the administrator, consumes the invitation and revokes replacements.

## Operational modules

The current admin area includes:

- dashboard and role-sensitive KPIs;
- administrator invitation/account management;
- field-officer management;
- prelaunch profile listing and review;
- member profile inspection;
- member photo approval/rejection;
- privileged audit logging.

## Review rules

Administrative review must display business-safe profile references rather than exposing internal IDs. Photo review changes approval state only; member-facing delivery still uses authorization and signed CloudFront URLs.

## UI rules

Admin views extend `Admin/Layouts/Main` and use Bootstrap, `icons.css` and `app.css`. Do not create a separate duplicate admin design system in `custom.css`. Navigation and KPIs must be generated from the authenticated role, not merely hidden with CSS.
