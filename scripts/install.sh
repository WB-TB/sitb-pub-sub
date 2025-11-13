#!/bin/sh

# Script to install sitb-pub-sub from GitHub and set up consumer service
USERID=sitb-ckg
GROUPID=sitb-ckg
REPO_URL="https://github.com/WB-TB/sitb-pub-sub"
ZIP_FILE="/tmp/sitb-pub-sub.zip"
TARGET_DIR="/opt/sitb-ckg"
LOG_DIR="/var/log/sitb-ckg"
SERVICE_CONSUMER_FILE="ckg-consumer.service"
SERVICE_PRODUCER_PUBSUB_FILE="ckg-producer-pubsub.service"
SERVICE_PRODUCER_API_FILE="ckg-producer-api.service"
TIMER_PRODUCER_PUBSUB_FILE="ckg-producer-pubsub.timer"
TIMER_PRODUCER_API_FILE="ckg-producer-api.timer"
SERVICE_CONSUMER_NAME="ckg-consumer"
SERVICE_PRODUCER_PUBSUB_NAME="ckg-producer-pubsub"
SERVICE_PRODUCER_API_NAME="ckg-producer-api"

echo "Starting installation of sitb-pub-sub..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root. Please use sudo."
    exit 1
fi

# Check if PHP is installed
PHP_EXEC=$(which php)
if [ -z "$PHP_EXEC" ]; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

# Create user and group if they don't exist
echo "Creating user and group: $USERID:$GROUPID"
if ! id "$USERID" &>/dev/null; then
    useradd -r -s /bin/false -d "$TARGET_DIR" "$USERID"
    echo "User $USERID created"
else
    echo "User $USERID already exists"
fi

if ! getent group "$GROUPID" &>/dev/null; then
    groupadd "$GROUPID"
    echo "Group $GROUPID created"
else
    echo "Group $GROUPID already exists"
fi

# Create target and log directory if it doesn't exist
echo "Creating target directory: $TARGET_DIR, log: $LOG_DIR"
mkdir -p "$TARGET_DIR"
mkdir -p "$LOG_DIR"

# Download the repository as zip
echo "Downloading repository from $REPO_URL..."
wget -q "$REPO_URL/archive/refs/heads/main.zip" -O "$ZIP_FILE"

if [ $? -ne 0 ]; then
    echo "Error: Failed to download repository"
    exit 1
fi

# Extract the zip file
echo "Extracting zip file to $TARGET_DIR..."
unzip -q "$ZIP_FILE" -d "$TARGET_DIR"

if [ $? -ne 0 ]; then
    echo "Error: Failed to extract zip file"
    rm -f "$ZIP_FILE"
    exit 1
fi

# Find the extracted directory (it will be sitb-pub-sub-main)
EXTRACTED_DIR="$TARGET_DIR/sitb-pub-sub-main"
if [ ! -d "$EXTRACTED_DIR" ]; then
    echo "Error: Extracted directory not found"
    rm -f "$ZIP_FILE"
    exit 1
fi

