# Sikh Anand Karaj

Sikh Anand Karaj is a Sikh community matrimonial web application built with CodeIgniter 4 and PostgreSQL.

This repository follows a simple layered architecture so that a developer can trace every feature from the browser to the database and back.

## Technology stack

| Area | Technology |
|---|---|
| Backend framework | CodeIgniter 4 |
| Language | PHP 8.2+ with strict types |
| Database | PostgreSQL |
| Frontend | Bootstrap, HTML5 and application CSS |
| JavaScript | Vanilla JavaScript |
| Enhanced select boxes | Choices.js |
| Client validation | HTML constraint validation plus `form-validator.js` |
| Server validation | CodeIgniter validation rules |
| Sessions | CodeIgniter session handling with PostgreSQL support |
| Icons | Material Design Icons |

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
- **View** renders HTML only.
- **Page JavaScript** controls page-specific behaviour.
- **Reusable JavaScript components** provide common behaviour such as Choices.js and form validation.

## Current project structure

```text
app/
├── Config/
│   ├── Routes.php
│   └── Services.php
├── Controllers/
│   └── Web/
├── Models/
├── Services/
│   └── Registration/
├── Validation/
├── Views/
│   ├── Components/
│   ├── Layouts/
│   └── Pages/
└── Common.php

public/assets/
├── css/
│   ├── app.css
│   └── custom.css
├── js/
│   ├── components/
│   ├── pages/
│   └── app.js
└── images/

sql/
├── schema/
├── updates/
└── seeds/

docs/
```

## Folder responsibilities

### `app/Controllers`

Controllers should stay thin.

A controller may:

- read GET or POST values;
- normalize simple request values;
- run server validation;
- call a service;
- store flashdata or session identifiers;
- redirect or return a view.

A controller must not contain:

- SQL;
- multi-table business rules;
- database transactions;
- large HTML blocks.

### `app/Validation`

Validation classes contain field rules, labels and messages.

Example:

```text
RegisterFreeValidation
```

Client validation improves usability, but server validation is always authoritative.

### `app/Services`

Services contain business logic.

Examples:

- deciding whether a registration is new or pending;
- rejecting a verified mobile number;
- deriving gender from profile relationship;
- generating profile reference numbers;
- starting and committing transactions;
- coordinating several models;
- returning a result DTO.

### `app/Models`

Each model represents one database table and contains table-specific queries.

Examples:

```text
UserModel
UserContactModel
ContactVerificationModel
```

Models should not decide business flow. That belongs in a service.

### `app/Views`

Views contain presentation only.

Use:

- layouts for the page shell;
- components for reusable UI;
- page folders for feature pages;
- `old()` for restoring submitted values;
- escaped output using `esc()`.

### `public/assets/js/components`

Reusable JavaScript used by several pages.

Current examples:

- `select-choice.js`
- `form-validator.js`

### `public/assets/js/pages`

Page-specific JavaScript.

Examples:

- `home.js`
- future `profile-edit.js`
- future `search.js`

Page scripts are passed from the controller using the `pageScripts` array and loaded by `Layouts/Main.php`.

### CSS files

- `app.css` contains reusable and application-wide styles.
- `custom.css` contains project or page-specific styles that do not already exist in `app.css`.

Before adding CSS to `custom.css`, search `app.css` first to avoid duplication.

## How to create a page from UI to database

A junior developer should follow this order.

### 1. Add the route

In `app/Config/Routes.php`:

```php
$routes->get('profile/edit', 'ProfileController::edit', [
    'as' => 'web.profile.edit',
]);

$routes->post('profile/edit', 'ProfileController::update', [
    'as' => 'web.profile.update',
]);
```

### 2. Create the controller

Create a controller under `app/Controllers/Web`.

The GET method loads the view. The POST method reads input, validates it and calls a service.

### 3. Create validation rules

Create a validation class under `app/Validation` when the form has several rules or conditional validation.

### 4. Create or update the model

Create a model under `app/Models` for each database table involved.

Keep simple table queries in the model.

### 5. Create the service

Create a service under `app/Services/<Feature>` when the feature has business logic, transactions or multiple model calls.

### 6. Add SQL

- New installation schema goes in `sql/schema`.
- Later database changes go in `sql/updates`.
- Master/reference data goes in `sql/seeds`.

Never edit an already-deployed update file. Add a new file instead.

### 7. Create the view

Create the page under `app/Views/Pages/<Feature>` and extend `Layouts/Main`.

