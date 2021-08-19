<?php

namespace Drupal\tie_tools\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeTimeAgoFormatter;

/**
 * Plugin implementation of the "datetime_time_ago_and_date" formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "datetime_time_ago_and_date",
 *   label = @Translation("Time ago and date"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeTimeAgoAndDateFormatter extends DateTimeTimeAgoFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $date = $item->date;
      $output = [];
      if (!empty($date)) {
        $output = [
          'date' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->dateFormatter->format($date->getTimestamp(), '', 'Y-m-d H:i:s'),
            '#attributes' => [
              'class' => [
                'datetime-date'
              ],
            ],
          ],
          'interval' => parent::formatDate($date) + [
            '#prefix' => '<span class="datetime-interval">(',
            '#suffix' => ')</span>',
          ],
        ];
      }
      $elements[$delta] = $output;
    }

    return $elements;
  }

}
