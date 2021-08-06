<?php

namespace Drupal\commerce_epayco\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the One-page payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "epayco_onepage_checkout",
 *   label = "ePayco (One page checkout)",
 *   display_label = "ePayco (One page checkout)",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_epayco\PluginForm\OffsiteRedirect\OnePageCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   requires_billing_information = FALSE
 * )
 */
class OnePageCheckout extends OffsiteCheckoutBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['auto_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-open payment modal'),
      '#description' => $this->t('Open modal when user arrives to payment process.'),
      '#default_value' => isset($this->configuration['auto_open']) ? $this->configuration['auto_open'] : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['auto_open'] = $form_state->getValue($form['#parents'])['auto_open'];
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $data = $this->getNeededPaymentValuesFromRequest($request);
    switch ($data['remote_status_code']) {
      case 1:
        // Aceptada - Accepted.
      case 3:
        // Pendiente - Pending.
        $this->entityTypeManager->getStorage('commerce_payment')->create([
          'state' => $data['remote_status_code'] == 1 ? 'completed' : 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'remote_id' => $data['remote_id'],
          'remote_state' => $data['remote_state'],
          'authorized' => REQUEST_TIME,
        ])->save();
        $this->messenger()->addMessage($this->t('Transaction @status by ePayco.', ['@status' => $data['remote_state']]));
        break;

      case 2:
        // Rechazada - Rejected.
      case 4:
        // Fallida - Failed.
        $this->messenger()->addMessage($this->t('Order was finished, but payment transaction failed. ePayco says: @status', ['@status' => $data['remote_state']]), 'error');
        if (!empty($data['remote_description'])) {
          $this->logger->log('error', $data['remote_description']);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $status = $request->get('status');
    $this->messenger()->addMessage($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
      '@status' => $status,
      '@gateway' => $this->getDisplayLabel(),
    ]), 'error');
  }

}
