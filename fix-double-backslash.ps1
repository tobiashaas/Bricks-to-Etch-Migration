# Fix double backslashes in Service Provider
$file = "etch-fusion-suite\includes\container\class-service-provider.php"
$content = Get-Content $file -Raw

# Replace double backslash with single backslash before EFS_
$content = $content -replace '\\\\EFS_', '\EFS_'

Set-Content -Path $file -Value $content -NoNewline
Write-Host "Fixed double backslashes in Service Provider"
