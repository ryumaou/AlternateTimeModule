<?php

namespace Drupal\alt_time\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use DateTime;
use Drupal\alt_time\TCDate;
use function Drupal\alt_time\tcdate;
use function Drupal\alt_time\tcdateToHTML;
use function Drupal\alt_time\calculateStardate;
use function Drupal\alt_time\calculateImperialDate;
use function Drupal\alt_time\calculateOrdinalDate;
use function Drupal\alt_time\calculateDaleReckoning;
use function Drupal\alt_time\applyYearOffset;

class AltTimeConvertForm extends FormBase {

  public function getFormId() {
    return 'alt_time_convert_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('alt_time.settings');
    $default_standard = $config->get('default_standard') ?? 'tc';

    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input date/time'),
      '#default_value' => $form_state->getValue('input') ?? '',
      '#description' => $this->t('Enter anything accepted by PHP DateTime (e.g., "now", "2025-12-09T13:00:00", UNIX timestamp). Leave empty for "now".'),
    ];

    $form['is_leap_second'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Treat as leap second (for TC)'),
      '#default_value' => $form_state->getValue('is_leap_second') ?? 0,
    ];

    $form['standard'] = [
      '#type' => 'radios',
      '#title' => $this->t('Time standard'),
      '#options' => [
        'tc' => $this->t('Terran Computational Time (TC)'),
        'stardate' => $this->t('Star Trek Stardate'),
        'imperial' => $this->t('Warhammer 40K Imperial Date'),
        'ordinal' => $this->t('Ordinal date (YYYY.DOY.HHMMSS)'),
        'dale' => $this->t('Dale Reckoning (Forgotten Realms)'),
      ],
      '#default_value' => $form_state->getValue('standard') ?? $default_standard,
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#button_type' => 'primary',
    ];

    $results = $form_state->get('results');
    if (!empty($results)) {
      $form['results'] = [
        '#type' => 'markup',
        '#markup' => $results,
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('alt_time.settings');
    $default_standard = $config->get('default_standard') ?? 'tc';

    $input = trim((string) $form_state->getValue('input'));
    $is_leap_second = (bool) $form_state->getValue('is_leap_second');
    $standard = $form_state->getValue('standard') ?? $default_standard;

    if ($input === '') {
      $input = 'now';
    }

    try {
      // Normalize input to DateTime.
      if ($input === 'now') {
        $dt = new DateTime('now');
      }
      elseif (preg_match('/^[\-]?\d+$/', $input)) {
        $dt = new DateTime();
        $dt->setTimestamp((int) $input);
      }
      else {
        $dt = new DateTime($input);
      }

      // Apply site-wide year offset.
      $dt = applyYearOffset($dt);

      if ($standard === 'tc') {
        $tc_array = tcdate($dt->format(DateTime::ATOM), $is_leap_second);
        $tc_html = tcdateToHTML($tc_array);

        $results  = '<h2>Terran Computational Time</h2>';
        $results .= '<p><strong>Source date (after offset):</strong> ' . $dt->format(DateTime::ATOM) . '</p>';
        $results .= '<p><strong>Formatted:</strong> ' . $tc_html . '</p>';
        $results .= '<p><strong>Raw TC date array:</strong></p>';
        $results .= '<pre>' . print_r($tc_array, TRUE) . '</pre>';
      }
      elseif ($standard === 'stardate') {
        $stardate = calculateStardate($dt);

        $results  = '<h2>Star Trek Stardate</h2>';
        $results .= '<p><strong>Gregorian date (after offset):</strong> ' . $dt->format(DateTime::ATOM) . '</p>';
        $results .= '<p><strong>Stardate:</strong> ' . $stardate . '</p>';
      }
      elseif ($standard === 'imperial') {
        $imperial = calculateImperialDate($dt);

        $results  = '<h2>Warhammer 40K Imperial Date</h2>';
        $results .= '<p><strong>Gregorian date (after offset):</strong> ' . $dt->format(DateTime::ATOM) . '</p>';
        $results .= '<p><strong>Imperial date:</strong> ' . $imperial . '</p>';
      }
      elseif ($standard === 'ordinal') {
        $ordinal = calculateOrdinalDate($dt);

        $results  = '<h2>Ordinal date (YYYY.DOY.HHMMSS)</h2>';
        $results .= '<p><strong>Gregorian date (after offset):</strong> ' . $dt->format(DateTime::ATOM) . '</p>';
        $results .= '<p><strong>Ordinal date:</strong> ' . $ordinal . '</p>';
      }
      else {
        // Dale Reckoning.
        $dr = calculateDaleReckoning($dt);

        $results  = '<h2>Dale Reckoning (Forgotten Realms)</h2>';
        $results .= '<p><strong>Gregorian date (after offset):</strong> ' . $dt->format(DateTime::ATOM) . '</p>';
        $results .= '<p><strong>Dale Reckoning:</strong> ' . $dr . '</p>';
      }
    }
    catch (\Throwable $e) {
      $results  = '<h2>Error converting date</h2>';
      $results .= '<p>' . $this->t('There was a problem converting the provided date/time: @msg', [
        '@msg' => $e->getMessage(),
      ]) . '</p>';
    }

    $form_state->set('results', $results);
    $form_state->setRebuild(TRUE);
  }

}
