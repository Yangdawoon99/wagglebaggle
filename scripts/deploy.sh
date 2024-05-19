#!/bin/bash
# deploy.sh

# Install dependencies if necessary
# sudo yum install -y httpd php php-mysql

# Copy files to the web server root directory
# Assuming CodeDeploy has copied the files to /var/www/html already

# Restart Apache to apply changes
sudo service httpd restart

# Restart Apache to apply changes
