# Port Queuing Management System (PQMS)

A comprehensive web-based Queue Management System designed for efficient port operations management with support for multiple service categories, cargo priority handling, and real-time updates.

## 🎯 Features

### Port Operations Features
- **Token Generation**: Quick token generation for various port services
- **Priority Handling**: 
  - Emergency cargo
  - Hazardous materials (HAZMAT)
  - Perishable goods
  - Urgent shipments
  - Express processing
  - Regular operations
- **Real-Time Status**: Track token status in real-time
- **Wait Time Estimation**: Get estimated processing times
- **Mobile-Friendly**: Responsive design for drivers and port staff
- **Optional Notifications**: SMS/Email notifications (when configured)

### Admin Features
- **Dashboard**: Comprehensive overview of daily port operations
- **Service Point Management**: Manage gates, windows, and inspection bays
- **Queue Control**: Call next token, start processing, complete, or mark no-show
- **Token Management**: View, search, and manage all tokens
- **Reports & Analytics**: Daily statistics, service performance, efficiency metrics
- **Role-Based Access**: Super Admin, Admin, and Service Point Staff roles

### Display Board
- **Live Display**: Real-time display of currently processing tokens
- **Auto-Refresh**: Automatic updates every 5 seconds
- **Full-Screen Mode**: Double-click for fullscreen display
- **Professional UI**: Clean and easy-to-read interface for port areas

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

## 🚀 Installation

1. **Clone/Download** the files to your XAMPP htdocs directory:
   ```
   c:\xampp\htdocs\qs
   ```

2. **Create Database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database.sql` file
   - This will create the database with all tables, views, and sample data

3. **Configure Database** (if needed):
   - Edit `config/database.php` if your database credentials differ:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'queue_system');
   ```

4. **Configure Base URL** (if needed):
   - Edit `config/config.php`:
   ```php
   define('BASE_URL', 'http://localhost/qs');
   ```

5. **Set Permissions** (Linux/Mac):
   ```bash
   chmod -R 755 /path/to/qs
   ```

6. **Start XAMPP**:
   - Start Apache and MySQL services

7. **Access the System**:
   - Main Page: http://localhost/qs
   - Admin Login: http://localhost/qs/admin/login.php
   - Default credentials: `admin` / `admin123`

## 📁 Directory Structure

```
qs/
├── admin/              # Admin panel
│   ├── includes/       # Header/footer templates
│   ├── dashboard.php   # Main dashboard
│   ├── counter.php     # Counter management
│   ├── tokens.php      # Token management
│   ├── reports.php     # Reports & analytics
│   ├── login.php       # Login page
│   └── logout.php      # Logout handler
├── api/                # REST API endpoints
│   ├── generate-token.php
│   ├── queue-status.php
│   ├── counter-operations.php
│   ├── get-services.php
│   └── get-token.php
├── assets/
│   └── css/
│       └── style.css   # Main stylesheet
├── config/             # Configuration files
│   ├── config.php      # App configuration
│   └── database.php    # Database connection
├── customer/           # Customer interface
│   ├── index.php       # Token generation
│   └── token-status.php # Status checker
├── display/            # Display board
│   └── index.php       # Live display
├── includes/           # PHP classes
│   ├── TokenManager.php
│   ├── ServiceManager.php
│   └── Auth.php
├── database.sql        # Database schema
├── index.php           # Landing page
└── README.md           # This file
```

## 🎮 Usage

### For Drivers/Port Users

1. **Get a Token**:
   - Visit http://localhost/qs/customer/
   - Select service type (Truck Entry, Container Pickup, etc.)
   - Choose cargo priority (Regular, Express, Urgent, Perishable, HAZMAT, Emergency)
   - Optionally provide contact info for notifications
   - Click "Generate Token"

2. **Check Status**:
   - Visit http://localhost/qs/customer/token-status.php
   - Enter your token number
   - View real-time status and queue position

### For Port Staff

