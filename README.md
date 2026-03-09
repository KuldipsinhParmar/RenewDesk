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

- Automated reminders sent **to the admin email** before renewals expire
- Configurable intervals: e.g. **30, 15, 7, 1 days** before
- Triggered by a **daily cron job** (`cron/send_reminders.php`)
- Manual "Send Now" also available per project item
- All sent emails logged in `reminder_logs` table

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
| Email | PHPMailer (SMTP) |
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
| `08_settings.sql` | `settings` | App config & SMTP |

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
