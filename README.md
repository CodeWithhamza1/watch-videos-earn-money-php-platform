# 🎬 YouTube Watch & Earn Platform (PHP + MySQL)

A fraud-resistant, real-user-driven platform where users watch YouTube videos and earn **5 PKR** per video completion. Built with **pure PHP**, **MySQL**, **JavaScript**, and **Tailwind CSS**, this platform includes real-time activity tracking, biometric verification, session security, and a robust admin panel for managing users, videos, and payments.

---

## ✨ Key Features

### 👤 User Dashboard
- 🎥 Embedded YouTube video player (no skipping or fast-forward)
- ⏳ Live watch progress tracking
- 💸 Earnings tracker (real-time balance updates)
- 📋 Watch history & session logs
- ⚠️ Tab-switch / back button / inactivity detection
- 🔐 Session security: IP lock, fingerprinting
- 👁️ Human verification prompts mid-video
- 🌙 Dark/Light mode toggle
- 📲 Mobile-responsive UI
- 🤝 Referral system
- 🧾 Withdraw system (with method selection)

### 🛠️ Admin Dashboard
- 🔐 Secure login (session or JWT-based)
- 📡 Real-time viewer monitoring
- 👁️ Video status per user (watching, idle, flagged)
- 📊 Graphical analytics (Chart.js/ApexCharts)
- ⚠️ Fraud detection (tab switches, repeated sessions, suspicious patterns)
- 🧑‍💻 Micro-level user management (ban, verify, reset, logs)
- 📹 Video manager (add/edit/schedule videos)
- 💼 Withdrawal log with manual/auto approval

---

## 🧱 Tech Stack

| Layer     | Tech Used                        |
|-----------|----------------------------------|
| Frontend  | HTML, Tailwind CSS, JavaScript   |
| Backend   | PHP (OOP), MySQL                 |
| Auth      | PHP Sessions / JWT               |
| Analytics | Chart.js / ApexCharts            |
| Security  | FingerprintJS, reCAPTCHA v3      |
| Tracking  | `visibilitychange`, AJAX, Events |

---

## 🗂️ Folder Structure

```
📁 root/
│
├── 📁 admin/            # Admin dashboard files
├── 📁 assets/           # Tailwind CSS, JS, images
├── 📁 auth/             # Login, Register, Logout
├── 📁 components/       # UI Components
├── 📁 config/           # DB and App Config
├── 📁 dashboard/        # User dashboard pages
├── 📁 functions/        # Helper and DB functions
├── 📁 logs/             # Fraud/session logs
├── 📁 database/         # SQL dump & migration files
├── 📁 withdraw/         # Withdraw system files
└── 📄 index.php         # Main entry point
```

---

## ⚙️ Installation Guide

### 1. 📥 Clone the Repository

```bash
git clone https://github.com/your-username/youtube-watch-earn.git
cd youtube-watch-earn
```

### 2. 🗄️ Database Setup

- Open `phpMyAdmin` or MySQL CLI.
- Import the SQL file located at:

```
/database/youtube_earn.sql
```

This will create the necessary tables and test users.

### 3. ⚙️ Configuration

Create a file named `.env.php` or `config.php` in the `/config/` folder and add your database credentials:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'yt_watch');
?>
```

Also configure:
- Base URL of the site
- Admin email
- Default session timeout

### 4. 🧩 Setup Permissions

Make sure the following folders are writable (for logging):

```bash
chmod -R 755 logs/
chmod -R 755 withdraw/
```

### 5. 🧪 Testing Locally

Run the project using Apache (XAMPP, Laragon, etc.) and navigate to:

```
http://localhost/yt-watch/
```

Use the test user credentials from the SQL file to log in.

---

## 🧠 Advanced Anti-Cheat Measures

| Action                  | Platform Response                          |
|-------------------------|---------------------------------------------|
| Tab switch detected     | Pause video, show warning modal             |
| Page refresh/back click | Invalidate session and reset watch time     |
| Auto-clickers/scripts   | Human verification popups mid-video         |
| Multi-login or VPN      | Block by FingerprintJS + IP + Session lock  |
| Inactivity >15s         | Trigger session reset                       |

---

## 📊 Admin Monitoring Widgets

- 👁️ Active Viewers Panel
- 📈 Session Heatmap
- 💰 Earnings vs Views Chart
- ⚠️ Fraud Alerts Feed
- 📹 Video Scheduler
- 🧾 Withdraw Request Manager

---

## 🧑‍💻 Developer Notes

- Entire app is written in **vanilla PHP (no frameworks)**.
- Uses **modular functions** and **componentized HTML**.
- Easily portable to Laravel or CodeIgniter later.
- Lightweight, scalable, and optimized for real-world usage in regions like **Pakistan**.

---

## 🤝 Contributing

Pull requests are welcome. For major changes:
1. Open an issue first to discuss changes.
2. Create a feature branch `feature/your-feature-name`
3. Submit your PR after testing

---

## 📜 License

MIT License — use it freely, modify it, and improve upon it. Attribution appreciated 🙌

---

## ✨ Credits & Maintainer

Developed with ❤️ by [**Muhammad Hamza Yousaf**](https://github.com/codewithhamza1)  
📍 Lahore, Pakistan  
🎯 Dreaming Big: `In sha Allah!` 🚀  
📬 Let's connect on LinkedIn or GitHub!

---

> 🎉 This project is just the beginning. Let's build something impactful — fair tech for real users, made by passionate coders like you!
