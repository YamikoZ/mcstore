@echo off
echo.
echo ================================================
echo   MCStore - XAMPP Auto Setup
echo ================================================
echo.

set XAMPP_DIR=C:\xampp
set APACHE_CONF=%XAMPP_DIR%\apache\conf\httpd.conf
set VHOSTS_CONF=%XAMPP_DIR%\apache\conf\extra\httpd-vhosts.conf
set MCSTORE_PATH=%XAMPP_DIR%\htdocs

echo [1/3] Checking XAMPP...
if not exist "%APACHE_CONF%" (
    echo ERROR: XAMPP not found at %XAMPP_DIR%
    pause & exit /b 1
)
echo       Found XAMPP at %XAMPP_DIR%

echo [2/3] Enabling mod_rewrite...
powershell -Command "(Get-Content '%APACHE_CONF%') -replace '#LoadModule rewrite_module','LoadModule rewrite_module' | Set-Content '%APACHE_CONF%'"
echo       mod_rewrite enabled

echo [3/3] Enabling Virtual Hosts...
powershell -Command "(Get-Content '%APACHE_CONF%') -replace '#Include conf/extra/httpd-vhosts.conf','Include conf/extra/httpd-vhosts.conf' | Set-Content '%APACHE_CONF%'"
echo       Virtual Hosts enabled

echo.
echo Checking VirtualHost config...
findstr /C:"mcstore" "%VHOSTS_CONF%" >nul 2>&1
if errorlevel 1 (
    echo Adding VirtualHost...
    echo. >> "%VHOSTS_CONF%"
    echo ^<VirtualHost *:80^> >> "%VHOSTS_CONF%"
    echo     DocumentRoot "%MCSTORE_PATH%" >> "%VHOSTS_CONF%"
    echo     ServerName localhost >> "%VHOSTS_CONF%"
    echo     ^<Directory "%MCSTORE_PATH%"^> >> "%VHOSTS_CONF%"
    echo         Options FollowSymLinks >> "%VHOSTS_CONF%"
    echo         AllowOverride All >> "%VHOSTS_CONF%"
    echo         Require all granted >> "%VHOSTS_CONF%"
    echo     ^</Directory^> >> "%VHOSTS_CONF%"
    echo ^</VirtualHost^> >> "%VHOSTS_CONF%"
    echo       VirtualHost added
) else (
    echo       VirtualHost already exists
)

echo.
echo Restarting Apache...
taskkill /F /IM httpd.exe >nul 2>&1
timeout /t 2 >nul
start "" "%XAMPP_DIR%\apache\bin\httpd.exe"
timeout /t 2 >nul

echo.
echo ================================================
echo   Done! Open browser: http://localhost/
echo ================================================
echo.
pause
