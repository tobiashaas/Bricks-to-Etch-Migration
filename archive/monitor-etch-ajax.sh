#!/bin/bash

echo "================================================"
echo "Etch AJAX Monitor"
echo "================================================"
echo ""
echo "This will monitor Docker logs to see AJAX requests"
echo "when you save a class in Etch."
echo ""
echo "INSTRUCTIONS:"
echo "1. Keep this terminal open"
echo "2. In another window, open Etch in browser"
echo "3. Create/modify a class"
echo "4. Save it"
echo "5. Watch this terminal for AJAX requests"
echo ""
echo "Starting log monitor..."
echo "Press Ctrl+C to stop"
echo ""
echo "================================================"
echo ""

# Monitor Apache/Nginx access logs for AJAX requests
docker exec b2e-etch tail -f /var/log/apache2/access.log 2>/dev/null | grep -E "admin-ajax\.php|wp-json|POST|PUT" --line-buffered | while read line; do
    echo "🌐 $line"
done
