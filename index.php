<?php
declare(strict_types=1);

/*
 * How much do you need to drink? (a cup and a revi'is)
 * Single file: PHP does all the maths, drawing and translation. With JavaScript the page
 * updates live by asking this same file for just the result (?fragment=1);
 * without JavaScript the "Show result" button submits the form normally.
 * Language: ?lang=en (default) or ?lang=he.
 */

const DEFAULTS = ['top' => 6.4, 'bottom' => 4.5, 'height' => 7.6];
const OPINIONS = ['rcn' => 86.0, 'ci' => 150.0]; // volume of one revi'is in cc

define('LANG', ($_GET['lang'] ?? 'en') === 'he' ? 'he' : 'en');

const STR = [
    'en' => [
        'title' => 'How much do you need to drink?',
        'page_title' => "How much do you need to drink? | Revi'is cup calculator",
        'intro' => "Enter the inside measurements of your cup to see how high it must be filled to hold one revi'is (רביעית).",
        'other_lang' => 'עברית',
        'your_cup' => 'Your cup',
        'lbl_top' => 'Diameter at the top',
        'lbl_bottom' => 'Diameter at the bottom',
        'lbl_height' => 'Height',
        'hint_bottom' => 'Must be smaller than the top diameter.',
        'hint_height' => 'Measure inside the cup, from the floor of the cup to the rim.',
        'show' => 'Show result',
        'cm' => 'cm', 'cc' => 'cc', 'floz' => 'US fl oz',
        'rcn' => 'Rav Chaim Naeh', 'ci' => 'Chazon Ish',
        'aria' => 'Cup drawn to scale with the fill lines marked',
        'total' => 'This cup holds %s when full, %s tall.',
        'required' => "Required volume: %s (about %s) for one revi'is",
        'fill' => 'Fill this cup to:',
        'above' => 'above the inside bottom',
        'small' => "This cup holds only %s, which is less than one revi'is by this opinion.",
        'err_nan' => '%s: not a number. Using %s cm.',
        'err_range' => '%s: must be between %s and %s cm. Using %s cm.',
        'err_bottom' => 'The bottom diameter must be smaller than the top diameter. Using %s cm for the bottom.',
        'how' => 'How is this calculated?',
        'p1' => 'The cup is not a cylinder, so the volume does not grow evenly with height. Think of it as an upside-down cone whose tip (the <i>shpitz</i>) has been cut off.',
        'p2' => "Similar triangles give the full height of that cone from the cup's height and its two radii.",
        'p3' => "The volume of a cone is V = &#8531;&pi;r<sup>2</sup>h, so subtracting the missing tip from the full cone gives the cup's volume, and solving the same formula for one revi'is gives the fill height.",
        'p4' => "<b>Volume and height are different measurements.</b> The required volume (in cc) is how much liquid a revi'is is. The fill height (in cm) is how high that liquid reaches in <i>this</i> cup.",
        'p5' => '1 cc is the same as 1 cm<sup>3</sup> and 1 mL, and 1 US fluid ounce is about 29.6 cc.',
        'p6' => "<b>Why isn't half the volume at half the height?</b> The cup is narrower at the bottom and wider at the top, so the lower part holds less liquid per centimeter. A revi'is therefore reaches a larger share of the cup's height than of its volume. In a straight-sided cup the two would match.",
        'p7' => 'This is a geometric calculation only. Thick bases and curved sides change the real result, and halachic questions belong with your LOR.',
    ],
    'he' => [
        'title' => 'כמה צריכים לשתות?',
        'page_title' => 'כמה צריכים לשתות? | מחשבון רביעית לכוס',
        'intro' => 'הזינו את המידות הפנימיות של הכוס כדי לראות עד איזה גובה צריך למלא אותה כדי שתכיל רביעית.',
        'other_lang' => 'English',
        'your_cup' => 'הכוס שלכם',
        'lbl_top' => 'קוטר בחלק העליון',
        'lbl_bottom' => 'קוטר בחלק התחתון',
        'lbl_height' => 'גובה',
        'hint_bottom' => 'חייב להיות קטן מהקוטר העליון.',
        'hint_height' => 'מדדו בתוך הכוס, מהרצפה הפנימית ועד השפה.',
        'show' => 'הצג תוצאה',
        'cm' => 'ס״מ', 'cc' => 'סמ״ק', 'floz' => 'אונקיות נוזל אמריקאיות',
        'rcn' => 'הרב חיים נאה', 'ci' => 'החזון איש',
        'aria' => 'ציור הכוס בקנה מידה, עם קווי המילוי מסומנים',
        'total' => 'הכוס מכילה %s כשהיא מלאה, וגובהה %s.',
        'required' => 'נפח נדרש: %s (כ-%s) לרביעית',
        'fill' => 'ממלאים את הכוס עד:',
        'above' => 'מעל הרצפה הפנימית של הכוס',
        'small' => 'הכוס מכילה רק %s, פחות מרביעית לפי שיטה זו.',
        'err_nan' => '%s: אינו מספר. משתמשים ב-%s ס״מ.',
        'err_range' => '%s: חייב להיות בין %s ל-%s ס״מ. משתמשים ב-%s ס״מ.',
        'err_bottom' => 'הקוטר התחתון חייב להיות קטן מהקוטר העליון. משתמשים ב-%s ס״מ לתחתית.',
        'how' => 'איך מחשבים?',
        'p1' => 'הכוס אינה גליל, ולכן הנפח אינו גדל בצורה אחידה עם הגובה. אפשר לחשוב עליה כעל חרוט הפוך שהקצה שלו (ה<i>שפיץ</i>) נחתך.',
        'p2' => 'דמיון משולשים נותן את הגובה המלא של החרוט לפי גובה הכוס ושני הרדיוסים שלה.',
        'p3' => 'נפח חרוט הוא V = &#8531;&pi;r<sup>2</sup>h. אם מחסרים את הקצה החסר מהחרוט המלא מקבלים את נפח הכוס, ופתרון אותה נוסחה עבור רביעית אחת נותן את גובה המילוי.',
        'p4' => '<b>נפח וגובה הם מדידות שונות.</b> הנפח הנדרש (בסמ״ק) הוא כמות הנוזל של רביעית. גובה המילוי (בס״מ) הוא הגובה שהנוזל הזה מגיע אליו ב<i>כוס הזו</i>.',
        'p5' => 'סמ״ק (סנטימטר מעוקב) שווה בדיוק למיליליטר אחד, ואונקיית נוזל אמריקאית אחת היא כ-29.6 סמ״ק.',
        'p6' => '<b>למה חצי מהנפח לא נמצא בחצי מהגובה?</b> הכוס צרה בתחתיתה ורחבה בראשה, ולכן החלק התחתון מכיל פחות נוזל לכל סנטימטר. לכן רביעית מגיעה לחלק גדול יותר מגובה הכוס מאשר מנפחה. בכוס עם דפנות ישרות שניהם היו שווים.',
        'p7' => 'זהו חישוב גאומטרי בלבד. בסיס עבה ודפנות מעוגלות משנים את התוצאה בפועל, ושאלות הלכתיות יש להפנות לרב שלכם.',
    ],
];

