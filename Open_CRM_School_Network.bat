@echo off
title GD Goenka Admission CRM - School Network Launcher
color 0b

echo ============================================================
echo   GD GOENKA PUBLIC SCHOOL, PURNEA - ADMISSION CRM
echo   Detecting School Network Connection (LAN / Wi-Fi)...
echo ============================================================
echo.

:: Detect LAN IPv4 using PowerShell
for /f "tokens=*" %%i in ('powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias Ethernet, 'Wi-Fi' -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notlike '169.254*' -and $_.IPAddress -notlike '127*' } | Select-Object -ExpandProperty IPAddress -First 1)"') do set "LAN_IP=%%i"

if "%LAN_IP%"=="" (
    for /f "tokens=*" %%i in ('powershell -NoProfile -Command "[System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) | Where-Object { $_.AddressFamily -eq 'InterNetwork' -and $_.IPAddressToString -notlike '127.*' -and $_.IPAddressToString -notlike '169.254.*' } | Select-Object -ExpandProperty IPAddressToString -First 1"') do set "LAN_IP=%%i"
)

if "%LAN_IP%"=="" (
    set "TARGET_URL=http://localhost/ADMISSION-CRM-LEAD%%20FOLLOW%%20UP/"
    echo [INFO] No school LAN IP detected. Falling back to localhost.
) else (
    set "TARGET_URL=http://%LAN_IP%/ADMISSION-CRM-LEAD%%20FOLLOW%%20UP/"
    echo [SUCCESS] Active School Network IP: %LAN_IP%
)

echo.
echo ------------------------------------------------------------
echo   Opening in default browser:
echo   %TARGET_URL%
echo.
echo   Share this link with other school staff / counselors:
echo   %TARGET_URL%
echo ------------------------------------------------------------
echo.

start "" "%TARGET_URL%"

timeout /t 5
