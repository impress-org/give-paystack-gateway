<?php

namespace GivePaystack\Addon;

use Give_Addon_Activation_Banner;
use GivePaystack\Settings\PaystackSettings;

/**
 * Helper class responsible for showing add-on Activation Banner.
 * @since 3.0.0
 */
class ActivationBanner
{

    /**
     * Show activation banner
     *
     * @since 3.0.0
     * @return void
     */
    public function show()
    {
        // Check for Activation banner class.
        if ( ! class_exists('Give_Addon_Activation_Banner')) {
            include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
        }

        // Only runs on admin.
        $args = [
            'file' => GIVE_PAYSTACK_FILE,
            'name' => GIVE_PAYSTACK_NAME,
            'version' => GIVE_PAYSTACK_VERSION,
            'settings_url' => PaystackSettings::getSettingsUrl(),
            'documentation_url' => 'https://docs.givewp.com/paystack-doc',
            'support_url' => 'https://givewp.com/support/',
            'testing' => false, // Never leave true.
        ];

        new Give_Addon_Activation_Banner($args);
    }
}
