<?php

namespace GivePaystack\Settings;

use GivePaystack\Paystack\Gateway\PaystackGateway;

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
        add_action('give_admin_field_paystack_webhooks', [self::class, 'addPaystackWebhookField'], 10, 2);
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
            'name' => __('Paystack Webhooks', 'give-paystack'),
            'desc' => __('In order for Paystack to function properly, you must configure your webhooks.', 'give-paystack'),
            'id'   => 'paystack_webhooks',
            'wrapper_class' => 'give-paystack-webhooks-tr',
            'type'          => 'paystack_webhooks',
            'default' => PaystackGateway::webhook()->getNotificationUrl(),
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

    /**
     * Add the Paystack webhook field.
     *
     * This was forked from Stripe's webhook field.
    /**
     * Render the Paystack webhook field in the settings.
     *
     * @param array $value
     * @param array $option_value
     * @return void
     */
    public static function addPaystackWebhookField($value, $option_value)
    {
        $wrapperClass = !empty($value['wrapper_class']) ? 'class="' . esc_attr($value['wrapper_class']) . '"' : '';
        $webhookUrl = isset($value['default']) ? esc_url($value['default']) : '';
        $webhook_received_on = give_get_option('givewp_paystack_last_webhook_received_timestamp');
        $date_time_format = get_option('date_format') . ' ' . get_option('time_format');
        ?>
        <tr valign="top" <?php echo $wrapperClass; ?>>
            <th scope="row" class="titledesc">
                <label><?php esc_html_e('Paystack Webhooks', 'give-paystack'); ?></label>
            </th>
            <td class="give-forminp give-forminp-api_key">
                <div class="give-paystack-webhook-sync-wrap">
                    <p class="give-paystack-webhook-explanation" style="margin-bottom: 15px;">
                        <?php
                        esc_html_e('In order for Paystack to function properly, you must configure your Paystack webhooks.', 'give-paystack');
                        printf(
                            /* translators: 1. Webhook settings page. */
                            ' ' . __('You can visit your <a href="%1$s" target="_blank">Paystack Account Dashboard</a> to add a new webhook.', 'give-paystack'),
                            esc_url('https://dashboard.paystack.com/#/settings/developers')
                        );
                        echo ' ';
                        esc_html_e('Please add a new webhook endpoint for the following URL:', 'give');
                        ?>
                    </p>
                    <p style="margin-bottom: 15px;">
                        <strong><?php esc_html_e('Webhook URL:', 'give'); ?></strong>
                        <input style="width: 400px;" type="text" readonly
                            value="<?php echo esc_attr($webhookUrl); ?>" />
                    </p>
                    <?php if (!empty($webhook_received_on)) : ?>
                        <p>
                            <strong><?php esc_html_e('Last webhook received on', 'give'); ?></strong>
                            <?php echo esc_html(date_i18n($date_time_format, $webhook_received_on)); ?>
                        </p>
                    <?php endif; ?>
                    <p>
                        <?php
                        printf(
                            /* translators: 1. Documentation on webhook setup. */
                            __('See our <a href="%1$s" target="_blank">documentation</a> for more information.', 'give'),
                            esc_url('http://docs.givewp.com/paystack-webhooks')
                        );
                        ?>
                    </p>
                </div>
                <p class="give-field-description">
                    <?php esc_html_e('Paystack webhooks are critical to configure so that GiveWP can receive communication properly from the payment gateway. Webhooks for test-mode donations are configured separately on the Paystack dashboard. Note: webhooks do not function on localhost or websites in maintenance mode.', 'give-paystack'); ?>
                </p>
            </td>
        </tr>
        <?php
    }
}
