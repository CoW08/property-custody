
# property-custody
# School Management System - Property Custodian Management

A comprehensive web-based system for managing school property, assets, and supplies inventory.

## Project Structure

```
School Management System 2 Property Custodian Management for Supplies Inventory Management/
├── api/                          # Backend API endpoints
│   ├── auth.php                  # Authentication endpoints
│   ├── dashboard.php             # Dashboard data endpoints
│   ├── assets.php                # Asset management endpoints
│   └── supplies.php              # Supply management endpoints
├── components/                   # Reusable UI components
│   ├── login.php                 # Login form component
│   ├── sidebar.php               # Navigation sidebar component
│   ├── dashboard.php             # Dashboard component
│   ├── modal.php                 # Modal overlay component
│   ├── asset-registry.php        # Asset registry module
│   └── supplies-inventory.php    # Supplies inventory module
├── config/                       # Configuration files
│   ├── database.php              # Database connection class
│   ├── cors.php                  # CORS handling
│   └── config.php                # System configuration constants
├── js/                          # Client-side JavaScript
│   ├── api.js                    # API communication layer
│   ├── auth.js                   # Authentication handling
│   ├── main.js                   # Main application controller
│   └── dashboard.js              # Dashboard functionality
├── layouts/                      # Layout templates
│   └── layout.php                # Main layout template
├── logos/                        # Logo and images
│   └── logo.jpg                  # School logo
├── documentation/                # Project documentation
│   └── ...                       # Documentation files
├── database_setup.sql            # Database schema and initial data
├── index.php                     # Main application entry point
└── index.html                    # Original HTML file (kept for reference)
```

## Features

### Core Modules
1. **Asset Registry & Tagging** - Complete asset management with tagging system
2. **Property Issuance & Acknowledgment** - Property assignment and acknowledgment tracking
3. **Supplies Inventory Management** - Comprehensive supplies inventory system
4. **Custodian Assignment & Transfer** - Custodian management and transfers
5. **Preventive Maintenance Scheduling** - Maintenance planning and scheduling
6. **Lost, Damaged, or Unserviceable Items** - Damage and loss reporting
7. **Property Audit & Physical Inventory** - Audit management system
8. **Procurement Coordination** - Procurement request management
9. **Reports & Analytics** - Comprehensive reporting system
10. **User Roles & Access Control** - Role-based access management

### Technical Features
- **Responsive Design** - Built with Tailwind CSS
- **RESTful API** - PHP-based backend with JSON responses
- **Role-Based Access** - Multiple user roles with different permissions
- **Session Management** - Secure authentication system
- **Component-Based Architecture** - Modular and maintainable code structure
- **Database Logging** - Complete audit trail for all actions

## Installation

### Prerequisites
- XAMPP or similar PHP development environment
- MySQL 5.7+ or MariaDB
- Web browser with JavaScript enabled

### Setup Instructions

1. **Clone/Download** the project to your XAMPP htdocs directory

2. **Database Setup**
   ```sql
   # Import the database schema
   mysql -u root -p < database_setup.sql
   ```

3. **Configuration**
   - Update database credentials in `config/database.php` if needed
   - Modify system settings in `config/config.php`

4. **Access the Application**
   - Open your web browser
   - Navigate to `http://localhost/School Management System 2 Property Custodian Management for Supplies Inventory Management/`

## Default Login Credentials

- **Administrator**
  - Username: `admin`
  - Password: `admin123`

- **Property Custodian**
  - Username: `custodian`
  - Password: `custodian123`

- **Staff Member**
  - Username: `staff`
  - Password: `staff123`

## API Documentation

### Authentication Endpoints
- `POST /api/auth.php?action=login` - User login
- `POST /api/auth.php?action=logout` - User logout

### Dashboard Endpoints
- `GET /api/dashboard.php?action=stats` - Get dashboard statistics
- `GET /api/dashboard.php?action=recent_activities` - Get recent activities
- `GET /api/dashboard.php?action=alerts` - Get system alerts

### Asset Management Endpoints
- `GET /api/assets.php` - Get all assets
- `GET /api/assets.php?id={id}` - Get specific asset
- `POST /api/assets.php` - Create new asset
- `PUT /api/assets.php?id={id}` - Update asset
- `DELETE /api/assets.php?id={id}` - Delete asset

### Supply Management Endpoints
- `GET /api/supplies.php` - Get all supplies
- `GET /api/supplies.php?id={id}` - Get specific supply
- `POST /api/supplies.php` - Create new supply
- `PUT /api/supplies.php?id={id}` - Update supply
- `DELETE /api/supplies.php?id={id}` - Delete supply
- `GET /api/supplies.php?action=transactions` - Get supply transactions
- `POST /api/supplies.php?action=transaction` - Create supply transaction

## Database Schema

### Core Tables
- `users` - System users and authentication
- `assets` - Asset/property registry
- `asset_categories` - Asset categorization
- `supplies` - Supply inventory items
- `supply_transactions` - Supply in/out transactions
- `custodians` - Property custodians
- `property_assignments` - Asset assignments to custodians
- `maintenance_schedules` - Maintenance scheduling
- `property_audits` - Audit records
- `procurement_requests` - Procurement management
- `system_logs` - Activity logging and audit trail

## Development

### Adding New Modules
1. Create a new component file in `components/`
2. Add the module route in `js/main.js`
3. Create corresponding API endpoints in `api/`
4. Update navigation in `components/sidebar.php`

### Customization
- **Styling**: Modify Tailwind classes or add custom CSS
- **Configuration**: Update `config/config.php` for system settings
- **Database**: Extend schema as needed for additional features

## Security Features
- Session-based authentication
- SQL injection prevention with PDO prepared statements
- CORS handling for API requests
- Input validation and sanitization
- Activity logging for audit trails

## Browser Compatibility
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## Contributing
1. Follow the existing code structure
2. Maintain consistent naming conventions
3. Document any new API endpoints
4. Test thoroughly before submitting changes

## Support
For technical support or feature requests, please refer to the project documentation or contact the development team.

