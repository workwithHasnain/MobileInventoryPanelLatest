<?php

/**
 * SVG-based CAPTCHA Generator
 * Renders random words as a distorted SVG image — NO GD extension needed.
 * Stores the correct answer in $_SESSION['captcha'] for server-side validation.
 * Works on any server with basic PHP (no extensions required).
 */

// Suppress errors
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Word pool (200+ common words, 4-7 letters, easy to read) ──
$words = [
    'apple','beach','chair','dance','eagle','flame','grape','honey','ivory','juice',
    'kite','lemon','mango','noble','ocean','piano','quest','river','stone','tiger',
    'urban','vivid','wheat','yacht','zebra','alarm','bloom','candy','drift','ember',
    'frost','globe','haven','irony','jazzy','knack','lunar','mirth','nerve','olive',
    'pearl','quilt','rider','spark','trail','umbra','vault','wrist','xenon','yield',
    'angel','brave','coral','delta','elbow','fiery','giant','haste','input','jolly',
    'karma','lodge','maple','nexus','orbit','pixel','quirk','radar','solar','thorn',
    'ultra','vigor','waltz','youth','amber','blend','charm','dwarf','erupt','flora',
    'glaze','hitch','joust','kneel','lyric','medal','north','oxide','plumb','quota',
    'reign','swirl','tower','unity','vocal','whirl','prize','blaze','crisp','forge',
    'grasp','helix','index','knots','lever','mocha','noted','optic','plume','quake',
    'robin','stalk','truce','usher','venom','wings','azure','cider','draft','epoch',
    'flock','grind','hover','jewel','latch','molar','ninja','oasis','petal','relic',
    'siege','tulip','valor','woven','bison','cloak','denim','elfin','fungi','glyph',
    'heron','inlet','jolts','knave','lotus','mural','nifty','onyx','prism','quail',
    'rusty','sable','tempo','udder','viola','widow','zesty','basil','cedar','drape',
    'facet','gecko','husky','igloo','jiffy','kayak','llama','moose','neuro','otter',
    'pansy','ramen','sauna','tabby','valet','whelk','yeast','zilch','arrow','brisk',
    'crest','dense','exile','flint','grill','hydra','joker','kudos','lilac','modem',
    'omega','plank','rhyme','scale','trunk','union'
];

// Pick 2 different random words
$word1 = $words[array_rand($words)];
$word2 = $words[array_rand($words)];
while ($word2 === $word1) {
    $word2 = $words[array_rand($words)];
}
$captchaText = $word1 . ' ' . $word2;

// Store answer in session
$_SESSION['captcha'] = strtolower($captchaText);

// Release session lock immediately
session_write_close();

// ── SVG generation ──
$width  = 320;
$height = 80;

// Random pastel background
$bgR = rand(235, 248); $bgG = rand(235, 248); $bgB = rand(235, 248);

// Start SVG
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';

// Background
$svg .= '<rect width="' . $width . '" height="' . $height . '" fill="rgb(' . $bgR . ',' . $bgG . ',' . $bgB . ')"/>';

// ── Noise: random lines ──
for ($i = 0; $i < 6; $i++) {
    $r = rand(160, 210); $g = rand(160, 210); $b = rand(160, 210);
    $x1 = rand(0, $width); $y1 = rand(0, $height);
    $x2 = rand(0, $width); $y2 = rand(0, $height);
    $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgb(' . $r . ',' . $g . ',' . $b . ')" stroke-width="1"/>';
}

// ── Noise: random circles ──
for ($i = 0; $i < 12; $i++) {
    $r = rand(170, 215); $g = rand(170, 215); $b = rand(170, 215);
    $cx = rand(0, $width); $cy = rand(0, $height); $cr = rand(3, 15);
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $cr . '" fill="rgb(' . $r . ',' . $g . ',' . $b . ')" opacity="0.4"/>';
}

// ── Render each character with random position, rotation, and color ──
$fontSize = 28;
$textLen = strlen($captchaText);
$charSpacing = 20;
$totalTextW = $textLen * $charSpacing;
$startX = max(10, (int)(($width - $totalTextW) / 2));

for ($i = 0; $i < $textLen; $i++) {
    $char = htmlspecialchars($captchaText[$i], ENT_XML1);
    $x = $startX + ($i * $charSpacing);
    $y = 48 + rand(-6, 6);
    $angle = rand(-15, 15);
    $r = rand(15, 90); $g = rand(15, 90); $b = rand(15, 90);
    $svg .= '<text x="' . $x . '" y="' . $y . '" font-size="' . $fontSize . '" font-family="Arial,Helvetica,sans-serif" font-weight="bold" fill="rgb(' . $r . ',' . $g . ',' . $b . ')" transform="rotate(' . $angle . ' ' . $x . ' ' . $y . ')">' . $char . '</text>';
}

// ── Noise: lines on top of text ──
for ($i = 0; $i < 3; $i++) {
    $r = rand(140, 200); $g = rand(140, 200); $b = rand(140, 200);
    $x1 = rand(0, $width); $y1 = rand(10, $height - 10);
    $x2 = rand(0, $width); $y2 = rand(10, $height - 10);
    $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgb(' . $r . ',' . $g . ',' . $b . ')" stroke-width="1.5" opacity="0.6"/>';
}

$svg .= '</svg>';

// ── Output as SVG ──
header('Content-Type: image/svg+xml');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo $svg;
