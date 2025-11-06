#!/bin/bash

# Script to setup both API and PubSub cronjobs for producer

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root. Please use sudo."
    exit 1
fi

# Function to check if a cronjob exists
cronjob_exists() {
    local comment="$1"
    crontab -l 2>/dev/null | grep -F "$comment" >/dev/null
}

# Function to add a cronjob
add_cronjob() {
    local comment="$1"
    local job="$2"
    
    # Get current crontab
    crontab -l 2>/dev/null > /tmp/crontab_backup
    
    # Add the new cronjob
    echo "$job" >> /tmp/crontab_backup
    
    # Install the new crontab
    crontab /tmp/crontab_backup
    
    # Clean up
    rm /tmp/crontab_backup
    
    echo "Cronjob added successfully!"
}

# Function to remove a cronjob
remove_cronjob() {
    local comment="$1"
    
    # Get current crontab and remove the specific job
    crontab -l 2>/dev/null | grep -v "$comment" | crontab -
    
    echo "Cronjob removed successfully!"
}

# Function to show status
show_status() {
    echo "=== Producer Cronjobs Status ==="
    
    if cronjob_exists "CKG Producer API Mode"; then
        echo "✓ API Mode cronjob is active"
        crontab -l 2>/dev/null | grep -F "CKG Producer API Mode"
    else
        echo "✗ API Mode cronjob is not active"
    fi
    
    echo ""
    
    if cronjob_exists "CKG Producer PubSub Mode"; then
        echo "✓ PubSub Mode cronjob is active"
        crontab -l 2>/dev/null | grep -F "CKG Producer PubSub Mode"
    else
        echo "✗ PubSub Mode cronjob is not active"
    fi
    
    echo ""
    echo "=== Current Crontab ==="
    crontab -l 2>/dev/null || echo "No crontab found."
}

# Main menu
case "$1" in
    "add-api")
        if cronjob_exists "CKG Producer API Mode"; then
            echo "API Mode cronjob already exists!"
            echo "To remove it first, run: sudo $0 remove-api"
        else
            echo "Adding cronjob for CKG Producer API Mode..."
            echo "This will run the producer API mode every 6 hours."
            add_cronjob "CKG Producer API Mode" "0 */6 * * * $SCRIPT_DIR/run-producer-api.sh  # Run every 6 hours"
        fi
        ;;
    "add-pubsub")
        if cronjob_exists "CKG Producer PubSub Mode"; then
            echo "PubSub Mode cronjob already exists!"
            echo "To remove it first, run: sudo $0 remove-pubsub"
        else
            echo "Adding cronjob for CKG Producer PubSub Mode..."
            echo "This will run the producer PubSub mode every 12 hours."
            add_cronjob "CKG Producer PubSub Mode" "0 */12 * * * $SCRIPT_DIR/run-producer-pubsub.sh  # Run every 12 hours"
        fi
        ;;
    "add-all")
        echo "Adding both API and PubSub cronjobs..."
        $0 add-api
        $0 add-pubsub
        ;;
    "remove-api")
        if cronjob_exists "CKG Producer API Mode"; then
            echo "Removing cronjob for CKG Producer API Mode..."
            remove_cronjob "CKG Producer API Mode"
        else
            echo "API Mode cronjob not found!"
        fi
        ;;
    "remove-pubsub")
        if cronjob_exists "CKG Producer PubSub Mode"; then
            echo "Removing cronjob for CKG Producer PubSub Mode..."
            remove_cronjob "CKG Producer PubSub Mode"
        else
            echo "PubSub Mode cronjob not found!"
        fi
        ;;
    "remove-all")
        echo "Removing both API and PubSub cronjobs..."
        $0 remove-api
        $0 remove-pubsub
        ;;
    "status")
        show_status
        ;;
    "list")
        echo "=== Current Crontab ==="
        crontab -l 2>/dev/null || echo "No crontab found."
        ;;
    *)
        echo "Usage: $0 {add-api|add-pubsub|add-all|remove-api|remove-pubsub|remove-all|status|list}"
        echo ""
        echo "Commands:"
        echo "  add-api      - Add the producer API cronjob (runs every 6 hours)"
        echo "  add-pubsub   - Add the producer PubSub cronjob (runs every 12 hours)"
        echo "  add-all      - Add both API and PubSub cronjobs"
        echo "  remove-api   - Remove the producer API cronjob"
        echo "  remove-pubsub- Remove the producer PubSub cronjob"
        echo "  remove-all   - Remove both API and PubSub cronjobs"
        echo "  status       - Check status of all producer cronjobs"
        echo "  list         - List all current crontab entries"
        echo ""
        echo "Default schedules:"
        echo "  API Mode: Every 6 hours (0 */6 * * *)"
        echo "  PubSub Mode: Every 12 hours (0 */12 * * *)"
        echo ""
        echo "Log files:"
        echo "  API Mode: $PROJECT_ROOT/logs/producer-api-cron.log"
        echo "  PubSub Mode: $PROJECT_ROOT/logs/producer-pubsub-cron.log"
        exit 1
        ;;
esac