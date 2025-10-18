#!/bin/bash

echo "================================================"
echo "Etch Class Creation Monitor"
echo "================================================"
echo ""
echo "This script will help you understand how Etch stores classes."
echo ""
echo "INSTRUCTIONS:"
echo "1. Run this script and press ENTER"
echo "2. Create a new class in Etch (e.g., 'test-class-123')"
echo "3. Add some CSS properties (color, padding, etc.)"
echo "4. Save the class"
echo "5. Come back here and press ENTER again"
echo ""
echo "Press ENTER to capture BEFORE state..."
read

echo "📸 Capturing BEFORE state..."

# Capture current etch_styles
docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null > /tmp/etch_styles_before.json

# Count styles
BEFORE_COUNT=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null)

echo "✅ Captured $BEFORE_COUNT styles"
echo ""
echo "Now go to Etch and:"
echo "  1. Create a new class (e.g., 'test-class-123')"
echo "  2. Add CSS properties:"
echo "     - Color: red"
echo "     - Padding: 20px"
echo "     - Background: blue"
echo "  3. Save the class"
echo ""
echo "Press ENTER when done..."
read

echo ""
echo "📸 Capturing AFTER state..."

# Capture new etch_styles
docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null > /tmp/etch_styles_after.json

AFTER_COUNT=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null)

echo "✅ Captured $AFTER_COUNT styles"
echo ""
echo "================================================"
echo "ANALYSIS"
echo "================================================"
echo ""
echo "Styles before: $BEFORE_COUNT"
echo "Styles after:  $AFTER_COUNT"
echo "New styles:    $((AFTER_COUNT - BEFORE_COUNT))"
echo ""

# Find the new style
echo "🔍 Finding new style(s)..."
echo ""

python3 << 'EOF'
import json

with open('/tmp/etch_styles_before.json', 'r') as f:
    before = json.load(f)

with open('/tmp/etch_styles_after.json', 'r') as f:
    after = json.load(f)

# Find new keys
new_keys = set(after.keys()) - set(before.keys())

if not new_keys:
    print("⚠️  No new styles found!")
    print("   Maybe the class already existed?")
else:
    print(f"✅ Found {len(new_keys)} new style(s):\n")
    
    for key in new_keys:
        style = after[key]
        print(f"Key: {key}")
        print(f"Type: {style.get('type')}")
        print(f"Selector: {style.get('selector')}")
        print(f"Collection: {style.get('collection')}")
        print(f"CSS: {style.get('css')}")
        print(f"Readonly: {style.get('readonly')}")
        print("")
        print("Full JSON:")
        print(json.dumps(style, indent=2))
        print("")
        print("-" * 50)
        print("")

# Also check for modified styles
print("\n🔍 Checking for modified styles...")
modified = []
for key in before.keys():
    if key in after and before[key] != after[key]:
        modified.append(key)

if modified:
    print(f"⚠️  {len(modified)} style(s) were modified:")
    for key in modified:
        print(f"  - {key}: {after[key].get('selector')}")
else:
    print("✅ No existing styles were modified")

EOF

echo ""
echo "================================================"
echo "Files saved for manual inspection:"
echo "  - /tmp/etch_styles_before.json"
echo "  - /tmp/etch_styles_after.json"
echo "================================================"
