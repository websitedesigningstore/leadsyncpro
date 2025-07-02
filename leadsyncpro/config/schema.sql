-- LeadSync Pro CRM Database Schema
-- Complete database structure for all modules

-- Drop existing database and create new one
DROP DATABASE IF EXISTS leadsyncpro;
CREATE DATABASE leadsyncpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leadsyncpro;

-- ===============================
-- MODULE 1: Authentication & User Management
-- ===============================

-- Users table with role-based access
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'staff', 'manager', 'agent') DEFAULT 'staff',
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    smtp_host VARCHAR(100),
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(100),
    smtp_password VARCHAR(255),
    email_signature TEXT,
    whatsapp_template TEXT,
    email_template TEXT,
    call_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE
);

-- User sessions for security
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User activity logs
CREATE TABLE user_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password reset tokens
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- MODULE 2: Lead Management
-- ===============================

-- Lead sources lookup
CREATE TABLE lead_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lead statuses lookup
CREATE TABLE lead_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Main leads table
CREATE TABLE leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    alternate_phone VARCHAR(20),
    whatsapp_number VARCHAR(20),
    company VARCHAR(100),
    designation VARCHAR(50),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    country VARCHAR(50) DEFAULT 'India',
    pincode VARCHAR(10),
    lead_source_id INT,
    lead_status_id INT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT,
    lead_value DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'INR',
    expected_closure_date DATE,
    follow_up_date DATETIME,
    requirements TEXT,
    remarks TEXT,
    tags VARCHAR(255),
    is_converted BOOLEAN DEFAULT FALSE,
    converted_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (lead_source_id) REFERENCES lead_sources(id),
    FOREIGN KEY (lead_status_id) REFERENCES lead_statuses(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Lead files/attachments
CREATE TABLE lead_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Lead history/activity log
CREATE TABLE lead_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ===============================
-- MODULE 3: Communication & Templates
-- ===============================

-- Communication types
CREATE TABLE communication_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE
);

-- Communication logs
CREATE TABLE communications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    recipient VARCHAR(100),
    status ENUM('sent', 'delivered', 'failed', 'pending') DEFAULT 'sent',
    response TEXT,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (type_id) REFERENCES communication_types(id)
);

-- Communication templates
CREATE TABLE communication_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    type_id INT NOT NULL,
    subject VARCHAR(200),
    template TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES communication_types(id)
);

-- ===============================
-- MODULE 4: Dashboard & Analytics
-- ===============================

-- Dashboard widgets configuration
CREATE TABLE dashboard_widgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    widget_name VARCHAR(50) NOT NULL,
    widget_position INT DEFAULT 0,
    is_visible BOOLEAN DEFAULT TRUE,
    widget_size VARCHAR(20) DEFAULT 'col-md-3',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- MODULE 5: Daily Sales Report (DSR)
-- ===============================

-- Daily sales reports
CREATE TABLE daily_sales_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_date DATE NOT NULL,
    leads_added INT DEFAULT 0,
    leads_contacted INT DEFAULT 0,
    follow_ups_done INT DEFAULT 0,
    meetings_scheduled INT DEFAULT 0,
    conversions INT DEFAULT 0,
    remarks TEXT,
    challenges_faced TEXT,
    next_day_plan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, report_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- MODULE 6: Payments & Invoices
-- ===============================

-- Payment types
CREATE TABLE payment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Payments
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    invoice_number VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    payment_type_id INT,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    payment_date DATE NOT NULL,
    due_date DATE,
    status ENUM('paid', 'partially_paid', 'pending', 'overdue') DEFAULT 'pending',
    description TEXT,
    receipt_file VARCHAR(255),
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_type_id) REFERENCES payment_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- ===============================
-- MODULE 7: Notifications & Reminders
-- ===============================

-- Notification types
CREATE TABLE notification_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    template TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_type VARCHAR(50),
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES notification_types(id)
);

