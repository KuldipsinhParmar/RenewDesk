# Invoicing + Website Maintenance Report — Implementation Plan

## Context

RenewDesk currently tracks clients, projects, domains, hosting, maintenance (AMC), backups and tasks, but has no way to (1) bill a client or (2) produce the periodic website-health report the business already sends manually. Two real templates were supplied to match:

- **Invoice** (`Ekta Auto Finance.pdf`, "Developer Deck" brand): logo/company header, "Invoice to" + date + invoice #, line-item table (description/qty/price/total), subtotal/discount/grand total, payment method block (bank/IFSC/UPI/Gpay), signature, "thank you" footer.
- **Site Maintenance Report** (`Site_Maintenance_Report_Template.docx`): site header (name/URL/report date/period/prepared & reviewed by) followed by 16 numbered sections — loading speed & Core Web Vitals, image optimization, server performance, uptime/response monitoring, traffic & analytics, plugins list, caching, WP version, backup status, security checks, basic SEO, unnecessary links removed, broken links, sitemap/robots.txt, hardcoded design check, summary/recommendations + sign-off.

Decisions already confirmed:
- **PDF export = browser print-to-PDF** (a styled printable HTML page + `window.print()`). No new Composer dependency (repo only has PHPMailer; dompdf would add real bulk to the committed `vendor/`).
- **Maintenance report delivery = on-demand only** for now. No auto-email/cron wiring in this phase.
- Invoicing covers: generate-from-unpaid-items, an invoice list with status tracking, printable/PDF export, and manual ad-hoc invoices.

Everything follows the existing stack exactly: plain PHP + PDO endpoints under `public/api/`, vanilla JS pages using `rd-api.js` / `sidebar.js` / `v2.css`, migrations as dated `.sql` files in `database/`.

## Database (new files in `database/`)

1. `2026_07_10_160000_create_invoices_table.sql`
   ```sql
   CREATE TABLE invoices (
     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     invoice_number VARCHAR(50) NOT NULL UNIQUE,
     client_id INT UNSIGNED NOT NULL,
     project_id INT UNSIGNED NULL,
     invoice_date DATE NOT NULL,
     due_date DATE NULL,
     subtotal DECIMAL(10,2) DEFAULT 0.00,
     discount DECIMAL(10,2) DEFAULT 0.00,
     grand_total DECIMAL(10,2) DEFAULT 0.00,
     currency VARCHAR(10) DEFAULT 'INR',
     status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
     notes TEXT,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
     FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```
2. `2026_07_10_160001_create_invoice_items_table.sql`
   ```sql
   CREATE TABLE invoice_items (
     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     invoice_id INT UNSIGNED NOT NULL,
     description VARCHAR(255) NOT NULL,
     qty DECIMAL(10,2) DEFAULT 1,
     price DECIMAL(10,2) DEFAULT 0,
     total DECIMAL(10,2) DEFAULT 0,
     source_type ENUM('domain','hosting','maintenance','manual') DEFAULT 'manual',
     source_id INT UNSIGNED NULL,
     sort_order INT UNSIGNED DEFAULT 0,
     FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```
   `source_type`/`source_id` let an invoice item trace back to the `domains`/`hosting`/`maintenance` row it was generated from, so marking the invoice "paid" can cascade `client_paid = 1` on those rows — keeping the existing paid-tracking (used by `dashboard.php`, `project-details.html`) as the single source of truth.
3. `2026_07_10_160002_add_business_invoice_settings.sql` — `INSERT IGNORE` into the existing `settings` key/value table (same pattern as `2026_06_29_000000_add_email_alert_settings.sql`):
   `biz_name, biz_website, biz_phone, biz_email, biz_address, biz_logo_url, bank_name, bank_ifsc, bank_account, upi_id, gpay_number, signatory_name, invoice_prefix, invoice_next_number`.
4. `2026_07_10_160003_create_maintenance_reports_table.sql`
   ```sql
   CREATE TABLE maintenance_reports (
     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     project_id INT UNSIGNED NOT NULL,
     report_date DATE NOT NULL,
     period_start DATE NULL,
     period_end DATE NULL,
     prepared_by VARCHAR(150),
     reviewed_by VARCHAR(150),
     overall_health ENUM('good','fair','needs_attention') DEFAULT 'good',
     status ENUM('draft','final') DEFAULT 'draft',
     data LONGTEXT NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```
   The 16 template sections vary too much in shape (fixed metrics, variable-length plugin/backup/broken-link rows) to justify a dozen normalized tables. `data` holds one JSON document — `{cwv:{lcp,inp,cls}, image_opt:{...}, server:{...}, uptime:{...}, traffic:{sessions,users,page_views,bounce_rate,avg_session_duration,top_pages:[]}, plugins:[], caching_notes, wp_version:{...}, backups:[], security_checks:{...}, seo_basic:{...}, unnecessary_links_removed:{...}, broken_links:[], sitemap_url, robots_url, hardcoded_design:{...}, summary, recommendations, next_maintenance_date}` — mirroring the docx 1:1. Indexed columns (`project_id`, `report_date`, `status`, `overall_health`) cover all list/filter needs; the form/view pages just read and write the JSON blob.

