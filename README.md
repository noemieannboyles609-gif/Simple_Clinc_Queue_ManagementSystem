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
