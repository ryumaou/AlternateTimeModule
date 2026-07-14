<?php

namespace Drupal\alt_time\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use DateTime;
use function Drupal\alt_time\tcdate;
use function Drupal\alt_time\tcdateToHTML;
use function Drupal\alt_time\calculateStardate;
use function Drupal\alt_time\calculateImperialDate;
use function Drupal\alt_time\calculateOrdinalDate;
use function Drupal\alt_time\calculateDaleReckoning;
use function Drupal\alt_time\applyYearOffset;

/**
 * Plugin implementation of the 'alt_time' formatter.
 *
 * @FieldFormatter(
 *   id = "alt_time",
 *   label = @Translation("Alternate time systems"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *     "datetime"
 *   }
 * )
 */
class AltTimeFormatter extends FormatterBase {

  public static function defaultSettings() {
    return [
      'standard' => 'tc',
      'label_prefix' => '',
      'label_suffix' => '',
      'as_html' => TRUE,
    ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['standard'] = [
      '#type' => 'select',
      '#title' => $this->t('Time standard'),
      '#options' => [
        'tc' => $this->t('Terran Computational Time (TC)'),
        'stardate' => $this->t('Star Trek Stardate'),
        'imperial' => $this->t('Warhammer 40K Imperial Date'),
        'ordinal' => $this->t('Ordinal date (YYYY.DOY.HHMMSS)'),
        'dale' => $this->t('Dale Reckoning (Forgotten Realms)'),
      ],
      '#default_value' => $this->getSetting('standard'),
    ];

    $elements['label_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $this->getSetting('label_prefix'),
      '#description' => $this->t('Optional text to prepend to the rendered value. Leave blank for none.'),
    ];

    $elements['label_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $this->getSetting('label_suffix'),
      '#description' => $this->t('Optional text to append to the rendered value. Leave blank for none.'),
    ];

    $elements['as_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render TC as HTML spans'),
      '#default_value' => $this->getSetting('as_html'),
      '#description' => $this->t('For TC only. Other standards always render as plain text.'),
    ];

    return $elements;
  }

  public function settingsSummary() {
    $summary = [];
    $standard = $this->getSetting('standard');

    if ($standard === 'tc') {
      $summary[] = $this->t('Rendered as Terran Computational Time.');
    }
    elseif ($standard === 'stardate') {
      $summary[] = $this->t('Rendered as Star Trek Stardate.');
    }
    elseif ($standard === 'imperial') {
      $summary[] = $this->t('Rendered as Warhammer 40K Imperial Date.');
    }
    elseif ($standard === 'ordinal') {
      $summary[] = $this->t('Rendered as Ordinal date (YYYY.DOY.HHMMSS).');
    }
    else {
      $summary[] = $this->t('Rendered as Dale Reckoning (Forgotten Realms).');
    }

    $prefix = (string) $this->getSetting('label_prefix');
    $suffix = (string) $this->getSetting('label_suffix');
    $summary[] = $this->t('Prefix: %prefix', ['%prefix' => $prefix === '' ? '[blank]' : $prefix]);
    $summary[] = $this->t('Suffix: %suffix', ['%suffix' => $suffix === '' ? '[blank]' : $suffix]);

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $standard = $this->getSetting('standard');
    $label_prefix = (string) $this->getSetting('label_prefix');
    $label_suffix = (string) $this->getSetting('label_suffix');
    $as_html = (bool) $this->getSetting('as_html');

    foreach ($items as $delta => $item) {
      $value = $item->value;
      if ($value === NULL || $value === '') {
        continue;
      }

      if (preg_match('/^[\-]?\d+$/', (string) $value)) {
        $dt = new DateTime();
        $dt->setTimestamp((int) $value);
      }
      else {
        $dt = new DateTime($value);
      }

      $dt = applyYearOffset($dt);

      if ($standard === 'tc') {
        $tc_array = tcdate($dt->format(DateTime::ATOM));

        if ($as_html) {
          $tc_html = tcdateToHTML($tc_array);
          $output = $label_prefix . $tc_html . $label_suffix;

          $elements[$delta] = [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }}',
            '#context' => ['value' => $output],
          ];
        }
        else {
          $text = $label_prefix . ($tc_array['padded_date'] ?? $tc_array['date']) . $label_suffix;
          $elements[$delta] = [
            '#plain_text' => $text,
          ];
        }
      }
      elseif ($standard === 'stardate') {
        $elements[$delta] = [
          '#plain_text' => $label_prefix . calculateStardate($dt) . $label_suffix,
        ];
      }
      elseif ($standard === 'imperial') {
        $elements[$delta] = [
          '#plain_text' => $label_prefix . calculateImperialDate($dt) . $label_suffix,
        ];
      }
      elseif ($standard === 'ordinal') {
        $elements[$delta] = [
          '#plain_text' => $label_prefix . calculateOrdinalDate($dt) . $label_suffix,
        ];
      }
      else {
        $elements[$delta] = [
          '#plain_text' => $label_prefix . calculateDaleReckoning($dt) . $label_suffix,
        ];
      }
    }

    return $elements;
  }

}
