@echo off
REM ===========================================================
REM  Ultrademy - one-time GitHub setup
REM  Run this ONCE from C:\xampp\htdocs\ultra
REM  Usage:  git-setup.bat  YOUR_GITHUB_USERNAME
REM ===========================================================

if "%~1"=="" (
    echo Usage: git-setup.bat YOUR_GITHUB_USERNAME
    echo Example: git-setup.bat somorinfavour2002
    exit /b 1
)

set USERNAME=%~1

git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo Initialising repository...
    git init -b main
) else (
    echo Repository already initialised.
)

git remote remove origin >nul 2>&1
git remote add origin https://github.com/%USERNAME%/ultrademy.git

echo.
echo Remote set to: https://github.com/%USERNAME%/ultrademy.git
echo.
echo Next:
echo   1. Create an empty repo named "ultrademy" at https://github.com/new
echo      (no README, no .gitignore, no licence - keep it bare)
echo   2. Run: push.bat "initial scaffold"
echo.
