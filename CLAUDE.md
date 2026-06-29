---
description: Database changes must be captured as SQL files in Database/
alwaysApply: true
---

# Database SQL File Rules

- **Always** store SQL files in the `Database/` folder at the project root. Never place SQL files anywhere else.
- **Always** create a new SQL file for any database change, including:
  - creating/altering/dropping tables, columns, indexes, constraints
  - data migrations / backfills
  - seed or one-off `INSERT` / `UPDATE` / `DELETE` scripts
- **Always** name the file using: year, month, day, time, then description.
- **Format:** `YYYY_MM_DD_HHMMSS_description_of_change.sql`
- **Description requirements**:
  - use lowercase with underscores
  - start with a verb: `add_`, `create_`, `update_`, `fix_`, `drop_`, `migrate_`, `alter_`
- Files sort alphabetically = chronologically (oldest at top, newest at bottom).

## Examples

- `Database/2026_03_09_181900_create_admin_table.sql`
- `Database/2026_06_27_180000_create_countries_table.sql`
- `Database/2026_06_27_180003_alter_clients_add_country_id.sql`
