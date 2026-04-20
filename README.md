# 🗂️ RenewDesk

> **A simple PHP + AJAX admin portal to track domain renewals, hosting renewals, yearly maintenance contracts and backup schedules — with automated email reminders.**

---

## 🔗 Quick Links

| Service | URL |
|---|---|
| 🌐 **Web App** | [http://localhost:8880](http://localhost:8880) |
| 🗄️ **phpMyAdmin** | [http://localhost:8883](http://localhost:8883) |
| 💻 **GitHub Repo** | [https://github.com/KuldipsinhParmar/RenewDesk](https://github.com/KuldipsinhParmar/RenewDesk) |

---

## 🔐 Login Details

### Admin Portal
| Field | Value |
|---|---|
| URL | http://localhost:8880/login.html |
| Email | `admin@renewdesk.local` |
| Password | `Admin@123` |

> ⚠️ **Change the admin password after first login via the Settings page.**

### phpMyAdmin
| Field | Value |
|---|---|
| URL | http://localhost:8883 |
| Username | `root` |
| Password | `renewdesk_root_pass` |

---

## 🗃️ Database Credentials

| Field | Value |
|---|---|
| Host (from app) | `renewdesk_db` |
| Host (from host machine) | `127.0.0.1` |
| Port | `3380` |
| Database | `renewdesk_db` |
| Username | `renewdesk_user` |
| Password | `renewdesk_pass` |
| Root Password | `renewdesk_root_pass` |

---

## 📦 What This App Tracks (Per Project)

Each **Project** can have:

| Module | Details Stored |
|---|---|
| 🌐 **Domain** | Domain name · Registrar · Renewal date · Price |
| 🖥️ **Hosting** | Provider · Plan name · Renewal date · Price |
| 🔧 **Maintenance** | Yearly AMC · Start date · End date · Price |
| 💾 **Backup** | Frequency · Last backup date · Next backup date · Storage location |

---

## 📧 Email Reminders

- All reminders are sent directly to **`kuldipparmar18@gmail.com`** (hardcoded)
- Uses PHP native `mail()` — no SMTP configuration needed
- Covers all 4 asset types: **Domains**, **Hosting**, **Maintenance**, **Backups**
- Configurable intervals from Settings page: e.g. **30, 15, 7, 1 days** before expiry
- Triggered by a **daily cron job** (`cron/send_reminders.php`)
- Premium HTML email template with urgency color coding
- All sent emails logged in `reminder_logs` table

### ⏰ Cron Job Setup on Hostinger (Auto Send Reminders)

The cron job runs daily to check for expiring assets and send email alerts to `kuldipparmar18@gmail.com`.

> ⚠️ The `cron/` folder is blocked from direct web access for security. Use the **URL trigger** method below.

---

#### ✅ Option 1 — URL Trigger (Recommended for Hostinger)

A secure trigger endpoint is available at:

```
https://renewdesk.developerdeck.in/api/cron_trigger.php?key=RD-CRON-2024-xK9mP3qW7vN1
```

**Setup in hPanel:**

1. Log in to [hPanel](https://hpanel.hostinger.com)
2. Select your website → **Advanced** → **Cron Jobs**
3. Fill in:

| Field | Value |
|---|---|
| **Command** | `wget -qO- "https://renewdesk.developerdeck.in/api/cron_trigger.php?key=RD-CRON-2024-xK9mP3qW7vN1" > /dev/null 2>&1` |
| **Schedule** | Once a day (or custom: `0 8 * * *` for 8 AM daily) |

4. Click **Create**

> 💡 You can also use `curl` instead of `wget`:
> ```
> curl -s "https://renewdesk.developerdeck.in/api/cron_trigger.php?key=RD-CRON-2024-xK9mP3qW7vN1" > /dev/null 2>&1
> ```

**Test it now:** Open this URL in your browser to trigger it manually:
```
https://renewdesk.developerdeck.in/api/cron_trigger.php?key=RD-CRON-2024-xK9mP3qW7vN1
```

---

#### ⚙️ Option 2 — PHP CLI (If SSH Available)

If you prefer CLI, use the full server path:

| Field | Value |
|---|---|
| **Command** | `/usr/bin/php /home/u123456789/domains/renewdesk.developerdeck.in/public_html/cron/send_reminders.php` |
| **Schedule** | `0 8 * * *` |

> 📌 Replace `u123456789` with your actual Hostinger username (find it in hPanel → **Account** or **File Manager** breadcrumb).

To find the correct PHP path:

| PHP Version | Path |
|---|---|
| Default | `/usr/bin/php` |
| PHP 8.1 | `/usr/bin/php8.1` |
| PHP 8.2 | `/usr/bin/php8.2` |

---

#### 🧪 Test via SSH (Optional)

```bash
# Connect to Hostinger SSH
ssh u123456789@renewdesk.developerdeck.in -p 65002

# Run the cron manually
/usr/bin/php /home/u123456789/domains/renewdesk.developerdeck.in/public_html/cron/send_reminders.php
```

> 📌 Enable SSH: **hPanel** → **Advanced** → **SSH Access** → **Enable**

---

#### 📋 Cron Schedule Cheatsheet

| Schedule | Cron Expression |
|---|---|
| Every day at 8 AM | `0 8 * * *` |
| Every day at midnight | `0 0 * * *` |
| Every 12 hours | `0 */12 * * *` |
| Twice a day (8 AM & 6 PM) | `0 8,18 * * *` |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | Plain PHP 8.2 (no framework) |
| API | REST API (JSON) |
| Frontend | HTML5 + CSS3 + Vanilla JS (Fetch/AJAX) |
| UI | Bootstrap 5 + Bootstrap Icons |
| Database | MySQL 8.0 |
| Auth | PHP Session (single admin) |
| Email | PHP native `mail()` — direct to `kuldipparmar18@gmail.com` |
| Dev Env | Docker (web + db + phpmyadmin) |

---

## 🗃️ Database Tables

All SQL files are in `database/tables/` — one file per table:

| File | Table | Description |
|---|---|---|
| `01_admin.sql` | `admin` | Single admin login |
| `02_projects.sql` | `projects` | Core project records |
| `03_domains.sql` | `domains` | Domain renewal + price |
| `04_hosting.sql` | `hosting` | Hosting renewal + price |
| `05_maintenance.sql` | `maintenance` | Yearly AMC contract |
| `06_backups.sql` | `backups` | Backup schedule |
| `07_reminder_logs.sql` | `reminder_logs` | Email reminder history |
| `08_settings.sql` | `settings` | App config (remind days, timezone) |

### Import all tables (run once):
```bash
for f in database/tables/*.sql; do
  docker exec -i renewdesk_db mysql -u renewdesk_user -prenewesk_pass renewdesk_db < "$f"
done
```

---

## 🐳 Docker

### Start containers
```bash
docker compose up -d
```

### Stop containers
```bash
docker compose down
```

### Rebuild after Dockerfile changes
```bash
docker compose up -d --build
```

### View logs
```bash
docker compose logs -f web
```

---

## 📁 Project Structure

```
RenewDesk/
├── public/                        ← Apache document root
│   ├── index.html                 ← Main SPA
│   ├── login.html                 ← Admin login
│   └── assets/
│       ├── css/app.css
│       └── js/
│           ├── api.js             ← Fetch wrapper
│           ├── app.js             ← SPA router
│           ├── dashboard.js
│           ├── projects.js
│           └── settings.js
├── api/                           ← PHP REST API
│   ├── index.php                  ← URL router
│   ├── config/
│   │   ├── db.php                 ← PDO connection
│   │   ├── auth.php               ← Session guard
│   │   └── cors.php               ← CORS headers
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProjectController.php
│   │   ├── DomainController.php
│   │   ├── HostingController.php
│   │   ├── MaintenanceController.php
│   │   ├── BackupController.php
│   │   └── SettingsController.php
│   └── helpers/
│       ├── Response.php
│       └── Mailer.php
├── cron/
│   └── send_reminders.php         ← Daily cron script
├── database/
│   └── tables/                    ← SQL files (one per table)
│       ├── 01_admin.sql
│       ├── 02_projects.sql
│       ├── 03_domains.sql
│       ├── 04_hosting.sql
│       ├── 05_maintenance.sql
│       ├── 06_backups.sql
│       ├── 07_reminder_logs.sql
│       └── 08_settings.sql
├── docker/
│   └── apache/vhost.conf
├── Dockerfile
├── docker-compose.yml
├── .env
├── .env.example
└── .gitignore
```

---

## 🔌 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Admin login |
| POST | `/api/auth/logout` | Logout |
| GET | `/api/dashboard` | Summary + expiring soon |
| GET/POST | `/api/projects` | List / Create project |
| GET/PUT/DELETE | `/api/projects/{id}` | Get / Update / Delete |
| GET/POST | `/api/domains` | Domain list / add |
| PUT/DELETE | `/api/domains/{id}` | Update / Delete domain |
| GET/POST | `/api/hosting` | Hosting list / add |
| PUT/DELETE | `/api/hosting/{id}` | Update / Delete hosting |
| GET/POST | `/api/maintenance` | Maintenance list / add |
| PUT/DELETE | `/api/maintenance/{id}` | Update / Delete |
| GET/POST | `/api/backups` | Backup list / add |
| PUT/DELETE | `/api/backups/{id}` | Update / Delete |
| GET/PUT | `/api/settings` | Get / Update settings |

---

## 🚀 Development Phases

- [x] Phase 0 — Docker, Git, .gitignore, DB schema
- [ ] Phase 1 — PHP API foundation (router, auth, DB config)
- [ ] Phase 2 — CRUD APIs (projects, domains, hosting, maintenance, backup)
- [ ] Phase 3 — Frontend SPA (login, dashboard, project management)
- [ ] Phase 4 — Email reminders + cron job
- [ ] Phase 5 — Polish, validation, settings UI

---

*Built with ❤️ for internal project renewal tracking.*
