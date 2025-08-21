@echo off
echo Fixing XAMPP MySQL Connection Issues...
echo.

echo [1/4] Stopping all XAMPP processes...
taskkill /F /IM "httpd.exe" >nul 2>&1
taskkill /F /IM "mysqld.exe" >nul 2>&1
timeout /t 3 >nul

echo [2/4] Starting Apache...
start "" "C:\xampp\apache_start.bat"
timeout /t 5 >nul

echo [3/4] Starting MySQL without password protection...
cd /d "C:\xampp\mysql\bin"
start "" mysqld.exe --skip-grant-tables --skip-networking
timeout /t 10 >nul

echo [4/4] Resetting MySQL root password...
mysql.exe -u root -e "USE mysql; UPDATE user SET authentication_string='' WHERE User='root'; UPDATE user SET plugin='mysql_native_password' WHERE User='root'; FLUSH PRIVILEGES;"
timeout /t 3 >nul

echo.
echo MySQL has been reset. Restarting services...
taskkill /F /IM "mysqld.exe" >nul 2>&1
timeout /t 3 >nul

start "" "C:\xampp\mysql_start.bat"
timeout /t 5 >nul

echo.
echo ============================================
echo XAMPP MySQL Fix Complete!
echo.
echo You can now:
echo 1. Open phpMyAdmin: http://localhost/phpmyadmin
echo 2. Login with username: root (no password)
echo 3. Create the maternal_healthcare database
echo ============================================
pause
