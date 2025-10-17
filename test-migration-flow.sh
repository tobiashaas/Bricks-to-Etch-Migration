#!/bin/bash

echo "🧪 Testing Bricks to Etch Migration Flow"
echo "=========================================="
echo ""

# Test 1: Check if both WordPress sites are running
echo "1️⃣ Checking WordPress sites..."
BRICKS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
ETCH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8081)

if [ "$BRICKS_STATUS" = "200" ] && [ "$ETCH_STATUS" = "200" ]; then
    echo "   ✅ Both WordPress sites are running"
    echo "   - Bricks: http://localhost:8080 (HTTP $BRICKS_STATUS)"
    echo "   - Etch: http://localhost:8081 (HTTP $ETCH_STATUS)"
else
    echo "   ❌ WordPress sites not accessible"
    exit 1
fi
echo ""

# Test 2: Check if API is working
echo "2️⃣ Checking API endpoints..."
API_TEST=$(curl -s http://localhost:8081/wp-json/b2e/v1/auth/test)
if echo "$API_TEST" | grep -q "success"; then
    echo "   ✅ API is working"
    echo "   Response: $(echo $API_TEST | python3 -c 'import sys, json; print(json.load(sys.stdin)["message"])')"
else
    echo "   ❌ API not working"
    echo "   Response: $API_TEST"
    exit 1
fi
echo ""

# Test 3: Generate migration token on Etch site
echo "3️⃣ Generating migration token..."
echo "   ℹ️  Please generate a migration key manually in WordPress admin:"
echo "   - Open: http://localhost:8081/wp-admin/admin.php?page=bricks-etch-migration-generate"
echo "   - Click 'Generate Migration Key'"
echo "   - Copy the generated key"
echo ""
echo "   Then test validation on Bricks site:"
echo "   - Open: http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration"
echo "   - Paste the migration key"
echo "   - Click 'Validate Migration Key'"
echo ""

# Test 4: Check Docker logs for debugging
echo "4️⃣ Useful debugging commands:"
echo "   - View Etch logs: docker logs b2e-etch --tail 50"
echo "   - View Bricks logs: docker logs b2e-bricks --tail 50"
echo "   - Check plugin files: docker exec b2e-etch ls -la /var/www/html/wp-content/plugins/bricks-etch-migration/"
echo ""

echo "🎉 Setup complete! Ready for manual testing."
echo ""
echo "📋 Test Checklist:"
echo "   [ ] Generate migration key on Etch site"
echo "   [ ] Validate migration key on Bricks site"
echo "   [ ] Check that API key is returned in response"
echo "   [ ] Start migration"
echo "   [ ] Verify data transfer"
