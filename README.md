# Campus Attendance System with Face Recognition

A web application that records student and lecturer attendance automatically using in-browser face recognition. It also includes a leave/sick request flow verified by lecturers, and schedule control by administrators.

> **Note:** This is a learning project built for coursework. All sample users (names, IDs, and emails) are fictional.

## Features

**Administrator**

- Manage lecturers, students, courses, and class schedules
- Open or close attendance for a schedule, with a manual override
- Upload face photos (single or bulk) for face recognition
- View the attendance recap

**Lecturer**

- View own teaching schedule and record own attendance
- Manage student attendance for a session and review attendance history
- Verify students' leave/sick requests

**Student**

- Check in with face recognition through the browser camera
- Submit leave/sick requests with supporting evidence
- View course details and personal attendance history

**System**

- Face matching runs in the browser with face-api.js; the match distance and verification status are stored with each attendance record
- Automatic marking of absences for sessions without attendance
- Passwords are hashed with `password_hash()` (bcrypt)

## Tech Stack

| Layer    | Technology                         |
| -------- | ---------------------------------- |
| Backend  | Native PHP (PDO)                   |
| Database | MySQL / MariaDB                    |
| Frontend | HTML, CSS, Bootstrap 5, JavaScript |
| AI       | face-api.js (TensorFlow.js)        |

## Getting Started

### Requirements

- XAMPP (or any stack with Apache, PHP 7.4+, and MySQL/MariaDB)
- A modern browser with camera access (browsers allow the camera on `localhost` or HTTPS)

### Installation

1. Clone the repository into your web root (for XAMPP, `htdocs`) under the folder name **`kampus-absensi`**, because the application uses `/kampus-absensi/public/` in its paths: `git clone https://github.com/zell-portfolio/campus-attendance.git kampus-absensi`. If you download the ZIP instead, rename the extracted folder to `kampus-absensi`.
2. Open phpMyAdmin and import `kampus_absensi_db.sql`. The file creates the `kampus_absensi_db` database and its tables.
3. Check the database settings in `config/database.php`. The defaults match a standard XAMPP install (`localhost`, user `root`, empty password). `.env.example` lists the same values together with the base URL.
4. Open `http://localhost/kampus-absensi/public/setup_folders.php` once to create the upload folders (`public/uploads/faces`, `videos`, `pdfs`).
5. Open `http://localhost/kampus-absensi/public/` and log in.

### Demo Accounts

All demo accounts share the same password.

| Role     | Email                            | Password   |
| -------- | -------------------------------- | ---------- |
| Admin    | `admin@kampus.com`               | `admin123` |
| Lecturer | `dosen01@kampus.com`             | `admin123` |
| Student  | `mahasiswa08@student.kampus.com` | `admin123` |

## Important Notes

1. **Face photos are not included.** The `public/uploads/` folder is excluded from the repository because face photos are personal data. To try face recognition, log in as Admin, open **Upload Wajah** (face upload), and upload a photo for the user you want to test.
2. **Bulk upload.** While logged in as Admin, open `http://localhost/kampus-absensi/public/upload_massal.php`. File names must start with the user ID, for example `MHS032 Student Name.jpg` or `D001 Lecturer Name.jpg`. After a bulk upload, open `public/batch_extract_descriptors.php` to extract the face descriptors from the new photos.
3. **AI models are bundled.** The `public/assets/models/` folder contains the face-api.js model files, so face detection loads them locally instead of downloading them.
4. **Attendance data is empty.** The SQL file ships with sample users and courses only. Record new attendance to see results in the attendance recap.

## Project Structure

```
kampus-absensi/
├── config/
│   └── database.php          # PDO database connection
├── public/                   # Web root: entry point, form handlers, assets
│   ├── index.php
│   ├── process_*.php         # Form and action handlers
│   └── assets/               # CSS, JavaScript, face-api.js models
├── views/                    # Pages grouped by role
│   ├── admin/
│   ├── dosen/                # Lecturer
│   ├── mahasiswa/            # Student
│   ├── auth/
│   └── layouts/
├── kampus_absensi_db.sql     # Database schema and sample data
└── .env.example
```

## Disclaimer

This project was built to learn web development and is not hardened for production. Before deploying it anywhere public, review input validation for file uploads, access control on the utility pages in `public/`, and error handling.
