#!/bin/sh

# Script to install SITB-CKG from GitHub and set up consumer service
USERID=sitb-ckg
GROUPID=sitb-ckg
REPO_URL="https://github.com/WB-TB/sitb-pub-sub.git"
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

INSTALL_MODE=$1
if [ "$INSTALL_MODE" = "update" ]; then
    echo "Starting SITB-CKG update..."
else
    INSTALL_MODE="fresh"
    echo "Starting installation of SITB-CKG..."
fi

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "   -> [ERROR] This script must be run as root. Please use sudo."
    exit 1
fi

# Check if PHP is installed
PHPEXEC=$(which php)
if [ -z "$PHPEXEC" ]; then
    echo "   -> [ERROR] PHP is not installed. Please install PHP first."
    exit 1
fi

PHPVERSION=$($PHPEXEC -r 'echo PHP_VERSION;')
echo "Using PHP version: $PHPVERSION"

# Check if composer is installed
if command -v composer &> /dev/null; then
    echo " + Composer is installed."
else
    echo " + [WARNING]: Composer is not installed. Composer will be installed locally."
    curl -sS https://getcomposer.org/installer | $PHPEXEC
fi

if [ -f "composer.json" ]; then
    echo " + composer.json found in $TARGET_DIR."
else
    echo "   -> [ERROR] No composer.json found in the $TARGET_DIR."
    exit 1
fi

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "   -> [ERROR] git is not installed. Please install git first."
    exit 1
fi

if [ "$INSTALL_MODE" = "fresh" ]; then
    # Create user and group if they don't exist
    echo " + Creating user and group: $USERID:$GROUPID"
    if ! id "$USERID" &>/dev/null; then
        useradd -r -s /bin/false -d "$TARGET_DIR" "$USERID"
        echo "   -> User $USERID created"
    else
        echo "   -> User $USERID already exists"
    fi

    if ! getent group "$GROUPID" &>/dev/null; then
        groupadd "$GROUPID"
        echo "   -> Group $GROUPID created"
    else
        echo "   -> Group $GROUPID already exists"
    fi

    # Create log directory if it doesn't exist
    echo "   -> Creating log directory: $LOG_DIR"
    mkdir -p "$LOG_DIR"
elif [ ! -d "$TARGET_DIR/.git" ]; then
    echo "   -> [ERROR] Cannot update: Repository does not exist at $TARGET_DIR"
    exit 1
fi

# Clone or update the repository
if [ -d "$TARGET_DIR/.git" ]; then
    # Repository exists, pull latest changes
    echo " + Repository already exists at $TARGET_DIR"
    echo " + Updating repository from $REPO_URL..."
    cd "$TARGET_DIR"
    git fetch origin
    git reset --hard origin/main
    if [ $? -ne 0 ]; then
        echo "Error: Failed to update repository"
        exit 1
    fi
    echo "   -> Repository updated successfully"
else
    # Repository doesn't exist, clone it
    echo " + Cloning repository from $REPO_URL to $TARGET_DIR..."
    if [ -d "$TARGET_DIR" ]; then
        echo "   -> Directory $TARGET_DIR exists but is not a git repository"
        echo "   -> Please remove it or move it before running this script"
        exit 1
    fi
    git clone "$REPO_URL" "$TARGET_DIR"
    if [ $? -ne 0 ]; then
        echo "   -> Error: Failed to clone repository"
        exit 1
    fi
    echo "   -> Repository cloned successfully"
fi

# Set proper permissions
if [ "$INSTALL_MODE" = "fresh" ]; then
    echo " + Setting permissions..."
    chown -R $USERID:$GROUPID "$TARGET_DIR"
    chmod -R 755 "$TARGET_DIR"
    chown -R $USERID:$GROUPID "$LOG_DIR"
    chmod -R 755 "$LOG_DIR"
fi

# Install Composer dependencies
cd "$TARGET_DIR"
echo " + Installing Composer dependencies..."
# Install dependencies as the sitb-ckg user
# sudo -u $USERID composer install --no-dev --optimize-autoloader
sudo -u $USERID composer update
if [ $? -ne 0 ]; then
    echo "   -> [ERROR] Failed to install Composer dependencies"
    exit 1
fi
echo "   -> Composer dependencies installed successfully"

# Update the service file with the correct PHP path and working directory
sed -i "s|/usr/bin/php|$PHPEXEC|g" "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE"
sed -i "s|/usr/bin/php|$PHPEXEC|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_PUBSUB_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_PUBSUB_FILE"
sed -i "s|/usr/bin/php|$PHPEXEC|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_API_FILE"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/$SERVICE_PRODUCER_API_FILE"

# Update init.d scripts with correct paths
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/consumer/ckg-consumer"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/ckg-producer"

