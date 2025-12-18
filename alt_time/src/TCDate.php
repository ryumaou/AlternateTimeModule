<?php

namespace Drupal\alt_time;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Terran Computational Date implementation plus other time systems.
 */
class TCDate {
  public $utc_leap_seconds = [
    "1972-06-30T23:59:60+00:00", "1972-12-31T23:59:60+00:00",
    "1973-12-31T23:59:60+00:00", "1974-12-31T23:59:60+00:00",
    "1975-12-31T23:59:60+00:00", "1976-12-31T23:59:60+00:00",
    "1977-12-31T23:59:60+00:00", "1978-12-31T23:59:60+00:00",
    "1979-12-31T23:59:60+00:00", "1981-06-30T23:59:60+00:00",
    "1982-06-30T23:59:60+00:00", "1983-06-30T23:59:60+00:00",
    "1985-06-30T23:59:60+00:00", "1987-12-31T23:59:60+00:00",
    "1989-12-31T23:59:60+00:00", "1990-12-31T23:59:60+00:00",
    "1992-06-30T23:59:60+00:00", "1993-06-30T23:59:60+00:00",
    "1994-06-30T23:59:60+00:00", "1995-12-31T23:59:60+00:00",
    "1997-06-30T23:59:60+00:00", "1998-12-31T23:59:60+00:00",
    "2005-12-31T23:59:60+00:00", "2008-12-31T23:59:60+00:00",
    "2012-06-30T23:59:60+00:00",
  ];

  public $tc_leap_second_years = [
    2, 3, 4, 5, 6, 7, 8, 9, 10,
    11, 12, 13, 15, 18, 20, 21, 22, 23, 24, 26, 27, 29, 36, 39, 42,
  ];

  public $tc_epoch = -864000;
  public $date = ['date' => 0];

  private $unix_leap_seconds = [];
  private $min_tc_leap_second_year;
  private $max_tc_leap_second_year;

  public function __construct($date = 'now', $is_leap_second = FALSE) {
    $this->min_tc_leap_second_year = min($this->tc_leap_second_years);
    $this->max_tc_leap_second_year = max($this->tc_leap_second_years);

    foreach ($this->utc_leap_seconds as $utc_leap_second) {
      $datetime = new DateTime($utc_leap_second);
      $this->unix_leap_seconds[] = intval($datetime->format('U'));
    }

    $tc_timestamp = $this->toTCTimestamp($date, $is_leap_second);
    $this->date = $this->tcTimestampToDate($tc_timestamp);
  }

  /**
   * Takes a UTC date string or UNIX timestamp and converts it into a TC timestamp.
   */
  public function toTCTimestamp($date = 'now', $is_leap_second = FALSE) {
    if (preg_match('/^[\-]?[\d]*$/', $date)) {
      // Is timestamp.
      if ($is_leap_second) {
        $date--;
      }
      $datetime = new DateTime();
      $datetime->setTimestamp((int) $date);
    }
    else {
      // Is UTC date.
      if (strstr($date, ':59:60')) {
        $is_leap_second = TRUE;
        $date = str_replace(':59:60', ':59:59', $date);
      }
      $sign = "";
      $test_date = $date;
      $test_date_timezone = substr($date, -5);
      if (substr($date, 0, 1) == '-') {
        $sign = '-';
        $test_date = substr($date, 1);
      }
      $test_date_timezone = str_replace(['+', ':'], '', substr($date, -5));
      $test_date = substr($test_date, 0, -5);
      $date_arr = preg_split("/(\-|T|:)/", $test_date);
      if ($date_arr[0] == intval($date_arr[0]) && $date_arr[0] > 9999) {
        $datetime = new DateTime();
        $datetime->setDate($date_arr[0], $date_arr[1], $date_arr[2]);
        $datetime->setTime($date_arr[3], $date_arr[4], $date_arr[5]);
        $test_date_timezone = intval(substr($test_date_timezone, 0, -2)) * 3600 + intval(substr($test_date_timezone, -2)) * 60;
        $datetime->setTimezone($this->getDateTimeZone($test_date_timezone));
      }
      else {
        try {
          $datetime = new DateTime($date);
        }
        catch (Exception $e) {
          $datetime = new DateTime('now');
        }
      }
    }

    $unix_timestamp = intval($datetime->format('U'));

    $leap_seconds = 0;
    foreach ($this->unix_leap_seconds as $unix_leap_second) {
      if ($unix_timestamp + 1 == $unix_leap_second && $is_leap_second) {
        $unix_timestamp++;
        break;
      }
      if ($unix_timestamp < $unix_leap_second) {
        break;
      }
      else {
        $leap_seconds++;
      }
    }

    return $unix_timestamp - $this->tc_epoch + $leap_seconds;
  }

