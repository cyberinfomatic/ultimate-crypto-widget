<?php
/**
 * WordPress test-suite configuration.
 *
 * Defaults suit a local MySQL with a passwordless root user (Laragon, XAMPP).
 * Override with the WP_TESTS_DB_* environment variables in CI.
 *
 * The database named here is wiped on every run. Never point it at a real site.
 *
 * @package Ultimate_Crypto_Widget
 */

$ucwp_tests_root = dirname( __DIR__ );

define( 'ABSPATH', $ucwp_tests_root . '/vendor/roots/wordpress-no-content/' );
define( 'WP_CONTENT_DIR', $ucwp_tests_root . '/tests/.wp-content' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'ucw_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', false !== getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Ultimate Crypto Widget tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

define( 'WP_DEBUG', true );

define( 'AUTH_KEY', 'tests' );
define( 'SECURE_AUTH_KEY', 'tests' );
define( 'LOGGED_IN_KEY', 'tests' );
define( 'NONCE_KEY', 'tests' );
define( 'AUTH_SALT', 'tests' );
define( 'SECURE_AUTH_SALT', 'tests' );
define( 'LOGGED_IN_SALT', 'tests' );
define( 'NONCE_SALT', 'tests' );
