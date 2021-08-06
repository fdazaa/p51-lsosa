<?php

namespace Drupal\commerce_epayco\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the standard checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "epayco_standard_checkout",
 *   label = "ePayco (Standard checkout)",
 *   display_label = "ePayco (Standard checkout)",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_epayco\PluginForm\OffsiteRedirect\StandardCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   requires_billing_information = FALSE
 * )
 */
class StandardCheckout extends OffsiteCheckoutBase {

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
