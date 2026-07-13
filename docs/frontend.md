# Frontend, CSS and JavaScript

## View structure

Pages extend the main layout:

```php
$this->extend('Layouts/Main');
$this->section('content');
```

Shared UI belongs in `app/Views/Components`.

Current reusable components include:

- form alerts;
- field errors;
- header;
- footer.

## CSS structure

### `app.css`

Contains reusable application-wide styles:

- typography;
- buttons;
- forms;
- cards;
- alerts;
- Choices.js styles;
- common responsive behaviour.

### `custom.css`

Contains Sikh Anand Karaj-specific or page-specific additions not already present in `app.css`.

Before adding a selector to `custom.css`:

1. search `app.css`;
2. check whether a Bootstrap utility already solves the need;
3. avoid creating a second class with the same purpose;
4. scope page-specific CSS to the page or component.

## JavaScript structure

```text
public/assets/js/
├── components/
│   ├── select-choice.js
│   └── form-validator.js
├── pages/
│   └── home.js
└── app.js
```

### Global components

Reusable modules are loaded in `Layouts/Main.php`.

Do not copy their logic into page files.

### Page scripts

Controllers pass page-specific scripts:

```php
'pageScripts' => [
    'assets/js/pages/home.js',
],
```

The layout loads them after global scripts.

Do not also add the same script directly in the view, because that causes duplicate event listeners.

## Choices.js

Choices.js is opt-in.

```html
<select data-choice>
```

Options:

```html
data-choice-search="false"
data-choice-search="true"
data-choice-remove="true"
data-choice-placeholder="Select a value"
```

The shared module initializes selects on `DOMContentLoaded` and provides:

```javascript
SelectChoice.init(container);
SelectChoice.refresh(selectElement);
SelectChoice.destroy(selectElement);
```

Use `refresh()` after dynamically replacing options.

Do not call `new Choices()` in individual pages.

## Form validation

Forms opt in using:

```html
<form data-validate novalidate>
```

The validator:

- validates native HTML constraints;
- highlights invalid fields;
- displays the shared error element;
- handles radio groups;
- synchronizes invalid state with Choices.js;
- focuses the first invalid field.

Page JavaScript should handle only business-related UI behaviour, such as showing gender when profile type is Self.

## Accessibility

Every input needs a visible or visually hidden label.

Use:

```html
aria-describedby="fieldError fieldHelp"
```

Add `aria-invalid="true"` only while invalid.

Use `fieldset` and `legend` for related radio buttons.

Alerts should have an appropriate `role` and `aria-live` value.

## Responsive design

Prefer Bootstrap grid and utilities before custom media queries.

Test at minimum:

- small phone;
- large phone;
- tablet;
- desktop.

Do not rely only on visual shrinking. Check touch targets, wrapping, dropdown placement and validation messages.

## Frontend checklist

- Page extends the main layout.
- Shared UI uses components.
- No duplicate script loading.
- Existing CSS checked first.
- Choices.js uses `data-choice`.
- Form uses shared validation.
- Labels and ARIA references are correct.
- Old values render correctly.
- Mobile layout is tested.