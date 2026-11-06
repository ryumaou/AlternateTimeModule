# AlternateTimeModule
A Drupal module to allow you to alter time on your Drupal site.

This is offered for free and mostly as a sample of what you can do.

This module adds several different alternate time formats and allows for a year offset to throw things into the past or future.
The additional formats are Terran Computational Time (TC), Stardate, Warhammer 40K Imperial, Ordinal time, and Dale Reckoning.  Note that this is more about time as a timestamp or on a page or article as opposed to working on a calendar which is way, way, way harder to do.

The **Alternate Time Module** module provides multiple “alternate” date/time standards for Drupal, including:

- **Terran Computational Time (TC)**
- **Star Trek Stardate**
- **Warhammer 40K Imperial Date**
- **Ordinal Date** (YYYY.DOY.HHMMSS-style)
- **Dale Reckoning** (Forgotten Realms / D&D)

It includes:

- A **configuration page** for global settings  
- A **block** that shows the current time in your chosen standard  
- A **converter page** where users can enter any date and see alternate forms  
- **PHP helper functions** and a **Twig filter** for templates and custom code  

---

## Installation

1. Place the module in your custom modules directory, for example:

   ```text
   web/modules/custom/alt_time
   ```

2. Enable the module via:

   **UI:**

   - `Extend → Alternate Time`

   **Drush:**

   ```bash
   drush en alt_time -y
   ```

3. Clear caches:

   ```bash
   drush cr
   ```

---

## Configuration

### Automatic Article dates

While the module is enabled, Article nodes (content type machine name `article`)
automatically apply the `alt_time` Twig filter to their created timestamp. This
updates the date in the theme's submitted information and the created field when
it is displayed as node content. It uses the default time standard, year offset,
prefix, and suffix from the module settings. Stored timestamps are unchanged.

No theme edit or display configuration change is required. The theme must display
the standard node `date` variable or created field; custom templates that format
`node.created.value` directly should use `{{ node.created.value|alt_time }}`.
The content type's setting for showing author and date information still applies.
After updating an already enabled installation, run `drush cr`.

Configuration path:

> **Configuration → System → Alternate time**  
> `/admin/config/system/alt-time`

### 1. Default time standard

**Setting:** `Default alternate time standard`

Options:

- `tc` – Terran Computational Time (TC)  
- `stardate` – Star Trek Stardate  
- `imperial` – Warhammer 40K Imperial Date  
- `ordinal` – Ordinal date (YYYY.DOY.HHMMSS)  
- `dale` – Dale Reckoning (Forgotten Realms)  

This default is used by:

- The **current-time block** (unless overridden in code)
- The **converter form** (as its default selected standard)

### 2. Year offset

**Setting:** `Year offset` (integer, default `0`)

- Positive values shift the effective date **forward** by N years.
- Negative values shift the effective date **back** by N years.

The offset is applied to the `DateTime` object **before** conversion for all standards.

### 3. Prefix / suffix for display

The module allows configurable **prefix** and **suffix** strings for the rendered alternate time, especially in the current-time block. For example:

- Prefix: `Stardate now: `
- Suffix: ` (ship’s time)`

You can leave prefix and suffix **blank** if you want only the raw time string (e.g., no “Ordinal time:” label).

---

## Features

### 1. Current-time block

The module provides a block plugin, typically named something like:

> `Alternate time current clock`

Place it via:

> **Structure → Block layout**

Steps:

1. Click **“Place block”** in your desired region.
2. Locate **“Alternate time current clock”**.
3. Configure:
   - Optionally clear the **block title** to avoid showing a heading.
   - Use prefix/suffix settings from the module config as desired.

The block:

- Uses the **current date/time** as input.
- Applies the **global year offset**.
- Formats it in the **default time standard**.

Sample outputs (depending on chosen standard and config):

- Terran Computational Time:  
  `0002.03.15,01.20.45 TC`
- Star Trek Stardate:  
  `54321.75`
- Warhammer 40K Imperial Date:  
  `0 123.402.000.M3`
- Ordinal date:  
  `2026.031.145212`
- Dale Reckoning:  
  `Hammer 30th, 1502 DR`  
  or  
  `Midwinter 1502 DR — Happy holiday feast!`

---

### 2. Converter / tools page

The module exposes a conversion form at a route like:

> `/alt-time`

(You can optionally link this as a menu item under a “Tools” menu.)

The converter form provides:

- **Input date/time** field:
  - Accepts anything `DateTime` accepts, e.g.:
    - `now`
    - `2026-01-31T00:00:00`
    - `1672531200` (UNIX timestamp)
- **Leap second checkbox** for TC:
  - `Treat as leap second (for TC)`
- **Standard selector (radios)**:
  - Terran Computational Time (TC)
  - Star Trek Stardate
  - Warhammer 40K Imperial Date
  - Ordinal date
  - Dale Reckoning

On submission:

1. The input is normalized to a `DateTime` object.
2. The **year offset** is applied.
3. The selected standard is used to convert and display the result.

Examples of what you see:

