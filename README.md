# LeadSync Pro CRM

🚀 **Complete SaaS CRM Web Application** built with Core PHP, MySQL, HTML, CSS, and JavaScript

## 📋 **Features**

### 🔐 **Authentication & Security**
- ✅ **Secure Login System** with role-based access (Admin/Staff)
- ✅ **CSRF Protection** and SQL injection prevention
- ✅ **Password Hashing** with PHP built-in functions
- ✅ **Session Management** with timeout handling
- ✅ **Activity Logging** for complete audit trails

### 👥 **Lead Management**
- ✅ **Comprehensive Lead Forms** with 25+ fields
- ✅ **Lead Status Tracking** (New → Contacted → Converted)
- ✅ **Priority Management** (High/Medium/Low)
- ✅ **Source Tracking** (Facebook, Google, Referral, etc.)
- ✅ **File Upload Support** for documents and images
- ✅ **Assignment System** for team management
- ✅ **Advanced Search & Filtering** with pagination

### 💬 **Communication Automation**
- ✅ **WhatsApp Integration** with direct links
- ✅ **Email Templates** and sending capability
- ✅ **Call Tracking** and logging
- ✅ **Communication History** tracking
- ✅ **Template Management** for consistent messaging

### 💰 **Payment & Invoicing**
- ✅ **Payment Tracking** (UPI, Bank, Cash, Card, Cheque)
- ✅ **Invoice Management** with file uploads
- ✅ **Payment Status** monitoring
- ✅ **Transaction Records** with receipt management
- ✅ **Payment Links** integration

### 📊 **Analytics & Reporting**
- ✅ **Dashboard Statistics** with real-time metrics
- ✅ **Chart.js Integration** for data visualization
- ✅ **Daily Sales Reports** (DSR) functionality
- ✅ **Performance Tracking** and analytics
- ✅ **Lead Source Analytics** with charts

### 🔗 **API Integration**
- ✅ **RESTful API Endpoint** for external lead capture
- ✅ **Bearer Token Authentication** for security
- ✅ **Rate Limiting** (100 requests/hour)
- ✅ **API Request Logging** and monitoring
- ✅ **Webhook Ready** for third-party integrations

### 📱 **Mobile Responsive**
- ✅ **Mobile-First Design** with responsive layouts
- ✅ **Touch-Friendly Interface** elements
- ✅ **Hamburger Menu** for mobile navigation
- ✅ **Optimized Tables** with horizontal scrolling
- ✅ **Cross-Device Compatibility**

## 🗄️ **Database Schema**

The system includes **9 comprehensive tables**:

1. **`users`** - User management with roles, SMTP settings, API tokens
2. **`leads`** - Complete lead management with contact, business & pricing info
3. **`payments`** - Transaction tracking and invoice management
4. **`daily_reports`** - DSR (Daily Sales Report) functionality
5. **`templates`** - Communication templates (WhatsApp, Email, SMS)
6. **`activity_logs`** - Complete audit trail for all actions
7. **`communications`** - Communication history tracking
8. **`reminders`** - Automated follow-up system
9. **`api_requests`** - API usage tracking and monitoring

## 📁 **Project Structure**

```
leadsyncpro/
├── config/
│   └── database.php          # Database configuration & app settings
├── includes/
│   ├── functions.php         # Core PHP functions & utilities
│   ├── header.php           # Navigation template with responsive menu
│   └── footer.php           # Footer template
├── assets/
│   ├── css/
│   │   ├── style.css        # Main styling (27KB) with CSS variables
│   │   └── responsive.css   # Mobile responsiveness (7KB)
│   └── js/
│       └── main.js          # Interactive functionality (31KB)
├── database/
│   └── schema.sql           # Complete database schema (9.8KB)
├── api/
│   └── add_lead.php         # External lead capture API endpoint
├── uploads/                 # File upload directory (create manually)
├── index.php               # Main routing and entry point
├── login.php               # Authentication system with demo credentials
├── logout.php              # Session cleanup and logout
├── dashboard.php           # Main dashboard with statistics & charts
├── add_lead.php            # Comprehensive lead creation form
├── leads.php               # Lead listing with search & filters
├── view_lead.php           # Detailed lead view with history
├── edit_lead.php           # Lead editing functionality
├── users.php               # User management (Admin only)
└── README.md               # This documentation
```

## 🚀 **Installation Guide**

