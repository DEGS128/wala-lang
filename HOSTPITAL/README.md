# HUMAN RESOURCE 4 Compensation & HR Intelligence
## Hospital System

A comprehensive Human Resource Management System built with PHP, HTML, CSS, JavaScript, and MySQL, designed specifically for hospital environments with a focus on compensation and HR intelligence.

## 🏥 System Overview

The HR4 Hospital System is a fully functional, modern HR management platform that provides comprehensive solutions for:

- **Core Human Capital Management (HCM)**
- **Payroll Management**
- **Compensation Planning**
- **HR Analytics Dashboard**
- **HMO & Benefits Administration**

## ✨ Features

### 🎯 Core HCM Module
- Employee management and profiles
- Department and position management
- Attendance tracking
- Employee lifecycle management
- Search and filter capabilities

### 💰 Payroll Management
- Automated payroll processing
- Salary structure management
- Deductions and allowances
- Payroll reports and analytics
- Payment status tracking

### 📊 Compensation Planning
- Compensation plan creation and management
- Component-based compensation structure
- Salary benchmarking
- Performance-based compensation
- Plan status management

### 📈 HR Analytics Dashboard
- Real-time HR metrics
- Interactive charts and graphs
- Performance analytics
- Training and development tracking
- Department-wise analytics

### 🏥 HMO & Benefits Administration
- HMO provider management
- Plan configuration and management
- Employee benefit assignment
- Coverage tracking
- Provider relationship management

## 🎨 Design Features

- **Modern UI/UX**: Clean, professional interface with green and white color palette
- **Responsive Design**: Mobile-friendly design that works on all devices
- **Interactive Elements**: Dynamic charts, tables, and forms
- **User Experience**: Intuitive navigation and user-friendly interface
- **Professional Appearance**: Hospital-grade professional design

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Charts**: Chart.js for data visualization
- **Icons**: Font Awesome 6.0
- **Server**: XAMPP (Apache + MySQL + PHP)

## 📋 Prerequisites

Before installing the system, ensure you have:

- XAMPP installed and running
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)
- At least 100MB of free disk space

## 🚀 Installation Guide

### Step 1: Download and Extract
1. Download the system files
2. Extract to your XAMPP htdocs folder: `C:\xampp\htdocs\HR4\`

### Step 2: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start Apache and MySQL services
3. Ensure both services are running (green status)

### Step 3: Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `hr4_hospital`
3. Import the `database.sql` file to create all tables and sample data

### Step 4: Configure Database Connection
1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hr4_hospital');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### Step 5: Access the System
1. Open your web browser
2. Navigate to: `http://localhost/HR4/`
3. You will be redirected to the login page

## 🔐 Default Login Credentials

The system comes with pre-configured demo accounts:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| `admin` | `password` | Admin | Full system access |
| `hr_manager` | `password` | HR Manager | HR management access |
| `hr_staff` | `password` | HR Staff | Limited HR access |

## 📁 File Structure

```
HR4/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── modules/
│   ├── hcm/
│   │   └── index.php
│   ├── payroll/
│   │   └── index.php
│   ├── compensation/
│   │   └── index.php
│   ├── analytics/
│   │   └── index.php
│   └── hmo/
│       └── index.php
├── database.sql
├── index.php
├── login.php
├── logout.php
└── README.md
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to match your database settings:

```php
define('DB_HOST', 'localhost');     // Database host
define('DB_NAME', 'hr4_hospital');  // Database name
define('DB_USER', 'root');          // Database username
define('DB_PASS', '');              // Database password
```

### System Settings
- Update company information in the header
- Modify color scheme in `assets/css/style.css`
- Adjust chart configurations in `assets/js/main.js`

## 📊 Sample Data

The system includes sample data for:
- 5 departments (HR, IT, Finance, Operations, Marketing)
- 8 positions with salary grades
- 4 sample employees
- 3 HMO providers
- Sample compensation plans and components

## 🚀 Getting Started

### First Time Setup
1. **Login**: Use admin credentials to access the system
2. **Dashboard**: Review the main dashboard overview
3. **Modules**: Explore each module to understand functionality
4. **Data**: Review and modify sample data as needed

### Adding New Data
1. **Employees**: Use Core HCM module to add new employees
2. **Departments**: Create new departments as needed
3. **Positions**: Define new job positions
4. **HMO Plans**: Configure HMO plans and providers
5. **Compensation**: Set up compensation plans and structures

## 🔒 Security Features

- **Session Management**: Secure user sessions
- **Role-based Access**: Different access levels for different user types
- **Input Validation**: Form validation and sanitization
- **SQL Injection Protection**: Prepared statements for database queries
- **XSS Protection**: Output escaping and sanitization

## 📱 Mobile Responsiveness

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🎯 Use Cases

### For HR Managers
- Employee lifecycle management
- Compensation planning and analysis
- Performance tracking
- Strategic HR decision making

### For HR Staff
- Daily HR operations
- Employee data management
- Payroll processing
- Benefits administration

### For Administrators
- System configuration
- User management
- Data backup and maintenance
- System monitoring

## 🚨 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify XAMPP services are running
- Check database credentials in `config/database.php`
- Ensure database `hr4_hospital` exists

**Page Not Found (404)**
- Verify files are in correct XAMPP htdocs folder
- Check Apache configuration
- Ensure .htaccess file is present (if using)

**Login Issues**
- Verify database tables are created
- Check user credentials in database
- Ensure sessions are working

**Charts Not Displaying**
- Check internet connection for Chart.js CDN
- Verify JavaScript is enabled in browser
- Check browser console for errors

### Performance Optimization

1. **Database Indexing**: Add indexes to frequently queried columns
2. **Caching**: Implement Redis or Memcached for better performance
3. **Image Optimization**: Optimize any uploaded images
4. **CDN Usage**: Use CDN for static assets

## 🔄 Updates and Maintenance

### Regular Maintenance
- Database backups (weekly)
- Log file cleanup (monthly)
- Security updates (as needed)
- Performance monitoring (ongoing)

### System Updates
- Backup database before updates
- Test updates in development environment
- Update files systematically
- Verify functionality after updates

## 📞 Support

For technical support or questions:

1. **Documentation**: Review this README thoroughly
2. **Code Comments**: Check inline code comments
3. **Error Logs**: Review Apache and PHP error logs
4. **Community**: Seek help from PHP/MySQL communities

## 📄 License

This system is provided as-is for educational and business use. Please ensure compliance with your organization's policies and local regulations.

## 🎉 Conclusion

The HR4 Hospital System provides a robust, scalable solution for hospital HR management. With its comprehensive feature set, modern design, and user-friendly interface, it's designed to streamline HR operations and provide valuable insights for strategic decision-making.

---

**System Version**: 1.0.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+, Modern Browsers
