#!/bin/bash

# Script to run producer.php with PubSub mode
# This script is designed to be used with cron

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
PHP_EXEC=$(which php)
LOG_DIR="/var/log/sitb-ckg"
LOG_FILE="$LOG_DIR/producer-pubsub-cron.log"

# Create logs directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Check if PHP is installed
if [ -z "$PHP_EXEC" ]; then
    echo "[$(date)] ERROR: PHP is not installed" >> "$LOG_FILE"
    exit 1
fi

# Check if producer.php exists
if [ ! -f "$PROJECT_ROOT/producer.php" ]; then
    echo "[$(date)] ERROR: producer.php not found at $PROJECT_ROOT/producer.php" >> "$LOG_FILE"
    exit 1
fi

# Change to project directory
cd "$PROJECT_ROOT"

# Run the producer with PubSub mode
echo "[$(date)] Starting producer PubSub mode" >> "$LOG_FILE"
$PHP_EXEC producer.php --mode=pubsub >> "$LOG_FILE" 2>&1

# Check the exit status
if [ $? -eq 0 ]; then
    echo "[$(date)] Producer PubSub mode completed successfully" >> "$LOG_FILE"
else
    echo "[$(date)] ERROR: Producer PubSub mode failed with exit code $?" >> "$LOG_FILE"
fi

echo "----------------------------------------" >> "$LOG_FILE"