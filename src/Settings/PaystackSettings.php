<?php

namespace GivePaystack\Settings;

/**
 * Paystack settings.
 *
 * @since 3.0.0
 */
class PaystackSettings
{
    /**
     * Register the Paystack settings.
     *
     * @return void
     */
    public static function register()
    {
        add_filter('give_get_sections_gateways', [self::class, 'registerSections']);
        add_filter('give_get_settings_gateways', [self::class, 'addSettings']);
    }

    /**
     * Register the Paystack section.
     *
     * @param array $sections
     */
    public static function registerSections($sections): array
    {
        $sections['paystack'] = __('Paystack', 'give-paystack');
        return $sections;
    }

    /**
     * Add the Paystack settings.
     *
     * @since 3.0.0
     *
     * @param array $settings
     */
    public static function addSettings($settings): array
    {
        // Only show settings when in the Paystack section
        if (give_get_current_setting_section() !== 'paystack') {
            return $settings;
        }

        $settings[] = [
            'id'   => 'give_title_paystack_settings',
            'type' => 'title',
        ];

        $settings[] = [
            'name' => __('Paystack Live Public Key', 'give-paystack'),
            'desc' => __('Enter your Paystack live public key.', 'give-paystack'),
            'id'   => 'paystack_live_public_key',
            'type' => 'text',
        ];

        $settings[] = [
            'name' => __('Paystack Live Secret Key', 'give-paystack'),
            'desc' => __('Enter your Paystack live secret key.', 'give-paystack'),
            'id'   => 'paystack_live_secret_key',
            'type' => 'password',
        ];


        $settings[] = [
            'name' => __('Paystack Test Public Key', 'give-paystack'),
            'desc' => __('Enter your Paystack test public key.', 'give-paystack'),
            'id'   => 'paystack_test_public_key',
            'type' => 'text',
        ];

        $settings[] = [
            'name' => __('Paystack Test Secret Key', 'give-paystack'),
            'desc' => __('Enter your Paystack test secret key.', 'give-paystack'),
            'id'   => 'paystack_test_secret_key',
            'type' => 'password',
        ];

        $settings[] = [
            'name'    => esc_html__('Billing Details', 'give-paystack'),
            'desc'    => esc_html__('This will enable you to collect donor details. This is not required by Paystack (except email) but you might need to collect all information for record purposes', 'give-paystack'),
            'id'      => 'paystack_billing_details',
            'type'    => 'radio_inline',
            'options' => [
                'enabled'  => esc_html__('Enabled', 'give-paystack'),
                'disabled' => esc_html__('Disabled', 'give-paystack'),
            ],
            'default' => 'disabled',
        ];

        $settings[] = [
            'id'   => 'give_title_paystack_settings',
            'type' => 'sectionend',
        ];

        return $settings;
    }

    /**
     * Get the settings URL.
     */
    public static function getSettingsUrl(): string
    {
        return admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=paystack');
    }
}
