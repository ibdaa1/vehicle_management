# Vehicle Movements Enhancement - Implementation Guide

## Overview
This pull request implements enhancements to the vehicle movements system with new features for GPS coordinates, photo uploads, and permission-based access controls.

## 📋 Features Implemented

### 1. GPS Coordinates Tracking
- **Pull Coordinates**: Uses browser's Geolocation API to capture current location
- **Manual Entry**: Users can manually enter latitude/longitude
- **Save to Database**: Coordinates are stored with each movement
- **Permission**: Available to movement owner and admin/super-admin

### 2. Photo Upload System
- **Multiple Photos**: Upload multiple photos per movement
- **Preview**: See photo previews before uploading
- **Validation**: File type (JPEG, PNG, GIF) and size (5MB max) validation
- **Storage**: Photos stored in `/uploads/vehicle_movements/`
- **Database Tracking**: Metadata stored in `vehicle_movement_photos` table
- **Permission**: Available to movement owner and admin/super-admin

### 3. Movement Details Modal
- **Comprehensive View**: Shows vehicle info, coordinates, notes, and photos
- **Edit Capabilities**: Update coordinates, add photos, add notes
- **Responsive Design**: Works on desktop and mobile devices
- **RTL Support**: Proper right-to-left layout for Arabic

### 4. Permission-Based Access
- **Normal Users**: Can view/edit their own checked-out vehicles
- **Admin/Super-Admin**: Can view/edit all movements
- **Return Button**: Only visible to admin/super-admin users

## 🚀 Installation

### Step 1: Database Setup

Run the migration script to create necessary tables and columns:

```bash
mysql -u your_username -p your_database < database_migration.sql
```

Or execute the SQL manually:

```sql
-- Add coordinate columns
ALTER TABLE vehicle_movements 
ADD COLUMN latitude DECIMAL(10, 8) NULL,
ADD COLUMN longitude DECIMAL(11, 8) NULL;

-- Create photos table
CREATE TABLE IF NOT EXISTS vehicle_movement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_id INT NULL,
    vehicle_code VARCHAR(50) NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    taken_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    INDEX idx_vehicle_code (vehicle_code),
    INDEX idx_taken_by (taken_by),
    INDEX idx_movement_id (movement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: File System Setup

Create the uploads directory with proper permissions:

```bash
# Create directory
mkdir -p /path/to/vehicle_management/uploads/vehicle_movements

# Set permissions (web server needs write access)
chmod 755 /path/to/vehicle_management/uploads/vehicle_movements

# If using Apache with www-data user:
chown www-data:www-data /path/to/vehicle_management/uploads/vehicle_movements
```

### Step 3: PHP Configuration (Optional)

Ensure PHP is configured to allow file uploads of up to 5MB:

```ini
# In php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
```

Restart your web server after making changes.

### Step 4: Deploy Code

Deploy all changed files:
- `api/vehicle/upload.php` (NEW)
- `api/vehicle/update_movement_coords.php` (NEW)
- `public/vehicle_movements.html` (MODIFIED)
- `assets/js/vehicle_movements.js` (MODIFIED)
- `assets/css/vehicle_movements.css` (MODIFIED)
- `languages/ar_vehicle_movements.json` (MODIFIED)
- `languages/en_vehicle_movements.json` (MODIFIED)

## 🧪 Testing

See `TESTING_GUIDE.md` for comprehensive testing procedures.

### Quick Smoke Test

1. **Login** as a normal user
2. **Pickup** a vehicle
3. **Click** the "Details" button on your checked-out vehicle
4. **Verify** the modal opens
5. **Click** "Pull Coordinates" and allow location access
6. **Verify** coordinates populate
7. **Click** "Save Coordinates"
8. **Verify** success message
9. **Select** a photo and upload
10. **Verify** photo appears

## 🔒 Security Features

- ✅ Session-based authentication
- ✅ Permission checks on backend
- ✅ Prepared SQL statements (no SQL injection)
- ✅ File type and size validation
- ✅ MIME type verification
- ✅ Secure file naming
- ✅ Directory traversal protection

## 📁 File Structure

```
vehicle_management/
├── api/
│   └── vehicle/
│       ├── upload.php                      # NEW - Photo upload handler
│       ├── update_movement_coords.php      # NEW - Coordinate save handler
│       └── get_vehicle_movements.php       # MODIFIED - Enhanced permissions
├── public/
│   └── vehicle_movements.html              # MODIFIED - Added modal
├── assets/
│   ├── js/
│   │   └── vehicle_movements.js            # MODIFIED - Modal & upload logic
│   └── css/
│       └── vehicle_movements.css           # MODIFIED - Modal styles
├── languages/
│   ├── ar_vehicle_movements.json           # MODIFIED - Arabic translations
│   └── en_vehicle_movements.json           # MODIFIED - English translations
├── uploads/
│   └── vehicle_movements/                  # NEW - Photo storage
│       └── README.md
├── database_migration.sql                  # NEW - Database setup script
├── TESTING_GUIDE.md                        # NEW - Testing procedures
└── IMPLEMENTATION_GUIDE.md                 # NEW - This file
```

## 🌐 API Endpoints

### Upload Photos
```
POST /vehicle_management/api/vehicle/upload.php
Content-Type: multipart/form-data