1. **Login**:
   - Visit http://localhost/qs/admin/login.php
   - Use your credentials (default: admin/admin123)

2. **Manage Service Point**:
   - Go to Counter Management
   - Select your gate/window/bay
   - Call next token
   - Start processing
   - Complete service

3. **View Reports**:
   - Access Reports section (Admin/Super Admin only)
   - View daily statistics
   - Analyze service performance
   - Monitor service point efficiency

## 🔧 Configuration

### SMS Notifications (Optional)

Edit `config/config.php`:

```php
define('SMS_ENABLED', true);
define('SMS_API_KEY', 'your-api-key');
define('SMS_API_URL', 'https://api.semaphore.co/api/v4/messages');
```

### Service Categories

Edit service categories in database:
- Table: `service_categories`
- Add/modify service types
- Set priority levels
- Adjust average service times

### Counters

Configure counters in database:
- Table: `service_counters`
- Add/remove counters
- Map services to counters via `counter_services` table

## 👥 User Roles

1. **Super Admin**:
   - Full system access
   - User management
   - System configuration
   - Reports and analytics

2. **Admin**:
   - Service point management
   - Token management
   - Reports access

3. **Service Point Staff**:
   - Assigned gate/window/bay only
   - Call and process tokens
   - Update service point status

## 🚢 Port Service Categories by Vessel Type

### Cargo Ship Services
- **Cargo Ship - Berthing** (CGO-BTH) - Docking and berthing operations
- **Cargo Ship - Loading/Unloading** (CGO-LUD) - Container loading/unloading
- **Cargo Ship - Documentation** (CGO-DOC) - Customs and paperwork

### RORO Services (Roll-on/Roll-off)
- **RORO - Vehicle Entry** (ROR-ENT) - Vehicle entry processing
- **RORO - Vehicle Exit** (ROR-EXT) - Vehicle exit processing
- **RORO - Documentation** (ROR-DOC) - Vessel documentation and manifests

### Boat Services
- **Boat - Berthing** (BOT-BTH) - Small vessel berthing
- **Boat - Passenger Services** (BOT-PSG) - Passenger operations
- **Boat - Customs/Immigration** (BOT-CUS) - Clearance procedures

### General Services
- **General - Inspection** (GEN-INS) - Cargo and vessel inspection
- **Emergency Services** (EMG-SRV) - Emergency operations for all vessels

## 🎯 Priority Types

Port-specific priority handling:
1. **Emergency**: Highest priority for critical situations
2. **HAZMAT**: Hazardous materials requiring special handling
3. **Perishable**: Time-sensitive perishable goods
4. **Urgent**: Time-critical shipments
5. **Express**: Fast-track processing
6. **Regular**: Standard port operations

## 📊 Database Views

Pre-configured views for easy data access:
- `active_queue`: Current queue status
- `counter_status_view`: Real-time counter information
- `daily_statistics`: Daily performance metrics

## 🔐 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using PDO prepared statements
- XSS protection through input sanitization
- Session-based authentication
- Role-based access control

## 🐛 Troubleshooting

**Database Connection Error**:
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database `queue_system` exists

**Blank Page**:
- Check PHP error logs
- Enable error display in `php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```

**Admin Login Not Working**:
- Default credentials: `admin` / `admin123`
- Check `admin_users` table in database
- Password should be hashed

## 📈 Future Enhancements

- [ ] SMS integration with Semaphore/Twilio
- [ ] Email notifications
- [ ] Mobile app (iOS/Android)
- [ ] QR code token generation
- [ ] Appointment scheduling
- [ ] Multi-language support
- [ ] Voice announcements
- [ ] API documentation
- [ ] Docker containerization

## 📝 License

This project is open-source and available for port management operations.

## 👨‍💻 Support

For issues, questions, or contributions:
- Create an issue in the repository
- Contact port system administrator

## 📌 Version

Current Version: 1.0.0  
Release Date: January 17, 2026  
Application: Port Queuing Management System

---

**Note**: Change default admin password immediately after first login for security purposes.
