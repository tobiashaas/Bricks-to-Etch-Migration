# Fix Service Provider to use EFS_ class names
$file = "etch-fusion-suite\includes\container\class-service-provider.php"
$content = Get-Content $file -Raw

# Replace B2E_ with EFS_ in class instantiations
$content = $content -replace '\\B2E_', '\\EFS_'

Set-Content -Path $file -Value $content -NoNewline
Write-Host "Service Provider updated to use EFS_ class names"
