# 🎬 CineBook - Cinema Booking System

### FPT Aptech

**Tech Stack**: Laravel 12, PHP 8.2, MySQL 8, Blade, JavaScript, CSS3

## 👥 Team Members

- TRẦN LÊ MINH ANH – Student1686151 - Team Leader
- ĐINH LÊ HOÀNG CHÂU – Student1685484 - Team Member
- TĂNG HUỲNH TUẤN TÚ – Student1685504 - Team Member


## 📖 Project Overview

**CineBook** is a modern cinema ticket booking platform that allows users to:

- Browse lists of **Now Showing** and **Upcoming** movies  
- View detailed information for each movie  
- Select showtimes, rooms, and seats  
- Make online payments and receive e-tickets in the form of QR codes  

The system also provides a dedicated **Admin Panel** to manage the entire cinema operation, including movies, screening rooms, showtimes, tickets, and users.

## 📂 Documentation
The following resources are included in the DOCUMENT folder:
- File Review
- Installation Guide: Full instructions for setting up the project
- Powerpoint File for Presentation
- Video Demonstration: [https://youtu.be/eqw89cXJm-U?si=MSJvpBmaf4UA3a0_](https://www.youtube.com/watch?v=G6epcu_tSh4)

### ✨ Key Features

#### For Customers

- Browse currently showing and upcoming movies  
- View showtimes by date, time, and screening room  
- Choose seats via an interactive seat map with real-time status  
- Use a 10‑minute countdown timer for each booking session  
- Make online payments (simulated gateways such as VNPay or MoMo)  
- Automatically receive QR codes for each seat or seat pair  
- Rate and review movies  
- Manage booking history in a personal profile  

#### For Admin

- Full movie management: create, update, delete movie information  
- Screening room and seat layout configuration  
- Showtimes management by date, time, room, and movie  
- Booking management and overview of ticket sales  
- QR code scanning for check‑in at the cinema  
- Review and comment moderation  
- User account management  

## 🏗 Project Components

- **Backend**: Laravel 12 (RESTful, MVC, Eloquent ORM)
- **Frontend**: Blade templates with JavaScript and CSS3
- **Database**: MySQL (schema and sample data stored in the `mySQL/` folder)
- **QR Code System**: Generates and manages QR codes for each ticket  

## 🚀 Installation & Usage Guide

### 1. Requirements

To run CineBook locally, you should have:

- PHP version 8.2 or higher
- Composer for managing PHP dependencies
- MySQL 8.0 or higher for the database
- XAMPP or a similar environment to run the web server and database  

### 2. Setup Overview

The typical setup flow is:

1. Clone the project repository to your local machine
2. Create an environment configuration file from the provided example and update your database and app settings
3. Install PHP dependencies using Composer
4. Create a MySQL database named `cinebook` and import:
   - The database schema file
   - The sample data file
5. Create the storage link so that uploaded files are accessible publicly
6. Start the Laravel development server or use XAMPP
7. Access the application through your browser

(Concrete commands and configuration details are available inside the original project; this README keeps the instructions at a descriptive level and deliberately does not include code blocks.)

## 🔑 Default Accounts

The system includes sample accounts for testing:

### Admin Account

- Email: `admin@cinebook.com`  
- Password: `123456`  

### User Account

- Email: `user1@cinebook.com`  
- Password: `123456`  

You can log in with these accounts to explore both the customer and admin experiences.

## 📂 Folder Structure

The project is organized following standard Laravel conventions, for example:

- **app/**: application logic, including controllers and models  
- **database/**: migrations and seeders for the database  
- **mySQL/**: SQL files for database schema and sample data (such as `mySQL.sql` and `data.sql`)  
- **public/**: compiled CSS, JavaScript utilities, and public images  
- **resources/**: Blade views, source CSS, and JavaScript  
- **routes/**: application routes definition  

You can refer to additional documentation files inside the repository for more in‑depth configuration and extension details.

## 🎯 Core Functionality

### Booking Flow

1. The user selects a movie from the “Now Showing” or “Upcoming” list  
2. The user chooses a showtime based on date, time, and screening room  
3. The user selects seats using an interactive seat map with visual seat states  
4. Once the booking is confirmed, a 10‑minute countdown timer starts  
5. The user completes payment using the configured payment method  
6. On successful payment, the system generates and displays QR codes for the booked seats  

### QR Code System

- Each single seat receives one unique QR code  
- Couple seats or seat pairs can share a single QR code  
- QR codes are used at the cinema entrance for check‑in  
- Ticket status can move between states such as “active”, “checked”, and “cancelled”  

### Countdown Timer

- The countdown starts when the user confirms the booking  
- Each booking session has a duration of 10 minutes  
- The remaining time is preserved so it is not lost when navigating between pages  
- When less than one minute remains, the interface can highlight the warning  
- If time runs out before payment, unpaid bookings are automatically cancelled  

## 🛠 Technologies Used

### Backend

- **Laravel 12** as the main PHP framework  
- **MySQL** as the relational database for movies, showtimes, rooms, tickets, and users  
- A **QR code generation library** integrated into Laravel for ticket QR codes  

### Frontend

- **Blade Templates** as the main templating engine
- **JavaScript** for interactive features such as seat selection, countdown timers, and dynamic UI
- **CSS3** for layout, styling, and visual effects  

## 🐛 Troubleshooting

Common issues and how to approach them:

- Environment configuration problems (for example, missing or incorrect `.env` values such as APP_URL or database settings)
- Database not created or SQL files not imported
- MySQL or XAMPP services not started

When you encounter issues:

1. Double‑check the `.env` file, especially database credentials and application URL
2. Verify that the MySQL service is running and that the `cinebook` database exists
3. Confirm that all PHP dependencies are installed via Composer
4. Restart the web server as needed  

## 🤝 Contributing

Contributions are welcome.  
If you would like to add new features, improve performance, or fix bugs:

1. Create a new feature branch  
2. Make and test your changes  
3. Submit a pull request to the repository  

## 📝 License

This project is open‑source software released under the **MIT License**.  
You are free to use, modify, and distribute it within the terms of the license.

## 📧 Contact

For questions, feedback, or support:

- Open an **issue** directly on the GitHub repository: `cadaik01/cinebook`  
- Or use the contact information available on the author’s GitHub profile  

---

**Built with ❤️ using Laravel for CineBook**