  public function tcTimestampToUTC($tc_timestamp) {
    [$unix_timestamp, $is_leap_second] = $this->tcTimestampToUNIX($tc_timestamp);
    $unix_timestamp -= $is_leap_second;
    $datetime = new DateTime();
    $datetime->setTimestamp($unix_timestamp);
    $datetime->setTimezone($this->getDateTimeZone());
    $date = $datetime->format(DateTime::ATOM);
    if ($is_leap_second) {
      $date = str_replace('59:59', '59:60', $date);
    }
    return $date;
  }

  public function isLeapSecond($unix_timestamp = '', $date = '', $is_leap_second = 0) {
    if (in_array($date, $this->utc_leap_seconds) || $is_leap_second) {
      return 1;
    }
    if (in_array($unix_timestamp, $this->unix_leap_seconds) && $date != '' && strstr($date, ':59:60')) {
      return 1;
    }
    return 0;
  }

  /**
   * Returns [unix_timestamp, is_leap_second_flag].
   */
  public function tcTimestampToUNIX($tc_timestamp) {
    $unix_timestamp = $tc_timestamp + $this->tc_epoch;

    foreach ($this->unix_leap_seconds as $unix_leap_second) {
      if ($unix_timestamp == $unix_leap_second) {
        return [$unix_timestamp, 1];
      }
      if ($unix_timestamp > $unix_leap_second) {
        $unix_timestamp--;
      }
      if ($unix_timestamp < $unix_leap_second) {
        break;
      }
    }
    return [$unix_timestamp, 0];
  }

  public function tcTimestampToDate($tc_timestamp = 0, $year_base = '', $offset = 0) {
    $designator = 'TC' . ($year_base == '' ? '' : intval($year_base));
    [$unix_timestamp, $is_leap_second] = $this->tcTimestampToUNIX($tc_timestamp);
    $date = [
      'year' => 0,
      'month' => 0,
      'day' => 0,
      'hour' => 0,
      'minute' => 0,
      'second' => 0,
      'fraction' => 0,
      'designator' => $designator,
      'year_base' => ($year_base == '' ? '' : intval($year_base)),
      'offset' => $offset,
      'tc_timestamp' => $tc_timestamp,
      'unix_timestamp' => $unix_timestamp,
      'is_leap_second' => $is_leap_second,
    ];
    [$date['year'], $seconds_left] = $this->getYear(intval($tc_timestamp) + intval($offset), $year_base);

    $date['month'] = floor($seconds_left / (28 * $this->s('day')));
    $seconds_left -= $date['month'] * (28 * $this->s('day'));

    $date['day'] = floor($seconds_left / $this->s('day'));
    $seconds_left -= $date['day'] * $this->s('day');

    $date['hour'] = floor($seconds_left / $this->s('hour'));
    $seconds_left -= $date['hour'] * $this->s('hour');

    $date['minute'] = floor($seconds_left / $this->s('minute'));
    $seconds_left -= $date['minute'] * $this->s('minute');

    $date['second'] = floor($seconds_left);
    $seconds_left -= $date['second'];

    $seconds_left = explode('.', strval($seconds_left));
    if (isset($seconds_left[1])) {
      $date['fraction'] = intval($seconds_left[1]);
    }

    $fraction = ($date['fraction'] != 0 ? '.' . $date['fraction'] : '');
    $date['datemod'] = 0;
    $date['date'] = "{$date['year']}.{$date['month']}.{$date['day']},{$date['hour']}.{$date['minute']}.{$date['second']}$fraction $designator";

    $date['padded_date'] = $this->pad($date['year']) . "-" . $this->pad($date['month']) . "-" . $this->pad($date['day']) . " " . $this->pad($date['hour']) . ":" . $this->pad($date['minute']) . ":" . $this->pad($date['second']) . "$fraction $designator";

    return $date;
  }

  public function pad($unit, $num = 2) {
    return str_pad($unit, $num, '0', STR_PAD_LEFT);
  }

