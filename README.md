# Attendance Monitoring System

A web-based system for managing student attendance records efficiently and securely.

## Features

- **User Authentication**: Secure login system for administrators and students
- **Attendance Management**: Mark students as present, absent, or excused
- **Student Management**: Add, update, and manage student records
- **Appeal System**: Students can submit attendance appeals
- **Date-based Tracking**: Record and view attendance for specific dates
- **Individual Records**: Maintain separate attendance records for each student

## Setup Instructions

1. Clone the repository
2. Set up a PHP server (XAMPP, WAMP, or similar)
3. Place the project files in your web server directory
4. Create the following directory structure:
   ```
   assets/
   ├── data/
   │   ├── users.csv
   │   └── student_data/
   ```
5. Ensure write permissions for the data directory

## Usage

### Administrator
- Login with admin credentials
- Navigate to "Manage Attendance" to mark daily attendance
- Use "Manage Students" to handle student records
- Review and process appeals in "Manage Appeals"

### Students
- Login with student credentials
- View personal attendance records
- Submit appeals for attendance corrections

## Security

- Session-based authentication
- Input validation and sanitization
- Protected file access

## Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Modern web browser

## License

This project is licensed under the MIT License - see the LICENSE file for details.
