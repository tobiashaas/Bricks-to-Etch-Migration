#!/bin/bash

# Update CSS in Docker containers
echo "🎨 Updating CSS in Docker containers..."

# Copy to Bricks container
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/assets/css/admin.css | \
  docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/assets/css/admin.css > /dev/null

# Copy to Etch container  
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/assets/css/admin.css | \
  docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/assets/css/admin.css > /dev/null

# Also update in test-environment
cp /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/assets/css/admin.css \
   /Users/tobiashaas/bricks-etch-migration/test-environment/wordpress-bricks/wp-content/plugins/bricks-etch-migration/assets/css/admin.css

cp /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/assets/css/admin.css \
   /Users/tobiashaas/bricks-etch-migration/test-environment/wordpress-etch/wp-content/plugins/bricks-etch-migration/assets/css/admin.css

echo "✅ CSS updated in all containers!"
echo "🔄 Refresh your browser to see changes (Cmd+Shift+R for hard refresh)"
