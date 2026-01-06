#!/bin/bash

# Script to run the consumer application
# Detects and uses the PHP executable specified in $PHPEXEC
PHPEXEC=`which php`

# Check if PHPEXEC is set
if [ -z "$PHPEXEC" ]; then
    echo "Error: PHP not found."
    exit 1
fi

# Check if the PHP executable exists and is executable
if [ ! -x "$PHPEXEC" ]; then
    echo "Error: PHP executable not found or not executable: $PHPEXEC"
    exit 1
fi

# Check if the consumer script exists
CONSUMER_SCRIPT="/opt/sitb-ckg/consumer.php"
if [ ! -f "$CONSUMER_SCRIPT" ]; then
    echo "Error: Consumer script not found: $CONSUMER_SCRIPT"
    exit 1
fi

PHPVERSION=$($PHPEXEC -r 'echo PHP_VERSION;')

# Run the consumer
echo "Starting consumer with PHP: $PHPEXEC"
echo "Using PHP version: $PHPVERSION"
echo "Consumer script: $CONSUMER_SCRIPT"
if [ $# -gt 0 ]; then
    echo "Arguments: $@"
fi
$PHPEXEC -f "$CONSUMER_SCRIPT" "$@"

# Exit with the same code as the PHP script
exit $?
