@echo off
echo ============================================
echo Maternal Healthcare Tracker - Database Setup
echo ============================================
echo.

echo [1/3] Creating database directly via MySQL command line...
cd /d "C:\xampp\mysql\bin"

echo Creating maternal_healthcare database...
mysql.exe -u root -e "DROP DATABASE IF EXISTS maternal_healthcare; CREATE DATABASE maternal_healthcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %errorlevel% == 0 (
    echo ✅ Database created successfully!
) else (
    echo ❌ Error creating database. Check if MySQL is running.
    pause
    exit /b 1
)

echo.
echo [2/3] Importing database schema...
mysql.exe -u root maternal_healthcare < "C:\xampp\htdocs\maternal_healthcare_tracker\database\schema.sql"

if %errorlevel% == 0 (
    echo ✅ Database schema imported successfully!
) else (
    echo ❌ Error importing schema.
    pause
    exit /b 1
)

echo.
echo [3/3] Verifying database setup...
mysql.exe -u root -e "USE maternal_healthcare; SHOW TABLES;"

echo.
echo ============================================
echo ✅ DATABASE SETUP COMPLETE!
echo.
echo Your Maternal Healthcare Tracker is ready!
echo.
echo 🌐 Access your application:
echo    http://localhost/maternal_healthcare_tracker
echo.
echo 🔑 Default Login Credentials:
echo    Admin: admin / admin123
echo    Doctor: dr_sharma / doctor123
echo    Patient: Register new account
echo.
echo 📊 phpMyAdmin:
echo    http://localhost/phpmyadmin
echo    Username: root (no password)
echo ============================================
pause
