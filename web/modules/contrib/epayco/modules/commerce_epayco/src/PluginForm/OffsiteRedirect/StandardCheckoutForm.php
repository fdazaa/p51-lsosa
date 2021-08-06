<?php

namespace Drupal\commerce_epayco\PluginForm\OffsiteRedirect;

use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class StandardCheckoutForm extends OffsiteCheckoutFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var array $parameters */
    $parameters = $this->getPaymentParameters($payment, ['return_url' => $form['#return_url']], 'standard');

    // Let's validate if split payments is enabled.
    if (
      isset($parameters['checkout']['p_split_type']) &&
      isset($parameters['checkout']['p_split_merchant_receiver']) &&
      isset($parameters['checkout']['p_split_primary_receiver']) &&
      isset($parameters['checkout']['p_split_primary_receiver_fee'])
    ) {
      $redirect_form_url = $this->gatewayHandler::EPAYCO_SPLIT_PAYMENTS_API_URL;
      // Split payments do not support boolean values for "p_test_request".
      $parameters['checkout']['p_test_request'] = (bool) $parameters['checkout']['p_test_request'] ? 'TRUE' : 'FALSE';
    }
    else {
      $redirect_form_url = $this->gatewayHandler::EPAYCO_STANDARD_CHECKOUT_API_URL;
    }

    return $this->buildRedirectForm($form, $form_state, $redirect_form_url, $parameters['checkout'], 'post');
  }

}
