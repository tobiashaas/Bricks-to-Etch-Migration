#!/bin/bash

echo "🧪 Testing Token Validation Flow"
echo "================================="
echo ""

# Step 1: Generate a migration token on Etch site (simulate)
echo "1️⃣ Simulating token generation..."

# Generate a random token (64 characters)
TOKEN=$(openssl rand -hex 32)
EXPIRES=$(($(date +%s) + 28800)) # 8 hours from now
SOURCE_DOMAIN="http://localhost:8081"

echo "   Generated token: ${TOKEN:0:20}..."
echo "   Expires: $EXPIRES ($(date -r $EXPIRES '+%Y-%m-%d %H:%M:%S'))"
echo ""

# Step 2: Store token on Etch site (via direct database or API)
echo "2️⃣ Storing token on Etch site..."

# Use WordPress CLI to store the token (if available) or use API
# For now, we'll use a direct SQL command via docker
docker exec b2e-mysql-etch mysql -u wordpress -pwordpress wordpress_etch -e "
    DELETE FROM wp_options WHERE option_name = 'b2e_migration_token_value';
    INSERT INTO wp_options (option_name, option_value, autoload) 
    VALUES ('b2e_migration_token_value', '$TOKEN', 'yes');
" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "   ✅ Token stored in database"
else
    echo "   ❌ Failed to store token"
    exit 1
fi
echo ""

# Step 3: Build migration URL
echo "3️⃣ Building migration URL..."
MIGRATION_URL="${SOURCE_DOMAIN}?domain=${SOURCE_DOMAIN}&token=${TOKEN}&expires=${EXPIRES}"
echo "   Migration URL: ${MIGRATION_URL:0:80}..."
echo ""

# Step 4: Test token validation via API
echo "4️⃣ Testing token validation..."

VALIDATION_RESPONSE=$(curl -s -X POST http://localhost:8081/wp-json/b2e/v1/validate \
    -H "Content-Type: application/json" \
    -d "{\"token\":\"$TOKEN\",\"source_domain\":\"$SOURCE_DOMAIN\",\"expires\":$EXPIRES}")

echo "   Response: $VALIDATION_RESPONSE"
echo ""

# Check if validation was successful
if echo "$VALIDATION_RESPONSE" | grep -q '"success":true'; then
    echo "   ✅ Token validation successful!"
    
    # Extract API key from response
    API_KEY=$(echo "$VALIDATION_RESPONSE" | python3 -c 'import sys, json; print(json.load(sys.stdin).get("api_key", "NOT_FOUND"))')
    
    if [ "$API_KEY" != "NOT_FOUND" ] && [ ! -z "$API_KEY" ]; then
        echo "   ✅ API key received: ${API_KEY:0:20}..."
        echo ""
        echo "🎉 Token validation flow working correctly!"
        echo ""
        echo "📋 Next steps:"
        echo "   1. Use this migration URL in the Bricks site:"
        echo "      $MIGRATION_URL"
        echo "   2. The API key will be automatically extracted: ${API_KEY:0:20}..."
        echo "   3. Test the complete migration flow"
    else
        echo "   ⚠️  Token validated but no API key in response"
        echo "   Response: $VALIDATION_RESPONSE"
    fi
else
    echo "   ❌ Token validation failed"
    echo "   Response: $VALIDATION_RESPONSE"
    exit 1
fi
