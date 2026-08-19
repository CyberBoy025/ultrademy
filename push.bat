@echo off
REM ===========================================================
REM  Ultrademy - stage, commit and push everything
REM  Usage:  push.bat "your commit message"
REM  If no message is given, a timestamped one is used.
REM ===========================================================

setlocal

set MSG=%~1
if "%MSG%"=="" set MSG=update %DATE% %TIME%

git add -A

git diff --cached --quiet
if not errorlevel 1 (
    echo Nothing to commit - working tree matches the last commit.
    goto :push
)

git commit -m "%MSG%"
if errorlevel 1 (
    echo Commit failed.
    exit /b 1
)

:push
git push -u origin main
if errorlevel 1 (
    echo.
    echo Push failed. Common causes:
    echo   - The GitHub repo does not exist yet ^(create it at https://github.com/new^)
    echo   - You have not signed in to git yet - a browser window should appear,
    echo     or run: git config --global credential.helper manager
    exit /b 1
)

echo.
echo Pushed successfully.
endlocal
