<?php

namespace Drupal\analisis_respuestas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Analisis_respuestasController extends  ControllerBase
{
  public function configAnalisis_respuestas(){
    $config = $this->config('system.site');

    dpm($config, 'config');
    dpm($config->get('name'), 'name');

    $configEditable = $this->configFactory->getEditable('system.site');

    dpm($configEditable, 'config editable');

    $configEditable->set('slogan', 'Slogan editado en codigo');
    $configEditable->save();

    return ['#markup' => 'ruta de configuracion'];
  }
}
