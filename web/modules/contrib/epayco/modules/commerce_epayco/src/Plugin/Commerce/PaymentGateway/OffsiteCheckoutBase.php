<?php

namespace Drupal\commerce_epayco\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\epayco\Entity\Factory as ePaycoFactory;
use Drupal\epayco\GatewayHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for ePayco payment gateways.
 */
class OffsiteCheckoutBase extends OffsitePaymentGatewayBase {

  /**
   * The gateway handler.
   *
   * @var \Drupal\epayco\GatewayHandlerInterface
   */
  protected $gatewayHandler;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, GatewayHandlerInterface $gateway_handler, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->gatewayHandler = $gateway_handler;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('epayco.handler'),
      $container->get('commerce_epayco.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $factory = isset($this->configuration['factory']) ? ePaycoFactory::load($this->configuration['factory']) : NULL;

    $form['guidelines'] = [
      '#markup' => $this->t('Please, read carefully documentation at @docs_link.', [
        '@docs_link' => Link::fromTextAndUrl(
          'https://docs.epayco.co/payments/checkout',
          Url::fromUri('https://docs.epayco.co/payments/checkout')
        )->toString(),
      ]),
    ];
    $form['factory'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Factory'),
      '#description' => $this->t('Select one of the configuration entities from @config_link.', [
        '@config_link' => Link::fromTextAndUrl(
          'admin/config/services/epayco/factory',
          Url::fromRoute('entity.epayco_factory.list')
        )->toString(),
      ]),
      '#target_type' => 'epayco_factory',
      '#default_value' => $factory,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    if (substr($id, 0, 7) != 'epayco_') {
      $id = 'epayco_' . $id;
      // We need a way to know which payments are being processed with ePayco.
      $form_state->setValue('id', $id);
    }
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['factory'] = $values['factory'];
    }
  }

  /**
   * Helper method to get needed payment values from a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to search values on.
   */
  public function getNeededPaymentValuesFromRequest(Request $request) {
    $data = [
      'remote_state' => '',
      'remote_id' => '',
      'remote_status_code' => '',
      'remote_description' => '',
    ];
    // Now, let's fill previous values.
    if ($remote_ref = $request->query->get('ref_payco')) {
      // Response is received as $_GET parameter.
      $info = $this->gatewayHandler->getReferenceRemoteData($remote_ref);
      if (isset($info['data'])) {
        $data['remote_state'] = isset($info['data']['x_transaction_state']) ? $info['data']['x_transaction_state'] : '';
        $data['remote_id'] = isset($info['data']['x_ref_payco']) ? $info['data']['x_ref_payco'] : '';
        $data['remote_status_code'] = isset($info['data']['x_cod_transaction_state']) ? $info['data']['x_cod_transaction_state'] : '';
        $data['remote_description'] = isset($info['data']['x_response_reason_text']) ? $info['data']['x_response_reason_text'] : '';
      }
      else {
        $this->logger->log('error', 'Error fetching payment information for reference @remote_ref.', ['@remote_ref' => $remote_ref]);
      }
    }
    elseif (!array_diff(
      [
        'x_response',
        'x_ref_payco',
        'x_cod_response',
        'x_response_reason_text',
      ],
      array_keys($request->request->all())
    )) {
      // Response is received as $_POST parameters.
      $data['remote_state'] = $request->request->get('x_response');
      $data['remote_id'] = $request->request->get('x_ref_payco');
      $data['remote_status_code'] = $request->request->get('x_cod_response');
      $data['remote_description'] = $request->request->get('x_response_reason_text');
    }
    else {
      $this->logger->log('error', 'Error reading response values. Check ePayco documentation to know the correct variable names.');
    }

    return $data;
  }

}