/** Translate a key; extra arguments fill the %s placeholders. */
function t(string $key, string ...$args): string
{
    $s = STR[LANG][$key];
    return $args ? vsprintf($s, $args) : $s;
}

/** A number followed by its translated unit, e.g. "4.3 cm". */
function v(float $x, string $unit, int $d = 1): string
{
    return number_format($x, $d, '.', '') . ' ' . t($unit);
}

function n(float $x, int $d = 1): string
{
    return number_format($x, $d, '.', '');
}

/** Read one dimension from the query string, clamping and noting problems. */
function read_dim(string $key, float $default, float $min, float $max, string $labelKey, array &$notes): float
{
    if (!isset($_GET[$key]) || !is_string($_GET[$key]) || trim($_GET[$key]) === '') {
        return $default;
    }
    $raw = str_replace(',', '.', trim($_GET[$key]));
    if (!is_numeric($raw)) {
        $notes[] = t('err_nan', t($labelKey), n($default));
        return $default;
    }
    $val = (float) $raw;
    if ($val < $min || $val > $max) {
        $val = min(max($val, $min), $max);
        $notes[] = t('err_range', t($labelKey), n($min), n($max), n($val));
    }
    return $val;
}

/** A cup is the top slice of an upside-down cone with its tip cut off. */
function cup_geometry(float $top, float $bottom, float $h): array
{
    $tr = $top / 2;
    $br = $bottom / 2;
    $k = $h / ($tr - $br);                 // cone height per unit of radius
    $cut = $br * $k;                       // height of the missing tip
    $missing = M_PI * $br ** 2 * $cut / 3; // volume of the missing tip
    $volume = M_PI * $tr ** 2 * ($tr * $k) / 3 - $missing;
    return ['k' => $k, 'cut' => $cut, 'missing' => $missing, 'volume' => $volume];
}

