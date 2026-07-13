# Validation

The application uses three validation layers:

```text
Client validation
  ↓
Server validation
  ↓
Database constraints
```

## Client validation

Client validation improves user experience. It highlights the invalid field and shows help text below it.

Use `form[data-validate]` with `novalidate` so the shared validator controls the message display.

```html
<form method="post" data-validate novalidate>
```

Use native field attributes:

```html
<input
    type="email"
    id="email"
    name="email"
    required
    maxlength="128"
    data-error-required="Please enter the email address."
    data-error-email="Please enter a valid email address.">
```

The reusable client validator is:

```text
public/assets/js/components/form-validator.js
```

Do not duplicate required, email, pattern or length validation in page JavaScript.

## Server validation

Server validation is mandatory because JavaScript and HTML attributes can be bypassed.

Store feature rules under `app/Validation`.

```php
'email' => [
    'label' => 'Email address',
    'rules' => [
        'required',
        'valid_email',
        'max_length[128]',
    ],
    'errors' => [
        'required' => 'Please enter the email address.',
        'valid_email' => 'Please enter a valid email address.',
    ],
],
```

Run validation in the controller before calling a service.

```php
$validation = service('validation');
$validation->setRules(RegisterFreeValidation::rulesFor($input));

if (!$validation->run($input)) {
    return redirect()
        ->back()
        ->withInput()
        ->with('validationErrors', $validation->getErrors());
}
```

Pass only `$validation->getValidated()` to the service.

## Field errors

Use a field error when the user can fix one specific field.

Examples:

- invalid email format;
- missing full name;
- duplicate mobile number;
- gender not selected.

Use `Components/Forms/FieldError.php` directly below the field.

```php
<?= view('Components/Forms/FieldError', [
    'field' => 'email',
    'errorId' => 'emailError',
    'errors' => $validationErrors,
]) ?>
```

The input must reference the same ID:

```html
aria-describedby="emailError emailHelp"
```

## Form-level alerts

Use an alert for an error that is not owned by one field.

Examples:

- database unavailable;
- OTP provider unavailable;
- unexpected transaction failure;
- general access restriction.

Use `Components/Alerts/FormAlert.php` above the form.

## Resolve errors once in a view

At the top of the page view:

```php
$sessionValidationErrors = session('validationErrors');

$validationErrors = is_array($sessionValidationErrors)
    ? $sessionValidationErrors
    : [];

$sessionFormAlert = session('formAlert');

$formAlert = is_array($sessionFormAlert)
    ? $sessionFormAlert
    : null;
```

## Old values

Restore submitted values after a redirect:

```php
value="<?= esc(old('email'), 'attr') ?>"
```

For selects:

```php
<?= old('profile_created_for') === 'self' ? 'selected' : '' ?>
```

For radios:

```php
<?= old('gender') === 'M' ? 'checked' : '' ?>
```

## Conditional validation

Conditional rules belong in the validation class and must match the UI behaviour.

Example: gender is required only for `self`.

The page script adds or removes `required`; the server validation class independently applies the same business rule.

## Choices.js validation

Choices.js hides the original select and creates a visual wrapper. The shared validator synchronizes the invalid state with that wrapper.

Use the standard marker:

```html
<select data-choice data-choice-search="false">
```

Do not use `data-choices`.

## Database validation

Use PostgreSQL constraints as final protection:

- unique indexes;
- foreign keys;
- check constraints;
- `NOT NULL` constraints.

A pre-insert lookup alone is not enough for uniqueness because two requests can run at the same time.

## Validation checklist

- Client and server rules represent the same requirement.
- Server rules exist even when client rules exist.
- Field errors render below the field.
- General errors use an alert.
- Old values are restored.
- Error IDs match `aria-describedby`.
- Conditional fields update both UI and server rules.
- Database constraints protect final data integrity.