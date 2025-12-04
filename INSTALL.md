# Installation Guide

## 1. Database Setup
1. Create a new MySQL database (e.g., `pirnav_clone`).
2. Import the `pirnav_clone.sql` file (if you have the full dump).
3. Import `analytics.sql` to create the analytics tables.
4. Import `create_messages_table.sql` to create the messages table.
5. Import `add_user_krish.sql` to add the admin user.

## 2. Configuration
1. Open `includes/db.php`.
2. Update the database credentials:
   ```php
   $host = 'localhost';
   $db   = 'pirnav_clone';
   $user = 'your_db_user';
   $pass = 'your_db_password';
   ```

## 3. File Uploads
1. Upload all files to your web server (e.g., `public_html`).
2. Ensure the `assets/images/` directory and its subdirectories (`hero`, `services`, `clients`, `about`, `carousel`) are writable (permissions 755 or 777 depending on hosting).

## 4. Admin Panel
1. Go to `yourdomain.com/admin/login.php`.
2. Login with:
   - Username: `krish`
   - Password: `password` (or the one you set)

## 5. Analytics
1. The analytics script is automatically included in the footer.
2. Go to `yourdomain.com/admin/analytics.php` to view the dashboard.
