# Sikh Anand Karaj

Sikh Anand Karaj is a Sikh community matrimonial web application built with CodeIgniter 4, PHP 8.3 and PostgreSQL 16.

The application currently includes member registration, contact verification, member authentication, protected member pages and a role-based administration module with one-time invitations, audit logging and administrator account management.

## Technology stack

| Area | Technology |
|---|---|
| Backend framework | CodeIgniter 4 |
| Language | PHP 8.3 with strict types |
| Database | PostgreSQL 16 |
| Frontend | Bootstrap, `app.css`, HTML5 |
| JavaScript | Vanilla JavaScript |
| Enhanced selects | Choices.js |
| Client validation | HTML constraint validation plus reusable form validation |
| Server validation | CodeIgniter validation classes |
| Sessions | CodeIgniter sessions with PostgreSQL support |
| Icons | Remix Icons and Material Design Icons |
| Email delivery | Queue-based email service |
| Audit logging | PostgreSQL-backed administrator audit log |
| Hosting target | Apache on Ubuntu EC2 |
| Local development | XAMPP on Windows |

## Main code flow

```text
Browser
  ↓
Route
  ↓
Controller
  ↓
Validation
  ↓
Service
  ↓
Model
  ↓
PostgreSQL
  ↓
Service result
  ↓
Controller redirect/view
  ↓
Browser
```

Each layer has one clear responsibility:

- **Route** maps a URL to a controller method.
- **Controller** reads the request, validates it, calls a service and returns a response.
- **Validation class** contains reusable server-side validation rules.
- **Service** contains business rules and database transactions.
- **Model** performs table-specific database operations.
- **View** renders escaped HTML only.
- **Filter** protects routes and validates authentication or authorization state.
- **Page JavaScript** controls page-specific behaviour.
- **Reusable JavaScript components** provide shared behaviour such as password toggles, Choices.js and form validation.

## Current project structure

```text
app/
├── Config/
│   ├── Filters.php
│   ├── Routes.php
│   └── Services.php
├── Controllers/
│   ├── Admin/
│   ├── Api/
│   └── Web/
├── Filters/
│   ├── AdminAuthFilter.php
│   ├── SuperAdminFilter.php
│   └── WebAuthFilter.php
├── Models/
├── Services/
│   ├── Admin/
│   │   ├── Audit/
│   │   └── Authentication/
│   ├── Authentication/
│   ├── Email/
│   ├── Logging/
│   └── Registration/
├── Validation/
├── Views/
│   ├── Admin/
│   ├── Components/
│   ├── Layouts/
│   └── Pages/
└── Common.php

public/assets/
├── css/
│   ├── bootstrap.css
│   ├── icons.css
│   ├── app.css
│   └── custom.css
├── js/
│   ├── components/
│   ├── pages/
│   └── app.js
└── images/

scripts/
└── log_retention_cleanup.php

docs/
```

## Current feature status

### Member application

- Free registration.
- Mobile-number normalization and duplicate handling.
- OTP preparation and contact-verification records.
- Email verification.
- Login using email address or mobile number and password.
- Server-side and client-side validation.
- Protected member dashboard and profile routes.
- Session-based member authentication.

### Administration

- Separate administrator login.
- `SUPER_ADMIN` and `ADMIN` roles.
- Administrator dashboard.
- Create administrator account.
- One-time email invitation valid for 24 hours.
- Invitation inspection and acceptance.
- Password creation during invitation acceptance.
- Email verification during account activation.
- Resend invitation.
- Suspend verified administrator accounts.
- Administrator authentication and role filters.
- Administrator audit logging.
- Login, logout, invitation and administrator-management audit events.
- Log-retention cleanup script.

### In progress

- Secure initial super-admin bootstrap process.
- Final Admin UI cleanup using only `app.css` and Bootstrap utilities.
- Broader matrimonial profile, search and matching features.

## Administrator invitation flow

```text
Super Admin creates administrator
  ↓
AdminUserController::store()
  ↓
AdminUserValidation::createRules()
  ↓
AdminInvitationService::createAdmin()
  ↓
Create PENDING administrator
  ↓
Create hashed one-time invitation
  ↓
Queue invitation email
  ↓
Administrator opens /admin/invitation/{token}
  ↓
AdminInvitationController::show()
  ↓
AdminInvitationService::inspectToken()
  ↓
Display password creation form
  ↓
AdminInvitationController::accept()
  ↓
AdminUserValidation::passwordRules()
  ↓
AdminInvitationService::acceptInvitation()
  ↓
Lock invitation and administrator rows
  ↓
Set password, verify email and activate account
  ↓
Consume invitation and revoke other invitations
  ↓
Redirect to administrator login
```

## Administrator login flow

```text
POST /admin/login
  ↓
AdminAuthenticationController::login()
  ↓
AdminLoginValidation
  ↓
AdminLoginService::authenticate()
  ↓
Verify identifier, password, role and account status
  ↓
Regenerate session ID
  ↓
Create administrator session
  ↓
Update last_login_at
  ↓
Write audit event
  ↓
Redirect to /admin/dashboard
```

## Architecture rules

### Controllers

Controllers must stay thin. They may read input, normalize simple values, validate, call services, set flashdata or session values, and return a view or redirect.

