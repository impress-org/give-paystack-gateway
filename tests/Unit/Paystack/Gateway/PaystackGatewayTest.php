<?php

namespace GivePaystack\Tests\Unit\Paystack\Gateway;

use Give\Donations\Models\Donation;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Tests\TestCase;
use GivePaystack\Paystack\Gateway\PaystackGateway;
use Give\Tests\TestTraits\RefreshDatabase;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\Support\ValueObjects\Money;
use Give\Donations\ValueObjects\DonationType;
use Give\Donors\Models\Donor;
use Give\Framework\Http\Response\Types\RedirectResponse;

/**
 * @since 3.0.0
 */
class PaystackGatewayTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @var PaystackGateway
     */
    private $gateway;

    /**
     * @since 3.0.0
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->gateway = give(PaystackGateway::class);
    }

    /**
     * @since 3.0.0
     */
    public function testGatewayId()
    {
        $this->assertEquals('paystack', PaystackGateway::id());
        $this->assertEquals('paystack', $this->gateway->getId());
    }

    /**
     * @since 3.0.0
     */
    public function testGatewayName()
    {
        $this->assertEquals('Paystack', $this->gateway->getName());
    }

    /**
     * @since 3.0.0
     */
    public function testPaymentMethodLabel()
    {
        $this->assertEquals('Paystack', $this->gateway->getPaymentMethodLabel());
    }

    /**
     * @since 3.0.0
     */
    public function testSupportsFormVersions()
    {
        $this->assertEquals([2, 3], $this->gateway->supportsFormVersions());
    }

    /**
     * @since 3.0.0
     */
    public function testSupportsRefund()
    {
        $this->assertTrue($this->gateway->supportsRefund());
    }

    /**
     * @since 3.0.0
     */
    public function testCreatePayment()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::PENDING(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co') !== false) {
                return [
                    'body' => json_encode([
                        'status' => true,
                        'data' => [
                            'authorization_url' => 'https://checkout.paystack.com/test',
                            'reference' => 'test_reference',
                            'access_code' => 'test_access_code',
                        ],
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $gatewayData = [
            'successUrl' => 'https://example.com/success',
        ];

        $result = $this->gateway->createPayment($donation, $gatewayData);

        $this->assertInstanceOf(RedirectOffsite::class, $result);
        $this->assertEquals('https://checkout.paystack.com/test', $result->redirectUrl);
    }

    /**
     * @since 3.0.0
     */
    public function testCreatePaymentFailure()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::PENDING(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co') !== false) {
                return [
                    'body' => json_encode([
                        'status' => false,
                        'message' => 'Invalid amount',
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $gatewayData = [
            'successUrl' => 'https://example.com/success',
        ];

        $this->expectException(PaymentGatewayException::class);
        $this->gateway->createPayment($donation, $gatewayData);
    }

    /**
     * @since 3.0.0
     */
    public function testHandlePaystackReturn()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::PENDING(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();
        give_update_payment_meta($donation->id, '_give_paystack_reference', 'test_reference');

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co') !== false) {
                return [
                    'body' => json_encode([
                        'status' => true,
                        'data' => [
                            'status' => 'success',
                            'id' => 'paystack_transaction_id_123',
                        ],
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $queryParams = [
            'givewp-donation-id' => $donation->id,
            'givewp-success-url' => 'https://example.com/success',
        ];

        $result = $this->gateway->handlePaystackReturn($queryParams);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('https://example.com/success', $result->getTargetUrl());
    }

    /**
     * @since 3.0.0
     */
    public function testHandlePaystackReturnFailure()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::PENDING(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();
        give_update_payment_meta($donation->id, '_give_paystack_reference', 'test_reference');

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co') !== false) {
                return [
                    'body' => json_encode([
                        'status' => false,
                        'message' => 'Invalid reference',
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $queryParams = [
            'givewp-donation-id' => $donation->id,
            'givewp-success-url' => 'https://example.com/success',
        ];

        $this->expectException(PaymentGatewayException::class);
        $this->gateway->handlePaystackReturn($queryParams);
    }

    /**
     * @since 3.0.0
     */
    public function testRefundDonation()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::COMPLETE(),
            'gatewayId' => PaystackGateway::id(),
            'gatewayTransactionId' => 'paystack_txn_123',
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();
        give_update_payment_meta($donation->id, '_give_paystack_reference', 'test_reference');

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co/refund') !== false) {
                return [
                    'body' => json_encode([
                        'status' => true,
                        'data' => [
                            'status' => 'success',
                            'id' => 'refund_123',
                        ],
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $result = $this->gateway->refundDonation($donation);

        $this->assertInstanceOf(PaymentRefunded::class, $result);

        // Verify that a donation note was created
        $notes = give_get_payment_notes($donation->id);
        $refundNote = array_filter($notes, function($note) {
            return strpos($note->comment_content, 'Donation refunded in Paystack') !== false;
        });
        $this->assertNotEmpty($refundNote);
    }

    /**
     * @since 3.0.0
     */
    public function testRefundDonationFailure()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::COMPLETE(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        update_option('paystack_test_secret_key', 'test_secret_key');
        give_update_payment_meta($donation->id, '_give_paystack_reference', 'test_reference');

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co/refund') !== false) {
                return [
                    'body' => json_encode([
                        'status' => false,
                        'message' => 'Invalid reference',
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $this->expectException(PaymentGatewayException::class);

        try {
            $this->gateway->refundDonation($donation);
        } catch (PaymentGatewayException $exception) {
            // Verify that an error note was created
            $notes = give_get_payment_notes($donation->id);
            $errorNote = array_filter($notes, function($note) use ($donation) {
                return strpos($note->comment_content, 'Error! Donation ' . $donation->id . ' was NOT refunded') !== false;
            });
            $this->assertNotEmpty($errorNote);

            throw $exception; // Re-throw to satisfy expectException
        }
    }

    /**
     * @since 3.0.0
     */
    public function testRefundDonationWithoutReference()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation without a Paystack reference
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::COMPLETE(),
            'gatewayId' => PaystackGateway::id(),
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();
        // Note: No _give_paystack_reference meta is set

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('No Paystack reference found for this payment.');

        $this->gateway->refundDonation($donation);
    }

    /**
     * @since 3.0.0
     */
    public function testRefundDonationWithMissingStatusInResponse()
    {
        // Create and persist a donor
        $donor = Donor::create([
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create and persist a donation
        $donation = Donation::create([
            'amount' => Money::fromDecimal('10.00', 'USD'),
            'email' => 'test@example.com',
            'status' => DonationStatus::COMPLETE(),
            'gatewayId' => PaystackGateway::id(),
            'gatewayTransactionId' => 'paystack_txn_123',
            'formId' => 1,
            'formTitle' => 'Test Form',
            'donorId' => $donor->id,
            'firstName' => 'Test',
            'lastName' => 'User',
            'type' => DonationType::SINGLE(),
        ]);

        $this->updatePaystackSecretKey();
        give_update_payment_meta($donation->id, '_give_paystack_reference', 'test_reference');

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            if (strpos($url, 'paystack.co/refund') !== false) {
                return [
                    'body' => json_encode([
                        'status' => true,
                        'data' => [
                            // Missing 'status' field in data
                            'id' => 'refund_123',
                        ],
                    ]),
                ];
            }
            return $preempt;
        }, 10, 3);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Unable to refund Paystack transaction.');

        try {
            $this->gateway->refundDonation($donation);
        } catch (PaymentGatewayException $exception) {
            // Verify that an error note was created
            $notes = give_get_payment_notes($donation->id);
            $errorNote = array_filter($notes, function($note) use ($donation) {
                return strpos($note->comment_content, 'Error! Donation ' . $donation->id . ' was NOT refunded') !== false;
            });
            $this->assertNotEmpty($errorNote);

            throw $exception; // Re-throw to satisfy expectException
        }
    }

    /**
     * @since 3.0.0
     */
    public function updatePaystackSecretKey()
    {
        update_option('paystack_test_secret_key', 'test_secret_key');
    }
}
