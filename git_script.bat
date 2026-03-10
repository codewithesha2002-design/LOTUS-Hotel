@echo off
git status
git add .
git commit -m "Update by AI Assistant"
git remote remove origin
git remote add origin https://github.com/codewithesha2002-design/LOTUS-Hotel.git
git branch -M main
git push -u origin main
