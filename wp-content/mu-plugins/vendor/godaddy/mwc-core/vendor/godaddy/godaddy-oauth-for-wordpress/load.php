<?php

/**
 * OAuth Package Loader.
 *
 * Version-aware loading system that ensures only the highest version loads
 * when multiple plugins include different versions of this package.
 */

// Prevent direct access (skip gracefully when running from CLI, e.g. PHPUnit)
if (! defined('ABSPATH')) {
    if (PHP_SAPI === 'cli') {
        return;
    }

    exit;
}

if (! function_exists('godaddy_oauth_for_wordpress_initialize_1_0_0')) {
    // Load versions handler if not already loaded
    if (! class_exists('\\GoDaddy\\WordPress\\OAuth\\Versions')) {
        require_once __DIR__.'/src/Versions.php';
    }

    // Register hook to initialize the latest version
    add_action('plugins_loaded', ['\\GoDaddy\\WordPress\\OAuth\\Versions', 'initializeLatestVersion'], 99);

    // Register THIS version (1.0.0) with the versions handler
    GoDaddy\WordPress\OAuth\Versions::register(
        '1.0.0',
        'godaddy_oauth_for_wordpress_initialize_1_0_0'
    );

    // Define the initialization function for THIS version
    function godaddy_oauth_for_wordpress_initialize_1_0_0()
    {
        require_once __DIR__.'/src/Package.php';
        GoDaddy\WordPress\OAuth\Package::instance();
    }
}
