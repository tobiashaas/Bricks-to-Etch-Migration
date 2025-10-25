#!/bin/bash
# Validate WordPress plugin headers before release

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_FILE="$SCRIPT_DIR/../etch-fusion-suite.php"
README_FILE="$SCRIPT_DIR/../readme.txt"
CHANGELOG_FILE="$SCRIPT_DIR/../../CHANGELOG.md"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Validating plugin headers..."

# Extract version from tag
TAG_VERSION="${GITHUB_REF_NAME#v}"
echo "Tag version: $TAG_VERSION"

# Extract version from plugin file
PLUGIN_VERSION=$(grep -E "^\s*\*\s*Version:" "$PLUGIN_FILE" | sed -E 's/.*Version:\s*([0-9.]+).*/\1/')

if [ -z "$PLUGIN_VERSION" ]; then
    echo -e "${RED}❌ Unable to determine plugin version from $PLUGIN_FILE${NC}"
    exit 1
fi

echo "Plugin version: $PLUGIN_VERSION"

# Ensure text domain matches expectation
TEXT_DOMAIN=$(grep -E "^\s*\*\s*Text Domain:" "$PLUGIN_FILE" | sed -E 's/.*Text Domain:\s*([a-z0-9\-]+).*/\1/')
if [ "$TEXT_DOMAIN" != "etch-fusion-suite" ]; then
    echo -e "${RED}❌ Text domain mismatch! Expected 'etch-fusion-suite' but found '$TEXT_DOMAIN'${NC}"
    exit 1
fi

# Check if versions match
if [ "$TAG_VERSION" != "$PLUGIN_VERSION" ]; then
    echo -e "${RED}❌ Version mismatch!${NC}"
    echo "Tag version: $TAG_VERSION"
    echo "Plugin version: $PLUGIN_VERSION"
    exit 1
fi

# Check required headers
echo "Checking required headers..."

REQUIRED_HEADERS=(
    "Plugin Name:"
    "Version:"
    "Author:"
    "License:"
    "Requires at least:"
    "Tested up to:"
    "Requires PHP:"
)

for header in "${REQUIRED_HEADERS[@]}"; do
    if ! grep -q "$header" "$PLUGIN_FILE"; then
        echo -e "${RED}❌ Missing header: $header${NC}"
        exit 1
    fi
done

# Check readme.txt exists and validate
if [ ! -f "$README_FILE" ]; then
    echo -e "${RED}❌ readme.txt not found${NC}"
    exit 1
fi

echo "Validating readme.txt..."

# Extract stable tag from readme.txt
README_VERSION=$(grep -E "^Stable tag:" "$README_FILE" | sed -E 's/.*Stable tag:\s*([0-9.]+).*/\1/')

if [ -z "$README_VERSION" ]; then
    echo -e "${RED}❌ Stable tag not found in $README_FILE${NC}"
    exit 1
fi

echo "readme.txt stable tag: $README_VERSION"

# Check if readme.txt version matches plugin version
if [ "$PLUGIN_VERSION" != "$README_VERSION" ]; then
    echo -e "${RED}❌ Version mismatch between plugin and readme.txt!${NC}"
    echo "Plugin version: $PLUGIN_VERSION"
    echo "readme.txt stable tag: $README_VERSION"
    exit 1
fi

# Check required readme.txt fields
README_REQUIRED_FIELDS=(
    "Contributors:"
    "Tags:"
    "Requires at least:"
    "Tested up to:"
    "Requires PHP:"
    "Stable tag:"
    "License:"
)

for field in "${README_REQUIRED_FIELDS[@]}"; do
    if ! grep -q "$field" "$README_FILE"; then
        echo -e "${RED}❌ Missing readme.txt field: $field${NC}"
        exit 1
    fi
done

# Check CHANGELOG.md has entry for this version
if [ -f "$CHANGELOG_FILE" ]; then
    if ! grep -Eq "\[${TAG_VERSION}\]|Version ${TAG_VERSION}|v${TAG_VERSION}" "$CHANGELOG_FILE"; then
        echo -e "${RED}❌ No CHANGELOG entry for version $TAG_VERSION (expected '[${TAG_VERSION}]', 'Version ${TAG_VERSION}', or 'v${TAG_VERSION}')${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✅ All validations passed!${NC}"
exit 0
