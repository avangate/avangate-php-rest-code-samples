<?php
namespace customers;

use AvangateClient\Client;
use GuzzleHttp\Exception\ClientException;

class AddCustomer extends \PHPUnit_Framework_TestCase
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
     * Add a customer.
     */
    public function testAddACustomer()
    {
        $customer = [
            'FirstName'   => 'John',
            'LastName'    => 'Doe',
            'Email'       => 'john.doe@avangate.com',
            'Company'     => 'A',
            'FiscalCode'  => '123' . mt_rand(100, 999),
            'Phone'       => '021-000-' . mt_rand(10000, 99999),
            'Fax'         => '021-000-000',
            'Address1'    => 'DP10A',
            'Address2'    => 'CBP, b3',
            'Zip'         => '12345',
            'City'        => 'Bucharest',
            'State'       => 'Bucharest',
            'CountryCode' => 'RO',
            'Language'    => 'ro',
        ];

        $headers = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'verify' => false,
                'proxy' => ''
            ],
            'body' => json_encode($customer),
        ];

        $internalCustomerReference = null;

        try {
            $rawResponse = $this->client->post('customers/', $headers);
            $internalCustomerReference = json_decode($rawResponse->getBody()->getContents());

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }

        static::assertTrue(is_int($internalCustomerReference));
        static::assertTrue($internalCustomerReference > 0);

        return [
            'inputCustomerData' => $customer,
            'internalCustomerReference' => $internalCustomerReference
        ];
    }

    /**
     * Get details about a customer.
     * @depends testAddACustomer
     */
    public function testGetDetailsAboutACustomer($sample)
    {
        $inputCustomerData = $sample['inputCustomerData'];
        $internalCustomerReference = $sample['internalCustomerReference'];
        $customerDetails = null;

        try {
            $rawResponse = $this->client->get('customers/' . $internalCustomerReference . '/');
            $customerDetails = json_decode($rawResponse->getBody()->getContents());

        } catch (ClientException $e) {
            static::fail($e->getMessage() . ' -- ' . $e->getResponse()->getBody()->getContents());
        }

        static::assertInstanceOf('\stdClass', $customerDetails);
        static::assertEquals($internalCustomerReference,        $customerDetails->AvangateCustomerReference);
        static::assertEquals($inputCustomerData['FirstName'],   $customerDetails->FirstName);
        static::assertEquals($inputCustomerData['LastName'],    $customerDetails->LastName);
        static::assertEquals($inputCustomerData['Email'],       $customerDetails->Email);
        static::assertEquals($inputCustomerData['Phone'],       $customerDetails->Phone);
        static::assertEquals($inputCustomerData['Company'],     $customerDetails->Company);
        static::assertEquals($inputCustomerData['FiscalCode'],  $customerDetails->FiscalCode);
        static::assertEquals($inputCustomerData['Fax'],         $customerDetails->Fax);
        static::assertEquals($inputCustomerData['Address1'],    $customerDetails->Address1);
        static::assertEquals($inputCustomerData['Address2'],    $customerDetails->Address2);
        static::assertEquals($inputCustomerData['Zip'],         $customerDetails->Zip);
        static::assertEquals($inputCustomerData['City'],        $customerDetails->City);
        static::assertEquals($inputCustomerData['State'],       $customerDetails->State);
        static::assertEquals(strtolower($inputCustomerData['CountryCode']), $customerDetails->CountryCode);
    }
}
