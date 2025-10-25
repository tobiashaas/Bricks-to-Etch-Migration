@echo off
REM Fix symlinks for renamed plugin in both WordPress instances
REM Must be run as Administrator!

echo ========================================
echo Fixing Symlinks for Etch Fusion Suite
echo ========================================
echo.

set TARGET_PATH=C:\Github\Bricks2Etch\etch-fusion-suite

REM Bricks Instance
echo Processing: Bricks (Source)
echo ----------------------------------------
set BRICKS_PLUGINS=C:\Users\haast\Local Sites\bricks\app\public\wp-content\plugins

if exist "%BRICKS_PLUGINS%\bricks-etch-migration" (
    echo Removing old symlink: bricks-etch-migration
    rmdir "%BRICKS_PLUGINS%\bricks-etch-migration"
    echo [OK] Old symlink removed
) else (
    echo Old symlink not found
)

if exist "%BRICKS_PLUGINS%\etch-fusion-suite" (
    echo [OK] New symlink already exists
) else (
    echo Creating new symlink: etch-fusion-suite
    mklink /D "%BRICKS_PLUGINS%\etch-fusion-suite" "%TARGET_PATH%"
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Symlink created successfully
    ) else (
        echo [ERROR] Failed to create symlink. Run as Administrator!
    )
)

echo.

REM Etch Instance
echo Processing: Etch (Target)
echo ----------------------------------------
set ETCH_PLUGINS=C:\Users\haast\Local Sites\etch\app\public\wp-content\plugins

if exist "%ETCH_PLUGINS%\bricks-etch-migration" (
    echo Removing old symlink: bricks-etch-migration
    rmdir "%ETCH_PLUGINS%\bricks-etch-migration"
    echo [OK] Old symlink removed
) else (
    echo Old symlink not found
)

if exist "%ETCH_PLUGINS%\etch-fusion-suite" (
    echo [OK] New symlink already exists
) else (
    echo Creating new symlink: etch-fusion-suite
    mklink /D "%ETCH_PLUGINS%\etch-fusion-suite" "%TARGET_PATH%"
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Symlink created successfully
    ) else (
        echo [ERROR] Failed to create symlink. Run as Administrator!
    )
)

echo.
echo ========================================
echo Done! Both instances updated.
echo ========================================
echo.
pause
