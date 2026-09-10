# Setup Instructions - College Online Voting System

This project is a complete, secure, and beginner-friendly web application built using **PHP, HTML, CSS, JavaScript, and MySQL**. Follow the steps below to set it up and run it locally using **XAMPP**.

---

## 📋 Prerequisites
Ensure you have the following installed on your machine:
1. **XAMPP** (includes Apache server, PHP, and MySQL database). Get it here: [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Any web browser (Google Chrome, Firefox, Microsoft Edge).

---

## 🚀 Step-by-Step Installation Guide

### Step 1: Clone or Copy Project Files
1. Copy the entire project folder (named `old voting` or rename it to `college-voting`).
2. Move/Paste the folder into your local XAMPP directory, inside the `htdocs` folder:
   - **On Windows**: `C:\xampp\htdocs\college-voting\`

### Step 2: Start XAMPP Servers
1. Open the **XAMPP Control Panel** from your start menu.
2. Click **Start** for both the **Apache** and **MySQL** modules.
3. Verify that both modules display a green background, indicating they are running successfully.

### Step 3: Initialize the MySQL Database
1. Open your web browser and navigate to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click on **New** in the left sidebar to create a new database:
   - Database Name: `college_voting`
   - Collation: `utf8mb4_general_ci` or `utf8mb4_unicode_ci`
   - Click **Create**.
3. Click on the newly created `college_voting` database.
4. Click on the **Import** tab at the top.
5. Click **Choose File** and select the database schema file from the project directory:
   - Path: `C:\xampp\htdocs\college-voting\database\schema.sql`
6. Scroll to the bottom of phpMyAdmin and click **Import** (or **Go**).
7. You should see a success message indicating that the database tables and sample data were imported successfully.

### Step 4: Run the Application
Open your browser and navigate to:
👉 [http://localhost/college-voting/](http://localhost/college-voting/)

---

## 🔑 Default Login Credentials (for testing)

The database includes pre-configured sample accounts for instant testing of all three roles.

### 1. Administrator Account
Access the admin page via the Home Page or directly at `http://localhost/college-voting/admin/login.php`
- **Username:** `admin`
- **Password:** `admin123`

### 2. Candidate Account
Access the candidate portal via the Home Page or directly at `http://localhost/college-voting/candidate/login.php`
- **Email:** `michael@college.edu` *(Nomination Status: Approved)*
- **Email:** `david@college.edu` *(Nomination Status: Pending)*
- **Password:** `candidate123` (same for all seeded candidates)

### 3. Student Voter Account
Access the student portal via the Home Page or directly at `http://localhost/college-voting/student/login.php`
- **Student ID:** `S101`
- **Student ID:** `S102`
- **Student ID:** `S103`
- **Password:** `student123` (same for all seeded students)

---

## 🛠️ Testing Workflows (How to test the features)

1. **Test Application Review (Admin & Candidate)**:
   - Register a new candidate using the **Candidate Registration Form**. Fill in their manifesto and upload an avatar picture.
   - Login as the candidate. Note that their application status is **Pending**.
   - Login as the **Admin** (`admin`/`admin123`).
   - In the Admin Panel, locate the newly submitted candidate application under **Pending Applications**. Click **Approve**.
   - Log back in as the Candidate. Notice their status badge has changed to **Approved Candidate**.

2. **Test Single Voting Logic (Student/Voter)**:
   - Login as a student (e.g. `S101` / `student123`).
   - Navigate to the **Voting Panel**.
   - Select an approved candidate and click **Cast Vote**. Confirm the popup dialog.
   - Note the dashboard updates to a green **Vote Confirmed!** screen.
   - Try to navigate back or submit another vote; notice you are blocked by the system's checks. Check phpMyAdmin and observe that your vote is stored, and the student's `has_voted` column is updated to `1`.

3. **Test Results Declaration**:
   - Log back in as **Admin**.
   - Locate the **Election Phase Governance** box on the dashboard.
   - Change the phase to **Election Concluded** and click **Update System Phase**.
   - Go back to the Public Home Page (`http://localhost/college-voting/`). Notice the header banner says **Election Concluded**.
   - Log in as a student or simply go to the voting dashboard. Since the election has ended, the dashboard will display:
     - The **Election Winner** highlighted with a golden card.
     - An **Interactive Bar Chart** depicting vote tallies.
     - A detailed table indicating total votes and voter share percentages.
   - Log in as the approved candidate. Notice their vote count is now unlocked and displayed.
