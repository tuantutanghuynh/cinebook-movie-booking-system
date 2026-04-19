# CineBook - Cinema Booking System

### FPT Aptech

**Tech Stack**: Laravel 12, PHP 8.2, MySQL 8, React 18, Vite, Axios, Laravel Sanctum

## Team Members

- TRAN LE MINH ANH – Student1686151 - Team Leader
- DINH LE HOANG CHAU – Student1685484 - Team Member
- TANG HUYNH TUAN TU – Student1685504 - Team Member

## Project Overview

**CineBook** is a modern cinema ticket booking platform that allows users to:

- Browse lists of **Now Showing** and **Upcoming** movies
- View detailed information for each movie
- Select showtimes, rooms, and seats
- Make online payments and receive e-tickets in the form of QR codes

The system also provides a dedicated **Admin Panel** to manage the entire cinema operation, including movies, screening rooms, showtimes, tickets, and users.

## Documentation

The following resources are included in the DOCUMENT folder:

- File Review
- Installation Guide: Full instructions for setting up the project
- Powerpoint File for Presentation
- Video Demonstration: [Watch on YouTube](https://www.youtube.com/watch?v=G6epcu_tSh4)

## Key Features

### For Customers

- Browse currently showing and upcoming movies
- View showtimes by date, time, and screening room
- Choose seats via an interactive seat map with real-time status
- Use a 10-minute countdown timer for each booking session
- Make online payments (simulated gateways such as VNPay or MoMo)
- Automatically receive QR codes for each seat or seat pair
- Rate and review movies
- Manage booking history in a personal profile

### For Admin

- Full movie management: create, update, delete movie information
- Screening room and seat layout configuration
- Showtimes management by date, time, room, and movie
- Booking management and overview of ticket sales
- QR code scanning for check-in at the cinema
- Review and comment moderation
- User account management

## Project Components

- **Backend**: Laravel 12 (RESTful API, MVC, Eloquent ORM, Laravel Sanctum)
- **Frontend**: React 18 (SPA, React Router, Axios)
- **Database**: MySQL (schema and sample data stored in the `DOCUMENT/` folder)
- **QR Code System**: Generates and manages QR codes for each ticket

## Monorepo Structure

```
cinebook/
├── backend/        ← Laravel 12 API server (port 8000)
│   ├── app/
│   ├── routes/
│   ├── resources/
│   └── ...
├── frontend/       ← React 18 SPA (port 5173)
│   ├── src/
│   │   ├── pages/
│   │   ├── components/
│   │   ├── services/
│   │   └── hooks/
│   └── ...
└── guidance/       ← Learning documentation
```

## Installation & Usage Guide

### Requirements

To run CineBook locally, you should have:

- PHP 8.2 or higher
- Composer for managing PHP dependencies
- MySQL 8.0 or higher
- Node.js 18 or higher
- XAMPP or a similar local server environment

### Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend Setup

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Default Accounts

### Admin Account

- Email: `admin@cinebook.com`
- Password: `123456`

### User Account

- Email: `user1@gmail.com`
- Password: `12345678`

## Frontend Roadmap (React Migration)

The project is being migrated from Blade templates to a React SPA. Below is the current progress and plan:

### Completed

- [x] Monorepo restructure: `backend/` (Laravel) + `frontend/` (React)
- [x] Laravel Sanctum setup (token-based auth)
- [x] CORS configuration for React to Laravel communication
- [x] API endpoint: `GET /api/movies`
- [x] React app initialized with Vite + React Router
- [x] Axios instance with base configuration
- [x] `HomePage` — fetches and displays movie list from API
- [x] `LoginPage` — login form, stores token in localStorage
- [x] API endpoints: `POST /api/login`, `POST /api/logout`

### In Progress

- [ ] Axios interceptor — automatically attach token to every request
- [ ] Shared layout (Header + Footer)
- [ ] Protected Route — guard pages that require authentication

### Phase 2 — Core Pages

- [ ] `MovieDetailPage` — movie detail + reviews
- [ ] `NowShowingPage` — now showing movies with filter
- [ ] `UpcomingPage` — upcoming movies with filter
- [ ] `RegisterPage` — user registration

### Phase 3 — Booking Flow

- [ ] `ShowtimesPage` — select showtime
- [ ] `SeatMapPage` — interactive seat selection grid
- [ ] `BookingConfirmPage` — booking confirmation
- [ ] Countdown timer (10 minutes)
- [ ] `PaymentPage` — mock payment gateway
- [ ] `BookingSuccessPage` — display QR code tickets

### Phase 4 — User Profile

- [ ] `ProfilePage` — personal information
- [ ] `BookingHistoryPage` — booking history
- [ ] `ReviewsPage` — my reviews
- [ ] Change password

### Phase 5 — Admin Panel

- [ ] `AdminDashboard` — revenue and booking statistics
- [ ] Movie management (CRUD)
- [ ] Showtime management
- [ ] Room management + seat layout editor
- [ ] QR Check-in scanner
- [ ] Admin role-based route protection

## Core Functionality

### Booking Flow

1. The user selects a movie from the Now Showing or Upcoming list
2. The user chooses a showtime based on date, time, and screening room
3. The user selects seats using an interactive seat map with visual seat states
4. Once the booking is confirmed, a 10-minute countdown timer starts
5. The user completes payment using the configured payment method
6. On successful payment, the system generates and displays QR codes for the booked seats

### QR Code System

- Each single seat receives one unique QR code
- Couple seats or seat pairs can share a single QR code
- QR codes are used at the cinema entrance for check-in
- Ticket status can move between states: active, checked, and cancelled

### Countdown Timer

- The countdown starts when the user confirms the booking
- Each booking session has a duration of 10 minutes
- The remaining time is preserved when navigating between pages
- If time runs out before payment, unpaid bookings are automatically cancelled

## Technologies Used

### Backend

- **Laravel 12** as the main PHP framework
- **Laravel Sanctum** for token-based API authentication
- **MySQL** as the relational database
- **QR code generation library** integrated into Laravel

### Frontend

- **React 18** as the main UI framework (SPA)
- **React Router v6** for client-side routing
- **Axios** for HTTP communication with the Laravel API
- **Vite** for fast development and build tooling

## Troubleshooting

Common issues and how to approach them:

- CORS errors: make sure `frontend` origin is listed in `backend/config/cors.php`
- Token not sent: check Axios interceptor is configured in `frontend/src/services/api.js`
- Environment configuration problems: check `.env` values in both `backend/` and `frontend/`
- Database not created or SQL files not imported
- MySQL or XAMPP services not started

## Contributing

Contributions are welcome. If you would like to add new features, improve performance, or fix bugs:

1. Create a new feature branch
2. Make and test your changes
3. Submit a pull request to the repository

## License

This project is open-source software released under the **MIT License**.

## Contact

For questions, feedback, or support, open an issue directly on the GitHub repository.

---

Built with Laravel and React for CineBook