echo " + Repository cloned/updated successfully at $TARGET_DIR"

# Detect system manager (systemd or init.d)
echo " + Detecting system manager..."
SYSTEM_MANAGER="init.d"

if command -v systemctl &> /dev/null && [ -d /etc/systemd/system ]; then
    SYSTEM_MANAGER="systemd"
    echo "   -> System manager detected: systemd"
else
    echo "   -> System manager detected: init.d"
fi

# Install services based on system manager
echo " + Installing ckg-consumer and ckg-producer services..."
echo ""
echo " ----------------------------------------"

if [ "$SYSTEM_MANAGER" = "systemd" ]; then
    # Install systemd services
    echo " + Copying service files to /etc/systemd/system/"
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
    echo " + Reloading systemd daemon..."
    systemctl daemon-reload

    # Enable the service to start on boot
    echo " + Enabling $SERVICE_CONSUMER_NAME service..."
    systemctl enable "$SERVICE_CONSUMER_NAME.service"

    echo " + Enabling $SERVICE_PRODUCER_NAME service and timer..."
    systemctl enable "$SERVICE_PRODUCER_PUBSUB_NAME.service"
    systemctl enable "$SERVICE_PRODUCER_API_NAME.service"
    systemctl enable "$SERVICE_PRODUCER_PUBSUB_NAME.timer"
    systemctl enable "$SERVICE_PRODUCER_API_NAME.timer"

    if [ "$INSTALL_MODE" = "fresh" ]; then
        
        # Start the service
        echo " + Starting $SERVICE_CONSUMER_NAME service..."
        systemctl start "$SERVICE_CONSUMER_NAME.service"

        # Check service status
        echo " + Checking service status..."
        systemctl status "$SERVICE_CONSUMER_NAME.service"

        echo " + Starting $SERVICE_PRODUCER_PUBSUB_NAME timer..."
        systemctl start "$SERVICE_PRODUCER_PUBSUB_NAME.timer"

        echo " + Starting $SERVICE_PRODUCER_API_NAME timer..."
        systemctl start "$SERVICE_PRODUCER_API_NAME.timer"
    else
        # Restart the service
        echo " + Restarting $SERVICE_CONSUMER_NAME service..."
        systemctl restart "$SERVICE_CONSUMER_NAME.service"

        # Check service status
        echo " + Checking service status..."
        systemctl status "$SERVICE_CONSUMER_NAME.service"

        echo " + Restarting $SERVICE_PRODUCER_PUBSUB_NAME timer..."
        systemctl restart "$SERVICE_PRODUCER_PUBSUB_NAME.timer"

        echo " + Restarting $SERVICE_PRODUCER_API_NAME timer..."
        systemctl restart "$SERVICE_PRODUCER_API_NAME.timer"
    fi

    # Check if both services started successfully
    if systemctl is-active --quiet "$SERVICE_CONSUMER_NAME.service" && systemctl is-active --quiet "$SERVICE_PRODUCER_PUBSUB_NAME.timer"; then
        echo ""
        if [ "$INSTALL_MODE" = "fresh" ]; then
            echo "Installation completed successfully!"
            echo ""
            echo "Repository installed at: $TARGET_DIR"
            echo "Consumer service has been installed and started"    
        else
            echo "Update completed successfully!"
            echo ""
            echo "Repository at $TARGET_DIR has been updated"
            echo "Consumer service has been updated and restarted"
        fi

        echo ""
        echo "To update the repository in the future, run:"
        echo "  cd $TARGET_DIR && git pull"
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
        echo ""
    else
        echo ""
        if [ "$INSTALL_MODE" = "fresh" ]; then
            echo "   -> [ERROR] Failed to install services properly"
        else
            echo "   -> [ERROR] Failed to update services properly"
        fi
        echo "Consumer service status: $(systemctl is-active "$SERVICE_CONSUMER_NAME.service")"
        echo "Producer timer Pub/Sub status: $(systemctl is-active "$SERVICE_PRODUCER_PUBSUB_NAME.timer")"
        echo "Producer timer API status: $(systemctl is-active "$SERVICE_PRODUCER_API_NAME.timer")"
        echo ""
        exit 1
    fi
