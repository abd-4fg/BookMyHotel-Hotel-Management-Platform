# BookMyHotel - Hotel Booking Enterprise System

A comprehensive hotel booking platform built as an enterprise system for BookMyHotel.com, a Dubai-based company providing reservations for hotels across Asia and Europe.

---

## Table of Contents

- [Introduction](#introduction)
- [Technologies Used](#technologies-used)
- [Features](#features)
  - [User Authentication](#1-user-authentication)
  - [Hotel Browsing & Search](#2-hotel-browsing--search)
  - [Booking Management](#3-booking-management)
  - [Payment & Promotions](#4-payment--promotions)
  - [Guest Reviews](#5-guest-reviews)
  - [Admin Dashboard](#6-admin-dashboard)
  - [Staff Dashboard](#7-staff-dashboard)
  - [Maintenance Dashboard](#8-maintenance-dashboard)
  - [Notifications](#9-notifications)
  - [Multi-Language Support](#10-multi-language-support)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Database Schema](#database-schema)
- [Default Credentials](#default-credentials)
- [Project Structure](#project-structure)
- [License](#license)

---

## Introduction

BookMyHotel is an enterprise-level hotel booking platform that consolidates reservations from multiple hotels (Marriott, Hilton, Hyatt, and Four Seasons) across Asian and European markets. The system handles approximately 300 room-night reservations daily and provides a complete solution for customers, hotel staff, administrators, and maintenance personnel.

The system uses PHP and MySQL following the **Layered Architecture** pattern with **TOGAF** framework methodology. There are four types of users: customers, hotel staff, administrators, and maintenance staff. Each user type has different access levels and dashboards to ensure proper separation of duties.

---

## Technologies Used

The following technologies have been used in the development of BookMyHotel:

| Category | Technology |
|----------|------------|
| **Frontend** | HTML, CSS, JavaScript |
| **Backend** | PHP |
| **Database** | MySQL (phpMyAdmin) |
| **Server** | XAMPP (Apache) |
| **Icons** | Font Awesome |
| **Animations** | Animate.css |

---

## Features

### 1. User Authentication

The system manages user access through a secure login process with separate login pages for each role:

- **Customer Login** → `/login.php`
- **Admin Login** → `/admin-login.php`
- **Staff Login** → `/staff-login.php`
- **Maintenance Login** → `/maintenance-login.php`

Passwords are protected using bcrypt hashing for customer accounts. Admin and staff credentials use plain text for development simplicity.

### 2. Hotel Browsing & Search

- Browse four premium hotels (Marriott, Hilton, Hyatt, Four Seasons)
- View hotel descriptions, facilities, star ratings, and location
- Display hotel services (bar, restaurant, car rental, spa, local tours)
- Search rooms by date range, price, services, and room type
- Real-time availability calendar showing daily room availability
- Room details with bedding, bathroom, internet, and entertainment amenities

### 3. Booking Management

- Book rooms with check-in/check-out date selection
- Guest count selection with dynamic pricing (extra 50% surcharge per guest beyond 2)
- Real-time price calculation and breakdown
- View past and future bookings
- Cancel bookings (48 hours before check-in)
- Instant booking confirmation with notifications

### 4. Payment & Promotions

- Multiple secure payment method illustrations (Credit Card, Debit Card, PayPal, Apple Pay, Google Pay, Bank Transfer)
- Promotion code system with discount percentages
- Staff/Admin can generate and manage promotion codes
- Refund processing for cancelled bookings
- Payment status tracking (Pending, Paid, Refunded)

### 5. Guest Reviews

- Submit reviews with ratings (1-5 stars)
- Reviews only allowed for customers with confirmed bookings
- Aggregate review scores displayed on hotel pages
- Review moderation and management

### 6. Admin Dashboard

- Comprehensive analytics with Chart.js graphs:
  - Daily booking trend chart
  - Hotel performance chart
  - Popular room types chart
  - Monthly revenue trend chart
- Date-range filtering for analytics
- Room availability management by date
- Hotel management (Add, Edit, Delete hotels)
- Room management per hotel
- Customer account management
- Maintenance ticket creation and completion
- Customer ticket management
- Promotion code generation
- Booking confirmation, cancellation, and refund processing

### 7. Staff Dashboard

- Room availability management by date
- Customer management
- Customer ticket management
- Promotion code generation
- Pending booking confirmation
- Maintenance ticket creation
- Recent customer bookings view with search

### 8. Maintenance Dashboard

- View all maintenance tickets
- Update ticket status (In Progress, Completed)
- Add resolution notes
- Ticket statistics (Pending, In Progress, Completed, Total)

### 9. Notifications

- Real-time notification system with bell icon
- Notifications for:
  - Booking confirmations
  - Booking cancellations
  - Refund processing
  - New bookings (staff notification)
  - Maintenance ticket creation
  - Maintenance ticket completion
- Unread count badge on notification icon
- Mark notifications as read
- Full notifications page

### 10. Multi-Language Support

- English and Spanish language support
- Language persistence across all pages
- Complete translation of all text content
- Language switcher in header

---

## Getting Started

Follow these instructions to get a copy of the BookMyHotel project up and running on your local machine for development and testing purposes.

### Prerequisites

Before you proceed, ensure you have the following software installed:

- **XAMPP** (Version 7.4 or higher)
- **PHP** (Version 7.4 or higher)
- **MySQL** (Version 5.7 or higher)
- **Web Browser** (Chrome, Firefox, etc.)

### Installation

#### 1. Start XAMPP Services

```bash
Launch XAMPP Control Panel and start Apache and MySQL services
>Download and install XAMPP if not installed: https://www.apachefriends.org/
```

#### 2. Clone or copy the project to your local machine

```bash
Copy the project folder to C:\xampp\htdocs\bookmyhotel (Windows)
or /var/www/html/bookmyhotel (Linux/Mac)
```

#### 3. Create the database

```bash
Open phpMyAdmin at http://localhost/phpmyadmin
Create a new database named "bookmyhotel"
Select utf8_general_ci as collation
```

#### 4. Import the database schema

```bash
Click on the "bookmyhotel" database
Go to the Import tab
Select the "bookmyhotel.sql" file from the project in /database folder.
Click Go to execute the import
```

#### 5. Access the application

```bash
Open your browser and navigate to http://localhost/bookmyhotel/
```

BookMyHotel should now be up and running.

---

## Database Schema

The system includes the following key database tables:

| Table | Description |
|-------|-------------|
| **users** | User accounts with roles (customer, admin, hotel_staff, maintenance_staff) |
| **hotels** | Hotel master list with descriptions, locations, and facilities |
| **rooms** | Room types with pricing, occupancy, and availability per hotel |
| **services** | Hotel services (bar, restaurant, spa, car rental, etc.) |
| **bookings** | Reservation records with dates, guest count, and payment status |
| **reviews** | Guest reviews and ratings for hotels |
| **promotions** | Discount codes with percentages and validity periods |
| **maintenance_tickets** | Maintenance requests with status and resolution notes |
| **customer_tickets** | Customer support tickets from contact form |
| **notifications** | System notifications for all users |
| **room_availability_log** | Daily room availability tracking by date |

---

## Default Credentials

After installation, you can log in using the following demo accounts:

| Role | Email | Password | Login URL |
|------|-------|----------|-----------|
| **Admin** | `admin@bookmyhotel.com` | `admin123` | `/admin-login.php` |
| **Hotel Staff** | `staff@bookmyhotel.com` | `staff123` | `/staff-login.php` |
| **Maintenance Staff** | `maintenance@bookmyhotel.com` | `maintenance123` | `/maintenance-login.php` |
| **Customer** | `customer@bookmyhotel.com` | `customer123` | `/login.php` |

---

## Project Structure

```
bookmyhotel/
├── api/
│   ├── get_bookings.php
│   ├── get_hotels.php
│   ├── get_room_availability.php
│   ├── get_rooms.php
│   └── make_booking.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── config/
│   └── database.php
├── database/
│   └── bookmyhotel.sql
├── includes/
│   ├── footer.php
│   └── header.php
├── admin-dashboard.php
├── admin-hotels.php
├── admin-login.php
├── admin-rooms.php
├── booking.php
├── cancel-booking.php
├── contact.php
├── customer-tickets.php
├── dashboard.php
├── hotel-detail.php
├── hotels.php
├── index.php
├── login.php
├── logout.php
├── maintenance-dashboard.php
├── maintenance-login.php
├── manage-customers.php
├── my-bookings.php
├── notifications.php
├── profile.php
├── promotions.php
├── register.php
├── search.php
├── staff-dashboard.php
├── staff-login.php
├── submit-review.php
└── README.md
```

---

## License

This BookMyHotel project is distributed under the **MIT License**. You can find the full text of the license in the `LICENSE` file.