/** Water height (cm, from the inside bottom) holding $volume, or null if the cup is too small. */
function fill_level(array $g, float $volume): ?float
{
    if ($volume >= $g['volume']) {
        return null;
    }
    $r = (3 * ($g['missing'] + $volume) / ($g['k'] * M_PI)) ** (1 / 3);
    return $r * $g['k'] - $g['cut'];
}

function draw_cup(float $top, float $bottom, float $h, array $levels): string
{
    $s = min(200 / $top, 270 / $h);
    $tw = $top * $s;
    $bw = $bottom * $s;
    $H = $h * $s;
    $cx = 130.0;
    $base = 330.0;
    $ry = fn(float $w): float => $w * 0.07;
    $widthAt = fn(float $y): float => $bw + ($tw - $bw) * $y / $H;
    $bottomArc = 'L ' . n($cx + $bw / 2) . ' ' . n($base)
        . ' A ' . n($bw / 2) . ' ' . n($ry($bw)) . ' 0 0 1 ' . n($cx - $bw / 2) . ' ' . n($base) . ' Z';

    $out = '<svg class="cup" viewBox="0 0 380 370" role="img" aria-label="' . htmlspecialchars(t('aria')) . '">';
    $out .= '<path class="glass" d="M ' . n($cx - $tw / 2) . ' ' . n($base - $H) . ' L ' . n($cx + $tw / 2) . ' ' . n($base - $H) . ' ' . $bottomArc . '"/>';

    // Larger volume first so the smaller one sits in front of it.
    $labels = [];
    foreach (['ci', 'rcn'] as $id) {
        if (!isset($levels[$id])) {
            continue;
        }
        $y = $levels[$id] * $s;
        $w = $widthAt($y);
        $out .= '<path class="water ' . $id . '" d="M ' . n($cx - $w / 2) . ' ' . n($base - $y) . ' L ' . n($cx + $w / 2) . ' ' . n($base - $y) . ' ' . $bottomArc . '"/>';
        $out .= '<ellipse class="surface ' . $id . '" cx="' . n($cx) . '" cy="' . n($base - $y) . '" rx="' . n($w / 2) . '" ry="' . n($ry($w)) . '"/>';
        $labels[$id] = ['y' => $base - $y, 'edge' => $cx + $w / 2];
    }

    $out .= '<path class="outline" d="M ' . n($cx - $tw / 2) . ' ' . n($base - $H) . ' L ' . n($cx + $tw / 2) . ' ' . n($base - $H) . ' ' . $bottomArc . '"/>';
    $out .= '<ellipse class="rim" cx="' . n($cx) . '" cy="' . n($base - $H) . '" rx="' . n($tw / 2) . '" ry="' . n($ry($tw)) . '"/>';

    // Keep the two labels from overlapping when the lines are close together.
    if (isset($labels['ci'], $labels['rcn']) && $labels['rcn']['y'] - $labels['ci']['y'] < 34) {
        $labels['ci']['ly'] = $labels['rcn']['y'] - 34;
    }
    $x0 = $cx + $tw / 2 + 14;
    foreach ($labels as $id => $l) {
        $ly = $l['ly'] ?? $l['y'];
        $out .= '<line class="tick ' . $id . '" x1="' . n($l['edge']) . '" y1="' . n($l['y']) . '" x2="' . n($x0) . '" y2="' . n($l['y']) . '"/>';
        if ($ly !== $l['y']) {
            $out .= '<line class="tick ' . $id . '" x1="' . n($x0) . '" y1="' . n($l['y']) . '" x2="' . n($x0) . '" y2="' . n($ly) . '"/>';
        }
        $out .= '<text class="lab ' . $id . '" x="' . n($x0 + 6) . '" y="' . n($ly + 4) . '">' . v($levels[$id], 'cm') . '</text>';
        $out .= '<text class="sub" x="' . n($x0 + 6) . '" y="' . n($ly + 18) . '">' . htmlspecialchars(t($id)) . '</text>';
    }
    return $out . '</svg>';
}