  public function getYear($tc_timestamp = 0, $year_base = '') {
    if ($tc_timestamp < 0) {
      $offsets = ceil(abs($tc_timestamp) / $this->s('128years'));
      [$year, $seconds_left] = $this->getYear(($offsets * $this->s('128years')) + $tc_timestamp, 0);
      return [$year - ($offsets * 128), $seconds_left];
    }

    $seconds_left = $tc_timestamp;
    $upper_limit = ceil($this->max_tc_leap_second_year + 1 / 128) * 128;
    for ($year = 0; $seconds_left >= 0 && $year < $upper_limit; $year++) {
      $seconds_left -= $this->sInYear($year, $year_base);
    }

    if ($seconds_left < 0) {
      $year--;
      $seconds_left += $this->sInYear($year, $year_base);
      return [$year, $seconds_left];
    }

    $_128_cycles = floor($seconds_left / $this->s('128years'));
    $year += 128 * $_128_cycles;
    $seconds_left -= $_128_cycles * $this->s('128years');

    $first_4_cycles = floor($seconds_left / (4 * $this->s('year')));

    if ($first_4_cycles > 0) {
      $year += 4;
      $seconds_left -= 4 * $this->s('year');

      $_4_cycles = floor($seconds_left / $this->s('4years'));
      $year += 4 * $_4_cycles;
      $seconds_left -= $_4_cycles * $this->s('128years');

      if (floor($seconds_left / ($this->s('year') + $this->s('day')))) {
        $year += 1;
        $seconds_left -= $_4_cycles * $this->s('128years');
      }
    }

    $_1_cycles = floor($seconds_left / $this->s('year'));
    $year += $_1_cycles;
    $seconds_left -= $_1_cycles * $this->s('year');

    return [$year, $seconds_left];
  }

  public function sInYear($year = 0, $year_base = '') {
    $seconds = $this->s('year') + ($year % 4 == 0 && $year % 128 != 0 ? $this->s('day') : 0);
    if (!($year < $this->min_tc_leap_second_year || ($year_base != '' && intval($year_base) < $year) || $year > $this->max_tc_leap_second_year)) {
      $leap_second_count_by_year = array_count_values($this->tc_leap_second_years);
      if (isset($leap_second_count_by_year[$year])) {
        $seconds += $leap_second_count_by_year[$year];
      }
    }
    return $seconds;
  }

  public function s($str = 'now') {
    switch ($str) {
      case 'default': return $this->toTCTimestamp($str);
      case 'second': return 1;
      case 'minute': return 60;
      case 'hour': return 3600;
      case 'day': return 86400;
      case 'year': return 31536000;
      case '4years': return 126230400;
      case '128years': return 4039286400;
    }
    return 0;
  }

  private function getDateTimeZone($timezone = "UTC") {
    if (is_numeric($timezone)) {
      $timezone  = timezone_name_from_abbr("", intval($timezone), 0);
    }
    $date_timezone = new DateTimeZone("UTC");
    try {
      $date_timezone = new DateTimeZone($timezone);
    }
    catch (Exception $e) {}
    return $date_timezone;
  }

  public function tcYearToTCTimestamp($year) {
    $timestamp = 0;
    if ($year < 0) {
      for ($i = $year; $i < 0; $i++) {
        $timestamp -= $this->sInYear($i);
      }
    }
    else {
      for ($i = 0; $i < $year; $i++) {
        $timestamp += $this->sInYear($i);
      }
    }
    return $timestamp;
  }
}

/**
 * Wrapper: TC date array from a date string or timestamp.
 */
function tcdate($date = 'now', $is_leap_second = 0) {
  $date = new TCDate($date, $is_leap_second);
  return $date->date;
}

/**
 * Render TC date array to HTML spans.
 */
function tcdateToHTML($date = [], $delimiters = ['.', '.', ',', '.', '.', ' '], $units = '', $pad = 0, $class = "now") {
  if (empty($units)) {
    $units = ['year', 'month', 'day', 'hour', 'minute', 'second', 'designator'];
  }
  $i = 0;
  $html = '';
  foreach ($units as $unit) {
    $delimiter = (isset($delimiters[$i]) ? $delimiters[$i] : '');
    if (isset($date[$unit])) {
      $html .= "<span"
        . ($unit == 'designator' ? " data-tc_timestamp=\"{$date['tc_timestamp']}\"" : "")
        . " data-designator=\"{$date['designator']}\" data-class=\"$class\" data-unit=\"$unit\""
        . ($pad ? " data-pad=\"$pad\"" : '')
        . ">{$date[$unit]}</span>$delimiter";
    }
    $i++;
  }
  return $html;
}

/**
 * Star Trek Stardate calculator.
 */