else
    # Install init.d services
    echo " + Copying init.d scripts to /etc/init.d/"
    cp "$TARGET_DIR/scripts/consumer/ckg-consumer" "/etc/init.d/"
    cp "$TARGET_DIR/scripts/producer/ckg-producer" "/etc/init.d/"

    # Set proper permissions
    chmod 755 "/etc/init.d/ckg-consumer"
    chmod 755 "/etc/init.d/ckg-producer"

    # Enable services to start on boot using chkconfig if available
    if command -v chkconfig &> /dev/null; then
        echo " + Enabling services with chkconfig..."
        chkconfig --add ckg-consumer
        chkconfig --add ckg-producer
        chkconfig ckg-consumer on
        chkconfig ckg-producer on
    elif command -v update-rc.d &> /dev/null; then
        echo " + Enabling services with update-rc.d..."
        update-rc.d ckg-consumer defaults
        update-rc.d ckg-producer defaults
    else
        echo "   -> [WARNING] Neither chkconfig nor update-rc.d found. Services not enabled for auto-start."
    fi

    if [ "$INSTALL_MODE" = "fresh" ]; then
        # Start the services
        echo " + Starting ckg-consumer service..."
        /etc/init.d/ckg-consumer start

        echo " + Starting ckg-producer service..."
        /etc/init.d/ckg-producer start
    else
        # Restart the services
        echo " + Restarting ckg-consumer service..."
        /etc/init.d/ckg-consumer restart

        echo " + Restarting ckg-producer service..."
        /etc/init.d/ckg-producer restart
    fi

    # Check if both services started successfully
    if /etc/init.d/ckg-consumer status &> /dev/null && /etc/init.d/ckg-producer status &> /dev/null; then
        echo ""
        if [ "$INSTALL_MODE" = "fresh" ]; then
            echo "Installation completed successfully!"
            echo ""
            echo "Repository installed at: $TARGET_DIR"
            echo "Consumer and Producer services have been installed and started"    
        else
            echo "Update completed successfully!"
            echo ""
            echo "Repository at $TARGET_DIR has been updated"
            echo "Consumer and Producer services have been updated and restarted"    
        fi
        
        echo ""
        echo "To update the repository in the future, run:"
        echo "  cd $TARGET_DIR && git pull"
        echo ""
        echo "You can manage the services with:"
        echo "  Consumer Service:"
        echo "    Start service:    sudo service ckg-consumer start"
        echo "    Stop service:     sudo service ckg-consumer stop"
        echo "    Restart service:  sudo service ckg-consumer restart"
        echo "    Check status:     sudo service ckg-consumer status"
        echo "    View logs:       sudo tail -f /var/log/ckg-consumer.log"
        echo "  Producer Service:"
        echo "    Start service:    sudo service ckg-producer start"
        echo "    Stop service:     sudo service ckg-producer stop"
        echo "    Restart service:  sudo service ckg-producer restart"
        echo "    Check status:     sudo service ckg-producer status"
        echo "    View logs:       sudo tail -f /var/log/ckg-producer.log"
        echo ""
    else
        echo "   -> Error: Failed to install services properly"
        echo "   -> Consumer service status:"
        /etc/init.d/ckg-consumer status
        echo "   -> Producer service status:"
        /etc/init.d/ckg-producer status
        echo ""
        exit 1
    fi
fi

if [ "$INSTALL_MODE" = "fresh" ]; then
    NEED_CONFIGURE="no"
    # Check if credentials.json exists
    echo ""
    echo " ----------------------------------------"
    echo " + Checking configuration files..."
    if [ ! -f "$TARGET_DIR/credentials.json" ]; then
        echo ""
        echo "   -> [WARNING] credentials.json not found at $TARGET_DIR/credentials.json"
        echo "   -> Please create this file with your Google Cloud credentials."
        echo "   -> Example format:"
        echo "   -> {"
        echo "   ->   \"type\": \"service_account\","
        echo "   ->   \"project_id\": \"your-project-id\","
        echo "   ->   \"private_key_id\": \"...\","
        echo "   ->   \"private_key\": \"...\","
        echo "   ->   \"client_email\": \"...\","
        echo "   ->   \"client_id\": \"...\","
        echo "   ->   \"auth_uri\": \"https://accounts.google.com/o/oauth2/auth\","
        echo "   ->   \"token_uri\": \"https://oauth2.googleapis.com/token\""
        echo "   -> }"
        echo ""
        read -p "   -> Press Enter to continue after creating credentials.json..."
        NEED_CONFIGURE="yes"
    else
        echo "   -> credentials.json found"
    fi

    # Prompt user to configure config.php
    echo ""
    echo " + Please configure settings in $TARGET_DIR/config.php"
    echo "   -> Make sure to set the following required settings:"
    echo "   -> - google_cloud.project_id"
    echo "   -> - pubsub.default_subscription"
    echo "   -> - pubsub.default_topic"
    echo "   -> - api.base_url"
    echo "   -> - api.api_key"
    echo "   -> - Database connection settings"
    echo ""
    read -p "   -> Press Enter to continue after configuring config.php..."

    echo ""
    echo " ----------------------------------------"
    echo " + Configuration check completed."
    echo ""

    if [ "$NEED_CONFIGURE" = "yes" ]; then
        echo "   -> [NOTE] Please restart service after update config.php and credentials.json."
    fi
fi
exit 0