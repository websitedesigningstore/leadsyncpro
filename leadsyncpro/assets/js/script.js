// LeadSync Pro CRM - Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeSidebar();
    initializeTooltips();
    initializeModals();
    initializeFormValidation();
    initializeFileUpload();
    initializeDataTables();
    initializeNotifications();
    initializeCharts();
    autoLogout();
});

// Sidebar functionality
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
    
    // Set active navigation item
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
}

// Initialize Bootstrap tooltips
function initializeTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Initialize Bootstrap modals
function initializeModals() {
    // Auto-open modal if URL contains modal parameter
    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');
    if (modalParam) {
        const modal = document.getElementById(modalParam);
        if (modal && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modal).show();
        }
    }
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearValidation);
    });
}

// Validate individual field
function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    
    // Clear previous validation
    clearFieldValidation(field);
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }
    
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        showFieldError(field, 'Please enter a valid phone number');
        return false;
    }
    
    if (field.name === 'password' && value && value.length < 8) {
        showFieldError(field, 'Password must be at least 8 characters long');
        return false;
    }
    
    if (field.name === 'confirm_password') {
        const password = document.querySelector('input[name="password"]');
        if (password && value !== password.value) {
            showFieldError(field, 'Passwords do not match');
            return false;
        }
    }
    
    showFieldSuccess(field);
    return true;
}

// Clear field validation
function clearValidation(event) {
    clearFieldValidation(event.target);
}

function clearFieldValidation(field) {
    field.classList.remove('is-valid', 'is-invalid');
    const feedback = field.parentNode.querySelector('.invalid-feedback, .valid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    field.parentNode.appendChild(feedback);
}

function showFieldSuccess(field) {
    field.classList.add('is-valid');
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation
function isValidPhone(phone) {
    const phoneRegex = /^[+]?[\d\s\-\(\)]+$/;
    return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// File upload functionality
function initializeFileUpload() {
    const fileUploadAreas = document.querySelectorAll('.file-upload-area');
    
    fileUploadAreas.forEach(area => {
        const fileInput = area.querySelector('input[type="file"]');
        if (!fileInput) return;
        
        // Click to upload
        area.addEventListener('click', () => fileInput.click());
        
        // Drag and drop
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', () => handleFileSelect(fileInput));
    });
}

// Handle file selection
function handleFileSelect(input) {
    const files = input.files;
    const previewContainer = input.parentNode.querySelector('.file-preview');
    
    if (previewContainer) {
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item mb-2 p-2 border rounded';
            fileItem.innerHTML = `
                <i class="fas fa-file"></i>
                <span class="ms-2">${file.name}</span>
                <small class="text-muted ms-2">(${formatFileSize(file.size)})</small>
            `;
            previewContainer.appendChild(fileItem);
        });
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Initialize DataTables
function initializeDataTables() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
}

// AJAX helper functions
function makeAjaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-Token', csrfToken.getAttribute('content'));
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (callback) callback(true, response);
                } catch (e) {
                    if (callback) callback(true, xhr.responseText);
                }
            } else {
                if (callback) callback(false, xhr.statusText);
            }
        }
    };
    
    xhr.send(data ? JSON.stringify(data) : null);
}

// Show notifications
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Initialize notifications
function initializeNotifications() {
    // Check for flash messages
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        const message = flashMessage.textContent;
        const type = flashMessage.dataset.type || 'info';
        showNotification(message, type);
        flashMessage.remove();
    }
    
    // Auto-refresh notifications
    setInterval(loadNotifications, 60000); // Every minute
}

// Load notifications
function loadNotifications() {
    makeAjaxRequest('/leadsyncpro/api/notifications.php', 'GET', null, function(success, data) {
        if (success && data.unread_count !== undefined) {
            updateNotificationBadge(data.unread_count);
        }
    });
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Initialize charts
function initializeCharts() {
    // Dashboard charts will be initialized by Chart.js
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#495057';
    }
}

// Auto logout
function autoLogout() {
    let lastActivity = Date.now();
    const TIMEOUT = 60 * 60 * 1000; // 1 hour
    
    // Track user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            lastActivity = Date.now();
        }, true);
    });
    
    // Check for timeout
    setInterval(() => {
        if (Date.now() - lastActivity > TIMEOUT) {
            showNotification('Session expired. You will be redirected to login page.', 'warning');
            setTimeout(() => {
                window.location.href = '/leadsyncpro/auth/login.php?timeout=1';
            }, 3000);
        }
    }, 60000); // Check every minute
}

// Utility functions
function formatCurrency(amount, currency = 'INR') {
    const formatter = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
    });
    return formatter.format(amount);
}

function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    const options = format === 'short' 
        ? { year: 'numeric', month: 'short', day: 'numeric' }
        : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    
    return date.toLocaleDateString('en-IN', options);
}

function generateWhatsAppLink(phone, message = '') {
    const cleanPhone = phone.replace(/\D/g, '');
    const formattedPhone = cleanPhone.startsWith('91') ? cleanPhone : '91' + cleanPhone;
    return `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!', 'success', 2000);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Copied to clipboard!', 'success', 2000);
    }
}

// Export to Excel
function exportToExcel(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    
    window.URL.revokeObjectURL(url);
}

// Print function
function printPage() {
    window.print();
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Lead status colors
function getStatusColor(status) {
    const colors = {
        'new': '#007bff',
        'contacted': '#17a2b8',
        'qualified': '#28a745',
        'proposal': '#ffc107',
        'negotiation': '#fd7e14',
        'converted': '#28a745',
        'lost': '#dc3545',
        'hold': '#6c757d'
    };
    return colors[status.toLowerCase()] || '#6c757d';
}

// Priority colors
function getPriorityColor(priority) {
    const colors = {
        'low': '#28a745',
        'medium': '#ffc107',
        'high': '#fd7e14',
        'urgent': '#dc3545'
    };
    return colors[priority.toLowerCase()] || '#6c757d';
}

// Form serialization
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load more functionality for infinite scroll
function initializeInfiniteScroll(container, loadMoreCallback) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMoreCallback();
            }
        });
    });
    
    const sentinel = document.createElement('div');
    sentinel.className = 'scroll-sentinel';
    container.appendChild(sentinel);
    observer.observe(sentinel);
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    if (e.error && e.error.message) {
        showNotification('An error occurred: ' + e.error.message, 'danger');
    }
});

// Prevent form resubmission on refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}