# Authentication

## Purpose

The application has separate member and administrator authentication contexts. They use different routes, filters, views and session keys.

## Member authentication

Members may sign in with a verified email address or verified mobile number and a password.

- Email login is allowed only when the email is verified.
- Mobile login is allowed only when the mobile number is verified.
- A member with an unverified email may still use a verified mobile number.
- Client validation improves usability; server validation remains authoritative.
- Successful login regenerates the session ID.
- Protected member routes use `WebAuthFilter`.

## Administrator authentication

Administrator login is independent from member login.

- Only `VERIFIED` administrator accounts may log in.
- `PENDING` accounts must complete invitation acceptance.
- `SUSPENDED` accounts are denied.
- Protected Admin routes use `AdminAuthFilter`.
- Super-administrator routes also use `SuperAdminFilter`.
- Login, failure and logout events should be audited.

## Session separation

Member and administrator session keys must remain distinct. Logging out of one context should not unintentionally destroy the other context unless that behaviour is explicitly chosen.

Administrator session values include:

- `admin_is_authenticated`;
- `admin_user_id`;
- `admin_user_name`;
- `admin_role`;
- `admin_authenticated_at`.

## Password handling

- Hash passwords with `password_hash()` and `PASSWORD_DEFAULT`.
- Verify with `password_verify()`.
- Rehash with `password_needs_rehash()` when required.
- Never log, email or store plain passwords.
- Password reset and invitation tokens must be one-time, expiring and stored only as hashes.

## Authentication flow

```text
Request
  ↓
Controller
  ↓
Validation
  ↓
Authentication service
  ↓
Account/contact lookup
  ↓
Verification and status checks
  ↓
Password verification
  ↓
Session regeneration
  ↓
Authenticated session
  ↓
Redirect
```

## Testing checklist

- valid email login;
- valid mobile login;
- unverified email message;
- unverified mobile rejection;
- incorrect password;
- unknown identifier;
- pending administrator rejection;
- suspended administrator rejection;
- session regeneration;
- protected-route redirect;
- logout clears only intended keys.