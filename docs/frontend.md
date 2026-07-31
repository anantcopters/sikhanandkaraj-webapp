# Frontend, CSS and JavaScript

## Layouts

- Public/member pages extend `Layouts/Main`.
- Administrator pages extend `Admin/Layouts/Main`.
- Prelaunch pages use their dedicated prelaunch views/layout behavior.

## Styling

Use Bootstrap utilities and existing `public/assets/css/app.css` first. `custom.css` is only for genuinely missing, project-specific behavior. Do not duplicate cards, buttons, forms, badges, tables, avatars, menus or responsive rules already available.

All pages must be mobile-first and responsive. Use the approved assets: `bootstrap.css`, `icons.css`, `app.css`, and `custom.css` only where required.

## JavaScript

Reusable behavior belongs under `public/assets/js/components`; page behavior belongs under `public/assets/js/pages`. Controllers pass page scripts once through the layout. Do not duplicate script tags or initialize the same component twice.

Use shared form validation, OTP input handling, password visibility and Choices.js helpers. Page scripts must not reimplement generic validation.

## Current member UI rules

- Login presents password and OTP choices clearly.
- Registration does not render an email field.
- Dashboard does not display an email-activation banner.
- Member navigation includes Home, Matches, Interests and Messages with notification counts.
- Profile preview shows only approved/authorized media and uses signed URLs.

## Accessibility

Every control needs a label, error association and keyboard behavior. Use `aria-describedby`, conditional `aria-invalid`, semantic buttons, descriptive modal labels and adequate touch targets.
