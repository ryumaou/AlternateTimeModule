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
      'label_prefix' => '',
      'label_suffix' => '',
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

    $form['label_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $config['label_prefix'] ?? '',
      '#description' => $this->t('Optional text to prepend to the rendered value. Leave blank for none.'),
    ];

    $form['label_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $config['label_suffix'] ?? '',
      '#description' => $this->t('Optional text to append to the rendered value. Leave blank for none.'),
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['standard'] = $form_state->getValue('standard');
    $this->configuration['label_prefix'] = (string) $form_state->getValue('label_prefix');
    $this->configuration['label_suffix'] = (string) $form_state->getValue('label_suffix');
  }

  public function build() {
    $config = \Drupal::config('alt_time.settings');
    $config_default = $config->get('default_standard') ?? 'tc';
    $config_label_prefix = (string) ($config->get('label_prefix') ?? '');
    $config_label_suffix = (string) ($config->get('label_suffix') ?? '');

    $standard = $this->configuration['standard'] ?? $config_default;
    $label_prefix = array_key_exists('label_prefix', $this->configuration)
      ? (string) $this->configuration['label_prefix']
      : $config_label_prefix;
    $label_suffix = array_key_exists('label_suffix', $this->configuration)
      ? (string) $this->configuration['label_suffix']
      : $config_label_suffix;

    if ($standard === 'tc') {
      $dt = applyYearOffset(new DateTime('now'));
      $tc_array = tcdate($dt->format(DateTime::ATOM));
      $value = tcdateToHTML($tc_array);
      $markup = '<div class="alt-time-current">' . $label_prefix . $value . $label_suffix . '</div>';
    }
    elseif ($standard === 'stardate') {
      $dt = applyYearOffset(new DateTime('now'));
      $markup = '<div class="alt-time-current">' . $label_prefix . calculateStardate($dt) . $label_suffix . '</div>';
    }
    elseif ($standard === 'imperial') {
      $dt = applyYearOffset(new DateTime('now'));
      $markup = '<div class="alt-time-current">' . $label_prefix . calculateImperialDate($dt) . $label_suffix . '</div>';
    }
    elseif ($standard === 'ordinal') {
      $dt = applyYearOffset(new DateTime('now'));
      $markup = '<div class="alt-time-current">' . $label_prefix . calculateOrdinalDate($dt) . $label_suffix . '</div>';
    }
    else {
      $dt = applyYearOffset(new DateTime('now'));
      $markup = '<div class="alt-time-current">' . $label_prefix . calculateDaleReckoning($dt) . $label_suffix . '</div>';
    }

    return [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
