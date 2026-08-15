@echo off
cd /d "e:\PROJECTS\PROJECT ARS\ARS"
git add vercel.json
git commit -m "fix: Expand Vercel includeFiles to bundle all PHP files for serverless compatibility"
git push origin main
echo.
echo === Done! Vercel is redeploying. Check vercel.com in 1-2 minutes ===
pause
