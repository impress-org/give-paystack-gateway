<?php

namespace GivePaystack\Paystack\Gateway;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Http\Response\Types\RedirectResponse;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Contracts\WebhookNotificationsListener;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\Log\PaymentGatewayLog;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Framework\Support\Facades\Scripts\ScriptAsset;
use Give\Log\Log;
use GivePaystack\Paystack\Gateway\Actions\ProcessWebhookNotifications;
use GivePaystack\Paystack\Gateway\DataTransferObjects\InitializeTransactionResponse;

use const GIVE_PAYSTACK_URL;

/**
 * @since 3.0.0
 */
class PaystackGateway extends PaymentGateway implements WebhookNotificationsListener
{
    /**
     * @var array
     */
    public $secureRouteMethods = [
        'handlePaystackReturn',
    ];

    /**
     * @var array
     */
    public $routeMethods = [
        'webhookNotificationsListener',
    ];

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'paystack';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return __('Paystack', 'give-paystack');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('Paystack', 'give-paystack');
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        return sprintf(
            '<div style="text-align: center;">
                <img src="%s" alt="Paystack" style="max-width: 200px;" />
                <br />
                <br />
                <p style="font-size: 0.9rem;">
                    <strong>%s</strong>
                </p>
                <p style="font-size: 0.8rem;">
                    <strong>%s</strong> %s
                </p>
            </div>',
            esc_url(GIVE_PAYSTACK_URL . 'src/Paystack/Gateway/resources/images/logo.png'),
            esc_html__('Make your donation quickly and securely with Paystack', 'give-paystack'),
            esc_html__('How it works:', 'give-paystack'),
            esc_html__('A Paystack window will open after you click the Donate button where you can securely make your donation. You will then be brought back to this page to view your receipt.', 'give-paystack')
        );
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {
        try {
            // Initialize Paystack transaction
            $response = $this->initializePaystackTransaction($donation, $gatewayData);

            if (!isset($response->authorizationUrl)) {
                throw new PaymentGatewayException(
                    __('Unable to initialize Paystack transaction.', 'give-paystack')
                );
            }

            // Store the reference for later use
            give_update_payment_meta($donation->id, '_give_paystack_reference', $response->reference);

            return new RedirectOffsite($response->authorizationUrl);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                sprintf(
                    __('Paystack Error: %s', 'give-paystack'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refundDonation(Donation $donation): GatewayCommand
    {
        $reference = give_get_payment_meta($donation->id, '_give_paystack_reference', true);

        if (empty($reference)) {
            throw new PaymentGatewayException(
                __('No Paystack reference found for this payment.', 'give-paystack')
            );
        }

        try {
            $response = $this->refundPaystackTransaction($reference);

            $refundSuccessStatuses = [
                //Refund initiated, waiting for response from the processor
                'pending',
                //Refund has been received by the processor.
                'processing',
                //Refund has successfully been processed by the processor.
                'processed',
            ];

            if (!isset($response['status']) || !in_array($response['status'], $refundSuccessStatuses)) {
                Log::error('Unable to refund Paystack transaction details.', [
                    'reference' => $reference,
                    'data' => $response,
                    'status' => $response['status'],
                ]);

                throw new PaymentGatewayException(
                    __('Unable to refund Paystack transaction.', 'give-paystack')
                );
            }

            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf(
                    __('Donation refunded in Paystack for transaction ID: %s.  Paystack refund status: %s', 'give-paystack'),
                    $donation->gatewayTransactionId,
                    $response['status']
                ),
            ]);

            return new PaymentRefunded();
        } catch (PaymentGatewayException $exception) {
            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf(
                    __('Error! Donation %s was NOT refunded. Find more details on the error in the logs at Donations > Tools > Logs. To refund the donation, use the Paystack dashboard tools.', 'give-paystack'),
                    $donation->id
                ),
            ]);

            throw $exception;
        }
    }


