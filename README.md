# CareQueue – Simple Clinic Queue Management System

A modern, responsive, and feature-rich Clinic Queue Management System designed to streamline patient waiting lines, coordinate multi-station doctor consultations, and provide real-time digital signage and audio announcements.

---

## 🌟 Features Breakdown

1. **Patient Registration & Self-Check-in Kiosk (`kiosk.php`)**
   - Register with basic patient details or generate a temporary fictional profile with 1-click.
   - Categorize by priority: *Regular*, *Senior (60+)*, *PWD*, *Pregnant*, *Pediatric*, or *Urgent Triage*.
   - Department / Specialty selection: *General Medicine*, *Pediatrics*, *Dental Care*, *Laboratory*, *Vaccination*, and *Priority Care*.
   - **Thermal Ticket Receipt Slip**: Shows unique queue number, estimated wait time, patients ahead, QR/barcode simulation, and thermal print button (`window.print()`).
   - **Live Ticket Tracker**: Patients can look up their queue status in real time.

2. **Automated Queue Number Generation**
   - Generates department-prefixed sequential codes (e.g., `A001`, `P002`, `S001`).
   - Automated priority calculation ensuring urgent and elderly patients are prioritized in the calling queue.

3. **Queue Status Monitoring**
   - Real-time status tracking: `Waiting`, `Serving`, `Completed`, `Skipped`, and `Cancelled`.

4. **Next Patient Calling & Audio Announcements (`staff.php` & `display.php`)**
   - 1-Click "Call Next Patient" algorithm selecting highest priority and longest wait.
   - **Synthesized Melodic Chime**: Built using Web Audio API (D5 to A5 harmonic chime, 100% offline, zero external MP3 dependencies).
   - **Voice Calling Engine**: Uses Web Speech API to vocalize announcements: *"Now serving queue number A 0 0 2, please proceed to Counter 1 - General Medicine"*.

5. **Queue Display Board / TV Waiting Room View (`display.php`)**
   - High-contrast, airport/hospital digital signage interface designed for large TV monitors.
   - Giant "NOW SERVING" spotlight with pulse animation.
   - Multi-counter status grid showing active consultations and doctors.
   - Upcoming "Next in Line" waiting queue ticker.
   - Recently completed and skipped ticker ribbons.
   - Fullscreen mode support.

6. **Queue History & Records Audit (`history.php`)**
   - Comprehensive audit trail with check-in, call time, and completion timestamps.
   - Precise wait time and consultation duration tracking.
   - Real-time search by patient name, phone, or ticket code.
   - Filters for date, department, and status.
   - **Export to CSV** and **Export to JSON** capabilities.

7. **Queue Management (Skip & Recall)**
   - Ability for authorized staff to skip non-responsive patients with recorded reasons.
   - Dedicated "Skipped List" with 1-click "Recall to Station" functionality.
   - Department transfer support (e.g. transfer patient to Laboratory or Pharmacy).

8. **Clinic Administrator Dashboard (`admin.php`)**
   - Real-time KPI summary cards (Total visits, Waiting, Serving, Completed, Skipped, Avg Wait Mins, Avg Consultation Mins).
   - Visual department volume breakdown bars.
   - Demographic distribution charts.
   - Hourly patient arrival traffic graph.
   - Doctor/Station throughput performance metrics.
   - Consultation room & staff management (add/edit counters).
   - 1-Click Queue Reseeder with realistic fictional patients.

---

## 👥 User Roles & Access

| Role | Interface | Description |
| :--- | :--- | :--- |
| **Patient** | [`kiosk.php`](http://localhost/Final/kiosk.php) | Touch-friendly self check-in, ticket receipt printing, live status tracker. |
| **Clinic Staff / Doctor** | [`staff.php`](http://localhost/Final/staff.php) | Counter console, call next patient, stopwatch timer, skip, recall, transfer. |
| **Lobby Display** | [`display.php`](http://localhost/Final/display.php) | Public waiting room TV screen with audio chime and voice announcements. |
| **Administrator** | [`admin.php`](http://localhost/Final/admin.php) | Analytics dashboard, station/room configuration, queue maintenance. |
| **Records / Auditor** | [`history.php`](http://localhost/Final/history.php) | Queue transaction history, audit records, CSV/JSON export. |

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+ with PDO MySQL (with automatic schema creation and seeding).
- **Database**: MySQL / MariaDB (`clinic_queue_db`).
- **Frontend**: Pure Vanilla HTML5, CSS3 (Glassmorphism design system), and JavaScript (ES6+).
- **Audio**: Web Audio API (procedural synthesized chime) + Web Speech API (vocal caller).
- **Typography**: Google Fonts (*Plus Jakarta Sans* & *JetBrains Mono*).

---

## 🚀 How to Run Locally

1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your web browser and navigate to:
   ```
   http://localhost/Final/
   ```
3. The database `clinic_queue_db` and all sample fictional patient records will initialize automatically on first load!

---

## 🌐 CI/CD Automated Deployment to InfinityFree

This project features automated CI/CD deployment via **GitHub Actions** (`.github/workflows/deploy.yml`). Every push to the `main` branch automatically uploads and synchronizes the project files to your **InfinityFree** hosting account via FTP.

### Required GitHub Secrets

Go to your repository on GitHub: **Settings** &rarr; **Secrets and variables** &rarr; **Actions** and add the following secrets:

| Secret Name | Value Description | Example / Location in InfinityFree |
| :--- | :--- | :--- |
| `FTP_HOSTNAME` | Your InfinityFree FTP host | `ftpupload.net` (from InfinityFree Account Details) |
| `FTP_USERNAME` | Your FTP account username | `if0_38xxxxxx` (from InfinityFree Account Details) |
| `FTP_PASSWORD` | Your FTP / vPanel account password | Your InfinityFree account password |
| `FTP_SERVER_DIR` *(Optional)* | Target folder on server | Default is `htdocs/`. (e.g. `yourdomain.com/htdocs/` if using custom domain) |

### Setting Up MySQL on InfinityFree

1. In your **InfinityFree Client Area**, open **MySQL Databases**.
2. Create a new database (e.g. `clinic_db`).
3. Note down the **MySQL Host Name** (e.g., `sql302.infinityfree.com`), **MySQL Database Name**, **MySQL User Name**, and **Password**.
4. In your InfinityFree File Manager inside `htdocs/config/`, copy `db_config.sample.php` to `db_config.php` and fill in your MySQL credentials:
   ```php
   define('DB_HOST', 'sql302.infinityfree.com');
   define('DB_USER', 'if0_38123456');
   define('DB_PASS', 'your_account_password');
   define('DB_NAME', 'if0_38123456_clinic_db');
   define('DB_PORT', 3306);
   ```
5. When you visit your live site, CareQueue will automatically create all tables and initial seed data on your InfinityFree MySQL server!

### Manual Deployment Trigger

You can also trigger deployment manually at any time without creating a new commit:
1. Go to the **Actions** tab in your GitHub repository.
2. Select **Deploy to InfinityFree via FTP** in the left sidebar.
3. Click **Run workflow** &rarr; Select branch `main` &rarr; Click **Run workflow**.
