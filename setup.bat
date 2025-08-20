@echo off
echo ============================================
echo Maternal Healthcare Tracker - Setup Script
echo ============================================
echo.

echo [1/4] Checking XAMPP installation...
if not exist "C:\xampp\htdocs\" (
    echo ERROR: XAMPP not found at C:\xampp\
    echo Please install XAMPP from https://www.apachefriends.org/
    echo and make sure Apache and MySQL are running.
    pause
    exit /b 1
)
echo XAMPP directory found.
echo.

echo [2/4] Creating application directory...
if not exist "C:\xampp\htdocs\maternal_healthcare_tracker\" (
    mkdir "C:\xampp\htdocs\maternal_healthcare_tracker"
)
echo Application directory ready.
echo.

echo [3/4] Copying application files...
xcopy /E /I /Y "%~dp0*" "C:\xampp\htdocs\maternal_healthcare_tracker\"
echo Files copied successfully.
echo.

echo [4/4] Setup Instructions:
echo.
echo 1. Start XAMPP Control Panel
echo 2. Start Apache and MySQL services
echo 3. Open your web browser and go to: http://localhost/phpmyadmin
echo 4. Create a new database named: maternal_healthcare
echo 5. Import the SQL file: database/schema.sql
echo 6. Open: http://localhost/maternal_healthcare_tracker
echo.
echo Default Login Credentials:
echo - Admin: admin / admin123
echo - Doctor: dr_sharma / doctor123
echo - Register new patient through registration page
echo.
echo ============================================
echo Setup completed! Follow the instructions above.
echo ============================================
pause
