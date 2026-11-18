<?php

namespace Drupal\alt_time\Plugin\Block;

use Drupal\alt_time\Form\AltTimeConvertBlockForm;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for converting a visitor-entered date and time.
 *
 * @Block(
 *   id = "alt_time_convert_block",
 *   admin_label = @Translation("Alternate time converter"),
 * )
 */
class AltTimeConvertBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm(AltTimeConvertBlockForm::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Build the form for the current request and keep submission results local.
    return 0;
  }

}
