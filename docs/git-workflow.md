# Git Workflow

## Branches

Recommended long-lived branches:

```text
main
  Production-ready code

development
  Integrated development code
```

Create short-lived feature branches from `development`:

```text
feature/register-otp
feature/profile-details
fix/choices-initialization
refactor/form-validation
```

## Standard flow

```text
development
  ↓ create feature branch
feature/...
  ↓ commit and test
pull request
  ↓ review
merge into development
  ↓ release validation
merge into main
```

## Commit messages

Use a short type prefix:

```text
feat: add profile details form
fix: correct Choices selector
refactor: move registration logic to service
docs: add database guide
style: improve registration card spacing
test: add registration service tests
chore: update dependency configuration
```

A commit should represent one understandable change.

## Before pushing

- Pull the latest target branch.
- Resolve conflicts locally.
- Run syntax checks and tests.
- Check that no secret or local configuration was added.
- Review `git diff`.
- Use a meaningful commit message.

## Pull request description

Include:

- what changed;
- why it changed;
- files or modules affected;
- database scripts required;
- manual test steps;
- screenshots for visible UI changes;
- known limitations.

## Database changes

When a feature changes the database:

- commit the SQL file with the code;
- list the SQL filename in the pull request;
- never rewrite a script already executed in another environment;
- create a new update script for corrections.

## Review priorities

Review in this order:

1. correctness and security;
2. database integrity;
3. architecture boundaries;
4. error handling;
5. validation;
6. accessibility;
7. performance;
8. formatting.

## Merge rules

Do not merge when:

- registration or login flow is broken;
- SQL is missing;
- server validation is missing;
- secrets are present;
- a page script is loaded twice;
- the feature bypasses the service architecture without explanation;
- database writes are not transaction-safe.

## Hotfixes

For urgent production issues:

1. branch from `main`;
2. fix only the issue;
3. test;
4. merge into `main`;
5. merge the same fix back into `development`.

## Documentation changes

Update the relevant file under `docs` whenever an architectural convention changes. Record significant decisions in `decision-log.md`.