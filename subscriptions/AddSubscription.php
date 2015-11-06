<?php
namespace subscription;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;

class AddSubscription extends \PHPUnit_Framework_TestCase
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
     * Add a subscription.
     */
    public function testAddASubscription()
    {
        $product = $this->getARegularProduct();

        $subscriptionData = [
            'StartDate' => '2015-01-01',
            'ExpirationDate' => '2020-01-01',
            'LicenseHistory' => null,
            'LicenseStatus' => 'ACTIVE',
            'RecurringEnabled' => 'YES',
            'EndUser' => [
                'FirstName'   => 'John',
                'LastName'    => 'Doe',
                'Company'     => 'Company',
                'Email'       => 'john.doe@avangate.com',
                'Phone'       => '0123456789',
                'Fax'         => '9876543210',
                'Address1'    => 'address1',
                'Address2'    => 'address2',
                'Zip'         => '12345',
                'City'        => 'Bucharest',
                'State'       => 'State',
                'CountryCode' => 'GB',
                'Language'    => 'RO'
            ],
            'ActivationInfo' => [
                'Codes' => [
                    'Code'        => mt_rand(100, 999),
                    'Description' => '-',
                    'File'        => '',
                    'Extrainfo'   => [],
                ],
                'Description' => '-'
            ],
            'ExternalSubscriptionReference' => mt_rand(100, 999),
            'Product' => [
                'ProductId'        => $product->AvangateId,
                'ProductName'      => 'APRODUCT',
                'ProductCode'      => '',
                'ProductVersion'   => '1',
                'ProductQuantity'  => 1,
                'PriceOptionCodes' => []
            ],
            'NextRenewalPrice' => 100,
            'NextRenewalPriceCurrency' => 'USD',
            'CustomPriceBillingCyclesLeft' => 100,
        ];

        $headers = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'verify' => false,
                'proxy' => ''
            ],
            'body' => json_encode($subscriptionData),
        ];

        try {
            $rawResponse = $this->client->post('subscriptions/', $headers);
            static::assertEquals(201, $rawResponse->getStatusCode());

            // get added subscription
            $locationForAddedSubscription = $rawResponse->getHeader('Location');
            $subscriptionFromAPIResponse = $this->client->get($locationForAddedSubscription[0]);

            $subscriptionFromAPI = json_decode($subscriptionFromAPIResponse->getBody()->getContents());
            static::assertNotEmpty($subscriptionFromAPI->SubscriptionReference);
            static::assertEquals($subscriptionData['StartDate'], $subscriptionFromAPI->StartDate);

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * Extract a REGULAR product.
     */
    protected function getARegularProduct()
    {
        /** @var Response $rawResponse */
        $rawResponse = $this->client->get('products/?Limit=1&Enabled=1&Types[]=REGULAR');
        $responseBody = json_decode($rawResponse->getBody()->getContents());

        static::assertTrue(is_array($responseBody) && count($responseBody) === 1);
        $product = $responseBody[0];

        static::assertTrue(is_object($product) && isset($product->ProductCode));
        return $product;
    }
}
