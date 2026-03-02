# Engineering Log System

![Laravel](https://img.shields.io/badge/Laravel-12-red?style=for-the-badge&logo=laravel)
![Vue](https://img.shields.io/badge/Vue-3-42b883?style=for-the-badge&logo=vue.js)
![Inertia](https://img.shields.io/badge/Inertia.js-2.0-purple?style=for-the-badge)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3-38bdf8?style=for-the-badge&logo=tailwind-css)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Development-2496ED?style=for-the-badge&logo=docker)

A lightweight internal engineering log & reporting system built with Laravel 12 and Vue 3.

This project was created as a portfolio full-stack application demonstrating clean CRUD architecture, dashboard analytics, and dynamic PDF/Excel reporting.

---

## ✨ Overview

Engineering Log System is designed to:

- Track technical changes, incidents, and maintenance activities
- Categorize logs by type and impact level
- Monitor system status
- Generate professional PDF and Excel reports
- Provide visual dashboard analytics

It simulates a real internal company reporting tool.

---

## 🚀 Features

### 🔹 Log Management

- Create, edit, delete logs
- Rich text editor (Tiptap)
- Log types:
    - Change
    - Error
    - Fix
    - Maintenance
    - Decision
    - Deployment
    - Idea
- Impact classification:
    - Low
    - Medium
    - High
    - Critical
- Daily filtering view

---

### 🔹 System Management

- Multiple systems tracking
- System status:
    - Active
    - Maintenance
    - Paused
    - Deprecated
- System detail page with related logs

---

### 🔹 Dashboard Analytics

- Logs today / this month
- High & critical impact counter
- Logs per day chart
- Logs by type distribution

---

### 🔹 Reporting Center

- Export logs as:
    - PDF
    - Excel
- Scope options:
    - All logs
    - Daily
    - Monthly
    - Custom date range
    - Per system
- Dynamic report title & summary
- Professional formatted PDF layout

---

## 🛠 Tech Stack

### Backend

- Laravel 12
- Eloquent ORM
- RESTful Controllers
- MySQL

### Frontend

- Vue 3
- Inertia.js
- TailwindCSS
- Chart.js
- Tiptap Editor

### Reporting

- Laravel Excel (Maatwebsite)
- DomPDF

### Development Environment

- Docker
- Laravel Sail
- WSL

---

## 📊 Architecture Highlights

- Clean separation between frontend and backend
- Centralized query builder for report filtering
- Dynamic metadata generation for reports
- Reusable enum-based UI mapping (status, type, impact)
- Multi-account Git SSH setup for professional workflow

---

## 📦 Installation (Development)

```bash
git clone git@github-alze:AlzeHazeka/engineering-log-system.git
cd engineering-log-system

cp .env.example .env

sail up -d
sail artisan key:generate
sail artisan migrate --seed
sail npm install
sail npm run dev
```

Visit:

```
http://localhost
```

---

## 🎯 Purpose

This project was built as:

- A portfolio full-stack application
- A demonstration of practical internal tooling
- An exercise in clean architecture and reporting systems

---

## 📌 Author

**Rahardyandra Naufal Effendy Pratama**  
Full Stack Developer  
Laravel • Vue • MySQL • Docker
