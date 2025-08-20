# Maternal Healthcare Tracker - Deployment Guide

## Overview
This is a complete Maternal Healthcare Management System built with PHP, MySQL, HTML/CSS/Bootstrap, and JavaScript. It supports three user roles: Pregnant Women, Doctors/ASHA Workers, and Administrators.

## System Requirements

### Software Requirements
- **Web Server**: Apache 2.4+ (XAMPP recommended for local development)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Extensions**: PDO, PDO_MySQL, OpenSSL (for password hashing)

### Hardware Requirements (Minimum)
- CPU: 2 cores
- RAM: 4GB
- Storage: 10GB free space
- Network: Stable internet connection for SMS/Email services

## Installation Instructions

### Option 1: XAMPP Local Setup (Recommended for Testing)

1. **Download and Install XAMPP**
   ```
   Download XAMPP from: https://www.apachefriends.org/
   Install with Apache, MySQL, PHP, and phpMyAdmin
   ```

2. **Setup Project Directory**
   ```
   Copy the 'maternal_healthcare_tracker' folder to:
   C:/xampp/htdocs/maternal_healthcare_tracker
   ```

3. **Database Setup**
   - Start Apache and MySQL from XAMPP Control Panel
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema:
     - Create new database named 'maternal_healthcare'
     - Import the file: `database/schema.sql`
   - Or run the SQL file directly in phpMyAdmin

4. **Configuration**
   - Edit `config/database.php` if needed (default settings should work with XAMPP)
   - Ensure the following settings:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'maternal_healthcare');
     ```

5. **Test the Installation**
   - Navigate to: http://localhost/maternal_healthcare_tracker
   - You should see the login page

### Option 2: Production Server Setup

1. **Server Requirements**
   - Ubuntu 20.04+ or CentOS 7+
   - Apache/Nginx web server
   - MySQL/MariaDB database server
   - PHP 7.4+ with required extensions

2. **Installation Steps**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install LAMP stack
   sudo apt install apache2 mysql-server php php-mysql php-curl php-json php-mbstring -y
   
   # Enable Apache modules
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   
   # Create database and user
   sudo mysql
   CREATE DATABASE maternal_healthcare;
   CREATE USER 'mhc_user'@'localhost' IDENTIFIED BY 'secure_password_here';
   GRANT ALL PRIVILEGES ON maternal_healthcare.* TO 'mhc_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   # Upload and configure application files
   # Update config/database.php with production credentials
   ```

## Default User Accounts

After importing the database, you can log in with these default accounts:

### Administrator Account
- **Username**: admin
- **Password**: admin123
- **Email**: admin@maternalhealth.com

### Doctor/ASHA Worker Account
- **Username**: dr_sharma
- **Password**: doctor123
- **Email**: dr.sharma@hospital.com

### Test Patient Account
You can register a new patient account through the registration page.

## Features Included

### For Pregnant Women:
- ✅ User registration with pregnancy details
- ✅ Personal dashboard with pregnancy timeline
- ✅ Trimester-based nutrition tips
- ✅ Emergency contacts with ambulance call feature
- ✅ ANC visit history and reminders
- ✅ Risk level monitoring
- ✅ Progress tracking with EDD countdown

### For Doctors/ASHA Workers:
- ✅ Patient management interface
- ✅ ANC visit recording forms
- ✅ Health parameter tracking (BP, Hb, weight, etc.)
- ✅ Risk detection and alert system
- ✅ Patient assignment management
- ✅ Visit history and analytics

### For Administrators:
- ✅ Comprehensive analytics dashboard
- ✅ User management system
- ✅ Report generation capabilities
- ✅ Risk alert monitoring
- ✅ System statistics and trends
- ✅ Doctor-patient assignment management

### Backend Features:
- ✅ Secure authentication system
- ✅ Role-based access control
- ✅ Automated risk detection triggers
- ✅ ANC reminder system (email/SMS ready)
- ✅ RESTful API for data management
- ✅ Comprehensive logging system

## API Endpoints

### Authentication
- `POST /backend/auth/auth.php` - Login/Register
- `GET /logout.php` - Logout

### Visits Management
- `GET /backend/api/visits.php` - Get visits
- `POST /backend/api/visits.php` - Create visit
- `PUT /backend/api/visits.php` - Update visit
- `DELETE /backend/api/visits.php` - Delete visit

### Alerts Management
- `POST /backend/api/resolve_alert.php` - Resolve risk alert

