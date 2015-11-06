<?php
namespace orders;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class PlaceOrderWithTrialCreditCard extends \PHPUnit_Framework_TestCase
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
     * When cart item has trial period and price set then place as trial.
     */
    public function testWhenCartItemHasTrialPeriodAndPriceSetThenPlaceAsTrial()
    {
        $country = 'RO';
        $product = $this->getARegularProductThatAllowsTrial($country);

        // prepare order object
        $order = [
            'Items' => [
                0 => [
                    'Code' => $product->ProductCode,
                    'Quantity' => 1,
                    'Trial' => [
                        'Period' => 30,
                        'Price' => 9.99
                    ]
                ]
            ],
            'BillingDetails' => [
                'FirstName' => 'John',
                'LastName' => 'Doe',
                'Email' => 'john.doe@avangate.com',
                'CountryCode' => $country
            ],
            'PaymentDetails' => [
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

            print_r($responseBody);

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * Extract a REGULAR product that allows trial. Ignore products with mandatory pricing option groups.
     */
    protected function getARegularProductThatAllowsTrial($country)
    {
        /** @var Response $rawResponse */
        $rawResponse = $this->client->get('products/?Limit=10&Enabled=1&Types[]=REGULAR');
        $responseBody = json_decode($rawResponse->getBody()->getContents());

        static::assertTrue(is_array($responseBody) && count($responseBody) > 1);

        foreach ($responseBody as $item) {
            if (is_object($item) && !empty($item->ProductCode) && (!empty($item->TrialUrl) || !empty($item->TrialDescription))) {
                $pricingConfiguration = null;

                foreach ($item->PricingConfigurations as $entry) {
                    if ((bool)$entry->Default || in_array($country, $entry->BillingCountries)) {
                        $pricingConfiguration = $entry;
                    }
                }

                if (null === $pricingConfiguration) {
                    continue;
                }

                // filter required options (if any)
                foreach ($pricingConfiguration->PriceOptions as $priceOption) {
                    if ($priceOption->Required) {
                        $requiredPriceOptionGroupCodes[] = $priceOption->Code;
                    }
                }

                if (empty($requiredPriceOptionGroupCodes)) {
                    return $item;
                }
            }
        }

        static::fail('No products that can be set as trial were found (or were found but had some required price option groups that were not retrieved.');
    }
}