-- Reminder settings
CREATE TABLE reminder_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reminder_type VARCHAR(50) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    reminder_time TIME DEFAULT '09:00:00',
    days_before INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================
-- MODULE 8: API Integration
-- ===============================

-- API tokens
CREATE TABLE api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_name VARCHAR(100) NOT NULL,
    token_hash VARCHAR(255) UNIQUE NOT NULL,
    permissions JSON,
    rate_limit INT DEFAULT 1000,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- API request logs
CREATE TABLE api_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_id INT,
    endpoint VARCHAR(200) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data JSON,
    response_data JSON,
    status_code INT,
    execution_time FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE SET NULL
);

-- ===============================
-- MODULE 9: System Settings
-- ===============================

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- ===============================
-- Default Data Insertion
-- ===============================

-- Insert default admin user
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@leadsyncpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert default lead sources
INSERT INTO lead_sources (name) VALUES
('Website'), ('Social Media'), ('Email Campaign'), ('Cold Call'), ('Referral'), ('Trade Show'), ('Advertisement'), ('Partner'), ('Other');

-- Insert default lead statuses
INSERT INTO lead_statuses (name, color) VALUES
('New', '#007bff'),
('Contacted', '#17a2b8'),
('Qualified', '#28a745'),
('Proposal Sent', '#ffc107'),
('Negotiation', '#fd7e14'),
('Converted', '#28a745'),
('Lost', '#dc3545'),
('On Hold', '#6c757d');

-- Insert communication types
INSERT INTO communication_types (name, icon) VALUES
('WhatsApp', 'fab fa-whatsapp'),
('Email', 'fas fa-envelope'),
('Phone Call', 'fas fa-phone'),
('SMS', 'fas fa-sms'),
('Meeting', 'fas fa-calendar');

-- Insert payment types
INSERT INTO payment_types (name) VALUES
('UPI'), ('Bank Transfer'), ('Cash'), ('Credit Card'), ('Debit Card'), ('Cheque'), ('Net Banking');

-- Insert notification types
INSERT INTO notification_types (name, template) VALUES
('Follow-up Reminder', 'You have a follow-up scheduled for {lead_name} at {follow_up_time}'),
('Payment Due', 'Payment of {amount} is due for {lead_name} on {due_date}'),
('Lead Assignment', 'New lead {lead_name} has been assigned to you'),
('Status Update', 'Lead {lead_name} status updated to {status}');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('company_name', 'LeadSync Pro', 'text', 'Company Name', TRUE),
('company_logo', '', 'file', 'Company Logo', TRUE),
('company_email', 'info@leadsyncpro.com', 'email', 'Company Email', TRUE),
('company_phone', '', 'text', 'Company Phone', TRUE),
('company_address', '', 'textarea', 'Company Address', TRUE),
('theme_color', '#007bff', 'color', 'Primary Theme Color', TRUE),
('currency', 'INR', 'text', 'Default Currency', TRUE),
('timezone', 'Asia/Kolkata', 'select', 'Default Timezone', TRUE),
('date_format', 'd-m-Y', 'select', 'Date Format', TRUE),
('records_per_page', '25', 'number', 'Records Per Page', FALSE),
('session_timeout', '3600', 'number', 'Session Timeout (seconds)', FALSE),
('file_upload_limit', '5', 'number', 'File Upload Limit (MB)', FALSE);

-- Create indexes for better performance
CREATE INDEX idx_leads_assigned_to ON leads(assigned_to);
CREATE INDEX idx_leads_status ON leads(lead_status_id);
CREATE INDEX idx_leads_source ON leads(lead_source_id);
CREATE INDEX idx_leads_created_at ON leads(created_at);
CREATE INDEX idx_communications_lead_id ON communications(lead_id);
CREATE INDEX idx_communications_user_id ON communications(user_id);
CREATE INDEX idx_payments_lead_id ON payments(lead_id);
CREATE INDEX idx_user_activity_user_id ON user_activity(user_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);