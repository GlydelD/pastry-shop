# Artisan Pastry Shop Management System

A comprehensive, full-stack web application for managing a modern pastry shop. It features a robust **Admin Dashboard** for business management and a seamless **Customer Interface** for online ordering, all built with raw PHP, MySQL, and vanilla CSS/JS.

## 🚀 Key Features of the System

### 👤 Role-Based Access
- **Admin Dashboard**: A command center for store owners to manage the entire business.
- **Customer Dashboard**: A personalized shopping portal for users to browse, order, and track purchases.
- **Smart Redirects**: Automatically routes logged-in users to their appropriate dashboard (Admin vs. Customer).

### 🛒 Product & Inventory Management
- **Dynamic Category System**: Categories are automatically created and managed based on product entries—no manual setup required. New categories appear instantly in filters.
- **Full CRUD Operations**: Add, edit, and delete pastries (Name, Description, Price, Stock, Category, Image URL).
- **Real-time Inventory**: Automatic stock deduction upon ordering.
- **Low Stock Alerts**: Visual indicators on the admin dashboard for items running low.

### 📊 Analytics & Visualizations
- **Interactive Charts**:
    - **Revenue by Category**: Dynamic doughnut chart showing sales distribution (filters out non-selling categories automatically).
    - **Monthly Sales Trends**: Bar chart visualization of revenue over the last 6 months.
    - **Hourly Order Trends**: Line graph showing peak ordering times during the day.
    - **Customer Growth**: Tracking new user registrations over time.
- **Key Metrics**: Instant view of Total Revenue, Orders Today, Top Selling Item, and Low Stock count.

### 🛍️ Order System
- **Shopping Cart**: Fully functional cart with quantity adjustments and live total calculation.
- **Wishlist**: Customers can save favorite items for later.
- **Checkout Process**: Streamlined ordering flow.
- **Order Tracking**:
    - **Customers**: View order history and current status (Pending, Processing, Completed, Cancelled).
    - **Admins**: View all orders, filter by status, and update order progress.

### 🔍 Search & Filtering
- **Advanced Search**: Instant search functionality for products (name/description) and users.
- **Dynamic Filtering**: Filter products by category (automatically populated) and sort by Price (Low-High / High-Low).

### 🎨 UI/UX & Design
- **Theme Toggle**: specialized Dark Mode and Light Mode support with persistent preference.
- **Responsive Design**: Fully mobile-optimized interface for on-the-go management and ordering.
- **Modern Aesthetics**: Glassmorphism effects, smooth transitions, and a premium "Artisan" color palette.

---

### 1. Database Setup


1. Open http://localhost/pastry-shop/
2. Done it is automatic create database and tables 


---

## 💻 Tech Stack
- **Backend**: Native PHP 7.4+
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 (Custom Variables), Vanilla JavaScript
- **Libraries**: Chart.js (for analytics)
