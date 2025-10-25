# Update plugin scripts
Get-ChildItem -Path "etch-fusion-suite\scripts\*.js" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $content = $content -replace 'bricks-etch-migration', 'etch-fusion-suite'
    Set-Content -Path $_.FullName -Value $content -NoNewline
    Write-Host "Updated: $($_.Name)"
}

Write-Host "`nScripts updated!"
