# Incremental PostgreSQL Changes

All database changes must be stored as incrementally numbered PostgreSQL
SQL files.

## Naming format

NNN_description.sql

Examples:

001_initial_schema.sql
002_registration_tables.sql
003_email_verification.sql

## Rules

1. Never modify a SQL file after it has been deployed.
2. Never rename or delete an executed SQL file.
3. Create a new incremental file for every database change.
4. SQL files must use PostgreSQL-compatible syntax.
5. Every SQL file must be safe to execute inside a transaction.
6. Do not store passwords or environment-specific values in SQL files.