- **TC**
  - Source date (after offset)
  - HTML-formatted TC date
  - Raw TC date array
- **Stardate**
  - Source date (after offset)
  - Numeric stardate
- **Imperial**
  - Source date (after offset)
  - Imperial date text
- **Ordinal**
  - Source date (after offset)
  - Ordinal formatted date string
- **Dale Reckoning**
  - Source date (after offset)
  - FR date string (including holidays such as “Midwinter”)

---

## Supported standards (logic overview)

### Terran Computational Time (TC)

Core class:

```php
\Drupallt_time\TCDate
```

Key functions (in the module namespace):

```php
tcdate($date = 'now', $is_leap_second = 0);
tcdateToHTML(array $date, array $delimiters = [], array $units = [], $pad = 0, $class = 'now');
```

Features:

- Handles **leap seconds** using a built-in list.
- Defines its own epoch (`tc_epoch`) and leap-year rules.
- `tcdate()` returns an associative array with fields like:
  - `year`, `month`, `day`, `hour`, `minute`, `second`, `fraction`
  - `tc_timestamp`, `unix_timestamp`, `designator` (e.g. `TC`)
- `tcdateToHTML()` returns HTML `<span>` elements with `data-` attributes for styling and scripting.

---

### Star Trek Stardate

Function:

```php
calculateStardate(\DateTime $date): float;
```

- Uses a **base year** (e.g. 2323) and:
  - Computes `year - baseYear`.
  - Adds the fractional part of the year (day-of-year / days-in-year).
- Returns a numeric Stardate (e.g. `54321.75`), rounded to two decimal places.

---

### Warhammer 40K Imperial Date

Function:

```php
calculateImperialDate(\DateTime $date): string;
```

- Uses UTC as a Terran reference.
- Computes the “determined hour” from day-of-year and hour.
- Applies a Makr Constant (e.g., `0.11407955`) to derive the fraction.
- Formats an Imperial date string similar to:

  ```text
  0 123.402.000.M3
  ```

  where:

  - `0` is the check number (Terran standard),
  - `123` is the year fraction,
  - `402` is the year component,
  - `000` is a simplified constant in this implementation,
  - `M3` is the millennium.

---

### Ordinal date

Function:

```php
calculateOrdinalDate(\DateTime $date): string;
```

- Computes:
  - **Year** (YYYY)
  - **Day of year** (DOY, 001–365/366)
  - **Hour, minute, second**

- Returns a string in this format:

  ```text
  YYYY.DOY.HHMMSS
  ```

  For example:

  ```text
  2026.031.145212
  ```

  meaning: 2026, 31st day of the year, 14:52:12.

The code ensures a period after the **year** and **day-of-year**.

---

### Dale Reckoning (Forgotten Realms)

Key functions:

```php
calculateDaleReckoning(\DateTime $date): string;
frdates(int $day, int $month, int $year): string;
fr_nthday(int $day, int $small = 1): string;
```

- Maps Gregorian date to Dale Reckoning (DR).
- Handles months:

  - Hammer  
  - Alturiak  
  - Ches  
  - Tarkash  
  - Mirtul  
  - Kythorn  
  - Flamerule  
  - Elesias  
  - Eleint  
  - Marpenoth  
  - Uktar  
  - Nightal  

- Handles intercalary “holiday” days:

  - Midwinter  
  - Greengrass  
  - Midsummer  
  - Shieldmeet (leap years only)  
  - Harvesttide  
  - The Feast of the Moon  

- Returns either:

  - A normal day, e.g.:

    ```text
    Hammer 30th, 1502 DR
    ```

  - Or a holiday, e.g.:

    ```text
    Midwinter 1502 DR — Happy holiday feast!
    ```

---

## Programmatic usage (PHP)

In custom modules or theme code, after enabling `alt_time`, you can import and call the functions directly:

```php
use Drupallt_time\TCDate;
use function Drupallt_time	cdate;
use function Drupallt_time	cdateToHTML;
use function Drupallt_time\calculateStardate;
use function Drupallt_time\calculateImperialDate;
use function Drupallt_time\calculateOrdinalDate;
use function Drupallt_time\calculateDaleReckoning;
use function Drupallt_timepplyYearOffset;
```

### Example: Dale Reckoning from a node’s created time

```php
$created = new \DateTime();
$created->setTimestamp($node->getCreatedTime());

// Apply site-wide year offset.
$created = applyYearOffset($created);

$dr_string = calculateDaleReckoning($created);
// e.g. "Hammer 30th, 1502 DR"
```

### Example: TC date as HTML

```php
$dt = new \DateTime('now');
$dt = applyYearOffset($dt);

$tc_array = tcdate($dt->format(\DateTime::ATOM), FALSE);
$tc_html  = tcdateToHTML($tc_array);

// $tc_html now contains spans you can embed in markup.
```

---

## Twig usage

The module includes a Twig extension that adds a filter, typically named:

```twig
|alt_time
```

The filter:

- Accepts a date string or timestamp (or even the value from an entity field).
- Applies the global **year offset**.
- Converts to the chosen standard.