## Backend

- **`public/api/invoices.php`** (new, same shape as `hosting.php`/`clients.php`):
  - `GET` (list) — join `clients`, `projects`; optional `?client_id=`/`?project_id=`/`?status=` filters.
  - `GET ?id=` — invoice + its `invoice_items`.
  - `GET ?unpaid_for=<project_id>` — pulls unpaid rows (`client_paid = 0`) from `domains`, `hosting`, `maintenance` for that project and returns them as candidate line items (description built like "Domain 1 year — example.com", price from the row) for the "generate from unpaid" flow.
  - `POST` — body `{client_id, project_id?, invoice_date, due_date?, discount?, notes?, items:[{description,qty,price,source_type?,source_id?}]}`. Computes `subtotal`/`grand_total`, allocates `invoice_number` from `invoice_prefix` + `invoice_next_number` in `settings` (then increments the counter), inserts invoice + items in a transaction.
  - `PUT ?id=` — update editable fields/items, or just `{status}` to change status. When `status` transitions to `paid`, cascades `UPDATE domains/hosting/maintenance SET client_paid=1 WHERE id IN (...)` for every linked `invoice_items.source_id` (grouped by `source_type`).
  - `DELETE ?id=` — cascades via FK to `invoice_items`.

- **`public/api/maintenance_reports.php`** (new): `GET` (list, `?project_id=` filter, decodes summary fields only), `GET ?id=` (full record incl. parsed `data`), `POST`/`PUT` (validate + `json_encode` the payload into `data`), `DELETE`.

- **`public/api/settings.php`**: extend the `$allowed` whitelist array with the new `biz_*`/`bank_*`/`upi_id`/`gpay_number`/`signatory_name`/`invoice_prefix`/`invoice_next_number` keys (invoice_next_number is also written internally by `invoices.php`, not just via this endpoint).

## Frontend

- **`public/invoices.html`** (new) — list page mirroring `backups.html`'s DataTable pattern: columns Invoice #, Client, Project, Date, Grand Total, Status (`rd-badge` variants: draft=neutral, sent=accent, paid=good, overdue=bad, cancelled=neutral), Actions (View, Mark Paid, Delete). "New Invoice" modal: pick client → optional project → "Load Unpaid Items" button (calls `?unpaid_for=`) populates a checkable/editable row list, plus "+ Add Row" for manual lines; qty/price edits live-recompute totals; discount field; Save.
- **`public/invoice-view.html?id=`** (new) — printable page styled directly after the Developer Deck sample: logo + company block and payment-method block sourced from `settings` API, invoice meta + items table + totals from `invoices.php`. `@media print` rules hide the sidebar/buttons; a "Print / Save as PDF" button calls `window.print()`. Buttons to mark Sent/Paid.
- **`public/reports.html`** (new) — list of `maintenance_reports` across projects: Project, Report Date, Period, Health badge, Status, Actions (View, Edit, Delete).
- **`public/report-edit.html?id=&project_id=`** (new) — form mirroring the 16 docx sections, with add/remove-row controls for the variable-length groups (plugins, top pages, backups, broken links); fixed groups (CWV, uptime metrics, 6 security checks) are static fields. Save Draft / Finalize buttons hit `maintenance_reports.php`.
- **`public/report-view.html?id=`** (new) — printable rendering of the same 16 sections in the docx's order/style, using `.rd-badge-good/-warn/-bad` for the 0–49/50–89/90–100 score bands and Yes/No/OK status cells, ending in the Prepared By / Client Approval sign-off block (typed name + date, no e-signature). Same print-button pattern as the invoice view.
- **`public/assets/js/sidebar.js`** — add a new "Documents" nav group with `invoices` (receipt icon) and `reports` (file-text icon) links, following the existing `navLink()`/`ICONS` pattern.
- **`public/project-details.html`** — add one full-width "Documents" card (two sub-columns: Recent Invoices / Recent Reports, each with a "+ New" button deep-linking to `invoices.html`/`report-edit.html` pre-filled with this project), consistent with the existing card layout.
- **`public/settings.html`** — add a "Business & Invoice Details" `settings-card` (business name/website/phone/email/address/logo URL, bank name/IFSC/account, UPI, Gpay, signatory name, invoice number prefix) wired through the existing `loadSettings()`/`saveSettings()` functions.

## Verification

- Run the 4 new migrations against the local MySQL DB (`mysql < database/2026_07_10_...sql` in order) and confirm tables/columns exist.
- Create a manual invoice from `invoices.html`, confirm totals compute correctly, view/print it on `invoice-view.html`, mark it Paid, and confirm the linked `domains`/`hosting`/`maintenance` rows flip to `client_paid = 1` (check `project-details.html`'s Paid column).
- Generate an invoice from unpaid items on a real project with unpaid domain/hosting rows, confirm the line items match and amounts are correct.
- Fill out and save a maintenance report on `report-edit.html` for a project, reload it to confirm the JSON round-trips, then check `report-view.html` renders every section and the score/status color-coding matches the 0–49/50–89/90–100 and Yes/No bands.
- Confirm both `-view.html` pages print cleanly (sidebar/buttons hidden) via the browser print preview.
