# Update all references from bricks-etch-migration to etch-fusion-suite

$files = @(
    "test-environment\README.md",
    "test-environment\scripts\*.sh",
    "tests\*.sh",
    "tests\*.php",
    ".github\workflows\*.yml",
    "README.md"
)

foreach ($pattern in $files) {
    Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | ForEach-Object {
        $file = $_.FullName
        $content = Get-Content $file -Raw -ErrorAction SilentlyContinue
        
        if ($content) {
            # Replace folder references
            $content = $content -replace 'bricks-etch-migration', 'etch-fusion-suite'
            $content = $content -replace 'bricks_etch_migration', 'etch_fusion_suite'
            $content = $content -replace 'BRICKS_ETCH_MIGRATION', 'ETCH_FUSION_SUITE'
            
            # Replace old plugin references in wp-cli commands
            $content = $content -replace 'plugin activate bricks-etch-migration', 'plugin activate etch-fusion-suite'
            $content = $content -replace 'plugin deactivate bricks-etch-migration', 'plugin deactivate etch-fusion-suite'
            $content = $content -replace 'plugin is-active bricks-etch-migration', 'plugin is-active etch-fusion-suite'
            $content = $content -replace 'plugin get bricks-etch-migration', 'plugin get etch-fusion-suite'
            
            Set-Content -Path $file -Value $content -NoNewline
            Write-Host "Updated: $file"
        }
    }
}

Write-Host "`nAll references updated!"
