# Git Workflow

`development` is the integration branch. Create focused feature/fix/documentation branches from the latest `development`, then merge through a reviewed pull request.

## Required workflow

```text
latest development
  → short-lived branch
  → implementation and tests
  → documentation update
  → diff/scope review
  → pull request to development
```

## Scope discipline

Stage only intended files. Documentation-only work must not change runtime code, SQL, configuration or assets. Feature work that changes business logic must update the relevant docs and `decision-log.md` in the same pull request.

## Before merging

- branch is current with `development`;
- server validation and authorization are present;
- SQL updates are included and immutable;
- no secrets or local paths were committed;
- mobile/responsive and failure paths were tested;
- architecture boundaries match `project-rules.md`;
- the PR explains business-rule and migration impact.

Use meaningful commits such as `feat:`, `fix:`, `docs:`, `refactor:`, `test:` and `chore:`. Hotfixes merged to production must be brought back into `development`.