Controllers must not contain SQL, multi-table business rules or transaction logic.

### Services

Services contain business rules, coordinate models and own transactions. Services with dependencies are registered in `app/Config/Services.php` and obtained through `service()`.

### Models

Models represent database tables and contain table-specific reads and writes. Models do not decide the overall business flow.

### Views

Views render escaped HTML only. Use layouts, reusable components, `esc()` and named routes through `route_to()`.

### Filters

- `WebAuthFilter` protects member routes.
- `AdminAuthFilter` protects verified administrator routes.
- `SuperAdminFilter` restricts administrator-management routes to `SUPER_ADMIN`.

## CSS ownership

- `bootstrap.css` contains Bootstrap framework definitions.
- `app.css` is the primary stylesheet for reusable application and Admin UI classes.
- `custom.css` is reserved for public-site-specific styling that cannot be achieved using Bootstrap utilities or existing `app.css` selectors.
- Admin views must use `bootstrap.css`, `icons.css` and `app.css`.
- Admin views must not introduce duplicated `admin-*` component styles in `custom.css`.
- Before adding any selector, search Bootstrap utilities and `app.css` first.

## Form validation standard

Every form should provide the same experience on client and server. Each field should have:

- a unique `id`;
- a `name`;
- a label;
- relevant HTML constraints;
- a reusable error element;
- server-side rules;
- old-value restoration where appropriate;
- `aria-invalid` while invalid;
- `aria-describedby` linked to help and error text.

Field-specific errors appear below fields. General errors use the reusable alert component.

## Database rules

Use three levels of protection:

1. Client validation for fast feedback.
2. Server validation and service business rules.
3. PostgreSQL constraints and unique indexes as final protection.

Transactions belong in services. One-time invitation acceptance uses row-level locks to prevent concurrent consumption.

## Coding rules

- Use `declare(strict_types=1);` in PHP files.
- Follow PSR-4 namespace and filename casing exactly.
- Escape view output with `esc()`.
- Use named routes with `route_to()`.
- Keep controllers thin.
- Keep business logic in services.
- Keep queries in models.
- Register dependency-heavy services in `Config/Services.php`.
- Keep views free from SQL and business logic.
- Do not duplicate CSS or JavaScript.
- Admin UI must use existing `app.css` and Bootstrap classes.
- Use comments to explain why, not obvious syntax.
- Return small result objects from services instead of redirects or HTML.

## Documentation

- [Architecture](docs/architecture.md)
- [Administrator module](docs/admin-module.md)
- [Authentication](docs/authentication.md)
- [Registration](docs/registration.md)
- [Email verification](docs/email-verification.md)
- [Create a page from UI to DB](docs/create-page.md)
- [Validation](docs/validation.md)
- [Database](docs/database.md)
- [Frontend, CSS and JavaScript](docs/frontend.md)
- [Security](docs/security.md)
- [Operations and scheduled scripts](docs/operations.md)
- [Coding standards](docs/coding-standards.md)
- [Git workflow](docs/git-workflow.md)
- [Deployment](docs/deployment.md)
- [Architecture decision log](docs/decision-log.md)
- [Glossary](docs/glossary.md)

## Before committing a feature

- [ ] Route added and named.
- [ ] Route access level is correct: public, member, administrator or super administrator.
- [ ] Required filter is configured.
- [ ] Controller is thin.
- [ ] Server validation added.
- [ ] Client validation attributes added.
- [ ] Service contains business logic.
- [ ] All service dependencies are registered in `Config/Services.php`.
- [ ] Transaction used for multi-table writes.
- [ ] Model contains database queries.
- [ ] SQL schema/update file added when required.
- [ ] Every referenced controller, view, service, validation class and JavaScript file exists.
- [ ] Old form values are restored.
- [ ] Field errors and form alerts are tested.
- [ ] Authentication actions regenerate or safely clear the correct session values.
- [ ] Security-sensitive actions write an audit event.
- [ ] Audit failure does not falsely invalidate a completed business operation.
- [ ] Page JavaScript is loaded once.
- [ ] Existing CSS was checked before adding new CSS.
- [ ] Admin UI uses existing `app.css` and Bootstrap classes.
- [ ] No public-site CSS classes are reused in Admin views.
- [ ] No placeholder text remains in dynamic tables or KPI cards.
- [ ] Responsive layout tested.
- [ ] Accessibility attributes checked.
- [ ] Database constraints protect unique or valid data.

## Security reminders

- Never trust client-side validation.
- Never send unexpected request fields directly to a model.
- Never store a plain OTP or invitation token.
- Never log passwords or full authentication tokens.
- Never expose internal database IDs as public profile identifiers.
- Keep CSRF protection enabled for forms.
- Regenerate the session ID after successful login.
- Keep member and administrator session keys separate.
- Log technical errors, but show safe messages to users.
- Do not commit secrets or production credentials.

## Project status

The application is under active development.

Completed foundations include layered CodeIgniter architecture, PostgreSQL data modelling, reusable validation and JavaScript components, member registration and contact-verification preparation, member authentication and protected routes, administrator authentication, role-based administrator access, one-time administrator invitations, administrator account management, administrator audit logging and operational log-retention cleanup.

The next implementation phases will expand member profiles, matrimonial search, matching, communication and production deployment tooling.