function calculateStardate(DateTime $date): float {
  $baseYear = 2323;

  $year = (int) $date->format('Y');
  $dayOfYear = (int) $date->format('z') + 1;
  $daysInYear = (int) $date->format('L') ? 366 : 365;

  $fractionalYear = $dayOfYear / $daysInYear;

  $stardate = (($year - $baseYear) * 1000) + ($fractionalYear * 1000);

  return round($stardate, 2);
}

/**
 * Warhammer 40K Imperial Date calculator.
 */
function calculateImperialDate(DateTime $date): string {
  // Use UTC for consistency with Terran standard.
  $utc = clone $date;
  $utc->setTimezone(new DateTimeZone('UTC'));

  $year = (int) $utc->format('Y');
  $day_of_year = (int) $utc->format('z') + 1;
  $hour = (int) $utc->format('G');

  $determined_hour = (($day_of_year - 1) * 24) + $hour;
  $makr_constant = 0.11407955;

  $year_fraction = (int) floor($determined_hour * $makr_constant);

  $check_number = '0';
  $formatted_fraction = str_pad((string) $year_fraction, 3, '0', STR_PAD_LEFT);
  $year_component = str_pad((string) ($year % 1000), 3, '0', STR_PAD_LEFT);
  $millennium = 'M' . ceil($year / 1000);

  return sprintf('%s %s.%s.%s.%s', $check_number, $formatted_fraction, $year_component, '000', $millennium);
}

/**
 * Ordinal date: YYYY.DOY.HHMMSS.
 */
function calculateOrdinalDate(DateTime $date): string {
  $year = (int) $date->format('Y');
  $day_of_year = (int) $date->format('z') + 1;
  $hour = (int) $date->format('H');
  $minute = (int) $date->format('i');
  $second = (int) $date->format('s');

  // Format as YYYY.DOY.HHMMSS (period after year and after day-of-year).
  return sprintf(
    '%04d.%03d.%02d%02d%02d',
    $year,
    $day_of_year,
    $hour,
    $minute,
    $second
  );
}


/**
 * Ordinal helper: 1 -> 1st, 2 -> 2nd, 3 -> 3rd, etc.
 */
function fr_nthday(int $day, int $small = 1): string {
  $suffix = 'th';

  if ($day % 100 < 11 || $day % 100 > 13) {
    switch ($day % 10) {
      case 1:
        $suffix = 'st';
        break;
      case 2:
        $suffix = 'nd';
        break;
      case 3:
        $suffix = 'rd';
        break;
    }
  }

  return $day . $suffix;
}

/**
 * Forgotten Realms Dale Reckoning conversion (close to original frdates()).
 *
 * @param int $day
 * @param int $month
 * @param int $year
 *
 * @return string
 *   Example: "Hammer 1st, 1502 DR" or "Midwinter 1502 DR — Happy holiday feast!"
 */
