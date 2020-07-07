<?php

namespace Ptchr\Magento2RestClient\Tests;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Ptchr\Magento2RestClient\Client;
use Ptchr\Magento2RestClient\Exceptions\BillingAddressNotFoundException;
use Ptchr\Magento2RestClient\Exceptions\ShippingAddressNotFoundException;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @throws GuzzleException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client(
            $_SERVER['BASE_URL'],
            $_SERVER['ADMIN_USERNAME'],
            $_SERVER['ADMIN_PASSWORD']
        );
    }

    /**
     * @test
     * @throws GuzzleException
     */
    public function searching_a_customer()
    {
        $customer = $this->client->searchCustomerByEmail($_SERVER['CUSTOMER_EMAIL']);
        $this->assertIsArray($customer);
    }

    /** @test *
     * @throws GuzzleException
     * @throws BillingAddressNotFoundException
     * @throws ShippingAddressNotFoundException
     */
    public function creating_a_customer_order()
    {
        $customer = $this->client->searchCustomerByEmail($_SERVER['CUSTOMER_EMAIL'])['items'][0];
        $customerId = $customer['id'];

        $quoteId = $this->client->createCart($customerId);
        $this->assertIsInt($quoteId);

        $cart = $this->client->addProductToCart($quoteId, $_SERVER['TEST_PRODUCT_SKU'], 3);
        $this->assertIsArray($cart);

        $shippingMethods = $this->client->estimateAvailableShippingMethodsForCart($customer, $quoteId);
        $shippingMethod = $shippingMethods[0];

        $shippingInfo = $this->client->addShippingInformationToCart($customer, $quoteId, $shippingMethod['method_code'], $shippingMethod['carrier_code']);
        $this->assertIsArray($shippingInfo);

        $paymentMethods = $this->client->getAvailablePaymentMethodsForCart($quoteId);
        $this->assertIsArray($paymentMethods);

        $paymentMethod = $paymentMethods[0]['code'];
        $this->assertNotNull($this->client->setPaymentInformation($quoteId, $paymentMethod, 'test'));

        $order = $this->client->createOrder($quoteId, $paymentMethod, 'test');
        $this->assertNotNull($order);
    }
}