# Move contents to the target directory and remove the extra folder
echo "Moving files to target directory..."
mv "$EXTRACTED_DIR"/* "$TARGET_DIR/"
rm -rf "$EXTRACTED_DIR"

# Set proper permissions
echo "Setting permissions..."
chown -R $USERID:$GROUPID "$TARGET_DIR"
chmod -R 755 "$TARGET_DIR"
chown -R $USERID:$GROUPID "$LOG_DIR"
chmod -R 755 "$LOG_DIR"

# Clean up zip file
rm -f "$ZIP_FILE"

# Install Composer dependencies
echo "Installing Composer dependencies..."
cd "$TARGET_DIR"
if [ -f "composer.json" ]; then
    # Check if composer is installed
    if command -v composer &> /dev/null; then
        # Install dependencies as the sitb-ckg user
        # sudo -u $USERID composer install --no-dev --optimize-autoloader
        sudo -u $USERID composer update
        if [ $? -ne 0 ]; then
            echo "Error: Failed to install Composer dependencies"
            exit 1
        fi
        echo "Composer dependencies installed successfully"
    else
        echo "Warning: Composer is not installed. Please install Composer first."
        echo "You can install it with: curl -sS https://getcomposer.org/installer | php"
        echo "Then run: sudo -u $USERID composer install --no-dev --optimize-autoloader"
    fi
else
    echo "No composer.json found in $TARGET_DIR"
fi

# Update the service file with the correct PHP path and working directory
sed -i "s|/usr/bin/php|$PHP_EXEC|g" "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE"
sed -i "s|/usr/bin/php|$PHP_EXEC|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_PUBSUB_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_PUBSUB_FILE"
sed -i "s|/usr/bin/php|$PHP_EXEC|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_API_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_API_FILE"

echo "Repository downloaded and extracted successfully to $TARGET_DIR"

# Install consumer service
echo "Installing ckg-consumer and ckg-producer services..."

# Copy the service file to systemd directory
echo "Copying service files to /etc/systemd/system/"
cp "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE" "/etc/systemd/system/"
cp "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_PUBSUB_FILE" "/etc/systemd/system/"
cp "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_API_FILE" "/etc/systemd/system/"
cp "$TARGET_DIR/scripts/producer/$TIMER_PRODUCER_PUBSUB_FILE" "/etc/systemd/system/"
cp "$TARGET_DIR/scripts/producer/$TIMER_PRODUCER_API_FILE" "/etc/systemd/system/"

# Set proper permissions
chmod 644 "/etc/systemd/system/$SERVICE_CONSUMER_FILE"
chmod 644 "/etc/systemd/system/$SERVICE_PRODUCER_PUBSUB_FILE"
chmod 644 "/etc/systemd/system/$SERVICE_PRODUCER_API_FILE"
chmod 644 "/etc/systemd/system/$TIMER_PRODUCER_PUBSUB_FILE"
chmod 644 "/etc/systemd/system/$TIMER_PRODUCER_API_FILE"

# Reload systemd to recognize the new service
echo "Reloading systemd daemon..."
systemctl daemon-reload

# Enable the service to start on boot
echo "Enabling $SERVICE_CONSUMER_NAME service..."
systemctl enable "$SERVICE_CONSUMER_NAME.service"

echo "Enabling $SERVICE_PRODUCER_NAME service and timer..."
systemctl enable "$SERVICE_PRODUCER_PUBSUB_NAME.service"
systemctl enable "$SERVICE_PRODUCER_API_NAME.service"
systemctl enable "$SERVICE_PRODUCER_PUBSUB_NAME.timer"
systemctl enable "$SERVICE_PRODUCER_API_NAME.timer"

# Start the service
echo "Starting $SERVICE_CONSUMER_NAME service..."
systemctl start "$SERVICE_CONSUMER_NAME.service"

# Check service status
echo "Checking service status..."
systemctl status "$SERVICE_CONSUMER_NAME.service"

echo "Starting $SERVICE_PRODUCER_PUBSUB_NAME timer..."
systemctl start "$SERVICE_PRODUCER_PUBSUB_NAME.timer"

echo "Starting $SERVICE_PRODUCER_API_NAME timer..."
systemctl start "$SERVICE_PRODUCER_API_NAME.timer"

# Check if both services started successfully
if systemctl is-active --quiet "$SERVICE_CONSUMER_NAME.service" && systemctl is-active --quiet "$SERVICE_PRODUCER_NAME.timer"; then
    echo ""
    echo "Installation completed successfully!"
    echo ""
    echo "Repository installed at: $TARGET_DIR"
    echo "Consumer service has been installed and started"
    echo ""
    echo "You can manage the service with:"
    echo "  Consumer Service:"
    echo "    Start service:    sudo systemctl start ckg-consumer.service"
    echo "    Stop service:     sudo systemctl stop ckg-consumer.service"
    echo "    Restart service:  sudo systemctl restart ckg-consumer.service"
    echo "    Check status:     sudo systemctl status ckg-consumer.service"
    echo "    View logs:       sudo journalctl -u ckg-consumer.service -f"
    echo "  Producer Pub/Sub Service:"
    echo "    Start service:    sudo systemctl start ckg-producer-pubsub.timer"
    echo "    View logs:       sudo journalctl -u ckg-producer-pubsub.timer -f"
    echo "    Check timer:     sudo systemctl status ckg-producer-pubsub.timer"
    echo "  Producer API Service:"
    echo "    Start service:    sudo systemctl start ckg-producer-api.timer"
    echo "    View logs:       sudo journalctl -u ckg-producer-api.timer -f"
    echo "    Check timer:     sudo systemctl status ckg-producer-api.timer"
else
    echo "Error: Failed to install services properly"
    echo "Consumer service status: $(systemctl is-active "$SERVICE_CONSUMER_NAME.service")"
    echo "Producer timer Pub/Sub status: $(systemctl is-active "$SERVICE_PRODUCER_PUBSUB_NAME.timer")"
    echo "Producer timer API status: $(systemctl is-active "$SERVICE_PRODUCER_API_NAME.timer")"
    exit 1
fi

exit 0