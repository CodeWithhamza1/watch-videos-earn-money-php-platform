# 📦 YouTube Video Earning Platform — Complete Setup Guide

## 🎯 Objective
Build a simple **PHP + MySQL** platform where users can watch **YouTube videos** and earn **5 PKR** for each full video watched. The total earnings are capped at **300 PKR**, after which users will see a **WhatsApp contact button**.

Admins can track user earnings, manage videos, and handle suspicious activity from a **clean admin dashboard**.

---

## 🛠️ Tech Stack

### Frontend
- **HTML** – Markup for the user and admin pages
- **Tailwind CSS** – For a clean and responsive design
- **JavaScript (Alpine.js)** – Lightweight interactivity for frontend
- **YouTube IFrame API** – Embedded video player with event tracking

### Backend
- **PHP (Vanilla)** – Pure PHP for backend logic, no frameworks
- **MySQL** – Database for user management and watch logs
- **PHP Sessions** – For managing user sessions and authentication
- **Password Hashing** – Secure user authentication

---

## 👤 User Features

- **User Authentication (Login/Register)**
- Watch **YouTube videos** directly in the dashboard
- Earn **5 PKR** for each fully watched video
- **300 PKR limit** per user (after reaching this, users will be able to contact admin via WhatsApp)
- View **real-time earnings** and **watch progress**
- Detect tab switches, inactivity, and page refreshes to prevent fraud
- **WhatsApp button** appears after user reaches **300 PKR**

### WhatsApp Button
Once the user reaches **300 PKR**, a **WhatsApp contact button** will be displayed:

```html
<a href="https://wa.me/YOUR_PHONE_NUMBER" class="bg-green-600 text-white p-3 rounded-lg shadow-lg">💬 Contact Admin on WhatsApp</a>

## 🔐 Fraud Protection Features

### Tab Switching Detection
Uses visibilitychange event to pause the video and stop the reward if the tab is switched.

### Inactivity Detection
Ends the session if no activity (mouse, keyboard) is detected for 15 seconds.

### Page Refresh Detection
Resets the session and watch time if the page is refreshed or the user navigates away.

### Session Management
One session per user/IP/device to avoid multi-accounting.

### Device Fingerprint + IP Lock
Unique device identification to prevent multiple logins from different devices.

---

## 🧑‍💼 Admin Dashboard Features
Admin dashboard allows administrators to:

- View user earnings and activity logs
- Manage embedded videos (add, edit, remove videos)
- Track user sessions (including suspicious activity like tab switching)
- Approve or deny payouts once users reach 300 PKR
- Real-time view of active users and their watch progress
- Flag suspicious users and monitor logs

### Admin Dashboard Layout
#### Sidebar Navigation:
- Users Overview
- Videos Management
- Earnings & Payouts
- Suspicious Activity Log
- Responsive UI with light/dark mode toggle

#### Admin Stats:
- Total views
- Total earnings
- Flagged users