function render_result(float $top, float $bottom, float $h, array $notes): string
{
    $g = cup_geometry($top, $bottom, $h);
    $levels = [];
    foreach (OPINIONS as $id => $vol) {
        $lv = fill_level($g, $vol);
        if ($lv !== null) {
            $levels[$id] = $lv;
        }
    }

    ob_start();
    foreach ($notes as $note) {
        echo '<p class="note" role="alert">', htmlspecialchars($note), '</p>';
    }
    echo draw_cup($top, $bottom, $h, $levels);
    echo '<p class="total">', t('total', v($g['volume'], 'cc', 0), v($h, 'cm')), '</p>';
    echo '<div class="readings">';
    foreach (OPINIONS as $id => $vol) {
        echo '<section class="reading ', $id, '"><h3>', t($id), '</h3>';
        echo '<p class="vol">', t('required', v($vol, 'cc', 0), v($vol / 29.5735, 'floz')), '</p>';
        if (isset($levels[$id])) {
            echo '<p class="meta">', t('fill'), '</p>';
            echo '<p class="big">', n($levels[$id]), '<span> ', t('cm'), '</span></p>';
            echo '<p class="meta">', t('above'), '</p>';
        } else {
            echo '<p class="meta warn">', t('small', v($g['volume'], 'cc', 0)), '</p>';
        }
        echo '</section>';
    }
    echo '</div>';
    return (string) ob_get_clean();
}

// ---- Read and validate input --------------------------------------------

$notes = [];
$top    = read_dim('top',    DEFAULTS['top'],    0.2, 30.0, 'lbl_top', $notes);
$bottom = read_dim('bottom', DEFAULTS['bottom'], 0.1, 29.9, 'lbl_bottom', $notes);
$height = read_dim('height', DEFAULTS['height'], 0.1, 30.0, 'lbl_height', $notes);

if ($bottom >= $top) {
    $bottom = max(0.1, round($top * 0.7, 1));
    $notes[] = t('err_bottom', n($bottom));
}

if (isset($_GET['fragment'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo render_result($top, $bottom, $height, $notes);
    exit;
}

$otherLang = LANG === 'he' ? 'en' : 'he';
$otherHref = '?' . http_build_query(['lang' => $otherLang, 'top' => n($top), 'bottom' => n($bottom), 'height' => n($height)]);
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" dir="<?= LANG === 'he' ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(t('page_title')) ?></title>
  <style>
    :root {
      --bg: #eef2f3; --panel: #ffffff; --ink: #14232b; --muted: #55666f; --line: #cfd9de;
      --glass: #dfeaf0; --a: #1f6fd1; --b: #c9741a; --warn: #a3321f; --focus: #1f6fd1;
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #0f1a20; --panel: #16252d; --ink: #e6eef1; --muted: #93a5ae; --line: #2b3f49;
        --glass: #213845; --a: #5aa2f0; --b: #eaa04c; --warn: #ff9b86; --focus: #7bb6f5;
      }
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; background: var(--bg); color: var(--ink);
      font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }
    a { color: var(--a); }
    :focus-visible { outline: 3px solid var(--focus); outline-offset: 2px; }
    .wrap { max-width: 980px; margin: 0 auto; padding: 16px 20px 48px; }
    header { margin: 8px 0 24px; }
    .langrow { margin: 0; text-align: end; font-size: .9rem; }
    header h1 {
      margin: 8px 0 4px; font: 600 clamp(1.8rem, 4vw, 2.6rem)/1.1 "Iowan Old Style", Palatino, Georgia, serif;
    }
    header p.intro { margin: 0; color: var(--muted); max-width: 60ch; }
    .layout { display: grid; grid-template-columns: minmax(260px, 340px) 1fr; gap: 24px; align-items: start; }
    @media (max-width: 760px) { .layout { grid-template-columns: 1fr; } }
    .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 20px; }
    .panel + .panel { margin-top: 16px; }
    h2 { margin: 0 0 12px; font-size: 1.05rem; }
    .field { margin-bottom: 14px; }
    .field label { display: block; font-weight: 600; margin-bottom: 4px; }
    .field .hint { color: var(--muted); font-size: .85rem; margin: 2px 0 0; }
    .input { display: flex; align-items: center; gap: 8px; }
    .input input {
      flex: 1; min-width: 0; font: inherit; padding: 8px 10px; color: var(--ink);
      background: var(--bg); border: 1px solid var(--line); border-radius: 8px;
    }
    .input input:invalid { border-color: var(--warn); }
    .input span { color: var(--muted); }
    button {
      font: inherit; font-weight: 600; padding: 9px 16px; border-radius: 8px; cursor: pointer;
      border: 0; background: var(--a); color: #fff;
    }
    details summary { cursor: pointer; font-weight: 600; }
    details p { color: var(--muted); max-width: 60ch; }
    .note { margin: 0 0 12px; padding: 8px 12px; border-inline-start: 4px solid var(--warn); background: var(--bg); color: var(--warn); }
    .cup { width: 100%; max-width: 440px; height: auto; display: block; margin: 0 auto; direction: ltr; }
    .glass { fill: var(--glass); opacity: .55; }
    .outline, .rim { fill: none; stroke: var(--ink); stroke-width: 2; stroke-linejoin: round; }
    .water.rcn, .surface.rcn { fill: var(--a); }
    .water.ci, .surface.ci { fill: var(--b); }
    .water { opacity: .8; }
    .surface { opacity: 1; filter: brightness(1.15); }
    .tick { stroke-width: 1.5; }
    .tick.rcn { stroke: var(--a); } .tick.ci { stroke: var(--b); }
    .lab { font: 700 15px system-ui, sans-serif; }
    .lab.rcn { fill: var(--a); } .lab.ci { fill: var(--b); }
    .sub { font: 12px system-ui, sans-serif; fill: var(--muted); }
    .total { text-align: center; color: var(--muted); margin: 4px 0 16px; }
    .readings { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 480px) { .readings { grid-template-columns: 1fr; } }
    .reading { border: 1px solid var(--line); border-top-width: 5px; border-radius: 10px; padding: 12px 14px; }
    .reading.rcn { border-top-color: var(--a); } .reading.ci { border-top-color: var(--b); }
    .reading h3 { margin: 0; font-size: 1rem; }
    .reading .vol { margin: 0; color: var(--muted); font-size: .85rem; }
    .reading .big { margin: 6px 0 0; font: 600 2.6rem/1 "Iowan Old Style", Palatino, Georgia, serif; }
    .reading .big span { font-size: 1rem; font-family: system-ui, sans-serif; color: var(--muted); }
    .reading .meta { margin: 6px 0 0; font-size: .9rem; color: var(--muted); }
    .reading .warn { color: var(--warn); }
  </style>
