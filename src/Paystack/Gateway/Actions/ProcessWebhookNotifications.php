<?php

namespace GivePaystack\Paystack\Gateway\Actions;

use GivePaystack\Paystack\Gateway\PaystackGateway;
use Exception;

class ProcessWebhookNotifications
{
    /**
     * @since 3.0.0
     *
     * @see https://paystack.com/docs/payments/webhooks/#types-of-events
     * @throws Exception
     */
    public function __invoke(object $request, PaystackGateway $gateway)
    {
        switch ($request->event) {
            case 'charge.success':
                $gatewayTransactionId = $request->data->id;

                $gateway->webhook->events->donationCompleted($gatewayTransactionId);

                break;
            case 'refund.pending':
            case 'refund.processing':
            case 'refund.processed':
                $gatewayTransactionId = $request->data->id;

                $message = sprintf(
                    __('Donation refunded in Paystack for transaction ID: %s.  Paystack refund status: %s', 'give-paystack'),
                    $gatewayTransactionId,
                    $request->data->status
                );

                $gateway->webhook->events->donationRefunded($gatewayTransactionId, $message);

                break;
        }
    }
}
