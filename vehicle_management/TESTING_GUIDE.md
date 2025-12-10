# Vehicle Movements Enhancement - Testing Guide

## Overview
This document describes the testing procedures for the vehicle movements enhancements including coordinates, photo uploads, and permission-based features.

## Prerequisites

### 1. Database Setup
Execute the following SQL to add required tables and columns:

```sql
-- Add coordinate columns to vehicle_movements table
ALTER TABLE vehicle_movements 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL COMMENT 'GPS latitude coordinate',
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL COMMENT 'GPS longitude coordinate';

-- Create vehicle_movement_photos table
CREATE TABLE IF NOT EXISTS vehicle_movement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_id INT NULL COMMENT 'Reference to vehicle_movements.id if available',
    vehicle_code VARCHAR(50) NOT NULL COMMENT 'Vehicle code for the movement',
    photo_url VARCHAR(255) NOT NULL COMMENT 'Relative path to the uploaded photo',
    taken_by VARCHAR(50) NOT NULL COMMENT 'emp_id of the user who uploaded',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL COMMENT 'Optional notes about the photo',
    INDEX idx_vehicle_code (vehicle_code),
    INDEX idx_taken_by (taken_by),
    INDEX idx_movement_id (movement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Directory Permissions
Ensure the uploads directory is writable:
```bash
chmod 755 /path/to/vehicle_management/uploads/vehicle_movements
```

### 3. User Roles
Test with different user roles:
- **Normal User**: Regular employee (no special permissions)
- **Admin**: User with role_id = 2 (can_view_all_vehicles = true)
- **Super Admin**: User with role_id = 1 (can_view_all_vehicles = true)

## Test Cases

### Test 1: View Movement Details (Normal User)
**Purpose**: Verify normal users can view details for their own checked-out vehicles

**Steps**:
1. Login as a normal user
2. Pickup a vehicle (if not already checked out)
3. Click the "Details" button on your checked-out vehicle
4. Verify the modal opens with vehicle information

**Expected Results**:
- Modal opens successfully
- Vehicle code and movement info displayed
- Coordinate fields are visible
- "Pull Coordinates" button is visible
- "Save Coordinates" button is visible
- Photo upload section is visible
- "Return Vehicle" button is **NOT visible** (admin only)

### Test 2: View Movement Details (Admin)
**Purpose**: Verify admins can view details for any checked-out vehicle

**Steps**:
1. Login as admin/super admin
2. Find any checked-out vehicle (by any user)
3. Click the "Details" button
4. Verify the modal opens

**Expected Results**:
- Modal opens successfully
- All information is displayed
- All buttons are visible including "Return Vehicle" button

### Test 3: Pull GPS Coordinates
**Purpose**: Verify geolocation functionality

**Steps**:
1. Open movement details modal
2. Click "Pull Coordinates" button
3. Allow browser location permission when prompted
4. Wait for coordinates to populate

**Expected Results**:
- Browser requests location permission
- After granting permission, latitude and longitude fields populate with decimal values
- Success message is displayed
- Button returns to normal state

**Error Cases**:
- If permission denied: Error message displayed
- If geolocation not supported: Error message displayed

### Test 4: Save Coordinates
**Purpose**: Verify coordinate saving functionality

**Steps**:
1. Open movement details modal
2. Either pull coordinates or manually enter values:
   - Latitude: 25.2048 (example: Dubai)
   - Longitude: 55.2708
3. Click "Save Coordinates" button
4. Wait for confirmation

**Expected Results**:
- Success message displayed
- Coordinates saved to database
- No errors in console

**Validation**:
```sql
SELECT id, vehicle_code, latitude, longitude, movement_datetime 
FROM vehicle_movements 
WHERE latitude IS NOT NULL 
ORDER BY movement_datetime DESC LIMIT 10;
```

### Test 5: Photo Upload (Single Photo)
**Purpose**: Verify single photo upload

**Steps**:
1. Open movement details modal
2. Click "Choose Photos" button
3. Select one image file (JPEG, PNG, or GIF)
4. Verify preview appears
5. Click "Upload Photos" button
6. Wait for completion

**Expected Results**:
- Selected photo preview displays
- "Upload Photos" button becomes visible
- Upload completes successfully
- Success message shows "1 photo(s) uploaded successfully"
- Photo appears in existing photos section

**Validation**:
```sql
SELECT * FROM vehicle_movement_photos 
WHERE vehicle_code = 'YOUR_VEHICLE_CODE' 
ORDER BY created_at DESC;
```

Check that:
- File exists at the path in `photo_url`
- `taken_by` matches the current user's emp_id
- `vehicle_code` is correct

### Test 6: Photo Upload (Multiple Photos)
**Purpose**: Verify multiple photo upload

**Steps**:
1. Open movement details modal
2. Click "Choose Photos" button
3. Select multiple image files (2-5 photos)
4. Verify all previews appear
5. Click "Upload Photos" button

**Expected Results**:
- All selected photos show previews
- Upload completes for all photos
- Success message shows correct count
- All photos appear in existing photos section

### Test 7: Photo Removal Before Upload
**Purpose**: Verify removing photos from selection

**Steps**:
1. Select multiple photos
2. Click the "×" button on one preview
3. Verify photo is removed from selection
4. Upload remaining photos

**Expected Results**:
- Selected photo is removed from preview
- File count decreases
- Only remaining photos are uploaded

### Test 8: Photo Upload Validation
**Purpose**: Verify file type and size validation

**Steps**:
1. Try uploading a non-image file (e.g., .txt, .pdf)
2. Try uploading a very large image (>5MB)

**Expected Results**:
- Non-image files are rejected with error message
- Large files are rejected with size limit error
- Valid photos proceed normally

### Test 9: Return Vehicle (Admin Only)
**Purpose**: Verify return button permission

**Steps as Normal User**:
1. Login as normal user
2. Open movement details for your vehicle
3. Verify "Return Vehicle" button is **NOT visible**

**Steps as Admin**:
1. Login as admin
2. Open movement details for any checked-out vehicle
3. Verify "Return Vehicle" button **IS visible**
4. Click the button
5. Confirm the action
6. Verify vehicle is returned

**Expected Results**:
- Normal users cannot see return button
- Admins can see and use return button
- Return operation completes successfully
- Vehicle status updates to available

### Test 10: Permission Check - Photo Upload
**Purpose**: Verify users can only upload photos for their own movements or as admin

**Steps**:
1. As User A, pickup a vehicle
2. Logout and login as User B (non-admin)
3. Try to access User A's movement details
4. Verify User B cannot see the details button

**Expected Results**:
- Non-admin users cannot view other users' movements
- Photo upload permission is enforced server-side

### Test 11: Cross-Language Testing
**Purpose**: Verify translations work correctly

**Steps**:
1. Switch language to English
2. Open movement details
3. Verify all labels are in English
4. Switch to Arabic
5. Verify all labels are in Arabic

**Expected Results**:
- All UI elements translate correctly
- Buttons show proper text in both languages
- Error messages appear in selected language

### Test 12: Mobile Responsiveness
**Purpose**: Verify modal works on mobile devices

**Steps**:
1. Open page on mobile device or use browser dev tools
2. Open movement details modal
3. Test all functionality

**Expected Results**:
- Modal is fully visible and scrollable
- Buttons are appropriately sized
- Form inputs are accessible
- Photo selection works with mobile camera

## API Testing

### Test Upload API Directly
```bash
# Test photo upload
curl -X POST http://your-domain/vehicle_management/api/vehicle/upload.php \
  -F "vehicle_code=ABC123" \
  -F "movement_id=1" \
  -F "notes=Test upload" \
  -F "photos[]=@/path/to/image1.jpg" \
  -F "photos[]=@/path/to/image2.jpg" \
  --cookie "PHPSESSID=your_session_id"
