<?php

namespace Drupal\analisis_respuestas\Services;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;

class Repetir
{

  /** @var MessengerInterface */
  private $messenger;

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager)
  {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function repetir($palabra, $cantidad = 3) {
    $this->messenger->addError('Esto es un error del servicio');
    return str_repeat($palabra, $cantidad);
  }

}
