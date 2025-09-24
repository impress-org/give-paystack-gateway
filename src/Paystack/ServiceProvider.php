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

        $this->registerV2FormBillingAddressFields();

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
     * Register V2 form billing address fields (backwards compatibility)
     *
     * @since 3.0.0
     */
    private function registerV2FormBillingAddressFields()
    {
        add_action('give_paystack_cc_form', function ($form_id, $echo = true) {
            $billing_fields_enabled = give_get_option('paystack_billing_details');

            if ($billing_fields_enabled == 'enabled') {
                do_action('give_after_cc_fields');
            } else {
                //Remove Address Fields if user has option enabled
                remove_action('give_after_cc_fields', 'give_default_cc_address_fields');
            }

            return $form_id;
        }, 10, 2);
    }
}
