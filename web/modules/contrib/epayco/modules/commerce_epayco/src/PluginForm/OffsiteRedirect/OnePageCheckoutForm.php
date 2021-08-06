<?php

namespace Drupal\commerce_epayco\PluginForm\OffsiteRedirect;

use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class OnePageCheckoutForm extends OffsiteCheckoutFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var array $parameters */
    $parameters = $this->getPaymentParameters($payment, ['return_url' => $form['#return_url']], 'onepage');

    if ($parameters['configuration']['auto_open']) {
      $form['#attached']['drupalSettings']['epayco'] = [
        'paymentOption' => [
          'auto_open_first' => TRUE,
        ],
      ];
    }
    $form['actions']['commerce_epayco_payment_option'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['commerce-epayco--payment-option'],
      ],
      'payment_option' => $this->paymentOptionsHandler->buildRenderablePaymentOption(
        array_merge($parameters['factory'], $parameters['checkout']),
        [
          'provider' => 'commerce_epayco',
          'plugin_id' => $payment->getPaymentGatewayId(),
        ]
      ),
    ];

    return $form;
  }

}
