# 🧮 Task Manager (Laravel + Vue.js)

A full-featured **Task Management System** built with **Laravel 12 (Backend API)** and **Vue.js 3 (Frontend)**.

---

## 🚀 Features
✅ User Authentication (Login/Register)  
✅ Role-based Access (Admin/User)  
✅ Create Group and Group User
✅ Create, Assign, and Manage Tasks   
✅ RESTful API with Laravel  
✅ Modern UI with Vue 3 + Pinia + Axios  
✅ Responsive Design (Bootstrap)

---

## 🧠 Tech Stack
**Backend:** Laravel 12, MySQL, Sanctum  
**Frontend:** Vue.js 3, Pinia, Axios, Vite  
**Server:** PHP 8.2+, Node.js 18+

---

## ⚙️ Setup Instructions

### 🔹 Backend Setup
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve