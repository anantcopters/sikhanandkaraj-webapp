# Frontend, CSS and JavaScript

## View structure

Public and member pages normally extend:

```php
$this->extend('Layouts/Main');
```

Administrator pages extend:

```php
$this->extend('Admin/Layouts/Main');
```

Shared UI belongs in `app/Views/Components`. Admin-only shared UI should stay under `app/Views/Admin` or an Admin-specific component folder when it is reusable.

Reusable public components currently include form alerts, field errors, the header and the footer.

## CSS structure

### `bootstrap.css`

Contains Bootstrap framework styles and utilities.

### `app.css`

This is the primary application stylesheet. It already provides reusable styles for:

- typography;
- authentication layouts;
- topbar and page layout;
- buttons;
- forms;
- cards;
- alerts;
- avatars;
- badges;
- tables;
- Choices.js;
- responsive behaviour.

Admin pages must first use Bootstrap utilities and existing `app.css` classes.

### `custom.css`

This file is reserved for Sikh Anand Karaj public-site-specific or page-specific styling that cannot be achieved using Bootstrap utilities or an existing `app.css` selector.

Before adding a selector:

1. search `app.css`;
2. check Bootstrap utilities;
3. avoid creating a second class with the same purpose;
4. scope page-specific rules;
5. keep Admin pages independent from public registration and navbar classes.

Admin views must not introduce duplicated card, badge, table, avatar or heading components through new `admin-*` classes when equivalent App classes exist.

## JavaScript structure

```text
public/assets/js/
├── components/
│   ├── select-choice.js
│   └── form-validator.js
├── pages/
└── app.js
```

### Global components

Reusable modules are loaded by the layout. Do not copy their logic into page files.

### Page scripts

Controllers pass page-specific scripts:

```php
return view('Pages/Profile/Edit', [
    'pageScripts' => [
        'assets/js/pages/profile-edit.js',
    ],
]);
```

The layout loads page scripts after global scripts. Do not also include the same script directly in the view because that creates duplicate event listeners.

## Choices.js

Choices.js is opt-in:

```html
<select data-choice>
```

Supported options include:

```html
data-choice-search="false"
data-choice-search="true"
data-choice-remove="true"
data-choice-placeholder="Select a value"
```

Use the shared module:

```javascript
SelectChoice.init(container);
SelectChoice.refresh(selectElement);
SelectChoice.destroy(selectElement);
```

Use `refresh()` after dynamically replacing options. Do not call `new Choices()` inside individual page scripts.

## Form validation

Forms opt in using:

```html
<form data-validate novalidate>
```

The shared validator:

- validates native HTML constraints;
- highlights invalid fields;
- displays the shared field-error element;
- handles radio groups;
- synchronizes invalid state with Choices.js;
- focuses the first invalid field.

Page JavaScript should contain only page-specific behaviour, not duplicate generic validation logic.

## Administrator UI standard

- Use `id="page-topbar"` when relying on `#page-topbar` styles.
- Authenticated Admin content uses `page-content`.
- Page headings use `page-title-box`.
- Panels use `card`, `card-header` and `card-body` in that order.
- Status uses `badge` plus subtle background/text utilities.
- Initial avatars use `avatar-sm` and `avatar-title`.
- Tables use `table`, `table-hover`, `table-nowrap`, `align-middle`, `table-light` and `table-responsive` as appropriate.
- Authentication screens use `auth-page-wrapper`, `auth-page-content` and `min-vh-100`.
- Submit buttons use Bootstrap/App button classes and must not depend on public registration classes.
- Dynamic tables and KPI cards must not contain placeholder names, contacts or counts.
- Admin views should load `bootstrap.css`, `icons.css` and `app.css`. Remove `custom.css` after no Admin view depends on it.

Classes to remove from Admin views during migration include:

```text
admin-page-heading
admin-status
admin-status--pending
admin-status--verified
admin-status--suspended
admin-table
registration-form__submit
registration-submit__loading
public-navbar
public-navbar__container
```

## Accessibility

Every input needs a visible or visually hidden label.

Use:

```html
aria-describedby="fieldError fieldHelp"
```

Add `aria-invalid="true"` only while invalid. Use `fieldset` and `legend` for related radio controls. Alerts need an appropriate `role` and `aria-live` value.

## Responsive design

Prefer Bootstrap grid and utilities before custom media queries. Test at minimum on a small phone, large phone, tablet and desktop. Check touch targets, wrapping, dropdown placement, tables and validation messages rather than relying only on visual shrinking.

## Frontend checklist

- Correct public/member or Admin layout is extended.
- Shared UI uses components.
- No duplicate script loading exists.
- Existing CSS and Bootstrap utilities were checked first.
- Choices.js uses `data-choice`.
- Forms use shared validation.
- Labels and ARIA references are correct.
- Old values render correctly.
- Dynamic content is escaped.
- No placeholder data remains.
- Mobile and desktop layouts are tested.
