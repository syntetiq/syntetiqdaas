#!/bin/bash

set -e

# Export Docker envs to a file for www-data
printenv | grep -v "no_proxy" > /etc/environment
chown www-data:www-data /etc/environment

# Load crontab for www-data
# crontab -u www-data /crontab.txt

# Start process cron
cron

# Start process supervisord
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf
