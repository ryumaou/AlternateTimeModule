<?php

namespace Drupal\alt_time\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
 * Provides a block showing the current alternate time.
 *
 * @Block(
 *   id = "alt_time_block",
 *   admin_label = @Translation("Alternate time current clock"),
 * )
 */
class AltTimeBlock extends BlockBase {

  public function defaultConfiguration() {
    return [
      'standard' => 'tc',
      'show_label' => TRUE,
    ] + parent::defaultConfiguration();
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $site_config = \Drupal::config('alt_time.settings');
    $default_standard = $site_config->get('default_standard') ?? 'tc';

    $form['standard'] = [
      '#type' => 'select',
      '#title' => $this->t('Time standard'),
      '#options' => [
        'tc' => $this->t('Terran Computational Time (TC)'),
        'stardate' => $this->t('Star Trek Stardate'),
        'imperial' => $this->t('Warhammer 40K Imperial Date'),
        'ordinal' => $this->t('Ordinal date (YYYYDOYHHMMSS)'),
        'dale' => $this->t('Dale Reckoning (Forgotten Realms)'),
      ],
      '#default_value' => $config['standard'] ?? $default_standard,
    ];

    $form['show_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show label'),
      '#default_value' => $config['show_label'] ?? TRUE,
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['standard'] = $form_state->getValue('standard');
    $this->configuration['show_label'] = (bool) $form_state->getValue('show_label');
  }

  public function build() {
    $config = \Drupal::config('alt_time.settings');
    $config_default = $config->get('default_standard') ?? 'tc';

    $standard = $this->configuration['standard'] ?? $config_default;
    $show_label = $this->configuration['show_label'] ?? TRUE;

    if ($standard === 'tc') {
      $dt = applyYearOffset(new DateTime('now'));
      $tc_array = tcdate($dt->format(DateTime::ATOM));
      $tc_html = tcdateToHTML($tc_array);
      $label = $show_label ? '<strong>Current TC:</strong> ' : '';

      $markup = '<div class="alt-time-current">' . $label . $tc_html . '</div>';
    }
    elseif ($standard === 'stardate') {
      $dt = applyYearOffset(new DateTime('now'));
      $stardate = calculateStardate($dt);
      $label = $show_label ? '<strong>Current Stardate:</strong> ' : '';

      $markup = '<div class="alt-time-current">' . $label . $stardate . '</div>';
    }
    elseif ($standard === 'imperial') {
      $dt = applyYearOffset(new DateTime('now'));
      $imperial = calculateImperialDate($dt);
      $label = $show_label ? '<strong>Current Imperial date:</strong> ' : '';

      $markup = '<div class="alt-time-current">' . $label . $imperial . '</div>';
    }
    elseif ($standard === 'ordinal') {
      $dt = applyYearOffset(new DateTime('now'));
      $ordinal = calculateOrdinalDate($dt);
      $label = $show_label ? '<strong>Current ordinal date:</strong> ' : '';

      $markup = '<div class="alt-time-current">' . $label . $ordinal . '</div>';
    }
    else {
      $dt = applyYearOffset(new DateTime('now'));
      $dr = calculateDaleReckoning($dt);
      $label = $show_label ? '<strong>Current Dale Reckoning:</strong> ' : '';

      $markup = '<div class="alt-time-current">' . $label . $dr . '</div>';
    }

    return [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
