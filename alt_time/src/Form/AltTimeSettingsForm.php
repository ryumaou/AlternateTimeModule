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

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('alt_time.settings')
      ->set('default_standard', $form_state->getValue('default_standard'))
      ->set('year_offset', (int) $form_state->getValue('year_offset'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
