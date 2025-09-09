<?php

namespace GivePaystack\Paystack;

use Give\Framework\PaymentGateways\PaymentGatewayRegister;
use GivePaystack\Paystack\Gateway\PaystackGateway;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;
use Give\Helpers\Hooks;

/**
 * Main service provider for the Paystack gateway.
 *
 * @since 3.0.0
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        // No container bindings needed for Paystack.
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Register the Paystack gateway
        add_action('givewp_register_payment_gateway', function (PaymentGatewayRegister $paymentGatewayRegister) {
            $paymentGatewayRegister->registerGateway(PaystackGateway::class);
        });

        // Link transaction ID in donation details to Paystack dashboard
        $this->linkDonationDetailsToPaystackDashboard();

        // Register African currencies
        $this->registerCurrencies();

    }

    /**
     * Link donation details to Paystack dashboard
     *
     * @since 3.0.0
     */
    private function linkDonationDetailsToPaystackDashboard()
    {
        Hooks::addFilter('give_payment_details_transaction_id-' . PaystackGateway::id(), PaystackGateway::class,
            'linkTransactionId', 10, 2);
    }

    /**
     * Register currencies
     *
     * TODO: remove once GiveWP currency field is updated and supports these currencies
     *
     * @since 3.0.0
     */
    private function registerCurrencies()
    {
        //TODO: remove once GiveWP currency field is updated and supports GHS
        add_action('givewp_donation_form_schema', static function (\Give\Framework\FieldsAPI\DonationForm $form) {
            /** @var \Give\Framework\FieldsAPI\Currency|null $currencyField */
            $currencyField = $form->getNodeByName('currency');

            if ($currencyField) {
                $currencyField->forgetRule('currency');
            }
        });


        add_filter('give_currencies', function ($currencies) {
            if (!array_key_exists('GHS', $currencies)) {
                $currencies['GHS'] = [
                    'admin_label' => sprintf(__('Ghana Cedis (%1$s)', 'give'), 'GHS'),
                    'symbol' => 'GHS;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => '.',
                        'decimal_separator' => ',',
                        'number_decimals' => 2,
                    ],
                ];
            }

            if (!array_key_exists('NGN', $currencies)) {
                $currencies['NGN'] = [
                    'admin_label' => sprintf(__('Nigerian Naira (%1$s)', 'give'), '&#8358;'),
                    'symbol' => '&#8358;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => ',',
                        'decimal_separator' => '.',
                        'number_decimals' => 2,
                    ],
                ];
            }

            if (!array_key_exists('ZAR', $currencies)) {
                $currencies['ZAR'] = [
                    'admin_label' => sprintf(__('South African Rands (%1$s)', 'give'), 'ZAR'),
                    'symbol' => 'ZAR;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => '.',
                        'decimal_separator' => ',',
                        'number_decimals' => 2,
                    ],
                ];
            }

            if (!array_key_exists('KES', $currencies)) {
                $currencies['KES'] = [
                    'admin_label' => sprintf(__('Kenyan Shillings (%1$s)', 'give'), 'KES'),
                    'symbol' => 'KES;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => '.',
                        'decimal_separator' => ',',
                        'number_decimals' => 2,
                    ],
                ];
            }

            if (!array_key_exists('XOF', $currencies)) {
                $currencies['XOF'] = [
                    'admin_label' => sprintf(__('West African CFA franc (%1$s)', 'give'), 'XOF'),
                    'symbol' => 'XOF;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => '.',
                        'decimal_separator' => ',',
                        'number_decimals' => 2,
                    ],
                ];
            }

            if (!array_key_exists('EGP', $currencies)) {
                $currencies['EGP'] = [
                    'admin_label' => sprintf(__('Egyptian Pound (%1$s)', 'give'), 'EGP'),
                    'symbol' => '£;',
                    'setting' => [
                        'currency_position' => 'before',
                        'thousands_separator' => '.',
                        'decimal_separator' => ',',
                        'number_decimals' => 2,
                    ],
                ];
            }

            return $currencies;
        }, 10, 1);
    }
}
