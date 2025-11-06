#!/bin/bash

# Script to install and manage the CKG Consumer systemd service
LOG_DIR="/var/log/sitb-ckg"
SERVICE_FILE="consumer.service"
SERVICE_NAME="consumer"
PHP_EXEC=$(which php)
WORKING_DIR=$(pwd)

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root. Please use sudo."
    exit 1
fi

# Check if PHP is installed
if [ -z "$PHP_EXEC" ]; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

mkdir -p "$LOG_DIR"

# Update the service file with the correct PHP path and working directory
sed -i "s|/usr/bin/php|$PHP_EXEC|g" "$SERVICE_FILE"
sed -i "s|/var/www/html|$WORKING_DIR|g" "$SERVICE_FILE"

# Copy the service file to systemd directory
echo "Copying service file to /etc/systemd/system/"
cp "$SERVICE_FILE" "/etc/systemd/system/"

# Set proper permissions
chmod 644 "/etc/systemd/system/$SERVICE_FILE"

# Reload systemd to recognize the new service
echo "Reloading systemd daemon..."
systemctl daemon-reload

# Enable the service to start on boot
echo "Enabling $SERVICE_NAME service..."
systemctl enable "$SERVICE_NAME.service"

# Start the service
echo "Starting $SERVICE_NAME service..."
systemctl start "$SERVICE_NAME.service"

# Check service status
echo "Checking service status..."
systemctl status "$SERVICE_NAME.service"

echo ""
echo "Service installation completed!"
echo ""
echo "Usage commands:"
echo "  Start service:    sudo systemctl start $SERVICE_NAME.service"
echo "  Stop service:     sudo systemctl stop $SERVICE_NAME.service"
echo "  Restart service:  sudo systemctl restart $SERVICE_NAME.service"
echo "  Check status:     sudo systemctl status $SERVICE_NAME.service"
echo "  View logs:       sudo journalctl -u $SERVICE_NAME.service -f"
echo ""