@echo off
title GD Goenka CRM - Real-Time Instant File Synchronizer
color 0b
echo ==============================================================
echo   GD GOENKA PUBLIC SCHOOL CRM - LIVE INSTANT SYNCHRONIZER
echo ==============================================================
echo.
echo Watching:
echo   D:\SHAMSHER-PHOTO\ADMISSION-CRM-LEAD FOLLOW UP
echo   and
echo   C:\xampp\htdocs\ADMISSION-CRM-LEAD FOLLOW UP
echo.
echo Whenever you edit ANY file in D: or C:, it updates INSTANTLY!
echo Refresh your browser to see immediate changes.
echo.
echo (Keep this small window open while developing)
echo ==============================================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\watch_sync.ps1"
pause
