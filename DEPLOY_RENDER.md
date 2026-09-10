# Deploying College Voting System on Render (100% Free)

This guide walks you through deploying this **PHP & MySQL College Voting System** to **Render** for free so recruiters can click a live URL directly from your resume and test all features (Admin, Candidate, and Student Voter).

---

## 🏗️ Architecture Overview

- **Web Server:** Render Web Service running PHP 8.2 + Apache in Docker (Free tier).
- **Database:** Free Managed Cloud MySQL (e.g., [TiDB Serverless](https://tidbcloud.com/) or [Aiven](https://aiven.io/)) with 5 GB free forever and no credit card required.
- **Auto-Provisioning:** The application will automatically create all tables and populate seed data (admin, candidates, students) upon your first visit!

---

## 🚀 Step-by-Step Deployment Instructions

### Step 1: Commit and Push Code to GitHub

Open your terminal or command prompt inside this project folder:

```bash
git add .
git commit -m "Configure Dockerfile and cloud database support for Render deployment"
git push origin main
```

---

### Step 2: Get a Free Cloud MySQL Database (Takes 2 Minutes)

Because Render does not provide a native free MySQL database, we recommend **TiDB Serverless** (developed by PingCAP):
- **100% Free Forever** (5 GB storage)
- **No credit card required**
- Full MySQL 8.0 protocol support

1. Visit [https://tidbcloud.com/](https://tidbcloud.com/) and click **Sign Up** (use **Continue with GitHub** for 1-click registration).
2. Click **Create Cluster** and select **TiDB Serverless** (Free plan).
3. Name your cluster (e.g., `voting-db`) and click **Create**.
4. Once created, click **Connect** in the dashboard.
5. Choose **Connection Method: General** or **MySQL Client**.
6. You will see:
   - **Host:** (e.g., `gateway01.us-east-1.prod.aws.tidbcloud.com`)
   - **Port:** `4000`
   - **Database Name:** `test`
   - **Username:** (e.g., `2aBcdEf.root`)
   - **Password:** (click *Generate Password* and copy it)

> 💡 *Note: You can also use any other free MySQL provider like Aiven, Clever Cloud, or Railway.*

---

### Step 3: Deploy Web Service on Render

1. Go to [https://render.com/](https://render.com/) and sign in with your **GitHub account**.
2. From the Render Dashboard, click **New +** (top right) and select **Web Service**.
3. Under **Connect a repository**, select `college-voting-system` (or search for it).
4. Configure your Web Service settings:
   - **Name:** `college-voting-system` (or your preferred name, e.g. `anuseershika-voting`)
   - **Region:** Choose the region closest to you (e.g., Singapore, Frankfurt, or Oregon).
   - **Branch:** `main`
   - **Runtime:** **Docker** (Render will automatically detect the `Dockerfile`).
   - **Instance Type:** **Free** ($0/month).
5. Scroll down to the **Environment Variables** section and click **Add Environment Variable** to add the following:

| Key | Value (from TiDB / Cloud DB) |
|---|---|
| `DB_HOST` | `your-db-host.prod.aws.tidbcloud.com` |
| `DB_PORT` | `4000` (or `3306` if using standard MySQL) |
| `DB_NAME` | `test` (or `college_voting`) |
| `DB_USER` | `your_user.root` |
| `DB_PASS` | `your_database_password` |
| `DB_SSL` | `true` |

6. Click **Deploy Web Service** at the bottom of the page.
7. Render will build the Docker container and start Apache. Once the status shows **Live**, click your service URL (e.g., `https://college-voting-system.onrender.com`).

> 🎉 **Zero-Touch DB Migration:** The very first time you open your live link, the application will automatically detect that tables are missing, create all 5 database tables, and insert default test accounts!

---

## 🔑 Demo Credentials for Testing & Resume

You can include these credentials in your GitHub README or project demo note for recruiters:

| Role | Username / Identifier | Password | Access URL |
|---|---|---|---|
| **Administrator** | `admin` | `admin123` | `/admin/login.php` |
| **Student Voter** | `S101` (or `S102`, `S103`) | `student123` | `/student/login.php` |
| **Approved Candidate** | `michael@college.edu` | `candidate123` | `/candidate/login.php` |
| **Pending Candidate** | `david@college.edu` | `candidate123` | `/candidate/login.php` |

---

## 📄 How to Feature This Project on Your Resume

### Example Resume Entry

**College Online Voting & Governance System** | *PHP, MySQL, Apache, Docker, JavaScript, CSS3*
- **Live Demo:** [https://your-app-name.onrender.com](https://your-app-name.onrender.com) | **GitHub:** [https://github.com/Anuseershika/college-voting-system](https://github.com/Anuseershika/college-voting-system)
- Engineered a full-stack, role-based electoral web application featuring secure 3-tier authentication for Administrators, Candidates, and Student Voters.
- Implemented single-vote enforcement using PDO transactions and duplicate verification to ensure electoral integrity and prevent double-voting.
- Developed an administrative dashboard providing end-to-end election phase governance (Setup, Voting, Concluded) with real-time result tabulation and winner determination.
- Containerized the application using Docker and deployed a highly available production environment on Render integrated with cloud MySQL.

---

## ⚡ Important Render Free Tier Tips

- **Free Tier Cold Starts:** Render free services spin down into sleep mode after 15 minutes of inactivity. When a recruiter clicks the link, it might take 30–50 seconds to wake up for the first request.
- **Tip to avoid cold starts during job applications:** Use a free uptime monitor like [UptimeRobot](https://uptimerobot.com/) or [Cron-Job.org](https://cron-job.org/) to ping your URL every 10 minutes so your demo remains instantly accessible 24/7!
