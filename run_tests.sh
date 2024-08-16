#!/bin/bash

# create a function that always print action based on parameter passed into the funtion
function action() {
#	echo with background color
	echo -e "\033[32m=> $1\033[0m"
}

# Install PHPUnit
action "Installing PHPUnit"
composer global require "phpunit/phpunit"
action "PHPUnit Installed"

# Install WordPress Coding Standards
action "Installing WordPress Coding Standards"
composer global require wp-coding-standards/wpcs
action "WordPress Coding Standards Installed"
action "Setting up WordPress Coding Standards"
phpcs --config-set installed_paths $HOME/.composer/vendor/wp-coding-standards/wpcs
action "WordPress Coding Standards Set"

# Run PHP CodeSniffer (PHPCS)
action "Running PHP CodeSniffer"
phpcs
action "PHP CodeSniffer Complete"

# Clean up previous test directories
action "Cleaning up previous test directories"
rm -rf $WP_TESTS_DIR $WP_CORE_DIR
action "Clean up complete"

# Install WordPress test environment4
action "Installing WordPress test environment"
bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest
action "WordPress test environment installed"

# Run PHPUnit tests
action "Running PHPUnit tests"
phpunit
action "PHPUnit tests complete"

# Run PHPUnit tests for multisite
action "Running PHPUnit tests for multisite"
WP_MULTISITE=1 phpunit
action "PHPUnit tests for multisite complete"
