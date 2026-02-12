#!/bin/sh

# Script to install SITB-CKG from GitHub and set up consumer service
USERID=sitb-ckg
GROUPID=sitb-ckg
REPO_URL="https://github.com/WB-TB/sitb-pub-sub.git"
TARGET_DIR="/opt/sitb-ckg"
LOG_DIR="/var/log/sitb-ckg"
SERVICE_CONSUMER_FILE="ckg-consumer.service"
SERVICE_CONSUMER_NAME="ckg-consumer"
NO_GIT=0
INSTALL_MODE=$1
USE_COMPOSER=$(which composer)
USE_GIT=$(which git)
USE_WGET=$(which wget)
USE_CURL=$(which curl)
USE_SYSTEMD=$(which systemctl)
USE_CHKCONFIG=$(which chkconfig)
USE_UPDATERC=$(which update-rc.d)
USE_SERVICE=$(which service)
if [ "$INSTALL_MODE" = "update" ]; then
    echo "Starting SITB-CKG update..."
else
    INSTALL_MODE="fresh"
    echo "Starting installation of SITB-CKG ($INSTALL_MODE)..."
fi

# Check if running as root
if [ "$(id -u)" -ne 0 ]; then
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
if [ -z "$USE_COMPOSER" ]; then
    echo " + [WARNING]: Composer is not installed. Composer will be installed locally."
    curl -sS https://getcomposer.org/installer | $PHPEXEC
fi

# Check if git is installed
if [ -z "$USE_GIT" ]; then
    echo "   -> [WARNING] git is not installed. Will download repository as zip file instead."
    NO_GIT=1
elif [ -f "$TARGET_DIR/version" -a ! -d "$TARGET_DIR/.git" ]; then # tidak melalui git
    NO_GIT=1
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
    chown "$USERID:$GROUPID" "$LOG_DIR"
    chmod 755 "$LOG_DIR"
else
    if [ "$NO_GIT" -eq 0 -a ! -d "$TARGET_DIR/.git" ]; then
        echo "   -> [ERROR] Cannot update: Repository does not exist at $TARGET_DIR"
        exit 1
    fi
    if [ "$NO_GIT" -eq 1 -a ! -d "$TARGET_DIR" ]; then
        echo "   -> [ERROR] Cannot update: Directory does not exist at $TARGET_DIR"
        exit 1
    fi

    if [ ! -f "$TARGET_DIR/composer.json" ]; then
        echo "   -> [ERROR] No composer.json found in the $TARGET_DIR."
        exit 1
    fi
fi

