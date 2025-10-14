#!/bin/bash
echo "🧪 Running WordPress Integration Tests..."
echo "========================================"
echo ""

# Test source site
echo "Testing Source Site..."
cd source-site
php ../wordpress-test.php
echo ""

# Test target site
echo "Testing Target Site..."
cd ../target-site
php ../wordpress-test.php
echo ""

echo "✅ All tests completed!"
