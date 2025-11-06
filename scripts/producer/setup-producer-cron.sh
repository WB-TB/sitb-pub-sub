#!/bin/bash

# Script to setup cronjob for producer API mode

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
RUN_SCRIPT="$SCRIPT_DIR/run-producer-api.sh"
CRON_COMMENT="CKG Producer API Mode"
CRON_JOB="0 */6 * * * $RUN_SCRIPT  # Run every 6 hours"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root. Please use sudo."
    exit 1
fi

# Check if the run script exists
if [ ! -f "$RUN_SCRIPT" ]; then
    echo "ERROR: Run script not found at $RUN_SCRIPT"
    exit 1
fi

# Check if the run script is executable
if [ ! -x "$RUN_SCRIPT" ]; then
    echo "ERROR: Run script is not executable. Please run: chmod +x $RUN_SCRIPT"
    exit 1
fi

# Function to check if cronjob already exists
cronjob_exists() {
    crontab -l 2>/dev/null | grep -F "$CRON_COMMENT" >/dev/null
}

# Function to add cronjob
add_cronjob() {
    # Get current crontab
    crontab -l 2>/dev/null > /tmp/crontab_backup
    
    # Add the new cronjob
    echo "$CRON_JOB" >> /tmp/crontab_backup
    
    # Install the new crontab
    crontab /tmp/crontab_backup
    
    # Clean up
    rm /tmp/crontab_backup
    
    echo "Cronjob added successfully!"
}

# Function to remove cronjob
remove_cronjob() {
    # Get current crontab and remove the specific job
    crontab -l 2>/dev/null | grep -v "$CRON_COMMENT" | crontab -
    
    echo "Cronjob removed successfully!"
}

# Main menu
case "$1" in
    "add")
        if cronjob_exists; then
            echo "Cronjob already exists!"
            echo "To remove it first, run: sudo $0 remove"
        else
            echo "Adding cronjob for CKG Producer API Mode..."
            echo "This will run the producer API mode every 6 hours."
            add_cronjob
        fi
        ;;
    "remove")
        if cronjob_exists; then
            echo "Removing cronjob for CKG Producer API Mode..."
            remove_cronjob
        else
            echo "Cronjob not found!"
        fi
        ;;
    "status")
        if cronjob_exists; then
            echo "Cronjob is active:"
            crontab -l 2>/dev/null | grep -F "$CRON_COMMENT"
        else
            echo "Cronjob is not active."
        fi
        ;;
    "list")
        echo "Current crontab:"
        crontab -l 2>/dev/null || echo "No crontab found."
        ;;
    *)
        echo "Usage: $0 {add|remove|status|list}"
        echo ""
        echo "Commands:"
        echo "  add     - Add the producer API cronjob (runs every 6 hours)"
        echo "  remove  - Remove the producer API cronjob"
        echo "  status  - Check if the cronjob is active"
        echo "  list    - List all current crontab entries"
        echo ""
        echo "Default schedule: Every 6 hours (0 */6 * * *)"
        echo "Log file: $PROJECT_ROOT/logs/producer-api-cron.log"
        exit 1
        ;;
esac