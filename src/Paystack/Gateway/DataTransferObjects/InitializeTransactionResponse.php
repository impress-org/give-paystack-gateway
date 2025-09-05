<?php

namespace GivePaystack\Paystack\Gateway\DataTransferObjects;

class InitializeTransactionResponse
{
    public string $authorizationUrl;
    public string $reference;
    public string $accessCode;

    public function __construct(array $data)
    {
        $this->authorizationUrl = $data['authorization_url'];
        $this->reference = $data['reference'];
        $this->accessCode = $data['access_code'];
    }

    /**
     * Create a new InitializeTransactionResponse from an array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        return new self([
            'authorization_url' => $data['authorization_url'],
            'reference' => $data['reference'],
            'access_code' => $data['access_code'],
        ]);
    }
}
