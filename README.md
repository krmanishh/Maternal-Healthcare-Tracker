# 🤱 Maternal Healthcare Tracker

A comprehensive web-based maternal healthcare management system designed to improve pregnancy care and monitoring. Built with PHP, MySQL, Bootstrap, and JavaScript.

![License](https://img.shields.io/badge/License-Educational-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479a1)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-7952b3)

## 🌟 Features

### For Pregnant Women
- **👩‍⚕️ Easy Registration**: Simple registration with pregnancy details
- **📊 Personal Dashboard**: Comprehensive pregnancy timeline and progress tracking
- **🍎 Nutrition Guidance**: Trimester-specific nutrition tips and recommendations
- **🚨 Emergency Support**: Quick access to emergency contacts and ambulance services
- **📅 Visit Reminders**: Automated ANC visit reminders via email/SMS
- **⚡ Risk Monitoring**: Real-time risk level assessment and alerts

### For Doctors/ASHA Workers
- **👥 Patient Management**: Complete patient list with risk categorization
- **📝 Visit Recording**: Detailed ANC visit forms with health parameters
- **🔍 Health Tracking**: Monitor BP, Hemoglobin, weight, and other vital signs
- **⚠️ Risk Detection**: Automated alerts for high-risk conditions
- **📈 Analytics**: Patient progress tracking and visit history
- **👨‍⚕️ Assignment Management**: Manage assigned patients efficiently

### For Administrators
- **📊 Analytics Dashboard**: Comprehensive system statistics and trends
- **👤 User Management**: Manage all system users and roles
- **📋 Report Generation**: Excel/PDF export capabilities
- **🚨 Alert Monitoring**: System-wide risk alert management
- **📈 Compliance Tracking**: ANC compliance and outcome monitoring
- **⚙️ System Management**: Overall system health and configuration

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+, MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5.1, JavaScript
- **Charts**: Chart.js for analytics visualization
- **Icons**: Font Awesome 6.0
- **Architecture**: MVC pattern with RESTful APIs

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP) or similar LAMP/WAMP stack
- Web browser (Chrome, Firefox, Safari, Edge)

### Installation

1. **Download and Install XAMPP**
   ```
   Visit: https://www.apachefriends.org/
   Download and install XAMPP with Apache, MySQL, and PHP
   ```

2. **Setup the Application**
   - Download/clone this repository
   - Copy the `maternal_healthcare_tracker` folder to `C:/xampp/htdocs/`
   - Or run the included `setup.bat` script (Windows only)

3. **Database Setup**
   - Start Apache and MySQL from XAMPP Control Panel
   - Open http://localhost/phpmyadmin
   - Create database: `maternal_healthcare`
   - Import: `database/schema.sql`

4. **Access the Application**
   ```
   Open: http://localhost/maternal_healthcare_tracker
   ```


## 📁 Project Structure

```
maternal_healthcare_tracker/
├── 🏠 index.php                 # Login page
├── 📝 register.php              # Patient registration
├── 👩‍⚕️ patient_dashboard.php     # Patient interface
├── 👨‍⚕️ doctor_dashboard.php      # Doctor interface  
├── ⚙️ admin_dashboard.php        # Admin interface
├── 🔐 logout.php                # Logout handler
├── 📊 config/                   # Configuration files
│   └── database.php             # Database settings
├── 🔧 backend/                  # Server-side logic
│   ├── auth/                    # Authentication system
│   ├── api/                     # RESTful API endpoints
│   └── cron/                    # Scheduled tasks
├── 🎨 frontend/                 # Client-side assets
│   ├── css/                     # Stylesheets
│   ├── js/                      # JavaScript files
│   └── images/                  # Images and icons
├── 🗄️ database/                 # Database files
│   └── schema.sql               # Database structure
├── 📋 logs/                     # System logs
├── 📖 DEPLOYMENT_GUIDE.md       # Detailed setup guide
└── 🚀 setup.bat                 # Quick setup script
```

