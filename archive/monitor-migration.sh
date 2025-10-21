#!/bin/bash

# Monitor Migration Process
# This script monitors the migration progress by checking WordPress options

echo "========================================="
echo "Migration Progress Monitor"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BRICKS_CONTAINER="b2e-bricks"
MAX_POLLS=60
POLL_INTERVAL=2

echo "Monitoring migration progress on Bricks site..."
echo "Press Ctrl+C to stop monitoring"
echo ""

POLL_COUNT=0
LAST_STATUS=""
LAST_PERCENTAGE=""
LAST_MESSAGE=""

while [ $POLL_COUNT -lt $MAX_POLLS ]; do
    POLL_COUNT=$((POLL_COUNT + 1))
    
    # Get migration progress from WordPress options table
    PROGRESS_JSON=$(docker exec $BRICKS_CONTAINER wp option get b2e_migration_progress --format=json --allow-root 2>/dev/null)
    
    if [ -z "$PROGRESS_JSON" ] || [ "$PROGRESS_JSON" = "false" ]; then
        echo -e "${YELLOW}⏳ No migration in progress (Poll $POLL_COUNT)${NC}"
        sleep $POLL_INTERVAL
        continue
    fi
    
    # Parse JSON response
    STATUS=$(echo "$PROGRESS_JSON" | grep -o '"status":"[^"]*"' | sed 's/"status":"//;s/"//' | head -1)
    PERCENTAGE=$(echo "$PROGRESS_JSON" | grep -o '"percentage":[0-9]*' | sed 's/"percentage"://' | head -1)
    MESSAGE=$(echo "$PROGRESS_JSON" | grep -o '"message":"[^"]*"' | sed 's/"message":"//;s/"//' | head -1)
    CURRENT_STEP=$(echo "$PROGRESS_JSON" | grep -o '"current_step":"[^"]*"' | sed 's/"current_step":"//;s/"//' | head -1)
    
    # Only print if something changed
    if [ "$STATUS" != "$LAST_STATUS" ] || [ "$PERCENTAGE" != "$LAST_PERCENTAGE" ] || [ "$MESSAGE" != "$LAST_MESSAGE" ]; then
        TIMESTAMP=$(date '+%H:%M:%S')
        
        # Color code based on status
        if [ "$STATUS" = "completed" ]; then
            echo -e "${GREEN}[$TIMESTAMP] ✅ Status: $STATUS | Progress: $PERCENTAGE% | Step: $CURRENT_STEP${NC}"
            echo -e "${GREEN}[$TIMESTAMP] Message: $MESSAGE${NC}"
        elif [ "$STATUS" = "error" ]; then
            echo -e "${RED}[$TIMESTAMP] ❌ Status: $STATUS | Progress: $PERCENTAGE% | Step: $CURRENT_STEP${NC}"
            echo -e "${RED}[$TIMESTAMP] Message: $MESSAGE${NC}"
        elif [ "$STATUS" = "running" ]; then
            echo -e "${BLUE}[$TIMESTAMP] 🔄 Status: $STATUS | Progress: $PERCENTAGE% | Step: $CURRENT_STEP${NC}"
            echo -e "${BLUE}[$TIMESTAMP] Message: $MESSAGE${NC}"
        else
            echo -e "[$TIMESTAMP] Status: $STATUS | Progress: $PERCENTAGE% | Step: $CURRENT_STEP"
            echo -e "[$TIMESTAMP] Message: $MESSAGE"
        fi
        
        LAST_STATUS="$STATUS"
        LAST_PERCENTAGE="$PERCENTAGE"
        LAST_MESSAGE="$MESSAGE"
    fi
    
    # Check if migration is complete or failed
    if [ "$STATUS" = "completed" ]; then
        echo ""
        echo -e "${GREEN}=========================================${NC}"
        echo -e "${GREEN}✅ Migration completed successfully!${NC}"
        echo -e "${GREEN}=========================================${NC}"
        echo ""
        
        # Show migration stats
        STATS_JSON=$(docker exec $BRICKS_CONTAINER wp option get b2e_migration_stats --format=json --allow-root 2>/dev/null)
        if [ -n "$STATS_JSON" ] && [ "$STATS_JSON" != "false" ]; then
            echo "Migration Statistics:"
            echo "$STATS_JSON" | python3 -m json.tool 2>/dev/null || echo "$STATS_JSON"
        fi
        
        exit 0
    elif [ "$STATUS" = "error" ]; then
        echo ""
        echo -e "${RED}=========================================${NC}"
        echo -e "${RED}❌ Migration failed with error${NC}"
        echo -e "${RED}=========================================${NC}"
        echo ""
        
        # Show error logs
        echo "Recent error logs:"
        docker exec $BRICKS_CONTAINER wp option get b2e_error_log --format=json --allow-root 2>/dev/null | python3 -m json.tool 2>/dev/null || echo "No error logs found"
        
        exit 1
    fi
    
    sleep $POLL_INTERVAL
done

echo ""
echo -e "${YELLOW}=========================================${NC}"
echo -e "${YELLOW}⚠️  Monitoring timeout reached${NC}"
echo -e "${YELLOW}=========================================${NC}"
echo ""
echo "Last known status: $LAST_STATUS ($LAST_PERCENTAGE%)"
echo "Last message: $LAST_MESSAGE"