```

### Test Coordinates API Directly
```bash
# Test coordinate update
curl -X POST http://your-domain/vehicle_management/api/vehicle/update_movement_coords.php \
  -H "Content-Type: application/json" \
  -d '{"movement_id":1,"latitude":25.2048,"longitude":55.2708}' \
  --cookie "PHPSESSID=your_session_id"
```

## Performance Testing

### File Upload Performance
- Test uploading 1 file: Should complete < 2 seconds
- Test uploading 5 files: Should complete < 10 seconds
- Test uploading 10 files: Should complete < 20 seconds

### Coordinate Accuracy
- Compare GPS coordinates with known locations
- Verify decimal precision (8 decimal places for latitude, 11 for longitude)

## Security Testing

### Test Permission Enforcement
1. Try accessing upload API without authentication
2. Try updating coordinates for another user's movement
3. Try SQL injection in form inputs
4. Try uploading malicious files (scripts, executables)

**Expected Results**:
- All unauthorized requests are blocked with 401/403
- SQL injection attempts are prevented by prepared statements
- Only image files are accepted
- File content is validated, not just extension

## Common Issues and Troubleshooting

### Issue: Photos not uploading
**Check**:
- Directory permissions (should be 755)
- PHP upload_max_filesize and post_max_size settings
- File type validation in upload.php
- Browser console for JavaScript errors

### Issue: Geolocation not working
**Check**:
- HTTPS is required for geolocation API
- Browser permissions for location access
- Browser compatibility (Chrome, Firefox, Safari, Edge)

### Issue: Modal not opening
**Check**:
- JavaScript console for errors
- Ensure vehicle has active checkout
- User has permission to view

### Issue: Return button not showing for admin
**Check**:
- User role in database
- can_view_all_vehicles flag in roles table
- Session data correctness

## Success Criteria

All tests should pass with:
- ✅ No JavaScript errors in console
- ✅ No PHP errors in server logs
- ✅ All permissions correctly enforced
- ✅ Data correctly saved to database
- ✅ Files correctly saved to filesystem
- ✅ Translations working in both languages
- ✅ Responsive on all screen sizes
- ✅ No security vulnerabilities

## Post-Deployment Validation

After deploying to production:
1. Verify database migrations applied
2. Check directory permissions
3. Test with real user accounts
4. Monitor error logs for 24-48 hours
5. Collect user feedback
