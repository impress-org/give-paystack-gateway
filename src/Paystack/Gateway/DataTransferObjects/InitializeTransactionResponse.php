<?php

namespace GivePaystack\Paystack\Gateway\DataTransferObjects;

/**
 * @since 3.0.0
 */
class InitializeTransactionResponse
{
    public string $authorizationUrl;

    public string $reference;

    public string $accessCode;

    /**
     * @since 3.0.0
     */
    public function __construct(array $data)
    {
        $this->authorizationUrl = $data['authorization_url'];
        $this->reference = $data['reference'];
        $this->accessCode = $data['access_code'];
    }

    /**
     * Create a new InitializeTransactionResponse from an array
     *
     * @since 3.0.0
     */
    public static function fromArray(array $data): self
    {
        return new self([
            'authorization_url' => $data['authorization_url'],
            'reference' => $data['reference'],
            'access_code' => $data['access_code'],
        ]);
    }
}