Parameters:
- photos[] (file, required): Array of image files
- vehicle_code (string, required): Vehicle code
- movement_id (int, optional): Movement ID
- notes (string, optional): Notes about photos

Returns:
{
  "success": true,
  "message": "2 photo(s) uploaded successfully",
  "uploaded": [...],
  "errors": [],
  "total_uploaded": 2,
  "total_errors": 0
}
```

### Update Coordinates
```
POST /vehicle_management/api/vehicle/update_movement_coords.php
Content-Type: application/json

Parameters:
{
  "movement_id": 123,
  "vehicle_code": "ABC123",
  "latitude": 25.2048,
  "longitude": 55.2708
}

Returns:
{
  "success": true,
  "message": "Coordinates updated successfully",
  "data": {
    "movement_id": 123,
    "vehicle_code": "ABC123",
    "latitude": 25.2048,
    "longitude": 55.2708,
    "updated_at": "2024-01-01 12:00:00"
  }
}
```

## 🌍 Browser Requirements

- **Geolocation API**: Chrome 5+, Firefox 3.5+, Safari 5+, Edge 12+
- **File API**: Chrome 13+, Firefox 3.6+, Safari 6+, Edge 12+
- **HTTPS**: Required for Geolocation API to work

## 🔧 Configuration

### File Upload Limits
Edit `api/vehicle/upload.php`:
```php
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // Change to desired size
```

### Allowed File Types
Edit `api/vehicle/upload.php`:
```php
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
// Add more types as needed
```

### Coordinate Validation
Edit `api/vehicle/update_movement_coords.php`:
```php
define('MIN_LATITUDE', -90);
define('MAX_LATITUDE', 90);
define('MIN_LONGITUDE', -180);
define('MAX_LONGITUDE', 180);
```

## 📊 Database Schema

### vehicle_movements (Modified)
```sql
ALTER TABLE vehicle_movements 
ADD COLUMN latitude DECIMAL(10, 8) NULL,
ADD COLUMN longitude DECIMAL(11, 8) NULL;
```

### vehicle_movement_photos (New)
```sql
CREATE TABLE vehicle_movement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_id INT NULL,
    vehicle_code VARCHAR(50) NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    taken_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL
);
```

## ⚠️ Troubleshooting

### Photos Not Uploading
1. Check directory permissions: `ls -la uploads/vehicle_movements`
2. Check PHP error log: `tail -f /var/log/php/error.log`
3. Verify PHP upload settings: `php -i | grep upload`
4. Check browser console for errors

### Geolocation Not Working
1. Ensure site is served over HTTPS
2. Check browser permissions
3. Verify browser console for errors
4. Test in different browsers

### Return Button Not Showing for Admin
1. Verify user role_id in database
2. Check can_view_all_vehicles flag in roles table
3. Verify session data: `print_r($_SESSION['user'])`

## 📝 Changelog

### Version 1.0.0 (Current)
- ✨ Added GPS coordinates capture and storage
- ✨ Added multi-photo upload system
- ✨ Added movement details modal
- ✨ Added permission-based access controls
- ✨ Added comprehensive translations (AR/EN)
- 🐛 Fixed permission enforcement
- 🔒 Enhanced security with validation
- 📚 Added comprehensive documentation

## 🤝 Contributing

When making changes:
1. Follow existing code style
2. Use prepared statements for SQL
3. Add translations for new strings
4. Update tests as needed
5. Document API changes

## 📄 License

This is part of the vehicle_management system. Follow the project's license.

## 💬 Support

For issues or questions:
1. Check TESTING_GUIDE.md
2. Review error logs
3. Check browser console
4. Contact system administrator

---

**Last Updated**: December 2024
**Author**: Copilot for ibdaa1/vehicle_management
