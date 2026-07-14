<?php

namespace Drupal\alt_time\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use DateTime;
use function Drupal\alt_time\tcdate;
use function Drupal\alt_time\tcdateToHTML;
use function Drupal\alt_time\calculateStardate;
use function Drupal\alt_time\calculateImperialDate;
use function Drupal\alt_time\calculateOrdinalDate;
use function Drupal\alt_time\calculateDaleReckoning;
use function Drupal\alt_time\applyYearOffset;

/**
 * Twig extension for alternate time systems.
 */
class AltTimeExtension extends AbstractExtension {

  public function getFilters() {
    return [
      new TwigFilter('alt_time', [$this, 'altTimeFilter'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Convert a date value into an alternate time string/HTML.
   *
   * @param mixed $value
   *   Date value (string, timestamp, DateTime).
   * @param string|null $standard
   *   'tc', 'stardate', 'imperial', 'ordinal', 'dale' or NULL for default.
   * @param array $options
   *   - as_html: For TC, render as spans (TRUE) or plain text (FALSE).
   *   - label_prefix: Optional explicit prefix override.
   *   - label_suffix: Optional explicit suffix override.
   *
   * @return string
   *   Rendered alternate time.
   */
  public function altTimeFilter($value, ?string $standard = NULL, array $options = []): string {
    $config = \Drupal::config('alt_time.settings');

    if ($standard === NULL || $standard === '') {
      $standard = $config->get('default_standard') ?? 'tc';
    }

    $standard = strtolower($standard);
    $as_html = $options['as_html'] ?? TRUE;
    $label_prefix = array_key_exists('label_prefix', $options)
      ? (string) $options['label_prefix']
      : (string) ($config->get('label_prefix') ?? '');
    $label_suffix = array_key_exists('label_suffix', $options)
      ? (string) $options['label_suffix']
      : (string) ($config->get('label_suffix') ?? '');

    $dt = $this->normalizeToDateTime($value);
    if (!$dt) {
      return '';
    }

    $dt = applyYearOffset($dt);

    if ($standard === 'stardate') {
      return $label_prefix . calculateStardate($dt) . $label_suffix;
    }
    elseif ($standard === 'imperial') {
      return $label_prefix . calculateImperialDate($dt) . $label_suffix;
    }
    elseif ($standard === 'ordinal') {
      return $label_prefix . calculateOrdinalDate($dt) . $label_suffix;
    }
    elseif ($standard === 'dale') {
      return $label_prefix . calculateDaleReckoning($dt) . $label_suffix;
    }

    // Default: TC.
    $tc_array = tcdate($dt->format(DateTime::ATOM));

    if ($as_html) {
      $tc_html = tcdateToHTML($tc_array);
      return $label_prefix . $tc_html . $label_suffix;
    }
    else {
      return $label_prefix . ($tc_array['padded_date'] ?? $tc_array['date']) . $label_suffix;
    }
  }

  /**
   * Normalize arbitrary value to DateTime or NULL on failure.
   */
  protected function normalizeToDateTime($value): ?DateTime {
    if ($value instanceof DateTime) {
      return clone $value;
    }

    if (is_numeric($value) && preg_match('/^[\-]?\d+$/', (string) $value)) {
      $dt = new DateTime();
      $dt->setTimestamp((int) $value);
      return $dt;
    }

    if (is_string($value) && trim($value) !== '') {
      try {
        return new DateTime($value);
      }
      catch (\Throwable $e) {
        return NULL;
      }
    }

    return NULL;
  }

}