## 🔧 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/backend/auth/auth.php` | User authentication |
| GET | `/backend/api/visits.php` | Retrieve visit records |
| POST | `/backend/api/visits.php` | Create new visit |
| PUT | `/backend/api/visits.php` | Update visit record |
| DELETE | `/backend/api/visits.php` | Delete visit record |
| POST | `/backend/api/resolve_alert.php` | Resolve risk alerts |

## 📊 Database Schema

| Table | Purpose |
|-------|---------|
| `users` | User accounts and authentication |
| `pregnancies` | Pregnancy records and details |
| `visits` | ANC visit records and health data |
| `reminders` | Automated reminder system |
| `risk_alerts` | Risk detection and alerts |
| `nutrition_tips` | Trimester-based nutrition advice |
| `emergency_contacts` | Emergency service information |

## 🔄 Automated Features

### Risk Detection Engine
- Automatic monitoring of health parameters
- Real-time alert generation for high-risk conditions
- Trigger-based risk level updates

### Reminder System
- Scheduled ANC visit reminders
- Email and SMS notifications (configurable)
- WHO guideline-based visit scheduling

### Analytics & Reporting
- Real-time dashboard updates
- Automated report generation
- Compliance tracking and monitoring

## 🛡️ Security Features

- **Password Hashing**: Secure password storage with PHP's password_hash()
- **Role-Based Access**: Three-tier access control system
- **Session Management**: Secure session handling
- **Input Validation**: SQL injection and XSS prevention
- **CSRF Protection**: Form token validation

## 🎯 WHO Compliance

This system follows World Health Organization (WHO) guidelines for:
- ✅ ANC visit scheduling (8 visits minimum)
- ✅ Risk assessment protocols
- ✅ Maternal nutrition recommendations
- ✅ Emergency care pathways
- ✅ Data collection standards

## 📈 Scalability

The system is designed to handle:
- **Users**: 1,000+ concurrent users
- **Data**: Millions of visit records
- **Performance**: Optimized database queries
- **Deployment**: Cloud-ready architecture

## 🔮 Future Enhancements

- [ ] Mobile app integration (iOS/Android)
- [ ] Telemedicine consultation features
- [ ] AI/ML-based risk prediction
- [ ] Multi-language support
- [ ] Integration with wearable devices
- [ ] Advanced analytics and reporting
- [ ] Blockchain-based health records

## 🤝 Contributing

This is an educational project. For healthcare implementation:

1. Ensure compliance with local healthcare regulations
2. Implement proper data encryption
3. Add audit logging capabilities
4. Conduct security penetration testing
5. Obtain necessary healthcare certifications

## 📄 License

This project is created for educational purposes. For production use in healthcare settings, ensure compliance with:
- Local healthcare regulations (FDA, CE marking, etc.)
- Data protection laws (GDPR, HIPAA, etc.)
- Security standards (ISO 27001, NIST, etc.)

## 🆘 Support

### Documentation
- 📖 [Deployment Guide](DEPLOYMENT_GUIDE.md) - Complete setup instructions
- 📝 [API Documentation](backend/api/) - RESTful API details
- 🗄️ [Database Schema](database/schema.sql) - Database structure

### Common Issues
1. **Database Connection**: Check credentials in `config/database.php`
2. **File Permissions**: Ensure proper read/write permissions
3. **XAMPP Issues**: Verify Apache and MySQL are running
4. **Browser Compatibility**: Use modern browsers (Chrome, Firefox, Safari, Edge)

### System Requirements
- **OS**: Windows 10+, macOS 10.14+, Ubuntu 18.04+
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 10GB free space
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

**⚠️ Important Notice**: This system is designed for educational and development purposes. For production deployment in healthcare environments, please ensure compliance with all applicable healthcare regulations, data protection laws, and security standards in your jurisdiction.

**Made with ❤️ for better maternal healthcare worldwide**
