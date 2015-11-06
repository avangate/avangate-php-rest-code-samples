<?php
namespace products;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;

class AddProduct extends \PHPUnit_Framework_TestCase
{
    /**
     * Add a regular product.
     */
    public function testAddARegularProduct()
    {
        $client = new Client([
            'code' => MERCHANT_CODE,
            'key' => MERCHANT_APIKEY,
            'base_uri' => 'https://api.avangate.com/3.0/'
        ]);

        $productData = [
            'ProductCode' => 'PRODUCT_TEST_' . uniqid(),
            'ProductType' => 'REGULAR',
            'ProductName' => 'AV | Team',
            'ProductVersion' => '',
            'GroupName' => 'General',
            'ShippingClass' => null,
            'GiftOption' => false,
            'ShortDescription' => '',
            'LongDescription' => '',
            'SystemRequirements' => '',
            'ProductCategory' => false,
            'Platforms' => [],
            'ProductImages' => [],
            'TrialUrl' => '',
            'TrialDescription' => '',
            'Enabled' => true,
            'AdditionalFields' => [],
            'Translations' => [],
            'PricingConfigurations' => [
                [
                    'Name' => 'AV | Price Configuration',
                    'Code' => '54BCEB100D',
                    'Default' => true,
                    'BillingCountries' => [],
                    'PricingSchema' => 'DYNAMIC',
                    'PriceType' => 'NET',
                    'DefaultCurrency' => 'USD',
                    'Prices' => [
                        'Regular' => [
                            [
                                'Amount' => 39.99,
                                'Currency' => 'USD',
                                'MinQuantity' => '1',
                                'MaxQuantity' => '99999',
                                'OptionCodes' => []
                            ]
                        ],
                        'Renewal' => []
                    ],
                    'PriceOptions' => []
                ]
            ],
            'Prices' => [],
            'BundleProducts' => [],
            'Fulfillment' => 'BY_VENDOR',
            'GeneratesSubscription' => true,
            'SubscriptionInformation' => [
                'DeprecatedProducts' => [],
                'BundleRenewalManagement' => 'GLOBAL',
                'BillingCycle' => '-1',
                'BillingCycleUnits' => 'M',
                'IsOneTimeFee' => true,
                'ContractPeriod' => null,
                'UsageBilling' => 0,
                'GracePeriod' => null,
                'RenewalEmails' => [
                    'Type' => 'GLOBAL',
                    'Settings' => [
                        'ManualRenewal' => [
                            'Before30Days' => false,
                            'Before15Days' => false,
                            'Before7Days' => true,
                            'Before1Day' => false,
                            'OnExpirationDate' => false,
                            'After5Days' => false,
                            'After15Days' => false
                        ],
                        'AutomaticRenewal' => [
                            'Before30Days' => false,
                            'Before15Days' => false,
                            'Before7Days' => true,
                            'Before1Day' => false,
                            'OnExpirationDate' => false,
                            'After5Days' => false,
                            'After15Days' => false
                        ]
                    ]
                ]
            ],
            'FulfillmentInformation' => [
                'IsStartAfterFulfillment' => false,
                'IsElectronicCode' => false,
                'IsDownloadLink' => false,
                'IsBackupMedia' => false,
                'IsDownloadInsuranceService' => false,
                'IsInstantDeliveryThankYouPage' => true,
                'IsDisplayInPartnersCPanel' => false,
                'CodeList' => null,
                'BackupMedia' => null,
                'ProductFile' => null,
                'AdditionalInformationByEmail' => 'install instructions',
                'AdditionalInformationEmailTranslations' => [
                    [
                        'Name' => null,
                        'Description' => 'install instructions french',
                        'Language' => 'FR'
                    ],
                    [
                        'Name' => null,
                        'Description' => 'install instructions japanese',
                        'Language' => 'JA'
                    ]
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
            'body' => json_encode($productData),
        ];

        try {
            $rawResponse = $client->post('products/', $headers);
            static::assertEquals(201, $rawResponse->getStatusCode());

            // get added product
            $locationForAddedProduct = $rawResponse->getHeader('Location');
            $productFromAPIResponse = $client->get($locationForAddedProduct[0]);
            $productFromAPI = json_decode($productFromAPIResponse->getBody()->getContents());

            static::assertEquals($productData['ProductCode'], $productFromAPI->ProductCode);
            static::assertNotEmpty($productFromAPI->AvangateId);

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }
    }
}
