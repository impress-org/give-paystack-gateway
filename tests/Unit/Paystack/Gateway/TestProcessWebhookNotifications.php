<?php

namespace GivePaystack\Tests\Unit\Paystack\Gateway;

use GivePaystack\Paystack\Gateway\Actions\ProcessWebhookNotifications;
use GivePaystack\Paystack\Gateway\PaystackGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @unreleased
 */

class TestProcessWebhookNotifications extends TestCase
{
    /**
     * @unreleased
     */
    public function testChargeSuccessTriggersDonationCompleted()
    {
        $gateway = $this->createGatewayWithEventMocks([
            'donationCompleted' => [
                'times' => 1,
                'with' => ['123'],
            ],
        ]);

        $request = (object)[
            'event' => 'charge.success',
            'data' => (object)[
                'id' => '123',
            ],
        ];

        (new ProcessWebhookNotifications())($request, $gateway);
    }

    /**
     * @unreleased
     */
    public function testRefundPendingTriggersDonationRefunded()
    {
        $transactionId = 'TXN_1';
        $status = 'pending';
        $expectedMessage = sprintf(
            'Donation refunded in Paystack for transaction ID: %s.  Paystack refund status: %s',
            $transactionId,
            $status
        );

        $gateway = $this->createGatewayWithEventMocks([
            'donationRefunded' => [
                'times' => 1,
                'with' => [$transactionId, $expectedMessage],
            ],
        ]);

        $request = (object)[
            'event' => 'refund.pending',
            'data' => (object)[
                'id' => $transactionId,
                'status' => $status,
            ],
        ];

        (new ProcessWebhookNotifications())($request, $gateway);
    }

    /**
     * @unreleased
     */
    public function testRefundProcessingTriggersDonationRefunded()
    {
        $transactionId = 'TXN_2';
        $status = 'processing';
        $expectedMessage = sprintf(
            'Donation refunded in Paystack for transaction ID: %s.  Paystack refund status: %s',
            $transactionId,
            $status
        );

        $gateway = $this->createGatewayWithEventMocks([
            'donationRefunded' => [
                'times' => 1,
                'with' => [$transactionId, $expectedMessage],
            ],
        ]);

        $request = (object)[
            'event' => 'refund.processing',
            'data' => (object)[
                'id' => $transactionId,
                'status' => $status,
            ],
        ];

        (new ProcessWebhookNotifications())($request, $gateway);
    }

    /**
     * @unreleased
     */
    public function testRefundProcessedTriggersDonationRefunded()
    {
        $transactionId = 'TXN_3';
        $status = 'processed';
        $expectedMessage = sprintf(
            'Donation refunded in Paystack for transaction ID: %s.  Paystack refund status: %s',
            $transactionId,
            $status
        );

        $gateway = $this->createGatewayWithEventMocks([
            'donationRefunded' => [
                'times' => 1,
                'with' => [$transactionId, $expectedMessage],
            ],
        ]);

        $request = (object)[
            'event' => 'refund.processed',
            'data' => (object)[
                'id' => $transactionId,
                'status' => $status,
            ],
        ];

        (new ProcessWebhookNotifications())($request, $gateway);
    }

    /**
     * @unreleased
     */
    public function testNoEventDoesNothing()
    {
        $gateway = $this->createGatewayWithEventMocks([]);

        $request = (object)[];

        (new ProcessWebhookNotifications())($request, $gateway);

        $this->assertTrue(true);
    }

    /**
     * Helper to create a PaystackGateway instance with mocked webhook events methods.
     *
     * @param array $expectedMethods methodName => ['times' => int, 'with' => array]
     * @return PaystackGateway|MockObject
     */
    private function createGatewayWithEventMocks(array $expectedMethods)
    {
        /** @var PaystackGateway $gateway */
        $gateway = $this->getMockBuilder(PaystackGateway::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $webhook = new \stdClass();

        /** @var MockObject $events */
        $events = $this->getMockBuilder(\Give\Framework\PaymentGateways\Webhooks\WebhookEvents::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_keys($expectedMethods))
            ->getMock();

        foreach ($expectedMethods as $method => $config) {
            $invocation = $events->expects($this->exactly($config['times']))->method($method);
            if (isset($config['with'])) {
                $invocation->with(...$config['with']);
            }
        }

        $webhook->events = $events;
        $gateway->webhook = $webhook;

        return $gateway;
    }
}
