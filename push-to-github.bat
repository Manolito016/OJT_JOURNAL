@echo off
echo ================================================
echo  Pushing OJT Journal to GitHub
echo ================================================
echo.

cd /d "%~dp0"

echo Setting up Git user...
git config user.name "Manolito016"
git config user.email "Manolito016@users.noreply.github.com"

echo.
echo Adding all files...
git add .

echo.
echo Creating initial commit...
git commit -m "Initial commit: OJT AI Journal Report Generator"

echo.
echo Connecting to GitHub repository...
git remote add origin https://github.com/Manolito016/OJT_JOURNAL.git

echo.
echo Setting main branch...
git branch -M main

echo.
echo Pushing to GitHub...
git push -u origin main

echo.
echo ================================================
echo  Done! Visit your repository:
echo  https://github.com/Manolito016/OJT_JOURNAL
echo ================================================
echo.
pause