if [ "$NO_GIT" -eq 1 ]; then
    if [ "$INSTALL_MODE" = "update" ]; then
        echo "   -> Checking for version update..."
        VERSION_URL="https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/refs/heads/main/version"
        
        if [ -n "$USE_WGET" ]; then
            wget -q -O "$TEMP_DIR/remote_version" "$VERSION_URL"
        elif [ -n "$USE_CURL" ]; then
            curl -sL -o "$TEMP_DIR/remote_version" "$VERSION_URL"
        else
            echo "   -> [ERROR] Neither wget nor curl is available. Cannot check version."
            rm -rf "$TEMP_DIR"
            exit 1
        fi
        
        if [ $? -ne 0 ]; then
            echo "   -> [ERROR] Failed to download version file"
            rm -rf "$TEMP_DIR"
            exit 1
        fi
        
        # Read remote version
        REMOTE_VERSION=$(cat "$TEMP_DIR/remote_version" | tr -d '[:space:]')
        
        # Read local version if exists
        if [ -f "$TARGET_DIR/version" ]; then
            LOCAL_VERSION=$(cat "$TARGET_DIR/version" | tr -d '[:space:]')
        else
            LOCAL_VERSION=""
        fi
        
        # Compare versions
        if [ "$REMOTE_VERSION" = "$LOCAL_VERSION" ]; then
            echo "   -> [INFO] No version update available. Current version: $LOCAL_VERSION"
            rm -rf "$TEMP_DIR"
            exit 0
        else
            echo "   -> [INFO] Version update available: $LOCAL_VERSION -> $REMOTE_VERSION"
        fi
    fi

    # Handle installation without git - download zip from REPO_URL and extract
    echo " + Downloading repository as zip file..."
    
    # Create temp directory for download
    TEMP_DIR=$(mktemp -d)
    
    # Download zip file from GitHub
    ZIP_URL="${REPO_URL%.git}/archive/refs/heads/main.zip"
    echo "   -> Downloading from: $ZIP_URL"
    
    if [ -n "$USE_WGET" ]; then
        wget -q -O "$TEMP_DIR/repo.zip" "$ZIP_URL"
    elif [ -n "$USE_CURL" ]; then
        curl -sL -o "$TEMP_DIR/repo.zip" "$ZIP_URL"
    else
        echo "   -> [ERROR] Neither wget nor curl is available. Cannot download repository."
        rm -rf "$TEMP_DIR"
        exit 1
    fi

    if [ $? -ne 0 ]; then
        echo "   -> [ERROR] Failed to download repository zip file"
        rm -rf "$TEMP_DIR"
        exit 1
    fi
    
    echo "   -> Download completed successfully"
    
    # Extract zip file
    echo "   -> Extracting zip file..."
    unzip -q "$TEMP_DIR/repo.zip" -d "$TEMP_DIR"
    
    if [ $? -ne 0 ]; then
        echo "   -> [ERROR] Failed to extract zip file"
        rm -rf "$TEMP_DIR"
        exit 1
    fi
    
    # Find the extracted directory
    EXTRACTED_DIR=$(find "$TEMP_DIR" -maxdepth 1 -type d -name "sitb-pub-sub-*" | head -n 1)
    
    if [ -z "$EXTRACTED_DIR" ]; then
        echo "   -> [ERROR] Could not find extracted repository directory"
        rm -rf "$TEMP_DIR"
        exit 1
    fi
    
    echo "   -> Extraction completed successfully"
    
    # Copy/overwrite contents to TARGET_DIR
    echo "   -> Copying files to $TARGET_DIR..."
    
    if [ "$INSTALL_MODE" = "fresh" ]; then
        # For fresh install, create directory if it doesn't exist
        if [ ! -d "$TARGET_DIR" ]; then
            mkdir -p "$TARGET_DIR"
            chown "$USERID:$GROUPID" "$TARGET_DIR"
            chmod 755 "$TARGET_DIR"
        fi
    fi
    
    # Copy all files from extracted directory to TARGET_DIR, overwriting existing files
    cp -rf "$EXTRACTED_DIR"/* "$TARGET_DIR/"
    
    if [ $? -ne 0 ]; then
        echo "   -> [ERROR] Failed to copy files to $TARGET_DIR"
        rm -rf "$TEMP_DIR"
        exit 1
    fi
    
    echo "   -> Files copied successfully"
    
    # Clean up temp directory
    rm -rf "$TEMP_DIR"
    echo "   -> Temporary files cleaned up"
else
    # Menambahkan safe.directory untuk Git
    git config --global --add safe.directory "$TARGET_DIR"

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

        # Clone the repository
        git clone "$REPO_URL" "$TARGET_DIR"
        
        if [ $? -ne 0 ]; then
            echo "   -> Error: Failed to clone repository"
            exit 1
        fi
        echo "   -> Repository cloned successfully"
    fi
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

# Update init.d scripts with correct paths
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/consumer/ckg-consumer"
sed -i "s|/opt/sitb-ckg|$TARGET_DIR|g" "$TARGET_DIR/scripts/producer/ckg-producer"

echo " + Repository cloned/updated successfully at $TARGET_DIR"

# Detect system manager (systemd or init.d)
echo " + Detecting system manager..."
SYSTEM_MANAGER="init.d"

if [ -n "$USE_SYSTEMD" ] && [ -d /run/systemd/system ]; then
    SYSTEM_MANAGER="systemd"
    echo "   -> System manager detected: systemd"
else
    echo "   -> System manager detected: init.d"
fi

# Install services based on system manager
echo " + Installing ckg-consumer service and ckg-producer cronjob..."

if [ "$SYSTEM_MANAGER" = "systemd" ]; then
    # Install systemd consumer service
    echo " + Copying consumer service file to /etc/systemd/system/"
    cp "$TARGET_DIR/scripts/consumer/$SERVICE_CONSUMER_FILE" "/etc/systemd/system/"

    # Set proper permissions
    chmod 644 "/etc/systemd/system/$SERVICE_CONSUMER_FILE"

    # Reload systemd to recognize the new service
    echo " + Reloading systemd daemon..."
    systemctl daemon-reload

    # Enable the service to start on boot
    echo " + Enabling $SERVICE_CONSUMER_NAME service..."
    systemctl enable "$SERVICE_CONSUMER_NAME.service"

    if [ "$INSTALL_MODE" = "fresh" ]; then
        # Start the service
        echo " + Starting $SERVICE_CONSUMER_NAME service..."
        systemctl start "$SERVICE_CONSUMER_NAME.service"

        # Check service status
        echo " + Checking service status..."
        systemctl status "$SERVICE_CONSUMER_NAME.service"
    else
        # Restart the service
        echo " + Restarting $SERVICE_CONSUMER_NAME service..."
        systemctl restart "$SERVICE_CONSUMER_NAME.service"

        # Check service status
        echo " + Checking service status..."
        systemctl status "$SERVICE_CONSUMER_NAME.service"
    fi

    # Setup cronjobs for producer services (works on both systemd and init.d)
    echo " + Setting up cronjobs for producer services..."
    
    # Remove existing cronjobs for ckg-producer if they exist
    if [ -f "/etc/cron.d/ckg-producer" ]; then
        echo "   -> Removing existing cronjobs..."
        rm -f "/etc/cron.d/ckg-producer"
    fi
    
    # Create cronjob file for producer services
    echo "   -> Creating cronjob file /etc/cron.d/ckg-producer"
    cat > "/etc/cron.d/ckg-producer" << EOF
# CKG Producer Cronjobs
# Run ckg-producer pubsub or api daily at 2:00 AM
0 2 * * * root $TARGET_DIR/scripts/producer/ckg-producer >> /var/log/ckg-producer.log 2>&1

# Update Service
# Check for git updates every 15 minutes and pull if there are changes
0 0 * * * root $TARGET_DIR/scripts/install.sh update >> /var/log/ckg-update.log 2>&1
EOF
    
    # Set proper permissions for cron file
    chmod 644 "/etc/cron.d/ckg-producer"
    
    # Reload cron service to recognize new cronjobs
    echo "   -> Reloading cron service..."
    if [ -n "$USE_SYSTEMD" ] && systemctl is-active --quiet cron; then
        systemctl reload cron
    elif [ -n "$USE_SYSTEMD" ] && systemctl is-active --quiet crond; then
        systemctl reload crond
    fi
    
    echo "   -> Cronjobs configured successfully"

    if [ "$INSTALL_MODE" = "fresh" ]; then
        echo " + Producer services will run via cronjobs at scheduled times"
        echo "   -> ckg-producer pubsub or api: Daily at 2:00 AM"
    else
        echo " + Producer cronjobs updated"
        echo "   -> ckg-producer pubsub or api: Daily at 2:00 AM"
    fi

    # Check if consumer service started successfully
    if systemctl is-active --quiet "$SERVICE_CONSUMER_NAME.service"; then
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
        if [ "$NO_GIT" -eq 1 ]; then
            echo "  sudo $TARGET_DIR/scripts/install.sh update"
        else
            echo "  cd $TARGET_DIR && git pull"
        fi
    else
        echo ""
        if [ "$INSTALL_MODE" = "fresh" ]; then
            echo "   -> [ERROR] Failed to install services properly"
        else
            echo "   -> [ERROR] Failed to update services properly"
        fi
        echo "Consumer service status: $(systemctl is-active "$SERVICE_CONSUMER_NAME.service")"
        echo ""
    fi

    echo ""
    echo " ----------------------------------------"
    echo ""
    echo "You can manage the service with:"
    echo "  Consumer Service:"
    echo "    Start service:    sudo systemctl start ckg-consumer.service"
    echo "    Stop service:     sudo systemctl stop ckg-consumer.service"
    echo "    Restart service:  sudo systemctl restart ckg-consumer.service"
    echo "    Check status:     sudo systemctl status ckg-consumer.service"
    echo "    View logs:        sudo journalctl -u ckg-consumer.service -f"
    echo "  Producer Services (Cronjobs):"
    echo "    Schedule:         /etc/cron.d/ckg-producer"
    echo "    Runs:             Daily at 2:00 AM"
    echo "    View log:         sudo tail -f /var/log/ckg-producer.log"
    echo "    Manually run:     sudo $TARGET_DIR/scripts/producer/ckg-producer [pubsub|api]"
    echo ""
else
    # Install init.d consumer service
    echo " + Copying init.d consumer script to /etc/init.d/"
    cp "$TARGET_DIR/scripts/consumer/ckg-consumer" "/etc/init.d/"
    
    # Set proper permissions
    chmod 755 "/etc/init.d/ckg-consumer"

    # Enable consumer service to start on boot using chkconfig if available
    if [ -n "$USE_CHKCONFIG" ]; then
        echo " + Enabling consumer service with chkconfig..."
        chkconfig --add ckg-consumer
        chkconfig ckg-consumer on
    elif [ -n "$USE_UPDATERC" ]; then
        echo " + Enabling consumer service with update-rc.d..."
        update-rc.d ckg-consumer defaults
    else
        echo "   -> [WARNING] Neither chkconfig nor update-rc.d found. Consumer service not enabled for auto-start."
    fi

    # Setup cronjobs for producer services
    echo " + Setting up cronjobs for producer services..."
    
    # Remove existing cronjobs for ckg-producer if they exist
    if [ -f "/etc/cron.d/ckg-producer" ]; then
        echo "   -> Removing existing cronjobs..."
        rm -f "/etc/cron.d/ckg-producer"
    fi
    
    # Create cronjob file for producer services
    echo "   -> Creating cronjob file /etc/cron.d/ckg-producer"
    cat > "/etc/cron.d/ckg-producer" << EOF
# CKG Producer Cronjobs
# Run ckg-producer pubsub or api daily at 2:00 AM
0 2 * * * root $TARGET_DIR/scripts/producer/ckg-producer >> /var/log/ckg-producer.log 2>&1
EOF
    
    # Set proper permissions for cron file
    chmod 644 "/etc/cron.d/ckg-producer"
    
    # Reload cron service to recognize new cronjobs
    echo "   -> Reloading cron service..."
    if [ -n "$USE_SERVICE" ]; then
        service cron reload 2>/dev/null || service crond reload 2>/dev/null
    elif [ -f /etc/init.d/cron ]; then
        /etc/init.d/cron reload 2>/dev/null
    elif [ -f /etc/init.d/crond ]; then
        /etc/init.d/crond reload 2>/dev/null
    fi
    
    echo "   -> Cronjobs configured successfully"

    if [ "$INSTALL_MODE" = "fresh" ]; then
        # Start the services
        echo " + Starting ckg-consumer service..."
        echo -n "   -> "
        /etc/init.d/ckg-consumer start

        echo " + Producer services will run via cronjobs at scheduled times"
        echo "   -> ckg-producer pubsub or api: Daily at 2:00 AM"
    else
        # Restart the consumer service
        echo " + Restarting ckg-consumer service..."
        echo -n "   -> "
        /etc/init.d/ckg-consumer restart

        echo " + Producer cronjobs updated"
        echo "   -> ckg-producer pubsub or api: Daily at 2:00 AM"
    fi

    echo ""
    echo " ----------------------------------------"

    # Check if consumer service started successfully
    if /etc/init.d/ckg-consumer status &> /dev/null; then
        echo ""
        if [ "$INSTALL_MODE" = "fresh" ]; then
            echo "Installation completed successfully!"
            echo ""
            echo "Repository installed at: $TARGET_DIR"
            echo "Consumer service has been installed and started"
            echo "Producer services will run via cronjobs"
        else
            echo "Update completed successfully!"
            echo ""
            echo "Repository at $TARGET_DIR has been updated"
            echo "Consumer service has been updated and restarted"
            echo "Producer cronjobs have been updated"
        fi
    else
        echo "   -> Error: Failed to install services properly"
        echo "   -> Consumer service status:"
        /etc/init.d/ckg-consumer status
        echo ""
    fi

    echo ""
    echo " ----------------------------------------"
    echo ""
    echo "To update the repository in the future, run:"
    if [ "$NO_GIT" -eq 1 ]; then
        echo "  sudo $TARGET_DIR/scripts/install.sh update"
    else
        echo "  cd $TARGET_DIR && git pull"
    fi
    echo ""
    echo "You can manage the services with:"
    echo "  Consumer Service:"
    echo "    Start service:    sudo service ckg-consumer start"
    echo "    Stop service:     sudo service ckg-consumer stop"
    echo "    Restart service:  sudo service ckg-consumer restart"
    echo "    Check status:     sudo service ckg-consumer status"
    echo "    View logs:        sudo tail -f /var/log/ckg-consumer.log"
    echo "  Producer Services (Cronjobs):"
    echo "    Schedule:         /etc/cron.d/ckg-producer"
    echo "    Runs:             Daily at 2:00 AM"
    echo "    View log:         sudo tail -f /var/log/ckg-producer.log"
    echo "    Manually run:     sudo $TARGET_DIR/scripts/producer/ckg-producer [pubsub|api]"
    echo ""
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
        # read -p "   -> Press Enter to continue after creating credentials.json..."
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
    # read -p "   -> Press Enter to continue after configuring config.php..."

    echo ""
    echo " ----------------------------------------"
    echo " + Configuration check completed."
    echo ""

    if [ "$NEED_CONFIGURE" = "yes" ]; then
        echo "   -> [NOTE] Please restart service after update config.php and credentials.json."
        if [ "$SYSTEM_MANAGER" = "systemd" ]; then
            echo "      To restart the service, run:"
            echo "        sudo systemctl restart $SERVICE_CONSUMER_NAME.service"
        else
            echo "      To restart the service, run:"
            echo "        sudo service ckg-consumer restart"
        fi
        echo "      Cronjobs will automatically run at scheduled times"
        echo "      To manually run producer: sudo $TARGET_DIR/scripts/producer/ckg-producer [pubsub|api]"
    fi
fi
exit 0