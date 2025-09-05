<?php

namespace GivePaystack\Addon;

use GivePaystack\Settings\PaystackSettings;

class Links
{
    /**
     * Add settings link
     * @return array
     * @since 3.0.0
     */
    public function __invoke($actions)
    {
        $newActions = array(
            'settings' => sprintf(
                '<a href="%1$s">%2$s</a>',
                PaystackSettings::getSettingsUrl(),
                __('Settings', 'give-paystack')
            ),
        );

        return array_merge($newActions, $actions);
    }
}
