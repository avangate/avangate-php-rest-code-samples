<?php
namespace orders;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class PlaceOrderWithTestCreditCard extends \PHPUnit_Framework_TestCase
{
    protected $client;

    public function setUp()
    {
        $this->client = new Client([
            'code' => MERCHANT_CODE,
            'key' => MERCHANT_APIKEY,
            'base_uri' => 'https://api.avangate.com/3.0/'
        ]);
    }

    /**
     * When order is set as TEST with CC payment method then place.
     */
    public function testWhenOrderIsSetAsTESTWithCCPaymentMethodThenPlace()
    {
        $order = [
            'Items' => [
                0 => [
                    'Code' => $this->getARegularProductCode(),
                    'Quantity' => 1
                ]
            ],
            'BillingDetails' => [
                'FirstName' => 'John',
                'LastName' => 'Doe',
                'Email' => 'john.doe@avangate.com',
                'CountryCode' => 'RO'
            ],
            'PaymentDetails' => [
                'Type' => 'TEST',
                'Currency' => 'EUR',
                'PaymentMethod' => [
                    'CardType' => 'visa',
                    'CardNumber' => '4111111111111111',
                    'CCID' => '123',
                    'ExpirationMonth' => '10',
                    'ExpirationYear' => '2020',
                    'HolderName' => 'John Doe',
                ]
            ]
        ];

        $headers = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'verify' => false,
                'proxy' => ''
            ],
            'body' => json_encode($order),
        ];

        try {
            $rawResponse = $this->client->post('orders/', $headers);
            $responseBody = json_decode($rawResponse->getBody()->getContents());

            static::assertTrue(is_object($responseBody));
            static::assertNotEmpty($responseBody->RefNo);
            static::assertEquals('TEST', $responseBody->PaymentDetails->Type);

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * Extract a REGULAR product code.
     */
    protected function getARegularProductCode()
    {
        /** @var Response $rawResponse */
        $rawResponse = $this->client->get('products/?Limit=1&Enabled=1&Types[]=REGULAR');
        $responseBody = json_decode($rawResponse->getBody()->getContents());

        static::assertTrue(is_array($responseBody) && count($responseBody) === 1);
        $product = $responseBody[0];

        static::assertTrue(is_object($product) && isset($product->ProductCode));
        return $product->ProductCode;
    }
}
