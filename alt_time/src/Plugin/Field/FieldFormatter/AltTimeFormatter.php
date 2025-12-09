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
      'show_label' => TRUE,
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

    $elements['show_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show label'),
      '#default_value' => $this->getSetting('show_label'),
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

    if ($this->getSetting('show_label')) {
      $summary[] = $this->t('Label is displayed.');
    }
    else {
      $summary[] = $this->t('Label is hidden.');
    }

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $standard = $this->getSetting('standard');
    $show_label = (bool) $this->getSetting('show_label');
    $as_html = (bool) $this->getSetting('as_html');

    foreach ($items as $delta => $item) {
      $value = $item->value;
      if ($value === NULL || $value === '') {
        continue;
      }

      // Normalize to DateTime for non-TC branches; TC will do its own.
      if ($standard === 'tc') {
        // Interpret as timestamp or date string.
        if (preg_match('/^[\-]?\d+$/', (string) $value)) {
          $dt = new DateTime();
          $dt->setTimestamp((int) $value);
        }
        else {
          $dt = new DateTime($value);
        }

        $dt = applyYearOffset($dt);

        $tc_array = tcdate($dt->format(DateTime::ATOM));
        $label_html = $show_label ? '<span class="tc-label">TC:</span> ' : '';

        if ($as_html) {
          $tc_html = tcdateToHTML($tc_array);
          $output = $label_html . $tc_html;

          $elements[$delta] = [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }}',
            '#context' => ['value' => $output],
          ];
        }
        else {
          $text = ($show_label ? 'TC: ' : '') . ($tc_array['padded_date'] ?? $tc_array['date']);
          $elements[$delta] = [
            '#plain_text' => $text,
          ];
        }
      }
      elseif ($standard === 'stardate') {
        if (preg_match('/^[\-]?\d+$/', (string) $value)) {
          $dt = new DateTime();
          $dt->setTimestamp((int) $value);
        }
        else {
          $dt = new DateTime($value);
        }

        $dt = applyYearOffset($dt);
        $stardate = calculateStardate($dt);
        $label_text = $show_label ? 'Stardate ' : '';

        $elements[$delta] = [
          '#plain_text' => $label_text . $stardate,
        ];
      }
      elseif ($standard === 'imperial') {
        if (preg_match('/^[\-]?\d+$/', (string) $value)) {
          $dt = new DateTime();
          $dt->setTimestamp((int) $value);
        }
        else {
          $dt = new DateTime($value);
        }

        $dt = applyYearOffset($dt);
        $imperial = calculateImperialDate($dt);
        $label_text = $show_label ? 'Imperial date ' : '';

        $elements[$delta] = [
          '#plain_text' => $label_text . $imperial,
        ];
      }
      elseif ($standard === 'ordinal') {
        if (preg_match('/^[\-]?\d+$/', (string) $value)) {
          $dt = new DateTime();
          $dt->setTimestamp((int) $value);
        }
        else {
          $dt = new DateTime($value);
        }

        $dt = applyYearOffset($dt);
        $ordinal = calculateOrdinalDate($dt);
        $label_text = $show_label ? 'Ordinal date ' : '';

        $elements[$delta] = [
          '#plain_text' => $label_text . $ordinal,
        ];
      }
      else {
        // Dale Reckoning.
        if (preg_match('/^[\-]?\d+$/', (string) $value)) {
          $dt = new DateTime();
          $dt->setTimestamp((int) $value);
        }
        else {
          $dt = new DateTime($value);
        }

        $dt = applyYearOffset($dt);
        $dr = calculateDaleReckoning($dt);
        $label_text = $show_label ? 'Dale Reckoning ' : '';

        $elements[$delta] = [
          '#plain_text' => $label_text . $dr,
        ];
      }
    }

    return $elements;
  }

}
