@echo off
setlocal

set PLUGIN=modulux-shipping-helper
set VERSION=1.0.0
set ZIP=%PLUGIN%-%VERSION%.zip
set TEMP_DIR=%PLUGIN%-temp

echo Removing old ZIP if exists...
if exist "%ZIP%" del "%ZIP%"

echo Creating temporary folder...
if exist "%TEMP_DIR%" rmdir /s /q "%TEMP_DIR%"
xcopy "%PLUGIN%" "%TEMP_DIR%" /E /I /H >nul

echo Removing excluded files...
powershell -Command ^
    "Get-ChildItem -Path '%TEMP_DIR%' -Recurse | Where-Object { " ^
        "$_.FullName -match '\\.git' -or " ^
        "$_.FullName -match 'node_modules' -or " ^
        "$_.Name -eq '.DS_Store' -or " ^
        "$_.Name -eq 'README.md' -or " ^
        "$_.Name -eq 'deploy.bat' -or " ^
        "$_.Name -eq 'deploy.sh' -or " ^
        "$_.FullName -like '*assets\*.png' " ^
    "} | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue"

echo Creating plugin ZIP...
powershell -Command "Compress-Archive -Path '%TEMP_DIR%\*' -DestinationPath '%ZIP%' -Force"

echo Cleaning up...
rmdir /s /q "%TEMP_DIR%"

echo Done. Plugin packaged as: %ZIP%
pause
