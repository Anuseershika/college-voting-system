# 🗳️ College Online Voting System

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Render](https://img.shields.io/badge/Deploy-Render-46E3B7?logo=render&logoColor=white)](https://render.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE.md)

A complete, role-based electoral web application engineered using **PHP, MySQL, Apache, and Docker**. Designed for academic institutions to manage elections transparently, securely, and efficiently.

---

## 🌟 Demo Credentials

To test the system immediately without registering, use the pre-seeded demo accounts below.

> ⚠️ **These credentials are for demo/testing purposes only.** Change all passwords before any real-world deployment.

| Portal | Role | Username / Identifier | Password | Direct Path |
|---|---|---|---|---|
| **Admin** | Administrator | `admin` | `admin123` | `/admin/login.php` |
| **Student** | Registered Voter | `S101` / `S102` / `S103` | `student123` | `/student/login.php` |
| **Candidate** | Approved Candidate | `michael@college.edu` | `candidate123` | `/candidate/login.php` |
| **Candidate** | Pending Candidate | `david@college.edu` | `candidate123` | `/candidate/login.php` |

---

## 📸 Screenshots

> Add screenshots of the app here. Suggested captures:
> - `Home Page` — election phase banner and portal links
> - `Student Voting Panel` — candidate cards and cast-vote button
> - `Admin Dashboard` — pending nominations, phase control, and stats
> - `Results Page` — winner spotlight and vote-tally bar chart
>
> Place image files in `assets/images/screenshots/` and reference them like:
> ```md
> ![Admin Dashboard](assets/images/screenshots/admin-dashboard.png)
> ```

---

## 🎯 Key Features

### 1. 🛡️ Three-Tier Role-Based Access Control (RBAC)
- **Student Voter Portal:** Browse candidates by department and manifesto, cast a single verified vote, and view real-time election announcements.
- **Candidate Portal:** Submit candidacy applications with manifestos, department details, and profile pictures; track approval status and monitor vote tally post-election.
- **Admin Control Panel:** Manage student voters, approve/reject candidate nominations, control election phases, and view audit statistics.

### 2. 🔒 Electoral Integrity & Security
- **Single-Vote Guarantee:** Database-level uniqueness constraints and atomic PDO transactions prevent double-voting or race conditions.
- **Election Phase Governance:** State machine enforcing three distinct election phases:
  1. `Setup & Registration Phase`: Polls closed, candidate registration open.
  2. `Voting Phase`: Polls open, live results suppressed to eliminate bandwagon bias.
  3. `Election Concluded`: Polls locked, winner proclaimed, and full tally breakdown unlocked.
- **Password Security:** One-way password hashing using PHP's native `PASSWORD_DEFAULT` (Bcrypt).
- **SQL Injection Defense:** Strict PDO prepared statements across all queries.

### 3. 📊 Analytics & Reporting
- Real-time vote percentage breakdown and winner spotlight upon election conclusion.
- Administrative overview cards showing active registrations, votes cast, and pending nominations.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.2 (PDO MySQL)
- **Database:** MySQL 8.0 / TiDB Serverless Cloud
- **Server:** Apache HTTP Server
- **Containerization:** Docker & Docker Compose
- **Frontend:** HTML5, Modern CSS3 (Grid & Flexbox), Vanilla JavaScript
- **Hosting:** Render (Cloud Docker Web Service)

---

## 🚀 Quick Deployment to Render

This repository is pre-configured with a production-ready `Dockerfile` and automated schema initialization.

1. **Fork or Push** this repository to your GitHub account.
2. Get a free MySQL database on [TiDB Serverless](https://tidbcloud.com/) or [Aiven](https://aiven.io/) (takes ~2 minutes, no credit card required).
3. Connect your repository to [Render](https://render.com/) as a **Web Service**.
4. Set the environment variables in Render:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `DB_SSL=true`
5. Render will automatically build the Docker image and deploy. The database schema and seed data are imported automatically on first visit!

👉 For detailed step-by-step deployment instructions, see [DEPLOY_RENDER.md](DEPLOY_RENDER.md).

---

## 💻 Local Development Setup (XAMPP)

1. Clone or copy the folder to `C:\xampp\htdocs\college-voting\`.
2. Start **Apache** and **MySQL** in your XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/` and create a database named `college_voting`.
4. Import `database/schema.sql`.
5. Access the app at `http://localhost/college-voting/`.

For complete local testing workflows, consult [setup_instructions.md](setup_instructions.md).