### **Prerequisites**
- **PHP 7.4+** with PDO extension
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Web Server** (Apache/Nginx)
- **SSL Certificate** (recommended for production)

### **Step 1: Download & Setup**
```bash
# Download/Clone the project
# Upload to your hosting server
# Set proper file permissions (755 for directories, 644 for files)
```

### **Step 2: Database Setup**
1. Create a MySQL database named `leadsyncpro`
2. Import the database schema:
```sql
# Import the schema file
mysql -u username -p leadsyncpro < database/schema.sql
```

### **Step 3: Configuration**
1. Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'leadsyncpro');
define('BASE_URL', 'https://yourdomain.com/leadsyncpro/');
```

### **Step 4: Create Upload Directory**
```bash
mkdir uploads
chmod 755 uploads
```

### **Step 5: Insert Demo Users**
```sql
INSERT INTO users (name, email, password, role, status, api_token) VALUES
('Administrator', 'admin@leadsyncpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'demo_admin_token_here'),
('Staff Member', 'staff@leadsyncpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', 'demo_staff_token_here');
```

### **Step 6: Access the System**
- **URL:** `https://yourdomain.com/leadsyncpro/`
- **Admin Login:** `admin@leadsyncpro.com` / `password`
- **Staff Login:** `staff@leadsyncpro.com` / `password`

## 🔑 **Demo Credentials**

The system includes built-in demo credentials for testing:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@leadsyncpro.com | password |
| **Staff** | staff@leadsyncpro.com | password |

## 🛠️ **API Usage**

### **Add Lead via API**
```bash
curl -X POST https://yourdomain.com/leadsyncpro/api/add_lead.php \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "mobile_number": "+919876543210",
    "email": "john@example.com",
    "source": "website"
  }'
```

### **API Response**
```json
{
  "success": true,
  "message": "Lead added successfully",
  "lead_id": 123
}
```

## 🎨 **Customization**

### **Branding**
- Update `APP_NAME` in `config/database.php`
- Modify CSS variables in `assets/css/style.css`
- Replace logo/branding in header template

### **Email Configuration**
- Configure SMTP settings in user profile
- Customize email templates in the system

### **WhatsApp Integration**
- Update `WHATSAPP_BASE_URL` in config
- Customize message templates

## 🔒 **Security Features**

- ✅ **CSRF Token Protection** on all forms
- ✅ **SQL Injection Prevention** with prepared statements
- ✅ **XSS Protection** with input sanitization
- ✅ **File Upload Security** with extension validation
- ✅ **Session Security** with timeout handling
- ✅ **Password Hashing** with PHP's password_hash()
- ✅ **Role-Based Access Control** (RBAC)
- ✅ **Activity Logging** for audit trails

## 📋 **System Requirements**

### **Minimum Requirements**
- **PHP:** 7.4+
- **MySQL:** 5.7+ or MariaDB 10.2+
- **Memory:** 256MB RAM
- **Storage:** 100MB disk space
- **Web Server:** Apache 2.4+ or Nginx 1.16+

### **Recommended for Production**
- **PHP:** 8.0+
- **MySQL:** 8.0+ or MariaDB 10.5+
- **Memory:** 512MB+ RAM
- **Storage:** 1GB+ disk space
- **SSL:** Valid SSL certificate
- **Backup:** Regular database backups

## 🌟 **Key Benefits**

- ✅ **Self-Hosted** - Complete control over your data
- ✅ **Multi-User** - Role-based team collaboration
- ✅ **Mobile Responsive** - Works on all devices
- ✅ **API Ready** - Easy third-party integrations
- ✅ **Hostinger Compatible** - Works with shared hosting
- ✅ **Production Ready** - Enterprise-grade security
- ✅ **Scalable** - Grows with your business
- ✅ **Modern UI/UX** - Professional appearance

## 🆘 **Support & Updates**

### **Common Issues**
1. **Database Connection Error:** Check credentials in `config/database.php`
2. **File Upload Issues:** Ensure `uploads/` directory has write permissions
3. **Login Problems:** Verify users table has demo credentials
4. **API Not Working:** Check API token and request format

### **Performance Optimization**
- Enable PHP OPcache for better performance
- Use MySQL query caching
- Implement CDN for static assets
- Regular database optimization

## 📄 **License**

This project is released under the MIT License. Free for personal and commercial use.

---

**LeadSync Pro CRM** - Complete Lead & Client Management Solution 🚀

Built with ❤️ using Core PHP, MySQL, HTML, CSS & JavaScript
