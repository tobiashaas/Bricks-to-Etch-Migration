#!/bin/bash
echo "🧹 Cleaning up test environment..."
echo "================================="
echo ""

# Stop any running servers
pkill -f "php -S localhost:8080"
pkill -f "php -S localhost:8081"

# Remove test directories
cd ..
rm -rf wordpress-test

# Drop databases
mysql -u root -p -e "DROP DATABASE IF EXISTS source_db;"
mysql -u root -p -e "DROP DATABASE IF EXISTS target_db;"

echo "✅ Cleanup completed!"
