@echo off
chcp 65001 >nul
echo.
echo ================================================
echo   MCStore — XAMPP Auto Setup
echo ================================================
echo.

:: หา XAMPP จาก script location
set SCRIPT_DIR=%~dp0
:: ขึ้นไป 2 ระดับ (mcstore → htdocs → xampp)
for %%A in ("%SCRIPT_DIR%..\..") do set XAMPP_DIR=%%~fA
set APACHE_CONF=%XAMPP_DIR%\apache\conf\httpd.conf
set VHOSTS_CONF=%XAMPP_DIR%\apache\conf\extra\httpd-vhosts.conf
set HTDOCS=%XAMPP_DIR%\htdocs
set MCSTORE_PATH=%XAMPP_DIR%\htdocs\mcstore

echo [1/4] ตรวจสอบ XAMPP...
if not exist "%APACHE_CONF%" (
    echo ERROR: ไม่พบ XAMPP ที่ %XAMPP_DIR%
    echo กรุณาวางโฟลเดอร์ mcstore ไว้ใน C:\xampp\htdocs\
    pause & exit /b 1
)
echo       พบ XAMPP ที่ %XAMPP_DIR%

:: ─────────────────────────────────────────────
:: [2] Enable mod_rewrite ใน httpd.conf
:: ─────────────────────────────────────────────
echo [2/4] เปิดใช้ mod_rewrite...
findstr /C:"LoadModule rewrite_module" "%APACHE_CONF%" | findstr /V "#" >nul 2>&1
if errorlevel 1 (
    powershell -Command "(Get-Content '%APACHE_CONF%') -replace '#LoadModule rewrite_module','LoadModule rewrite_module' | Set-Content '%APACHE_CONF%'"
    echo       เปิด mod_rewrite แล้ว
) else (
    echo       mod_rewrite เปิดอยู่แล้ว
)

:: ─────────────────────────────────────────────
:: [3] Enable httpd-vhosts.conf ใน httpd.conf
:: ─────────────────────────────────────────────
echo [3/4] เปิดใช้ Virtual Hosts...
findstr /C:"Include conf/extra/httpd-vhosts.conf" "%APACHE_CONF%" | findstr /V "#" >nul 2>&1
if errorlevel 1 (
    powershell -Command "(Get-Content '%APACHE_CONF%') -replace '#Include conf/extra/httpd-vhosts.conf','Include conf/extra/httpd-vhosts.conf' | Set-Content '%APACHE_CONF%'"
    echo       เปิด Virtual Hosts แล้ว
) else (
    echo       Virtual Hosts เปิดอยู่แล้ว
)

:: ─────────────────────────────────────────────
:: [4] เพิ่ม VirtualHost ใน httpd-vhosts.conf
:: ─────────────────────────────────────────────
echo [4/4] ตั้งค่า VirtualHost...
findstr /C:"DocumentRoot" "%VHOSTS_CONF%" | findstr /C:"mcstore" >nul 2>&1
if errorlevel 1 (
    :: backup ไฟล์เดิมก่อน
    copy "%VHOSTS_CONF%" "%VHOSTS_CONF%.bak" >nul

    :: เพิ่ม VirtualHost block
    echo. >> "%VHOSTS_CONF%"
    echo ^<VirtualHost *:80^> >> "%VHOSTS_CONF%"
    echo     DocumentRoot "%MCSTORE_PATH%" >> "%VHOSTS_CONF%"
    echo     ServerName localhost >> "%VHOSTS_CONF%"
    echo     ^<Directory "%MCSTORE_PATH%"^> >> "%VHOSTS_CONF%"
    echo         Options Indexes FollowSymLinks >> "%VHOSTS_CONF%"
    echo         AllowOverride All >> "%VHOSTS_CONF%"
    echo         Require all granted >> "%VHOSTS_CONF%"
    echo     ^</Directory^> >> "%VHOSTS_CONF%"
    echo ^</VirtualHost^> >> "%VHOSTS_CONF%"

    echo       เพิ่ม VirtualHost สำหรับ mcstore แล้ว
) else (
    echo       VirtualHost มีอยู่แล้ว
)

:: ─────────────────────────────────────────────
:: Restart Apache
:: ─────────────────────────────────────────────
echo.
echo กำลัง restart Apache...
set APACHE_BIN=%XAMPP_DIR%\apache\bin\httpd.exe
if exist "%APACHE_BIN%" (
    taskkill /F /IM httpd.exe >nul 2>&1
    timeout /t 1 >nul
    start "" "%APACHE_BIN%"
    timeout /t 2 >nul
    echo Apache restart แล้ว
) else (
    echo.
    echo *** กรุณา restart Apache ใน XAMPP Control Panel ด้วยตนเอง ***
)

echo.
echo ================================================
echo   Setup เสร็จแล้ว!
echo   เปิดเบราว์เซอร์ไปที่: http://localhost/
echo ================================================
echo.
pause
