<?php
/**
 * Copyright (c) 2012-2018, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @author     Mollie B.V. <info@mollie.nl>
 * @copyright  Mollie B.V.
 * @license    Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @category   Mollie
 * @package    Mollie
 * @link       https://www.mollie.nl
 * @codingStandardsIgnoreStart
 */

if (!defined('_PS_VERSION_')) {
    return;
}

/**
 * Class MolliePaymentModuleFrontController
 *
 * @property Context? $context
 * @property Mollie       $module
 */
class MolliePaymentModuleFrontController extends ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Adapter_Exception
     * @throws SmartyException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $this->context->smarty->assign('link', $this->context->link);

        if (!$this->validate(
            $cart,
            $customer
        )) {
            $this->errors[] = $this->module->lang['This payment method is not available.'];
            $this->setTemplate('error.tpl');

            return;
        }

        $method = Tools::getValue('method');
        if (in_array($method, array('cartasi', 'cartesbancaires'))) {
            $method = 'creditcard';
        }
        $issuer = Tools::getValue('issuer') ?: null;

        // If no issuer was set yet and the issuer list has its own page, show issuer list here
        if (!$issuer
            && Configuration::get(Mollie::MOLLIE_ISSUERS) === Mollie::ISSUERS_OWN_PAGE
            && $method === \MollieModule\Mollie\Api\Types\PaymentMethod::IDEAL
        ) {
            $tplData = array();
            try {
                $issuers = $this->module->getIssuerList();
            } catch (\MollieModule\Mollie\Api\Exceptions\ApiException $e) {
                $this->setTemplate('error.tpl');
                $this->errors[] = Configuration::get(Mollie::MOLLIE_DISPLAY_ERRORS)
                    ? $e->getMessage()
                    : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
                return;
            } catch (PrestaShopException $e) {
                $this->setTemplate('error.tpl');
                $this->errors[] = Configuration::get(Mollie::MOLLIE_DISPLAY_ERRORS)
                    ? $e->getMessage()
                    : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
                return;
            }
            $tplData['issuers'] = isset($issuers[\MollieModule\Mollie\Api\Types\PaymentMethod::IDEAL]) ? $issuers[\MollieModule\Mollie\Api\Types\PaymentMethod::IDEAL] : array();
            if (!empty($tplData['issuers'])) {
                $tplData['msg_bankselect'] = $this->module->lang['Select your bank:'];
                $tplData['msg_ok'] = $this->module->lang['OK'];
                $tplData['msg_return'] = $this->module->lang['Different payment method'];
                $tplData['link'] = $this->context->link;
                $tplData['cartAmount'] = (int) ($this->context->cart->getOrderTotal(true) * 100);
                $tplData['qrAlign'] = 'center';
                if (Configuration::get(Mollie::MOLLIE_QRENABLED) && Mollie::selectedApi() === Mollie::MOLLIE_PAYMENTS_API) {
                    $this->context->controller->addJS(_PS_MODULE_DIR_.'mollie/views/js/dist/front.min.js');
                }
                $this->context->smarty->assign($tplData);
                $this->setTemplate('mollie_issuers.tpl');

                return;
            }
        }

        $originalAmount = $cart->getOrderTotal(
            true,
            Cart::BOTH
        );
        $amount = $originalAmount;

        // Prepare payment
        $paymentData = Mollie::getPaymentData(
            $amount,
            Tools::strtoupper($this->context->currency->iso_code),
            $method,
            $issuer,
            (int) $cart->id,
            $customer->secure_key,
            false,
            Order::generateReference()
        );
        try {
            $payment = $this->createPayment($paymentData);
        } catch (\MollieModule\Mollie\Api\Exceptions\ApiException $e) {
            $this->setTemplate('error.tpl');
            $this->errors[] = Configuration::get(Mollie::MOLLIE_DISPLAY_ERRORS)
                ? $e->getMessage()
                : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
            return;
        } catch (PrestaShopException $e) {
            $this->setTemplate('error.tpl');
            $this->errors[] = Configuration::get(Mollie::MOLLIE_DISPLAY_ERRORS)
                ? $e->getMessage()
                : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
            return;
        }
        $orderReference = isset($payment->metadata->order_reference) ? pSQL($payment->metadata->order_reference) : '';

        // Store payment linked to cart
        if ($payment->method !== \MollieModule\Mollie\Api\Types\PaymentMethod::BANKTRANSFER) {
            try {
                Db::getInstance()->insert(
                    'mollie_payments',
                    array(
                        'cart_id'         => (int) $cart->id,
                        'method'          => pSQL($payment->method),
                        'transaction_id'  => pSQL($payment->id),
                        'order_reference' => pSQL($orderReference),
                        'bank_status'     => \MollieModule\Mollie\Api\Types\PaymentStatus::STATUS_OPEN,
                        'created_at'      => array('type' => 'sql', 'value' => 'NOW()'),
                    )
                );
            } catch (PrestaShopDatabaseException $e) {
                Mollie::tryAddOrderReferenceColumn();
                throw $e;
            }
        }

        $status = $payment->status;
        if (!isset($this->module->statuses[$payment->status])) {
            $status = 'open';
        }

        $paymentStatus = (int) $this->module->statuses[$status];

        if ($paymentStatus < 1) {
            $paymentStatus = Configuration::get('PS_OS_BANKWIRE');
        }

        if ($payment->method === \MollieModule\Mollie\Api\Types\PaymentMethod::BANKTRANSFER) {
            $this->module->currentOrderReference = $orderReference;
            $this->module->validateMollieOrder(
                (int) $cart->id,
                $paymentStatus,
                $originalAmount,
                isset(Mollie::$methods[$payment->method]) ? Mollie::$methods[$payment->method] : $this->module->name,
                null,
                array(),
                null,
                false,
                $customer->secure_key
            );

            $orderId = Order::getOrderByCartId((int) $cart->id);

            try {
                Db::getInstance()->insert(
                    'mollie_payments',
                    array(
                        'cart_id'         => (int) $cart->id,
                        'order_id'        => (int) $orderId,
                        'order_reference' => pSQL($orderReference),
                        'method'          => pSQL($payment->method),
                        'transaction_id'  => pSQL($payment->id),
                        'bank_status'     => \MollieModule\Mollie\Api\Types\PaymentStatus::STATUS_OPEN,
                        'created_at'      => array('type' => 'sql', 'value' => 'NOW()'),
                    )
                );
            } catch (PrestaShopDatabaseException $e) {
                Mollie::tryAddOrderReferenceColumn();
                throw $e;
            }
        }

        // Go to payment url
        Tools::redirect($payment->getCheckoutUrl());
    }

    /**
     * Checks if this payment option is still available
     * May redirect the user to a more appropriate page
     *
     * @param Cart     $cart
     * @param Customer $customer
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function validate($cart, $customer)
    {
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            // We be like: how did you even get here?
            Tools::redirect(Context::getContext()->link->getPageLink('index', true));
            return false;
        }

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return false;
        }

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return \MollieModule\Mollie\Api\Resources\Payment|\MollieModule\Mollie\Api\Resources\Order|null
     *
     * @throws PrestaShopException
     * @throws \MollieModule\Mollie\Api\Exceptions\ApiException
     */
    protected function createPayment($data)
    {
        /** @var \MollieModule\Mollie\Api\Resources\Payment|\MollieModule\Mollie\Api\Resources\Order $payment */
        $payment = $this->module->api->{Mollie::selectedApi()}->create($data);

        return $payment;
    }

    /**
     * Prepend module path if PS version >= 1.7
     *
     * @param string      $template
     * @param array       $params
     * @param string|null $locale
     *
     * @throws PrestaShopException
     *
     * @since 3.3.2
     */
    public function setTemplate($template, $params = [], $locale = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $template = "module:mollie/views/templates/front/17_{$template}";
        }

        parent::setTemplate($template, $params, $locale);
    }
}
