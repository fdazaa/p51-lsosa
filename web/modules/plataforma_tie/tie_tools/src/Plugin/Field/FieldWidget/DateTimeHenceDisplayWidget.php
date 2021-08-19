<?php

namespace Drupal\tie_tools\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'datetime_hence_display' widget.
 *
 * @FieldWidget(
 *   id = "datetime_hence_display",
 *   label = @Translation("Date and time (hence display)"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeHenceDisplayWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#prefix'] = '<div class="js--date-hence-dispatcher">';
    $element['#suffix'] = '</div>';
    $element['date_hence_output'] = [
      '#markup' => '<div class="js--date-hence-output"></div>',
    ];
    $element['#attached']['library'][] = 'tie_tools/date_widget.hence.behavior';

    return $element;
  }

}