### 8. Add JavaScript

- Reusable behaviour goes in `public/assets/js/components`.
- Page-only behaviour goes in `public/assets/js/pages`.

Pass page scripts from the controller:

```php
return view('Pages/Profile/Edit', [
    'pageTitle' => 'Edit Profile',
    'pageScripts' => [
        'assets/js/pages/profile-edit.js',
    ],
]);
```

### 9. Test the full flow

Test:

- empty form;
- invalid fields;
- valid submission;
- duplicate records;
- database failure;
- old values after redirect;
- mobile and desktop layout;
- keyboard navigation.

A detailed walkthrough is available in [docs/create-page.md](docs/create-page.md).

## Register Free example

The current registration flow is the reference implementation.

```text
Home registration form
  ↓
POST web.register.create
  ↓
RegistrationController::create()
  ↓
RegisterFreeValidation
  ↓
RegisterFreeService
  ↓
Case A: verified mobile exists → field error
Case B: unverified pending mobile exists → update pending registration
Case C: mobile does not exist → create user and contacts
  ↓
UserModel + UserContactModel + ContactVerificationModel
  ↓
Commit transaction
  ↓
Store pending registration identifiers in session
  ↓
Redirect to OTP page
```

## Form validation standard

Every form should provide the same experience on client and server.

Each field should have:

- a unique `id`;
- a `name`;
- a label;
- HTML constraints such as `required`, `type`, `pattern`, `minlength` and `maxlength`;
- a reusable error element below the field;
- server-side rules;
- old value restoration;
- `aria-invalid` when invalid;
- `aria-describedby` linked to help and error text.

Field-specific errors appear below the field. General errors use the reusable alert component.

See [docs/validation.md](docs/validation.md).

## Choices.js standard

Choices.js is opt-in.

```html
<select data-choice>
```

Disable search:

```html
<select data-choice data-choice-search="false">
```

Enable search:

```html
<select data-choice data-choice-search="true">
```

Multiple select with removable items:

```html
<select
    data-choice
    data-choice-search="true"
    data-choice-remove="true"
    multiple>
</select>
```

Do not manually call `new Choices()` inside page scripts. Use the shared `select-choice.js` module.

## Database rules

Use three levels of protection:

1. Client validation for fast feedback.
2. Server validation and service business rules.
3. PostgreSQL constraints and unique indexes as final protection.

Transactions belong in services, not controllers or models.

## Coding rules

- Use `declare(strict_types=1);` in PHP files.
- Escape view output with `esc()`.
- Use named routes with `route_to()`.
- Keep controllers thin.
- Keep business logic in services.
- Keep queries in models.
- Keep views free from SQL and business logic.
- Do not duplicate CSS or JavaScript.
- Use comments to explain why, not obvious syntax.
- Return small result objects from services instead of redirects or HTML.

See [docs/coding-standards.md](docs/coding-standards.md).

## Documentation

- [Architecture](docs/architecture.md)
- [Create a page from UI to DB](docs/create-page.md)
- [Validation](docs/validation.md)
- [Database](docs/database.md)
- [Frontend, CSS and JavaScript](docs/frontend.md)
- [Coding standards](docs/coding-standards.md)
- [Git workflow](docs/git-workflow.md)
- [Deployment](docs/deployment.md)
- [Architecture decision log](docs/decision-log.md)
- [Glossary](docs/glossary.md)

## Before committing a feature

- [ ] Route added and named.
- [ ] Controller is thin.
- [ ] Server validation added.
- [ ] Client validation attributes added.
- [ ] Service contains business logic.
- [ ] Transaction used for multi-table writes.
- [ ] Model contains database queries.
- [ ] SQL schema/update file added when required.
- [ ] Old form values are restored.
- [ ] Field errors and form alert are tested.
- [ ] Page JavaScript is loaded once.
- [ ] Existing CSS was checked before adding new CSS.
- [ ] Responsive layout tested.
- [ ] Accessibility attributes checked.
- [ ] Database constraints protect unique or valid data.

## Security reminders

- Never trust client-side validation.
- Never send unexpected request fields directly to a model.
- Never store a plain OTP.
- Never expose internal database IDs as public profile identifiers.
- Keep CSRF protection enabled for forms.
- Log technical errors, but show safe messages to users.
- Do not commit secrets or production credentials.

## Project status

The application is under active development. The registration and OTP preparation flow is the first reference feature. Future features should follow the same architecture unless an architecture decision is documented.