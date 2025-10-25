# Fix duplicate/self-referencing class_alias entries
Get-ChildItem -Path "bricks-etch-migration\includes" -Filter "*.php" -Recurse | ForEach-Object {
    $file = $_.FullName
    $content = Get-Content $file -Raw
    
    # Remove self-referencing class_alias calls (EFS -> EFS)
    $content = $content -replace "\\class_alias\s*\(\s*__NAMESPACE__\s*\.\s*'\\\\EFS_[^']+',\s*'EFS_[^']+'\s*\)\s*;\s*\r?\n", ""
    $content = $content -replace "class_alias\s*\(\s*EFS_[^:]+::class,\s*'EFS_[^']+'\s*\)\s*;\s*\r?\n", ""
    
    # Remove duplicate "Legacy alias" comments
    $content = $content -replace "(\r?\n// Legacy alias for backward compatibility\r?\n){2,}", "`n// Legacy alias for backward compatibility`n"
    
    Set-Content -Path $file -Value $content -NoNewline
    Write-Host "Fixed: $file"
}

Write-Host "`nCleanup complete!"
