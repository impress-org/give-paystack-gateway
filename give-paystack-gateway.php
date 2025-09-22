<?php
namespace GivePaystack;

use GivePaystack\Addon\Activation;
use GivePaystack\Addon\Environment;
use GivePaystack\Addon\ServiceProvider as AddonServiceProvider;
use GivePaystack\Settings\ServiceProvider as SettingsServiceProvider;
use GivePaystack\Paystack\ServiceProvider as PaystackServiceProvider;

/**
 * Plugin Name:         Give - Paystack Gateway
 * Plugin URI:          https://docs.givewp.com/paystack-add-on
 * Description:         Fundraise with Paystack and GiveWP
 * Version:             3.0.0
 * Requires at least:   6.6
 * Requires PHP:        7.4
 * Author:              GiveWP
 * Author URI:          https://givewp.com/
 * Text Domain:         give-paystack
 * Domain Path:         /languages
 */
defined('ABSPATH') or exit;

// Add-on name
define('GIVE_PAYSTACK_NAME', 'Give - Paystack Gateway');

// Versions
define('GIVE_PAYSTACK_VERSION', '3.0.0');
define('GIVE_PAYSTACK_MIN_GIVE_VERSION', '4.7.0');

// Add-on paths
define('GIVE_PAYSTACK_FILE', __FILE__);
define('GIVE_PAYSTACK_DIR', plugin_dir_path(GIVE_PAYSTACK_FILE));
define('GIVE_PAYSTACK_URL', plugin_dir_url(GIVE_PAYSTACK_FILE));
define('GIVE_PAYSTACK_BASENAME', plugin_basename(GIVE_PAYSTACK_FILE));

require_once __DIR__ . '/vendor/autoload.php';

// Activate add-on hook.
register_activation_hook(GIVE_PAYSTACK_FILE, [Activation::class, 'activateAddon']);

// Deactivate add-on hook.
register_deactivation_hook(GIVE_PAYSTACK_FILE, [Activation::class, 'deactivateAddon']);

// Uninstall add-on hook.
register_uninstall_hook(GIVE_PAYSTACK_FILE, [Activation::class, 'uninstallAddon']);

// Register the add-on service provider with the GiveWP core.
add_action(
    'before_give_init',
    function () {
        // Check Give min required version.
        if (Environment::giveMinRequiredVersionCheck()) {
            give()->registerServiceProvider(AddonServiceProvider::class);
            give()->registerServiceProvider(SettingsServiceProvider::class);
            give()->registerServiceProvider(PaystackServiceProvider::class);
        }
    }
);

// Check to make sure GiveWP core is installed and compatible with this add-on.
add_action('admin_init', [Environment::class, 'checkEnvironment']);