</head>
<body>
<div class="wrap">
  <header>
    <p class="langrow"><a id="lang" data-to="<?= $otherLang ?>" href="<?= htmlspecialchars($otherHref) ?>" hreflang="<?= $otherLang ?>" lang="<?= $otherLang ?>"><?= t('other_lang') ?></a></p>
    <h1><?= t('title') ?></h1>
    <p class="intro"><?= t('intro') ?></p>
  </header>

  <div class="layout">
    <div>
      <form class="panel" id="f" method="get">
        <input type="hidden" name="lang" value="<?= LANG ?>">
        <h2><?= t('your_cup') ?></h2>
        <div class="field">
          <label for="top"><?= t('lbl_top') ?></label>
          <div class="input"><input type="number" id="top" name="top" inputmode="decimal" min="0.2" max="30" step="0.1" value="<?= n($top) ?>" required><span><?= t('cm') ?></span></div>
        </div>
        <div class="field">
          <label for="bottom"><?= t('lbl_bottom') ?></label>
          <div class="input"><input type="number" id="bottom" name="bottom" inputmode="decimal" min="0.1" max="29.9" step="0.1" value="<?= n($bottom) ?>" required><span><?= t('cm') ?></span></div>
          <p class="hint"><?= t('hint_bottom') ?></p>
        </div>
        <div class="field">
          <label for="height"><?= t('lbl_height') ?></label>
          <div class="input"><input type="number" id="height" name="height" inputmode="decimal" min="0.1" max="30" step="0.1" value="<?= n($height) ?>" required><span><?= t('cm') ?></span></div>
          <p class="hint"><?= t('hint_height') ?></p>
        </div>
        <button type="submit"><?= t('show') ?></button>
      </form>

      <div class="panel">
        <details>
          <summary><?= t('how') ?></summary>
          <?php foreach (['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7'] as $p): ?>
          <p><?= t($p) ?></p>
          <?php endforeach; ?>
        </details>
      </div>
    </div>

    <div class="panel" id="result" aria-live="polite">
      <?= render_result($top, $bottom, $height, $notes) ?>
    </div>
  </div>
</div>

<script>
  (function () {
    var form = document.getElementById('f');
    var out = document.getElementById('result');
    var link = document.getElementById('lang');
    var timer;
    form.querySelector('button').hidden = true; // live updates replace the button

    function update() {
      var q = new URLSearchParams(new FormData(form));
      fetch('?' + q + '&fragment=1')
        .then(function (r) { return r.ok ? r.text() : null; })
        .then(function (html) {
          if (html === null) return;
          out.innerHTML = html;
          history.replaceState(null, '', '?' + q); // shareable link
          var l = new URLSearchParams(q);
          l.set('lang', link.dataset.to);          // language link keeps the current numbers
          link.href = '?' + l;
        })
        .catch(function () {});
    }

    form.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(update, 120);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); update(); });
  })();
</script>
</body>
</html>
