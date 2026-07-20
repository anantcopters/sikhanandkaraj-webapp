# Administrator Module

## Purpose

The administrator module provides separate authentication and role-based management for operational users.

## Roles

| Role | Access |
|---|---|
| `SUPER_ADMIN` | Dashboard and administrator management |
| `ADMIN` | Dashboard and permitted operational functionality |

## Account statuses

| Status | Meaning |
|---|---|
| `PENDING` | Invitation sent, password not yet created |
| `VERIFIED` | Invitation accepted and login allowed |
| `SUSPENDED` | Login blocked |

## Main files

### Controllers

- `app/Controllers/Admin/AdminAuthenticationController.php`
- `app/Controllers/Admin/AdminDashboardController.php`
- `app/Controllers/Admin/AdminInvitationController.php`
- `app/Controllers/Admin/AdminUserController.php`

### Services

- `app/Services/Admin/Authentication/AdminLoginService.php`
- `app/Services/Admin/AdminInvitationService.php`
- `app/Services/Admin/AdminManagementService.php`
- `app/Services/Admin/Audit/AdminAuditService.php`

### Filters

- `app/Filters/AdminAuthFilter.php`
- `app/Filters/SuperAdminFilter.php`

### Models

- `app/Models/AdminUserModel.php`
- `app/Models/AdminInvitationModel.php`
- `app/Models/AdminAuditLogModel.php`

### Views

- `app/Views/Admin/Authentication/Login.php`
- `app/Views/Admin/Authentication/AcceptInvitation.php`
- `app/Views/Admin/Dashboard/Index.php`
- `app/Views/Admin/Users/Index.php`
- `app/Views/Admin/Users/Create.php`
- `app/Views/Admin/Layouts/Main.php`

## Routes

| Method | Route | Access |
|---|---|---|
| GET | `/admin/login` | Public |
| POST | `/admin/login` | Public |
| GET | `/admin/invitation/{token}` | Public, token protected |
| POST | `/admin/invitation/{token}` | Public, token protected |
| POST | `/admin/logout` | Authenticated administrator |
| GET | `/admin/dashboard` | Authenticated administrator |
| GET | `/admin/users` | Super administrator |
| GET | `/admin/users/create` | Super administrator |
| POST | `/admin/users` | Super administrator |
| POST | `/admin/users/{id}/resend-invitation` | Super administrator |
| POST | `/admin/users/{id}/suspend` | Super administrator |

## Invitation creation

When a super administrator creates an administrator:

1. Input is validated through `AdminUserValidation::createRules()`.
2. Mobile and email values are normalized.
3. Duplicate contact values are rejected.
4. A `PENDING` administrator is created.
5. Mobile is marked verified according to the current product rule.
6. A cryptographically random invitation token is generated.
7. Only the SHA-256 token hash is stored.
8. The invitation expiry is set to 24 hours.
9. The invitation email is queued.
10. The operation is audited.

## Invitation acceptance

```text
GET invitation link
  ↓
AdminInvitationController::show()
  ↓
AdminInvitationService::inspectToken()
  ↓
Password form
  ↓
POST invitation link
  ↓
AdminUserValidation::passwordRules()
  ↓
AdminInvitationService::acceptInvitation()
```

During acceptance:

- the raw token format is validated;
- the token hash is used for lookup;
- the invitation row is locked with `FOR UPDATE`;
- expiry, used and revoked state are checked after locking;
- the administrator row is locked;
- the administrator must still be `PENDING` and have role `ADMIN`;
- the password is hashed with `PASSWORD_DEFAULT`;
- account status becomes `VERIFIED`;
- email is marked verified;
- password and verification timestamps are stored;
- the invitation is marked used;
- other outstanding invitations are revoked.

## Login rules

An administrator may log in only when:

- the identifier matches a stored email address or mobile number;
- the password is correct;
- the account role is allowed;
- the account status is `VERIFIED`;
- the account is not soft deleted.

Successful login regenerates the session ID, creates the Admin session, updates `last_login_at` and records an audit event.

## Administrator session values

- `admin_is_authenticated`
- `admin_user_id`
- `admin_user_name`
- `admin_role`
- `admin_authenticated_at`

Member and administrator session keys are intentionally separate. Administrator logout should remove only Admin-specific keys when both session contexts may coexist.

## Route protection

`AdminAuthFilter` protects authenticated administrator routes and rechecks the current administrator state.

`SuperAdminFilter` protects administrator-management routes and permits only `SUPER_ADMIN`.

Filters may reject requests or clear stale authentication state. Feature business logic remains in services.

## Audit events

The audit module records security-sensitive activity such as:

- login success;
- login failure;
- logout;
- administrator creation;
- invitation resend;
- invitation acceptance;
- administrator suspension;
- denied management actions where applicable.

Audit records may contain actor, target, before/after data, metadata and request context. Passwords, raw invitation tokens and secrets must never be logged.

Audit failures should be logged separately and must not falsely report an already committed business operation as unsuccessful.

## Administrator UI rules

Admin pages extend `Admin/Layouts/Main` and use:

- `bootstrap.css`;
- `icons.css`;
- `app.css`.

Use existing classes such as:

- `auth-page-wrapper` and `auth-page-content`;
- `page-content` and `page-title-box`;
- `card`, `card-header` and `card-body`;
- `avatar-sm`, `avatar-md` and `avatar-title`;
- `badge` with subtle background and text utilities;
- `table`, `table-hover`, `table-nowrap`, `table-light` and `table-responsive`;
- `btn-primary` and `btn-soft-*` variants.

Do not create duplicate Admin card, badge, avatar, heading or table classes in `custom.css`. Do not reuse public registration or public-navbar classes in Admin views.

## Operational requirement

A fresh environment requires a secure one-time super-admin bootstrap process. Permanent credentials must not be committed to SQL files or source code.
