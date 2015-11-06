<?php
namespace orders;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class RetryACharge extends \PHPUnit_Framework_TestCase
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
     * Place an order that will have a PENDING order status.
     */
    public function testPlaceAnOrderThatWillHaveAPENDINGOrderStatus()
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
                'Type' => 'PAYPAL',
                'Currency' => 'EUR',
                'PaymentMethod' => [
                    'Email' => 'customer@avangate.com',
                    'ReturnURL' => 'http://my.implementation.dev/callback/paypalreturn'
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
            static::assertEquals('PENDING', $responseBody->Status);

            $redirectURL = $responseBody->PaymentDetails->PaymentMethod->RedirectURL;
            static::assertNotEmpty($redirectURL); // redirect to this URL to finish the payment process

            return $responseBody;

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * For an order with PENDING status when the payment details are changed then retry.
     * @depends testPlaceAnOrderThatWillHaveAPENDINGOrderStatus
     */
    public function testForAnOrderWithPENDINGStatusWhenThePaymentDetailsAreChangedThenRetry($order)
    {
        static::assertEquals('PENDING', $order->Status);

        $order->PaymentDetails = [
            'Type' => 'CC',
            'Currency' => 'EUR',
            'PaymentMethod' => [
                'CardType' => 'visa',
                'CardNumber' => '4111111111111111',
                'CCID' => '123',
                'ExpirationMonth' => '10',
                'ExpirationYear' => '2020',
                'HolderName' => 'John Doe',
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
            static::assertEquals($order->RefNo, $responseBody->RefNo);
            static::assertEquals('CC', $responseBody->PaymentDetails->Type);

            return $responseBody;

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
