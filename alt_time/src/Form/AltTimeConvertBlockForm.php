<?php

namespace Drupal\alt_time\Form;

/**
 * Provides the compact converter form used in a block.
 */
class AltTimeConvertBlockForm extends AltTimeConvertForm {

  protected const COMPACT = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alt_time_convert_block_form';
  }

}
