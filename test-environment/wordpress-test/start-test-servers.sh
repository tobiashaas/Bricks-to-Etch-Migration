#!/bin/bash
echo "🚀 Starting WordPress test servers..."
echo "Source Site (Bricks): http://localhost:8080"
echo "Target Site (Etch): http://localhost:8081"
echo ""
echo "Admin credentials:"
echo "Username: admin"
echo "Password: admin"
echo ""
echo "Press Ctrl+C to stop servers"
echo ""

# Start source site
cd source-site
php -S localhost:8080 &
SOURCE_PID=$!

# Start target site
cd ../target-site
php -S localhost:8081 &
TARGET_PID=$!

# Wait for interrupt
trap "kill $SOURCE_PID $TARGET_PID; exit" INT
wait
