<?php

namespace Drupal\alt_time\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AltTimeSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['alt_time.settings'];
  }

  public function getFormId() {
    return 'alt_time_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alt_time.settings');
    $default_standard = $config->get('default_standard') ?? 'tc';
    $year_offset = (int) ($config->get('year_offset') ?? 0);
    $label_prefix = (string) ($config->get('label_prefix') ?? '');
    $label_suffix = (string) ($config->get('label_suffix') ?? '');

    $form['default_standard'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default time standard'),
      '#options' => [
        'tc' => $this->t('Terran Computational Time (TC)'),
        'stardate' => $this->t('Star Trek Stardate'),
        'imperial' => $this->t('Warhammer 40K Imperial Date'),
        'ordinal' => $this->t('Ordinal date (YYYY.DOY.HHMMSS)'),
        'dale' => $this->t('Dale Reckoning (Forgotten Realms)'),
      ],
      '#default_value' => $default_standard,
      '#description' => $this->t('Used wherever no explicit standard is provided (Twig filter, converter form, block defaults, field formatter defaults).'),
      '#required' => TRUE,
    ];

    $form['year_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Year offset'),
      '#default_value' => $year_offset,
      '#description' => $this->t('Number of years to offset all alternate time calculations. Use 0 for no offset, positive for future dates, negative for past dates.'),
      '#step' => 1,
    ];

    $form['label_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default prefix'),
      '#default_value' => $label_prefix,
      '#description' => $this->t('Text to prepend to alternate time values by default. Leave blank for no prefix.'),
    ];

    $form['label_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default suffix'),
      '#default_value' => $label_suffix,
      '#description' => $this->t('Text to append to alternate time values by default. Leave blank for no suffix.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('alt_time.settings')
      ->set('default_standard', $form_state->getValue('default_standard'))
      ->set('year_offset', (int) $form_state->getValue('year_offset'))
      ->set('label_prefix', (string) $form_state->getValue('label_prefix'))
      ->set('label_suffix', (string) $form_state->getValue('label_suffix'))
      ->clear('show_label')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
