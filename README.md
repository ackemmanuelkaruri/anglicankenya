# Anglican Church of Kenya - Church Management System

A comprehensive church management system for the Anglican Church of Kenya, managing hierarchical church structure from provinces down to parishes, including member management, ministries, families, giving, attendance, and sacrament records.

## Table of Contents
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Default Users](#default-users)
- [Project Structure](#project-structure)
- [Tech Stack](#tech-stack)

## Features

- **Multi-level Church Hierarchy**: Province → Diocese → Archdeaconry → Deanery → Parish
- **User Management**: Role-based access control (Super Admin, Diocese Admin, Archdeaconry Admin, Deanery Admin, Parish Admin, Member)
- **Family Management**: Track families and their members including dependents
- **Ministry Management**: Organize church ministries and their members
- **Sacrament Records**: Track baptism and confirmation records
- **Giving Management**: Record and track tithes and offerings (Cash, Mpesa, PayPal, Card)
- **Attendance Tracking**: Monitor service attendance
- **Audit Logging**: Comprehensive audit trail with database triggers
- **Authentication**: Secure login with Argon2id password hashing
- **Theme Support**: Light/Dark theme preferences per user

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.0
- **MySQL** >= 8.0 or **MariaDB** >= 10.5
- **Composer** (PHP dependency manager)
- **Apache** or **Nginx** web server (or PHP built-in server for development)
- **Git**

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/ackemmanuelkaruri/anglicankenya.git
cd anglicankenya
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Set Up Environment Variables

Create a `.env` file in the root directory:

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=anglicankenya
DB_USER=your_username
DB_PASSWORD=your_password

APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## Database Setup

### 1. Create Database

Log into MySQL and create the database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE anglicankenya CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
EXIT;
```

### 2. Import Database Schema

Import the provided SQL dump file:

```bash
mysql -u your_username -p anglicankenya < anglicankenya.sql
```

Or if using phpMyAdmin:
1. Open phpMyAdmin
2. Select the `anglicankenya` database
3. Click on "Import" tab
4. Choose the `anglicankenya.sql` file
5. Click "Go"

### 3. Verify Migration System

The database includes a migration tracking system. Check that migrations have been applied:

```sql
SELECT * FROM migrations;
```

You should see 6 migrations already executed.

## Configuration

### Database Triggers

The system includes automated audit triggers on the `users` table that log all INSERT, UPDATE, and DELETE operations. These are already set up in the SQL dump.

### Web Server Configuration

#### Option A: PHP Built-in Server (Quick Start)

```bash
php -S localhost:8000 -t public
```

Then visit: `http://localhost:8000`

#### Option B: Apache

Create virtual host configuration in `/etc/apache2/sites-available/anglican.conf`:

```apache
<VirtualHost *:80>
    ServerName anglican.local
    DocumentRoot /path/to/anglican-kenya-cms/public
    
    <Directory /path/to/anglican-kenya-cms/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/anglican_error.log
    CustomLog ${APACHE_LOG_DIR}/anglican_access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite anglican.conf
sudo systemctl reload apache2
```

Add to `/etc/hosts`:
```
127.0.0.1 anglican.local
```

#### Option C: Nginx

Create configuration in `/etc/nginx/sites-available/anglican`:

```nginx
server {
    listen 80;
    server_name anglican.local;
    root /path/to/anglican-kenya-cms/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/anglican /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

## Running the Application

### Development Mode

```bash
# Using PHP built-in server
php -S localhost:8000 -t public

# Or if you have a start script
./start.sh
```

### Production Mode

Ensure your web server (Apache/Nginx) is configured and running, then access the application via your configured domain.

## Default Users

The system comes with pre-seeded test users. Use these credentials to log in:

### Super Admin
- **Username**: `benique01`
- **Email**: `beniquecreations@gmail.com`
- **Password**: `password` (Change this immediately!)
- **Access**: Full system access

### Diocese Administrators
- **Nairobi Diocese**: `nairobi_admin` / `admin.nairobi@anglicankenya.org`
- **Mt Kenya South**: `mks_admin` / `admin.mks@anglicankenya.org`
- **Mombasa Diocese**: `mombasa_admin` / `admin.mombasa@anglicankenya.org`
- **Password**: `password` (for all test accounts)

### Archdeaconry Admin
- **Username**: `thimbigua_admin`
- **Password**: `password`

### Deanery Admin
- **Username**: `karuri_admin`
- **Password**: `password`

### Parish Admin
- **Username**: `parishadmin`
- **Password**: `password`

### Regular Members
- **Username**: `nairobi_member1` / `nairobi_member2`
- **Password**: `password`

**⚠️ IMPORTANT**: Change all default passwords immediately after first login!

## Project Structure

```
anglican-kenya-cms/
├── public/              # Web root directory
│   ├── index.php       # Entry point
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── assets/         # Images, fonts, etc.
├── src/                # Application source code
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Database models
│   ├── Views/          # HTML templates
│   └── Middleware/     # Authentication, etc.
├── config/             # Configuration files
│   └── database.php    # Database configuration
├── database/
│   └── migrations/     # SQL migration files
├── vendor/             # Composer dependencies
├── .env.example        # Environment template
├── composer.json       # PHP dependencies
└── README.md           # This file
```

## Tech Stack

- **Backend**: PHP 8.3+
- **Database**: MySQL 9.1 / MariaDB
- **Password Hashing**: Argon2id
- **Architecture**: MVC Pattern
- **Server**: Apache/Nginx or PHP Built-in Server

## Database Schema Overview

### Church Hierarchy
- `provinces` → Top level (e.g., "Anglican Church of Kenya")
- `dioceses` → 37 dioceses under provinces
- `archdeaconries` → Subdivisions of dioceses
- `deaneries` → Subdivisions of archdeaconries
- `parishes` → Local churches

### User Management
- `users` - Core user accounts with role-based access
- `user_details` - Extended user information
- `user_roles` - Role assignments
- `roles` - Role definitions
- `permissions` - Permission definitions
- `role_permissions` - Role-permission mappings

### Member Features
- `families` - Family units
- `family_members` - Family membership
- `dependents` - Children and dependents
- `ministries` - Church ministries
- `ministry_members` - Ministry participation
- `member_groups` - Service groups, cell groups, etc.

### Church Activities
- `sacrament_records` - Baptism and confirmation tracking
- `giving_transactions` - Tithes and offerings
- `attendance_records` - Service attendance
- `leadership_roles` - Leadership positions
- `events` - Church events

### System & Security
- `audit_log` - Automated audit trail
- `audit_logs` - Manual audit entries
- `login_attempts` - Login tracking for security
- `migrations` - Database version control

## Security Features

1. **Password Security**: Argon2id hashing algorithm
2. **Login Tracking**: All login attempts logged with IP addresses
3. **Audit Trail**: Database triggers automatically log all user changes
4. **Role-Based Access**: 6-tier permission system
5. **Email Verification**: Token-based email verification system
6. **Password Reset**: Secure password reset with expiring tokens

## Development Notes

### Running Migrations

If you need to run additional migrations:

```bash
php artisan migrate
# or your custom migration runner
```

### Checking Audit Logs

View audit trail:

```sql
SELECT * FROM audit_log ORDER BY change_timestamp DESC LIMIT 20;
```

### Adding New Diocese

```sql
INSERT INTO dioceses (province_id, diocese_name, bishop_name, headquarters)
VALUES (3, 'New Diocese Name', 'Bishop Name', 'Location');
```

## Troubleshooting

### Database Connection Issues

```bash
# Test database connection
mysql -u your_username -p anglicankenya -e "SELECT 1;"
```

### Permission Errors

```bash
# Fix file permissions
chmod -R 755 storage/ cache/
chmod -R 777 storage/logs/
```

### Port Already in Use

```bash
# Use a different port
php -S localhost:8080 -t public
```

## Support

For issues and questions:
- Email: ackemmanuelchurchkaruri@gmail.com
- GitHub Issues: https://github.com/ackemmanuelkaruri/anglicankenya/issues

## License

[Your License Here]

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request


# Anglican Church of Kenya - Church Management System

## Role-Based Access Control (RBAC) Matrix

### Roles
1. **Super Admin**: Full system access
2. **National Admin**: National-level operations
3. **Diocese Admin**: Diocese-level operations
4. **Archdeaconry Admin**: Archdeaconry-level operations
5. **Deanery Admin**: Deanery-level operations
6. **Parish Admin**: Parish-level operations
7. **Member**: Basic access

### Permissions Matrix

| Resource          | Super Admin | National Admin | Diocese Admin | Archdeaconry Admin | Deanery Admin | Parish Admin | Member |
|-------------------|-------------|----------------|---------------|--------------------|---------------|--------------|--------|
| View All Users    | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✓ (Deanery)   | ✓ (Parish)   | ✗      |
| Create User       | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✓ (Deanery)   | ✓ (Parish)   | ✗      |
| Edit User         | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✓ (Deanery)   | ✓ (Parish)   | ✓ (Self) |
| Delete User       | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✗             | ✗            | ✗      |
| Reset Password    | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✓ (Deanery)   | ✓ (Parish)   | ✗      |
| View Reports      | ✓           | ✓              | ✓ (Diocese)   | ✓ (Archdeaconry)   | ✓ (Deanery)   | ✓ (Parish)   | ✗      |
| System Settings   | ✓           | ✗              | ✗             | ✗                  | ✗             | ✗            | ✗      |
| Impersonate       | ✓           | ✗              | ✗             | ✗                  | ✗             | ✗            | ✗      |

### Scope Rules
- **Super Admin**: Can access all resources across the entire system
- **National Admin**: Can access resources at the national level and below
- **Diocese Admin**: Can access resources within their diocese and below
- **Archdeaconry Admin**: Can access resources within their archdeaconry and below
- **Deanery Admin**: Can access resources within their deanery and below
- **Parish Admin**: Can access resources within their parish only
- **Member**: Can only view and edit their own profile

### Notes
- "✓ (Diocese)" means the admin can only access resources within their diocese
- "✓ (Self)" means the member can only access their own profile
- Super Admins can impersonate other users for troubleshooting
- All actions are logged in the activity_log table