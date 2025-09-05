<?php

namespace GivePaystack\Settings;

use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;

/**
 * Service provider for Paystack settings.
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
        // Register Paystack settings here if needed.
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Register Paystack settings.
        PaystackSettings::register();
    }
}
