<?php
declare(strict_types=1);

/*
 * How much do you need to drink? (a cup and a reviis)
 * Single file: PHP does all the maths and drawing. With JavaScript the page
 * updates live by asking this same file for just the result (?fragment=1);
 * without JavaScript the "Show result" button submits the form normally.
 */

const DEFAULTS = ['top' => 6.4, 'bottom' => 4.5, 'height' => 7.6];
const OPINIONS = [
    'rcn' => ['name' => 'Rav Chaim Naeh', 'vol' => 86.0],
    'ci'  => ['name' => 'Chazon Ish',     'vol' => 150.0],
];

/** Read one dimension from the query string, clamping and noting problems. */
function read_dim(string $key, float $default, float $min, float $max, string $label, array &$notes): float
{
    if (!isset($_GET[$key]) || !is_string($_GET[$key]) || trim($_GET[$key]) === '') {
        return $default;
    }
    $raw = str_replace(',', '.', trim($_GET[$key]));
    if (!is_numeric($raw)) {
        $notes[] = "$label is not a number. Using $default cm.";
        return $default;
    }
    $v = (float) $raw;
    if ($v < $min || $v > $max) {
        $v = min(max($v, $min), $max);
        $notes[] = "$label must be between $min and $max cm. Using $v cm.";
    }
    return $v;
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

function n(float $x, int $d = 1): string
{
    return number_format($x, $d, '.', '');
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

    $out = '<svg class="cup" viewBox="0 0 380 370" role="img" aria-label="Cup drawn to scale with the fill lines marked">';
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
        $out .= '<text class="lab ' . $id . '" x="' . n($x0 + 6) . '" y="' . n($ly + 4) . '">' . n($levels[$id]) . ' cm</text>';
        $out .= '<text class="sub" x="' . n($x0 + 6) . '" y="' . n($ly + 18) . '">' . htmlspecialchars(OPINIONS[$id]['name']) . '</text>';
    }
    return $out . '</svg>';
}

function render_result(float $top, float $bottom, float $h, array $notes): string
{
    $g = cup_geometry($top, $bottom, $h);
    $levels = [];
    foreach (OPINIONS as $id => $o) {
        $lv = fill_level($g, $o['vol']);
        if ($lv !== null) {
            $levels[$id] = $lv;
        }
    }

    ob_start();
    foreach ($notes as $note) {
        echo '<p class="note" role="alert">', htmlspecialchars($note), '</p>';
    }
    echo draw_cup($top, $bottom, $h, $levels);
    echo '<p class="total">This cup holds ', n($g['volume'], 0), ' cm<sup>3</sup> when full, ', n($h), ' cm tall.</p>';
    echo '<div class="readings">';
    foreach (OPINIONS as $id => $o) {
        echo '<section class="reading ', $id, '"><h3>', htmlspecialchars($o['name']), '</h3>';
        echo '<p class="vol">1 reviis = ', n($o['vol'], 0), ' cm<sup>3</sup></p>';
        if (isset($levels[$id])) {
            $lv = $levels[$id];
            echo '<p class="big">', n($lv), '<span> cm</span></p>';
            echo '<p class="meta">from the inside bottom. That is ', n($lv / $h * 100, 0), '% of the height but ',
                n($o['vol'] / $g['volume'] * 100, 0), '% of the volume.</p>';
        } else {
            echo '<p class="meta warn">This cup holds only ', n($g['volume'], 0),
                ' cm<sup>3</sup>, which is less than one reviis by this opinion.</p>';
        }
        echo '</section>';
    }
    echo '</div>';
    return (string) ob_get_clean();
}

// ---- Read and validate input --------------------------------------------

$notes = [];
$top    = read_dim('top',    DEFAULTS['top'],    0.2, 30.0, 'Top diameter', $notes);
$bottom = read_dim('bottom', DEFAULTS['bottom'], 0.1, 29.9, 'Bottom diameter', $notes);
$height = read_dim('height', DEFAULTS['height'], 0.1, 30.0, 'Height', $notes);

if ($bottom >= $top) {
    $bottom = max(0.1, round($top * 0.7, 1));
    $notes[] = 'The bottom diameter must be smaller than the top diameter. Using ' . n($bottom) . ' cm for the bottom.';
}

if (isset($_GET['fragment'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo render_result($top, $bottom, $height, $notes);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>How much do you need to drink? | Reviis cup calculator</title>
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
    header h1 {
      margin: 8px 0 4px; font: 600 clamp(1.8rem, 4vw, 2.6rem)/1.1 "Iowan Old Style", Palatino, Georgia, serif;
    }
    header p { margin: 0; color: var(--muted); max-width: 60ch; }
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
    .note { margin: 0 0 12px; padding: 8px 12px; border-left: 4px solid var(--warn); background: var(--bg); color: var(--warn); }
    .cup { width: 100%; max-width: 440px; height: auto; display: block; margin: 0 auto; }
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
    <a href="../index.php">Back</a>
    <h1>How much do you need to drink?</h1>
    <p>Enter the inside measurements of your cup to see how high it must be filled to hold one reviis (רביעית).</p>
  </header>

  <div class="layout">
    <div>
      <form class="panel" id="f" method="get">
        <h2>Your cup</h2>
        <div class="field">
          <label for="top">Diameter at the top</label>
          <div class="input"><input type="number" id="top" name="top" inputmode="decimal" min="0.2" max="30" step="0.1" value="<?= n($top) ?>" required><span>cm</span></div>
        </div>
        <div class="field">
          <label for="bottom">Diameter at the bottom</label>
          <div class="input"><input type="number" id="bottom" name="bottom" inputmode="decimal" min="0.1" max="29.9" step="0.1" value="<?= n($bottom) ?>" required><span>cm</span></div>
          <p class="hint">Must be smaller than the top diameter.</p>
        </div>
        <div class="field">
          <label for="height">Height</label>
          <div class="input"><input type="number" id="height" name="height" inputmode="decimal" min="0.1" max="30" step="0.1" value="<?= n($height) ?>" required><span>cm</span></div>
          <p class="hint">Measure inside the cup, from the floor of the cup to the rim.</p>
        </div>
        <button type="submit">Show result</button>
      </form>

      <div class="panel">
        <details>
          <summary>How is this calculated?</summary>
          <p>The cup is not a cylinder, so the volume does not grow evenly with height. Think of it as an upside-down cone whose tip (the <i>shpitz</i>) has been cut off.</p>
          <p>Similar triangles give the full height of that cone from the cup's height and its two radii. The volume of a cone is V = &#8531;&pi;r<sup>2</sup>h, so subtracting the missing tip from the full cone gives the cup's volume, and solving the same formula for one reviis gives the fill height.</p>
          <p>This is a geometric calculation only. Thick bases and curved sides change the real result, and halachic questions belong with your rabbi.</p>
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
    var timer;
    form.querySelector('button').hidden = true; // live updates replace the button

    function update() {
      var q = new URLSearchParams(new FormData(form)).toString();
      fetch('?' + q + '&fragment=1')
        .then(function (r) { return r.ok ? r.text() : null; })
        .then(function (html) {
          if (html === null) return;
          out.innerHTML = html;
          history.replaceState(null, '', '?' + q); // shareable link
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