    /**
     * @inheritDoc
     */
    public function enqueueScript(int $formId)
    {
        $scriptAsset = ScriptAsset::get(GIVE_PAYSTACK_URL . 'build/paystackGateway.asset.php');

        // Enqueue our gateway script
        wp_enqueue_script(
            'give-paystack-gateway',
            GIVE_PAYSTACK_URL . 'build/paystackGateway.js',
            $scriptAsset['dependencies'],
            $scriptAsset['version'],
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function formSettings(int $formId): array
    {
        return [
            'testMode' => give_is_test_mode(),
            'formId' => $formId,
        ];
    }

    /**
     * Get the secret key
     *
     * @since 3.0.0
     */
    public function getSecretKey(): string
    {
        return give_is_test_mode() ? give_get_option('paystack_test_secret_key') : give_get_option('paystack_live_secret_key');
    }

    /**
     * Initialize a Paystack transaction
     *
     * @since 3.0.0
     */
    private function initializePaystackTransaction(Donation $donation, array $gatewayData): InitializeTransactionResponse
    {
        $email = $donation->email;
        $currency = $donation->amount->getCurrency()->getCode();
        $reference = $donation->purchaseKey;
        $paystackSecretKey = $this->getSecretKey();

        if (empty($paystackSecretKey)) {
            throw new PaymentGatewayException(__('Paystack API key is not configured', 'give-paystack'));
        }

        $redirectUrl = $this->generateSecureGatewayRouteUrl(
            'handlePaystackReturn',
            $donation->id,
            [
                'givewp-donation-id' => $donation->id,
                'givewp-success-url' => $gatewayData['successUrl']
            ]
        );

        $url = 'https://api.paystack.co/transaction/initialize';
        $body = [
            'amount' => $donation->amount->formatToMinorAmount(),
            'email' => $email,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $redirectUrl,
            'metadata' => apply_filters('givewp_paystack_transaction_initialization_metadata', [
                'custom_fields' => [
                    [
                        'display_name' => 'Plugin',
                        'variable_name' => 'plugin',
                        'value' => 'GiveWP'
                    ],
                    [
                        'display_name' => 'Form Title',
                        'variable_name' => 'form_title',
                        'value' => $donation->formTitle
                    ],
                    [
                        'display_name' => 'Donation ID',
                        'variable_name' => 'donation_id',
                        'value' => $donation->id
                    ]
                ]
            ])
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $paystackSecretKey,
            'Cache-Control' => 'no-cache',
        ];

        $response = wp_remote_post($url, [
            'body' => $body,
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            throw new PaymentGatewayException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['status']) || $body['status'] !== true) {
            throw new PaymentGatewayException(
                isset($body['message']) ? $body['message'] : __('Unknown error occurred.', 'give-paystack')
            );
        }

        return InitializeTransactionResponse::fromArray($body['data']);
    }

    /**
     * Refund a Paystack transaction
     *
     * @since 3.0.0
     */
    private function refundPaystackTransaction(string $reference): array
    {
        $url = 'https://api.paystack.co/refund';
        $body = [
            'transaction' => $reference,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->getSecretKey(),
            'Cache-Control' => 'no-cache',
        ];

        $response = wp_remote_post($url, [
            'body' => $body,
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            throw new PaymentGatewayException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['status']) || $body['status'] === false) {
            throw new PaymentGatewayException(
                isset($body['message']) ? $body['message'] : __('Unknown error occurred.', 'give-paystack')
            );
        }

        return $body['data'];
    }

    /**
     * Handle the return from Paystack
     *
     * @since 3.0.0
     */
    public function handlePaystackReturn(array $queryParams)
    {
        $successUrl = $queryParams['givewp-success-url'];
        $donationId = (int)$queryParams['givewp-donation-id'];
        $donation = Donation::find($donationId);

        if (!$donation) {
            throw new PaymentGatewayException(__('Donation not found.', 'give-paystack'));
        }

        $reference = give_get_payment_meta($donation->id, '_give_paystack_reference', true);

        if (empty($reference)) {
            throw new PaymentGatewayException(__('No Paystack reference found for this payment.', 'give-paystack'));
        }

        try {
            $response = $this->verifyPaystackTransaction($reference);

            $donation->gatewayTransactionId = (string)give_clean($response['id']);
            $donation->status = $this->getDonationStatusFromPaystackTransactionStatus($response['status']);
            $donation->save();

            return new RedirectResponse(esc_url_raw($successUrl));
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                sprintf(
                    __('Paystack Error: %s', 'give-paystack'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Verify a Paystack transaction
     *
     * @since 3.0.0
     */
    private function verifyPaystackTransaction(string $reference): array
    {
        $url = 'https://api.paystack.co/transaction/verify/' . $reference;
        $paystackSecretKey = $this->getSecretKey();
        $headers = [
            'Authorization' => 'Bearer ' . $paystackSecretKey,
            'Cache-Control' => 'no-cache',
        ];

        $response = wp_remote_get($url, [
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            throw new PaymentGatewayException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['status']) || $body['status'] !== true) {
            throw new PaymentGatewayException(
                isset($body['message']) ? $body['message'] : __('Unknown error occurred.', 'give-paystack')
            );
        }

        return $body['data'];
    }

    /**
     * Links the transaction ID in the donation details to the Paystack transaction details page.
     *
     * @since 3.0.0
     *
     * @return string A link to the Paystack transaction details.
     */
    public function linkTransactionId(?string $gatewayTransactionId, ?int $donationId)
    {
        if (empty($gatewayTransactionId)) {
            return '';
        }

        $url = 'https://dashboard.paystack.com/#/transactions/' . $gatewayTransactionId . '/analytics';
        $transactionLink = '<a href="' . esc_url($url) . '" target="_blank">' . $gatewayTransactionId . '</a>';

        return apply_filters('give_paystack_link_payment_details_transaction_id', $transactionLink);
    }

    /**
     * Webhook notifications listener.
     *
     * @see https://paystack.com/docs/payments/webhooks/#create-a-webhook-url
     * @see https://paystack.com/docs/payments/webhooks/#types-of-events
     * @see https://paystack.com/docs/payments/refunds/#listen-to-notifications
     *
     * @unreleased
     * @return void
     */
    public function webhookNotificationsListener()
    {
        // only a post with paystack signature header gets our attention
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)) {
            wp_die('Unauthorized request');
        }

        // Retrieve the request's body
        $input = @file_get_contents("php://input");
        $paystackSecretKey = $this->getSecretKey();

        // validate event do all at once to avoid timing attack
        if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $paystackSecretKey)) {
            wp_die('Unauthorized request');
        }

        http_response_code(200);

        $request = json_decode($input);

        give_update_option('givewp_paystack_last_webhook_received_timestamp', time());

        do_action('givewp_paystack_webhook_notification', $request, $this);

        do_action("givewp_paystack_webhook_notification_$request->event", $request, $this);

        Log::http('Paystack webhook received', ['request' => $request]);

        try {
            (new ProcessWebhookNotifications())($request, $this);
        } catch (\Exception $e) {
            PaymentGatewayLog::error('Paystack webhook error', ['error' => $e->getMessage()]);
        }

        exit();
    }

    /**
     * @unreleased
     *
     * @see https://paystack.com/docs/payments/verify-payments/#transaction-statuses
     */
    protected function getDonationStatusFromPaystackTransactionStatus(string $status): DonationStatus
    {
        switch ($status) {
            case 'abandoned':
                return DonationStatus::ABANDONED();
            case 'failed':
                return DonationStatus::FAILED();
            case 'reversed':
                return DonationStatus::REFUNDED();
            case 'success':
                return DonationStatus::COMPLETE();
            default:
                return DonationStatus::PROCESSING();
        }
    }
}
