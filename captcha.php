<?php

/**
 * Image-based CAPTCHA Generator
 * Renders random words as a distorted PNG image using PHP GD library.
 * Stores the correct answer in $_SESSION['captcha'] for server-side validation.
 */

// ── Prevent any text from corrupting the binary PNG stream ──
error_reporting(0);
ini_set('display_errors', '0');
while (ob_get_level()) ob_end_clean();

// ── GD check — fail gracefully with a visible error image ──
if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
    // Serve a tiny pre-built 1×1 red pixel PNG so the browser shows *something*
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    // Minimal valid 1×1 red PNG (67 bytes)
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}

// ── Session: open, write captcha answer, then CLOSE immediately ──
// Closing early releases the session file lock so concurrent requests
// (like the page that embedded this <img>) aren't blocked.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Word pool (200+ common words, 4-7 letters, easy to read) ──
$words = [
    'apple',
    'beach',
    'chair',
    'dance',
    'eagle',
    'flame',
    'grape',
    'honey',
    'ivory',
    'juice',
    'kite',
    'lemon',
    'mango',
    'noble',
    'ocean',
    'piano',
    'quest',
    'river',
    'stone',
    'tiger',
    'urban',
    'vivid',
    'wheat',
    'yacht',
    'zebra',
    'alarm',
    'bloom',
    'candy',
    'drift',
    'ember',
    'frost',
    'globe',
    'haven',
    'irony',
    'jazzy',
    'knack',
    'lunar',
    'mirth',
    'nerve',
    'olive',
    'pearl',
    'quilt',
    'rider',
    'spark',
    'trail',
    'umbra',
    'vault',
    'wrist',
    'xenon',
    'yield',
    'angel',
    'brave',
    'coral',
    'delta',
    'elbow',
    'fiery',
    'giant',
    'haste',
    'input',
    'jolly',
    'karma',
    'lodge',
    'maple',
    'nexus',
    'orbit',
    'pixel',
    'quirk',
    'radar',
    'solar',
    'thorn',
    'ultra',
    'vigor',
    'waltz',
    'youth',
    'amber',
    'blend',
    'charm',
    'dwarf',
    'erupt',
    'flora',
    'glaze',
    'hitch',
    'ivory',
    'joust',
    'kneel',
    'lyric',
    'medal',
    'north',
    'oxide',
    'plumb',
    'quota',
    'reign',
    'swirl',
    'tower',
    'unity',
    'vocal',
    'whirl',
    'prize',
    'blaze',
    'crisp',
    'forge',
    'grasp',
    'helix',
    'index',
    'knots',
    'lever',
    'mocha',
    'noted',
    'optic',
    'plume',
    'quake',
    'robin',
    'stalk',
    'truce',
    'usher',
    'venom',
    'wings',
    'azure',
    'cider',
    'draft',
    'epoch',
    'flock',
    'grind',
    'hover',
    'jewel',
    'latch',
    'molar',
    'ninja',
    'oasis',
    'petal',
    'relic',
    'siege',
    'tulip',
    'valor',
    'woven',
    'bison',
    'cloak',
    'denim',
    'elfin',
    'fungi',
    'glyph',
    'heron',
    'inlet',
    'jolts',
    'knave',
    'lotus',
    'mural',
    'nifty',
    'onyx',
    'prism',
    'quail',
    'rusty',
    'sable',
    'tempo',
    'udder',
    'viola',
    'widow',
    'zesty',
    'basil',
    'cedar',
    'drape',
    'facet',
    'gecko',
    'husky',
    'igloo',
    'jiffy',
    'kayak',
    'llama',
    'moose',
    'neuro',
    'otter',
    'pansy',
    'ramen',
    'sauna',
    'tabby',
    'umbra',
    'valet',
    'whelk',
    'yeast',
    'zilch',
    'arrow',
    'brisk',
    'crest',
    'dense',
    'exile',
    'flint',
    'grill',
    'hydra',
    'ivory',
    'joker',
    'kudos',
    'lilac',
    'modem',
    'noble',
    'omega',
    'plank',
    'rhyme',
    'scale',
    'trunk',
    'union'
];

// Pick 2 random words to form the CAPTCHA text
$word1 = $words[array_rand($words)];
$word2 = $words[array_rand($words)];

// Ensure the two words are different
while ($word2 === $word1) {
    $word2 = $words[array_rand($words)];
}

$captchaText = $word1 . ' ' . $word2;

// Store answer in session (case-insensitive comparison will be done during validation)
$_SESSION['captcha'] = strtolower($captchaText);

// ── Release session lock immediately so the parent page isn't blocked ──
session_write_close();

// ── Image generation settings ──
$width  = 320;
$height = 80;
$img = imagecreatetruecolor($width, $height);

// Background - light random tint
$bgR = rand(235, 248);
$bgG = rand(235, 248);
$bgB = rand(235, 248);
$bgColor = imagecolorallocate($img, $bgR, $bgG, $bgB);
imagefilledrectangle($img, 0, 0, $width, $height, $bgColor);

// ── Lightweight noise: fewer dots for speed ──
for ($i = 0; $i < 40; $i++) {
    $c = imagecolorallocate($img, rand(160, 210), rand(160, 210), rand(160, 210));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $c);
}

// ── Render text using GD built-in font (no TTF) ──
$builtinFont = 5;
$charW = imagefontwidth($builtinFont);
$charH = imagefontheight($builtinFont);
$textLen = strlen($captchaText);

// Center text horizontally
$totalTextW = $textLen * ($charW + 2);
$startX = max(10, (int)(($width - $totalTextW) / 2));
$baseY = (int)(($height - $charH) / 2);

// Render each character with varying color
for ($i = 0; $i < $textLen; $i++) {
    $char = $captchaText[$i];
    $charColor = imagecolorallocate($img, rand(20, 90), rand(20, 90), rand(20, 90));
    $yOffset = $baseY + rand(-3, 3);
    imagechar($img, $builtinFont, $startX + ($i * ($charW + 2)), $yOffset, $char, $charColor);
}

// ── Light noise overlay ──
for ($i = 0; $i < 50; $i++) {
    $c = imagecolorallocate($img, rand(180, 220), rand(180, 220), rand(180, 220));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $c);
}

// ── Output as PNG ──
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
imagepng($img, null, 9);
imagedestroy($img);
