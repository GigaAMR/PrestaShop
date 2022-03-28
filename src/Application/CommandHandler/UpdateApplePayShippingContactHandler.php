<?php

namespace Mollie\Application\CommandHandler;

use Address;
use Carrier;
use Cart;
use Configuration;
use Country;
use Customer;
use Language;
use Mollie\Application\Command\UpdateApplePayShippingContact;
use Mollie\Builder\ApplePayDirect\ApplePayCarriersBuilder;
use Tools;
use Mollie\DTO\ApplePay\Carrier\Carrier as AppleCarrier;

final class UpdateApplePayShippingContactHandler
{
    /**
     * @var ApplePayCarriersBuilder
     */
    private $applePayCarriersBuilder;
    /**
     * @var Language
     */
    private $language;

    public function __construct(
        ApplePayCarriersBuilder $applePayCarriersBuilder,
        Language $language
    ) {
        $this->applePayCarriersBuilder = $applePayCarriersBuilder;
        $this->language = $language;
    }

    public function handle(UpdateApplePayShippingContact $command): array
    {
        $customer = $this->createCustomer($command->getCustomerId());
        $deliveryAddress = $this->createAddress($customer->id, $command);
        $invoiceAddress = $this->createAddress($customer->id, $command);
        $cart = $this->updateCart($customer, $deliveryAddress->id, $invoiceAddress->id , $command->getCartId());
        $this->addProductToCart($cart, $command);

        $country = new Country($deliveryAddress->id_country);

        $applePayCarriers = $this->applePayCarriersBuilder->build(Carrier::getCarriersForOrder($this->language->id, true), $country->id_zone);

        $shippingMethods = array_map(function (AppleCarrier $carrier) {
            return [
                'identifier' => $carrier->getCarrierId(),
                'label' => $carrier->getName(),
                'amount' => $carrier->getAmount(),
                'detail' => $carrier->getDelay(),
            ];
        }, $applePayCarriers);

        $totals = array_map(function (AppleCarrier $carrier) use ($cart) {
            return [
                'type' => 'final',
                'label' => $carrier->getName(),
                'amount' => number_format($cart->getOrderTotal(true, Cart::BOTH, null, $carrier->getCarrierId()), 2, '.', ''),
            ];
        }, $applePayCarriers);

        return [
            'data' => [
                'shipping_methods' => $shippingMethods,
                'totals' => $totals,
            ],
            'success' => true
        ];
    }

    private function createAddress(int $customerId, UpdateApplePayShippingContact $command): Address
    {
        $address = new Address();
        $address->address1 = 'ApplePay';
        $address->lastname = 'ApplePay';
        $address->firstname = 'ApplePay';
        $address->id_customer = $customerId;
        $address->alias = 'applePay';
        $address->postcode = $command->getPostalCode();
        $address->id_country = Country::getByIso($command->getCountryCode());
        $address->country = $command->getCountry();
        $address->city = $command->getLocality();
        $address->add();

        return $address;
    }

    private function createCustomer(int $customerId): Customer
    {
        if ($customerId) {
            return new Customer($customerId);
        }
        $customer = new Customer();
        $customer->is_guest = 1;
        $customer->firstname = 'applePay';
        $customer->lastname = 'applePay';
        $customer->email = 'applePay@mollie.com';
        $customer->passwd = Tools::hash(microtime());
        $customer->add();

        return $customer;
    }

    private function updateCart(Customer $customer, int $deliveryAddressId, int $invoiceAddressId, int $cartId): cart
    {
        $cart = new Cart($cartId);
        $cart->id_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->secure_key = $customer->secure_key;
        $cart->id_address_delivery = $deliveryAddressId;
        $cart->id_address_invoice = $invoiceAddressId;
        $cart->id_customer = $customer->id;
        $cart->update();

        return $cart;
    }

    private function addProductToCart(Cart $cart, UpdateApplePayShippingContact $command): void
    {
        foreach ($command->getProducts() as $product) {
            $cart->deleteProduct($product->getProductId(), $product->getProductAttribute());
            $cart->updateQty($product->getWantedQuantity(), $product->getProductId(), $product->getProductAttribute());
        }
    }
}