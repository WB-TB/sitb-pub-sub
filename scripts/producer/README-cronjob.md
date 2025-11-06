# CKG Producer Cronjobs Setup

This directory contains scripts to set up cronjobs for running `producer.php` in both API and PubSub modes automatically.

## Files

### Execution Scripts
- `run-producer-api.sh` - Executes the producer in API mode
- `run-producer-pubsub.sh` - Executes the producer in PubSub mode

### Setup Scripts
- `setup-producer-cron.sh` - Setup script for API mode cronjob
- `setup-producer-pubsub-cron.sh` - Setup script for PubSub mode cronjob
- `setup-all-producer-crons.sh` - Unified setup script for both cronjobs

### Documentation
- `README-cronjob.md` - This documentation file

## Installation

### Option 1: Install Both Cronjobs (Recommended)

1. Make sure the setup script is executable:
   ```bash
   chmod +x scripts/setup-all-producer-crons.sh
   ```

2. Run the setup script with root privileges to add both cronjobs:
   ```bash
   sudo ./scripts/setup-all-producer-crons.sh add-all
   ```

### Option 2: Install Individual Cronjobs

1. Make sure the setup scripts are executable:
   ```bash
   chmod +x scripts/setup-producer-cron.sh
   chmod +x scripts/setup-producer-pubsub-cron.sh
   ```

2. Run the setup scripts with root privileges:
   ```bash
   # For API mode
   sudo ./scripts/setup-producer-cron.sh add
   
   # For PubSub mode
   sudo ./scripts/setup-producer-pubsub-cron.sh add
   ```

## Usage

### Managing Both Cronjobs (Recommended)

Use the unified setup script to manage both cronjobs:

- **Add both cronjobs**: `sudo ./scripts/setup-all-producer-crons.sh add-all`
- **Remove both cronjobs**: `sudo ./scripts/setup-all-producer-crons.sh remove-all`
- **Check status**: `sudo ./scripts/setup-all-producer-crons.sh status`
- **List all crontabs**: `sudo ./scripts/setup-all-producer-crons.sh list`

### Managing Individual Cronjobs

#### API Mode
- **Add cronjob**: `sudo ./scripts/setup-producer-cron.sh add`
- **Remove cronjob**: `sudo ./scripts/setup-producer-cron.sh remove`
- **Check status**: `sudo ./scripts/setup-producer-cron.sh status`

#### PubSub Mode
- **Add cronjob**: `sudo ./scripts/setup-producer-pubsub-cron.sh add`
- **Remove cronjob**: `sudo ./scripts/setup-producer-pubsub-cron.sh remove`
- **Check status**: `sudo ./scripts/setup-producer-pubsub-cron.sh status`

### Manual Execution

You can also run the producers manually:

```bash
# API mode
./scripts/run-producer-api.sh

# PubSub mode
./scripts/run-producer-pubsub.sh
```

### Viewing Logs

All cronjob executions are logged to separate files:

```
logs/producer-api-cron.log      # API mode logs
logs/producer-pubsub-cron.log    # PubSub mode logs
```

You can view the logs with:
```bash
# API mode logs
tail -f logs/producer-api-cron.log

# PubSub mode logs
tail -f logs/producer-pubsub-cron.log
```

## Configuration

### Schedules

#### API Mode
The default schedule is set to run every 6 hours:
```
0 */6 * * * /path/to/scripts/run-producer-api.sh
```

#### PubSub Mode
The default schedule is set to run every 12 hours:
```
0 */12 * * * /path/to/scripts/run-producer-pubsub.sh
```

Schedule meanings:
- Minute: 0 (at the start of the hour)
- Hour: */6 or */12 (every 6 or 12 hours)
- Day: * (every day)
- Month: * (every month)
- Weekday: * (every weekday)

To change the schedule, edit the `CRON_JOB` variable in the respective setup script.

### Log Rotation

The log files will grow over time. Consider setting up log rotation:

```bash
# For API mode
sudo logrotate -f /etc/logrotate.d/producer-api-cron

# For PubSub mode
sudo logrotate -f /etc/logrotate.d/producer-pubsub-cron
```

You can create logrotate configuration files:

#### API Mode Configuration
```
/var/www/html/logs/producer-api-cron.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

#### PubSub Mode Configuration
```
/var/www/html/logs/producer-pubsub-cron.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## Troubleshooting

### Common Issues

1. **Permission denied**: Make sure the script is executable and run with the correct user
2. **PHP not found**: The script will automatically detect PHP, but you can manually set the PHP path
3. **Path issues**: The script uses relative paths, so make sure you run it from the project root

### Debugging

1. Check the appropriate log file:
   ```bash
   # API mode
   tail -f logs/producer-api-cron.log
   
   # PubSub mode
   tail -f logs/producer-pubsub-cron.log
   ```

2. Test the script manually:
   ```bash
   # API mode
   ./scripts/run-producer-api.sh
   
   # PubSub mode
   ./scripts/run-producer-pubsub.sh
   ```

3. Check if PHP can run the script:
   ```bash
   # API mode
   php producer.php --mode=api
   
   # PubSub mode
   php producer.php --mode=pubsub
   ```

### System Requirements

- PHP CLI installed
- Write permissions to the logs directory
- Proper file permissions for the scripts

## Security Considerations

- The scripts run with the permissions of the user who set up the cronjob (typically root)
- Ensure the logs directory has appropriate permissions
- Consider running the script as a dedicated user if security is a concern
- Use separate log files for different modes to isolate potential issues

## Recommended Setup

For a production environment, we recommend:

1. **API Mode**: Runs every 6 hours to process data through the API
2. **PubSub Mode**: Runs every 12 hours to publish data to PubSub

This ensures that both data processing channels are regularly updated without overlapping too frequently.