## Automated Reminders Setup

### Cron Job Configuration
Add the following to your crontab to send daily reminders:

```bash
# Edit crontab
crontab -e

# Add this line to run daily at 9 AM
0 9 * * * /usr/bin/php /path/to/maternal_healthcare_tracker/backend/cron/send_reminders.php
```

### Email Configuration
For production use, integrate with a proper email service:
- **Recommended**: PHPMailer with SMTP
- **Services**: Gmail SMTP, SendGrid, Mailgun, AWS SES

### SMS Configuration
For SMS functionality, integrate with:
- **Twilio** (recommended)
- **AWS SNS**
- **Vonage (Nexmo)**
- Local SMS gateway providers

## Security Considerations

### Production Security Checklist:
- [ ] Change all default passwords
- [ ] Use HTTPS (SSL certificate)
- [ ] Enable PHP security configurations
- [ ] Configure proper file permissions (644 for files, 755 for directories)
- [ ] Set up regular database backups
- [ ] Enable error logging (disable display_errors)
- [ ] Configure firewall rules
- [ ] Use environment variables for sensitive data

### File Permissions:
```bash
# Set proper permissions
chmod -R 644 /path/to/maternal_healthcare_tracker
chmod -R 755 /path/to/maternal_healthcare_tracker/*/
chmod 600 config/database.php
chmod 755 backend/cron/send_reminders.php
```

## Database Backup

### Manual Backup:
```bash
mysqldump -u username -p maternal_healthcare > backup_$(date +%Y%m%d).sql
```

### Automated Backup (Cron):
```bash
# Add to crontab for daily backup at 2 AM
0 2 * * * mysqldump -u username -p maternal_healthcare > /backups/mhc_$(date +\%Y\%m\%d).sql
```

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable
   - Clear browser cookies

3. **File Permission Errors**
   - Set proper file permissions (see Security section)
   - Ensure web server can read/write necessary files

4. **Email/SMS Not Working**
   - Check configuration in `backend/cron/send_reminders.php`
   - Verify SMTP/SMS service credentials
   - Check server firewall settings

## Maintenance

### Regular Tasks:
- Monitor system logs: `logs/reminder_log.txt`
- Review and resolve pending risk alerts
- Update nutrition tips and emergency contacts
- Monitor system performance and disk space
- Regular security updates for PHP/MySQL/Apache

### Log Files:
- Reminder logs: `logs/reminder_log.txt`
- Apache logs: `/var/log/apache2/` (Linux) or `xampp/apache/logs/` (XAMPP)
- MySQL logs: `/var/log/mysql/` (Linux) or `xampp/mysql/logs/` (XAMPP)

## Support and Documentation

### File Structure:
```
maternal_healthcare_tracker/
├── index.php (Login page)
├── register.php (Patient registration)
├── patient_dashboard.php (Patient interface)
├── doctor_dashboard.php (Doctor interface)
├── admin_dashboard.php (Admin interface)
├── logout.php
├── config/
│   └── database.php (Database configuration)
├── backend/
│   ├── auth/ (Authentication system)
│   ├── api/ (RESTful APIs)
│   └── cron/ (Scheduled tasks)
├── frontend/
│   ├── css/ (Stylesheets)
│   ├── js/ (JavaScript files)
│   └── images/
├── database/
│   └── schema.sql (Database structure)
├── logs/ (System logs)
└── DEPLOYMENT_GUIDE.md (This file)
```

### Database Schema:
- **users**: User accounts and authentication
- **pregnancies**: Pregnancy records and details
- **visits**: ANC visit records and health data
- **reminders**: Automated reminder system
- **risk_alerts**: Risk detection and alerts
- **nutrition_tips**: Trimester-based nutrition advice
- **emergency_contacts**: Emergency service information

## Performance Optimization

### Database Optimization:
- Regular index maintenance
- Query optimization
- Database cleanup for old records

### Web Server Optimization:
- Enable gzip compression
- Configure browser caching
- Optimize images and static assets
- Use CDN for static resources (production)

## License and Credits

This Maternal Healthcare Tracker system is designed for healthcare organizations to improve maternal care management. It follows WHO guidelines for ANC visits and pregnancy monitoring.

**Important**: This system is for educational and development purposes. For production use in healthcare settings, ensure compliance with local healthcare regulations, data protection laws (GDPR, HIPAA), and security standards.

---

For technical support or customization requests, refer to the source code documentation and API endpoints listed above.
