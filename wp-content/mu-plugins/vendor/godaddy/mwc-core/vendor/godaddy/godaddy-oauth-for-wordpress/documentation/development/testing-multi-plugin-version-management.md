# Testing Multi-Plugin Version Management

This guide explains how to test the OAuth package's Action Scheduler-inspired version management system that prevents conflicts when multiple plugins bundle different versions.

## Overview

The OAuth package uses a sophisticated version management system:
- **Multiple plugins** can bundle different versions without conflicts
- **Only the highest version** loads and initializes  
- **All plugins** get access to the latest available version
- **No "class already declared" errors** occur

## Test Setup

### Prerequisites
- WordPress site with WooCommerce (for logging)
- Access to plugin directory and composer
- mwc-core plugin with OAuth package integrated

### Creating the Test Helper Plugin

1. **Create plugin directory:**
```bash
mkdir wp-content/plugins/oauth-helper-test
cd wp-content/plugins/oauth-helper-test
```

2. **Create `oauth-helper-test.php`:**
```php
<?php
/**
 * Plugin Name: OAuth Helper Test
 * Description: Test plugin to verify OAuth package multi-plugin loading
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the OAuth package (same as mwc-core does)
require_once plugin_dir_path(__FILE__) . 'vendor/godaddy/godaddy-oauth-for-wordpress/load.php';
```

3. **Create `composer.json`:**
```json
{
    "name": "test/oauth-helper-test",
    "description": "Test plugin to verify OAuth package multi-plugin loading",
    "type": "wordpress-plugin",
    "license": "GPL-2.0",
    "minimum-stability": "dev",
    "require": {
        "php": ">=7.4 <8.4",
        "godaddy/godaddy-oauth-for-wordpress": "dev-main"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:gdcorp-partners/godaddy-oauth-for-wordpress.git"
        }
    ],
    "config": {
        "allow-plugins": {
            "phpstan/extension-installer": true,
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}
```

4. **Install dependencies:**
```bash
composer install
```

## Test Scenarios

### Scenario 1: Basic Multi-Plugin Loading

**Test:** Both plugins load without conflicts

**Steps:**
1. Activate mwc-core plugin
2. Activate oauth-helper-test plugin
3. Check for fatal errors

**Expected Result:** ✅ Both plugins activate successfully with no conflicts

---

### Scenario 2: Version Management & Upgrade Testing

**Test:** Version system selects highest version and upgrading one plugin upgrades OAuth for all plugins

**QA Snippet** (add to helper plugin):
```php
add_action('plugins_loaded', function() {
    if (function_exists('wc_get_logger') && class_exists('\GoDaddy\WordPress\OAuth\Package')) {
        
        // Get which version actually initialized the system
        $initVersion = \GoDaddy\WordPress\OAuth\Versions::getLatestVersion();
        
        wc_get_logger()->info("🚀 OAuth Package Initialized: Version {$initVersion}", ['source' => 'oauth-qa']);
        
        // Confirm version management is working
        if ($initVersion === '1.1.0') {
            wc_get_logger()->info("✅ QA PASS: oauth-helper-test version is active", ['source' => 'oauth-qa']);
        } elseif ($initVersion === '1.0.0') {
            wc_get_logger()->info("ℹ️  QA INFO: mwc-core version is active", ['source' => 'oauth-qa']);
        }
    }
}, 100);
```

**Steps:**
1. Both plugins have same version (1.0.0) - Check logs should show version 1.0.0 active
2. Update helper plugin to version 1.1.0 (see instructions below)
3. Check logs - should show version 1.1.0 active

**Steps to Create Version 1.1.0:**

1. **Edit helper plugin's `vendor/godaddy/godaddy-oauth-for-wordpress/src/Package.php`:**
```php
const VERSION = '1.1.0';  // Change from 1.0.0
```

2. **Edit helper plugin's `vendor/godaddy/godaddy-oauth-for-wordpress/load.php`:**
```php
// Update check
function_exists('godaddy_oauth_initialize_1_1_0')

// Update version registration
\GoDaddy\WordPress\OAuth\Versions::register(
    '1.1.0',  // Updated version
    'godaddy_oauth_initialize_1_1_0'  // Updated function name
);

// Update initialization function
function godaddy_oauth_initialize_1_1_0() {  // Updated name
    require_once(__DIR__ . '/src/Package.php');
    \GoDaddy\WordPress\OAuth\Package::instance();
}
```

**Expected Result:** ✅ Higher version always wins initialization → System automatically uses v1.1.0, upgrading all plugins

---

### Architecture Explanation

1. **Composer Autoloading**: Makes classes *available* but doesn't initialize them
2. **Manual Loading**: `load.php` calls trigger the version management system
3. **Version Selection**: Only the highest version's initialization function runs
4. **Result**: All plugins get access to the latest OAuth functionality
