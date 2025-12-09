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
   *   - show_label: Whether to include a label prefix.
   *
   * @return string
   *   Rendered alternate time.
   */
  public function altTimeFilter($value, ?string $standard = NULL, array $options = []): string {
    if ($standard === NULL || $standard === '') {
      $config = \Drupal::config('alt_time.settings');
      $standard = $config->get('default_standard') ?? 'tc';
    }

    $standard = strtolower($standard);
    $as_html = $options['as_html'] ?? TRUE;
    $show_label = $options['show_label'] ?? TRUE;

    $dt = $this->normalizeToDateTime($value);
    if (!$dt) {
      return '';
    }

    $dt = applyYearOffset($dt);

    if ($standard === 'stardate') {
      $stardate = calculateStardate($dt);
      $label = $show_label ? 'Stardate ' : '';
      return $label . $stardate;
    }
    elseif ($standard === 'imperial') {
      $imperial = calculateImperialDate($dt);
      $label = $show_label ? 'Imperial date ' : '';
      return $label . $imperial;
    }
    elseif ($standard === 'ordinal') {
      $ordinal = calculateOrdinalDate($dt);
      $label = $show_label ? 'Ordinal date ' : '';
      return $label . $ordinal;
    }
    elseif ($standard === 'dale') {
      $dr = calculateDaleReckoning($dt);
      $label = $show_label ? 'Dale Reckoning ' : '';
      return $label . $dr;
    }

    // Default: TC.
    $tc_array = tcdate($dt->format(DateTime::ATOM));
    $label_prefix = $show_label ? 'TC: ' : '';

    if ($as_html) {
      $tc_html = tcdateToHTML($tc_array);
      return $label_prefix . $tc_html;
    }
    else {
      return $label_prefix . ($tc_array['padded_date'] ?? $tc_array['date']);
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
