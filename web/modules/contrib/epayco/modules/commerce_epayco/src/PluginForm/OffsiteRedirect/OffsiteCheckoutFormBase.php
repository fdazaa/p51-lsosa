<?php

namespace Drupal\commerce_epayco\PluginForm\OffsiteRedirect;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\epayco\GatewayHandlerInterface;
use Drupal\epayco\PaymentOptionsHandlerInterface;
use Drupal\epayco\Entity\Factory as ePaycoFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class OffsiteCheckoutFormBase extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * The gateway handler.
   *
   * @var \Drupal\epayco\GatewayHandlerInterface
   */
  protected $gatewayHandler;

  /**
   * The payment options handler.
   *
   * @var \Drupal\epayco\PaymentOptionsHandlerInterface
   */
  protected $paymentOptionsHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor method.
   *
   * @param \Drupal\commerce_epayco\GatewayHandlerInterface $gateway_handler
   *   The gateway handler.
   * @param \Drupal\epayco\PaymentOptionsHandlerInterface $payment_options
   *   The payment options manager class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(GatewayHandlerInterface $gateway_handler, PaymentOptionsHandlerInterface $payment_options, ModuleHandlerInterface $module_handler) {
    $this->gatewayHandler = $gateway_handler;
    $this->paymentOptionsHandler = $payment_options;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('epayco.handler'),
      $container->get('epayco.payment_options.handler'),
      $container->get('module_handler')
    );
  }

  /**
   * Helper method to get payment parameters.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity to fetch data from.
   * @param array $callback_endpoints
   *   Internal URLs to return after payment. At least "return_url" is required.
   * @param string $checkout_type
   *   The checkout type to fetch parameters for. Possible values are:
   *   - standard: Fetch array for standard checkout.
   *   - onepage: Fetch array for onsite checkout.
   */
  public function getPaymentParameters(PaymentInterface $payment, array $callback_endpoints, $checkout_type = 'standard') {
    if (empty($callback_endpoints) || !isset($callback_endpoints['return_url'])) {
      return [];
    }

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();
    /** @var \Drupal\commerce_store\Entity\Store $store */
    $store = $order->getStore();
    /** @var \Drupal\commerce_epayco\Entity\Factory $factory */
    $factory = ePaycoFactory::load($payment_gateway_plugin->getConfiguration()['factory']);
    /** @var \Drupal\address\AddressInterface $address */
    $address = $payment_gateway_plugin->collectsBillingInformation() ? $order->getBillingProfile()->address->first() : NULL;

    // Other needed variables.
    $__p_amount = $payment->getAmount()->getNumber();
    $__p_currency_code = $payment->getAmount()->getCurrencyCode();
    $__p_tax = 0;
    foreach ($order->collectAdjustments() as $tax) {
      $__p_tax += $tax->getAmount()->getNumber();
    }
    // Let's evaluate now needed parameters.
    $allowed_checkout_types = ['standard', 'onepage'];
    if (!in_array($checkout_type, $allowed_checkout_types)) {
      return [];
    }
    $parameters = [];
    switch ($checkout_type) {
      case 'standard':
        // Store needed values.
        $store__p_cust_id_cliente = $store->get('epayco_client_id')->getString();
        $store__p_key = $store->get('epayco_key')->getString();
        // Values validation.
        if ($store__p_cust_id_cliente && $store__p_key) {
          // If store overrides settings, let's use them for gateway.
          $__p_cust_id_cliente = $store__p_cust_id_cliente;
          $__p_key = $store__p_key;
          $__p_test_request = $store->get('epayco_mode')->getString() == '1';
        }
        else {
          // If settings are not overriden, then use global values.
          $__p_cust_id_cliente = $factory->getClientId();
          $__p_key = $factory->getKey();
          $__p_test_request = $factory->isTestMode();
        }
        $__p_signature = $this->gatewayHandler->getPaymentSignature($__p_cust_id_cliente, $__p_key, $order->id(), $__p_amount, $__p_currency_code);
        $parameters += [
          'checkout' => [
            'p_cust_id_cliente' => $__p_cust_id_cliente,
            'p_key' => $__p_key,
            'p_id_invoice' => $order->id(),
            'p_description' => $this->t('Purchase order #@order_id', ['@order_id' => $order->id()]),
            'p_amount' => $__p_amount,
            'p_amount_base' => $__p_amount - $__p_tax,
            'p_tax' => $__p_tax,
            'p_email' => $order->getEmail(),
            'p_currency_code' => $__p_currency_code,
            'p_signature' => $__p_signature,
            'p_test_request' => $__p_test_request,
            'p_customer_ip' => $order->getIpAddress(),
            'p_url_response' => $callback_endpoints['return_url'],
            'p_url_confirmation' => '',
            'p_confirm_method' => 'POST',
            'p_extra1' => '',
            'p_extra2' => '',
            'p_extra3' => '',
            'p_billing_document' => '',
            'p_billing_name' => $address ? $address->getGivenName() : '',
            'p_billing_lastname' => $address ? $address->getFamilyName() : '',
            'p_billing_address' => $address ? $address->getAddressLine1() . ' ' . $address->getAddressLine2() : '',
            'p_billing_country' => $address ? $address->getCountryCode() : '',
            'p_billing_email' => $order->getEmail(),
            'p_billing_phone' => '',
            'p_billing_cellphone' => '',
          ],
        ];
        break;

      case 'onepage':
        // Store needed values.
        $store__public_key = $store->get('epayco_api_public_key')->getString();
        $store__language = $store->get('epayco_language')->getString();
        // Values validation.
        if ($store__public_key && $store__language) {
          $__p_test_request = $store->get('epayco_mode')->getString() == '1';
          $__public_key = $store__public_key;
          $__language = $store__language;
        }
        else {
          // If settings are not overriden, then use global values.
          $__p_test_request = $factory->isTestMode();
          $__public_key = $factory->getApiPublicKey();
          $__language = $factory->getLanguageCode();
        }
        $parameters += [
          'factory' => [
            'key' => $__public_key,
            'test' => $__p_test_request,
          ],
          'configuration' => [
            'auto_open' => (boolean) $payment_gateway_plugin->getConfiguration()['auto_open'],
          ],
          'checkout' => [
            'name' => $this->t('Purchase order #@order_id', ['@order_id' => $order->id()]),
            'description' => $this->t('Purchase order #@order_id', ['@order_id' => $order->id()]),
            'invoice' => $order->id(),
            'currency' => $__p_currency_code,
            'amount' => $__p_amount,
            'tax_base' => $__p_amount - $__p_tax,
            'tax' => $__p_tax,
            'country' => $address ? $address->getCountryCode() : 'CO',
            'lang' => $__language,
            'external' => FALSE,
            'extra1' => '',
            'extra2' => '',
            'extra3' => '',
            'confirmation' => '',
            'response' => $callback_endpoints['return_url'],
            'name_billing' => $address ? $address->getGivenName() . (!empty($address->getFamilyName()) ? ' ' . $address->getFamilyName() : '') : '',
            'address_billing' => $address ? $address->getAddressLine1() . ' ' . $address->getAddressLine2() : '',
            'type_doc_billing' => '',
            'mobilephone_billing' => '',
            'number_doc_billing' => '',
          ],
        ];
        break;
    }
    // External modules may alter payment data before sending to the gateway.
    $this->moduleHandler->invokeAll('commerce_epayco_payment_data_alter', [$payment, &$parameters]);

    return $parameters;
  }

}
