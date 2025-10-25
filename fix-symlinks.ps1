# Fix symlinks for renamed plugin in both WordPress instances

$targetPath = "C:\Github\Bricks2Etch\etch-fusion-suite"
$instances = @(
    @{
        Name = "Bricks (Source)"
        Path = "C:\Users\haast\Local Sites\bricks\app\public\wp-content\plugins"
    },
    @{
        Name = "Etch (Target)"
        Path = "C:\Users\haast\Local Sites\etch\app\public\wp-content\plugins"
    }
)

foreach ($instance in $instances) {
    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host "Processing: $($instance.Name)" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    
    $pluginPath = $instance.Path
    $oldSymlink = Join-Path $pluginPath "bricks-etch-migration"
    $newSymlink = Join-Path $pluginPath "etch-fusion-suite"
    
    Write-Host "Plugin directory: $pluginPath"
    
    # Check if plugin directory exists
    if (-not (Test-Path $pluginPath)) {
        Write-Host "WARNING: Plugin directory does not exist!" -ForegroundColor Yellow
        continue
    }
    
    # Check if old symlink exists
    if (Test-Path $oldSymlink) {
        $item = Get-Item $oldSymlink
        if ($item.LinkType -eq 'SymbolicLink') {
            Write-Host "Found old symlink: $oldSymlink" -ForegroundColor Yellow
            Write-Host "Target: $($item.Target)"
            
            # Remove old symlink
            Remove-Item $oldSymlink -Force
            Write-Host "✓ Removed old symlink" -ForegroundColor Green
        } else {
            Write-Host "WARNING: 'bricks-etch-migration' exists but is not a symlink!" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Old symlink not found (already removed or never existed)"
    }
    
    # Check if new symlink already exists
    if (Test-Path $newSymlink) {
        $item = Get-Item $newSymlink
        if ($item.LinkType -eq 'SymbolicLink') {
            Write-Host "✓ New symlink already exists: $newSymlink" -ForegroundColor Green
            Write-Host "Target: $($item.Target)"
        } else {
            Write-Host "WARNING: 'etch-fusion-suite' exists but is not a symlink!" -ForegroundColor Yellow
        }
    } else {
        # Create new symlink
        Write-Host "Creating new symlink: $newSymlink -> $targetPath" -ForegroundColor Yellow
        try {
            New-Item -ItemType SymbolicLink -Path $newSymlink -Target $targetPath -ErrorAction Stop | Out-Null
            Write-Host "✓ Symlink created successfully!" -ForegroundColor Green
        } catch {
            Write-Host "ERROR: Failed to create symlink: $_" -ForegroundColor Red
            Write-Host "Make sure you're running PowerShell as Administrator!" -ForegroundColor Red
        }
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Done! Both instances updated." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