function frdates(int $day, int $month, int $year): string {
  $ts       = mktime(0, 0, 0, $month, $day, $year);
  $is_leap  = (int) date('L', $ts);
  $FRYear   = (int) date('Y', $ts) - 524;
  $doy      = (int) date('z', $ts) + 1; // 1–365 / 366

  $Fmonth   = 'Error';
  $Fday     = 0;
  $isHoliday = false;

  if ($is_leap === 1) {
    // ---------- LEAP YEAR ----------
    if ($doy >= 1 && $doy <= 30) {
      $Fmonth = 'Hammer';
      $Fday   = $doy;
    }
    elseif ($doy === 31) {
      $Fmonth   = 'Midwinter';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 32 && $doy <= 61) {
      $Fmonth = 'Alturiak';
      $Fday   = $doy - 31;
    }
    elseif ($doy >= 62 && $doy <= 91) {
      $Fmonth = 'Ches';
      $Fday   = $doy - 61;
    }
    elseif ($doy >= 92 && $doy <= 121) {
      $Fmonth = 'Tarkash';
      $Fday   = $doy - 91;
    }
    elseif ($doy === 122) {
      $Fmonth   = 'Greengrass';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 123 && $doy <= 152) {
      $Fmonth = 'Mirtul';
      $Fday   = $doy - 122;
    }
    elseif ($doy >= 153 && $doy <= 182) {
      $Fmonth = 'Kythorn';
      $Fday   = $doy - 152;
    }
    elseif ($doy >= 183 && $doy <= 212) {
      $Fmonth = 'Flamerule';
      $Fday   = $doy - 182;
    }
    elseif ($doy === 213) {
      $Fmonth   = 'Midsummer';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy === 214) {
      $Fmonth   = 'Shieldmeet';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 215 && $doy <= 244) {
      $Fmonth = 'Elesias';
      $Fday   = $doy - 214;
    }
    elseif ($doy >= 245 && $doy <= 274) {
      $Fmonth = 'Eleint';
      $Fday   = $doy - 244;
    }
    elseif ($doy === 275) {
      $Fmonth   = 'Harvesttide';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 276 && $doy <= 305) {
      $Fmonth = 'Marpenoth';
      $Fday   = $doy - 275;
    }
    elseif ($doy >= 306 && $doy <= 334) {
      $Fmonth = 'Uktar';
      $Fday   = $doy - 305;
    }
    elseif ($doy === 335) {
      $Fmonth   = 'The Feast of the Moon';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 336 && $doy <= 366) {
      $Fmonth = 'Nightal';
      $Fday   = $doy - 336;
    }
    else {
      $Fmonth = 'Error';
      $Fday   = 0;
    }
  }
  else {
    // ---------- NON-LEAP YEAR ----------
    if ($doy >= 1 && $doy <= 30) {
      $Fmonth = 'Hammer';
      $Fday   = $doy;
    }
    elseif ($doy === 31) {
      $Fmonth   = 'Midwinter';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 32 && $doy <= 61) {
      $Fmonth = 'Alturiak';
      $Fday   = $doy - 31;
    }
    elseif ($doy >= 62 && $doy <= 91) {
      $Fmonth = 'Ches';
      $Fday   = $doy - 61;
    }
    elseif ($doy >= 92 && $doy <= 121) {
      $Fmonth = 'Tarkash';
      $Fday   = $doy - 91;
    }
    elseif ($doy === 122) {
      $Fmonth   = 'Greengrass';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 123 && $doy <= 152) {
      $Fmonth = 'Mirtul';
      $Fday   = $doy - 122;
    }
    elseif ($doy >= 153 && $doy <= 182) {
      $Fmonth = 'Kythorn';
      $Fday   = $doy - 152;
    }
    elseif ($doy >= 183 && $doy <= 212) {
      $Fmonth = 'Flamerule';
      $Fday   = $doy - 182;
    }
    elseif ($doy === 213) {
      $Fmonth   = 'Midsummer';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 214 && $doy <= 243) {
      $Fmonth = 'Elesias';
      $Fday   = $doy - 213;
    }
    elseif ($doy >= 244 && $doy <= 273) {
      $Fmonth = 'Eleint';
      $Fday   = $doy - 243;
    }
    elseif ($doy === 274) {
      $Fmonth   = 'Harvesttide';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 275 && $doy <= 304) {
      $Fmonth = 'Marpenoth';
      $Fday   = $doy - 274;
    }
    elseif ($doy >= 305 && $doy <= 333) {
      $Fmonth = 'Uktar';
      $Fday   = $doy - 304;
    }
    elseif ($doy === 334) {
      $Fmonth   = 'The Feast of the Moon';
      $Fday     = 0;
      $isHoliday = true;
    }
    elseif ($doy >= 335 && $doy <= 365) {
      $Fmonth = 'Nightal';
      $Fday   = $doy - 335;
    }
    else {
      $Fmonth = 'Error';
      $Fday   = 0;
    }
  }

  // --- Final formatting ---
  if ($isHoliday) {
    return $Fmonth . ' ' . $FRYear . ' DR — Happy holiday feast!';
  }

  if ($Fday > 0) {
    return $Fmonth . ' ' . fr_nthday($Fday, 1) . ', ' . $FRYear . ' DR';
  }

  return 'Error: could not map date to Dale Reckoning.';
}


/**
 * Wrapper used by the module: DateTime → Dale Reckoning string.
 */
function calculateDaleReckoning(DateTime $date): string {
  $day   = (int) $date->format('d');
  $month = (int) $date->format('m');
  $year  = (int) $date->format('Y');

  return frdates($day, $month, $year);
}



/**
 * Apply site-wide year offset (alt_time.settings.year_offset) to a DateTime.
 */
function applyYearOffset(DateTime $date): DateTime {
  $config = \Drupal::config('alt_time.settings');
  $offset = (int) ($config->get('year_offset') ?? 0);

  if ($offset === 0) {
    return $date;
  }

  $date = clone $date;
  $modifier = ($offset > 0 ? '+' : '') . $offset . ' years';
  $date->modify($modifier);

  return $date;
}