### Basic usage

In a node template (e.g. `node--article.html.twig`):

```twig
{# Uses default standard from config #}
{{ node.created.value|alt_time }}

{# Force Stardate #}
{{ node.created.value|alt_time('stardate') }}

{# Dale Reckoning #}
{{ node.created.value|alt_time('dale') }}
```

You can pass one of:

- `tc`
- `stardate`
- `imperial`
- `ordinal`
- `dale`

If no argument is passed, the filter uses the **default standard** set in the module’s config.

---

## Using Alternate Time in Articles

### Template-level usage (recommended)

To show the Article’s published date in an alternate standard, use the Twig filter with the node’s `created` value:

```twig
<div class="article-meta">
  <span class="label">Published:</span>
  <span class="alt-time">
    {{ node.created.value|alt_time('dale') }}
  </span>
</div>
```

This:

- Uses the Article’s created time (effectively the published date).
- Applies the year offset.
- Renders it as a Dale Reckoning date.

### Preprocess approach

If you prefer to compute it in PHP:

```php
function YOURTHEME_preprocess_node(array &$variables) {
  if ($variables['node']->getType() === 'article') {
    $created = new \DateTime();
    $created->setTimestamp($variables['node']->getCreatedTime());

    $created = \Drupallt_timepplyYearOffset($created);
    $dr = \Drupallt_time\calculateDaleReckoning($created);

    $variables['alt_time_published'] = $dr;
  }
}
```

Then in Twig:

```twig
{{ alt_time_published }}
```

---

## Troubleshooting

- **Dale Reckoning holiday not appearing**  
  Ensure you are using the latest logic for `frdates()` and `calculateDaleReckoning()`, and that the function is not being overridden elsewhere.

- **Blank output for some dates**  
  Check logs for PHP exceptions. A typo in a helper function call (e.g. `fr_nthday`) will cause failures in the UI.

- **Block title appears when you don’t want it**  
  Edit the block in **Block layout** and clear or hide the title. Use the module’s prefix/suffix settings instead.

- **Labels like “Ordinal time:” are unwanted**  
  Blank out the prefix/suffix fields in the module config so only the computed time string is rendered.

---

## Examples

### Example 1: Show Dale Reckoning published date in Articles

**Goal:** Display the Article’s published date in Dale Reckoning under the title.

`node--article.html.twig`:

```twig
<h1>{{ label }}</h1>

<div class="article-meta">
  <span class="label">Published (Dale Reckoning):</span>
  <span class="alt-time">
    {{ node.created.value|alt_time('dale') }}
  </span>
</div>

{{ content }}
```

---

### Example 2: Display both TC and Stardate for the same Article

`node--article.html.twig`:

```twig
<div class="article-meta">
  <div>
    <strong>Published (TC):</strong>
    {{ node.created.value|alt_time('tc') }}
  </div>
  <div>
    <strong>Published (Stardate):</strong>
    {{ node.created.value|alt_time('stardate') }}
  </div>
</div>
```

This shows the same underlying publication time using two different standards.

---

### Example 3: Custom block showing Warhammer 40K Imperial date

In a custom module:

```php
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use function Drupallt_timepplyYearOffset;
use function Drupallt_time\calculateImperialDate;

/**
 * Provides an 'Imperial Date' custom block.
 *
 * @Block(
 *   id = "custom_imperial_date_block",
 *   admin_label = @Translation("Imperial Date (40K)"),
 * )
 */
class CustomImperialDateBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $now = new \DateTime('now');
    $now = applyYearOffset($now);

    $imperial = calculateImperialDate($now);

    return [
      '#markup' => $this->t('Imperial date: @date', ['@date' => $imperial]),
    ];
  }

}
```

---

### Example 4: Using the converter page to debug a holiday

To verify that Dale Reckoning holidays are mapped correctly:

1. Visit `/alt-time`.
2. Enter a test date (e.g., `2026-01-31`) in the input field.
3. Select **“Dale Reckoning (Forgotten Realms)”**.
4. Submit.

You should see something like:

```text
Dale Reckoning (Forgotten Realms)
Gregorian date (after offset): 2026-01-31T00:00:00+00:00
Dale Reckoning: Midwinter 1502 DR — Happy holiday feast!
```

If you don’t, verify:

- The `calculateDaleReckoning()` function is the latest version.
- Any year offset you’ve configured isn’t moving the date out of the expected holiday range.

---

### Example 5: Ordinal date label-free display in a sidebar

Place the built-in **Alternate time current clock** block in a sidebar region, then in the module config:

- Set **Default standard** to `ordinal`.
- Clear the prefix and suffix fields (leave them empty).

Result: the block will display something like:

```text
2026.031.153045
```

with no extra labels, suitable for a minimalist “system timestamp” display.

---

That’s it. The Alternate Time module provides a flexible set of date conversions for both front-end display (via Twig and blocks) and back-end logic (via PHP helpers), with a central configuration panel for defaults, offsets, and labels.
