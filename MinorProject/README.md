# IIPS Timetable Management System

A comprehensive system for managing college timetables, allowing administrators to create, manage, and publish timetables.

## Features

### Admin Panel

- **Timetable Creation**: Create new timetables with a visual grid interface
  - Add/remove time slots and days
  - Drag and drop interface for adding subjects
  - Conflict detection and resolution
  - Save and publish timetables
  
- **User Management**: Manage faculty and student accounts
  
- **Course Management**: Define courses, semesters, subjects, and faculty assignments

### User Dashboard

- View published timetables
- Search and filter timetables by course, semester, faculty, etc.
- Download timetables in different formats (PDF, Word, CSV)

## Getting Started

1. Run the initialization script to set up the database tables:
   ```
   http://localhost/IIPS_TTMS/init.php
   ```

2. Access the admin panel:
   ```
   http://localhost/IIPS_TTMS/admin-dashboard.php
   ```

3. Login with admin credentials (default: admin/admin)

4. Start creating timetables using the visual editor

## Implementation Details

- PHP backend with MySQL database
- No frameworks used, built with vanilla PHP/JS/CSS
- Responsive design for all devices
- AJAX for asynchronous data loading