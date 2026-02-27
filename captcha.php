<?php

/**
 * Image-based CAPTCHA Generator
 * Renders random words as a distorted PNG image using PHP GD library.
 * Stores the correct answer in $_SESSION['captcha'] for server-side validation.
 */

session_start();

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

// ── Image generation settings ──
$width  = 320;
$height = 80;

$img = imagecreatetruecolor($width, $height);

// Background - light random tint
$bgR = rand(230, 250);
$bgG = rand(230, 250);
$bgB = rand(230, 250);
$bgColor = imagecolorallocate($img, $bgR, $bgG, $bgB);
imagefilledrectangle($img, 0, 0, $width, $height, $bgColor);

// ── Noise: random dots ──
for ($i = 0; $i < 200; $i++) {
    $dotColor = imagecolorallocate($img, rand(150, 220), rand(150, 220), rand(150, 220));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $dotColor);
}

// ── Noise: random lines ──
for ($i = 0; $i < 6; $i++) {
    $lineColor = imagecolorallocate($img, rand(140, 200), rand(140, 200), rand(140, 200));
    imageline($img, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
}

// ── Noise: random arcs ──
for ($i = 0; $i < 3; $i++) {
    $arcColor = imagecolorallocate($img, rand(130, 190), rand(130, 190), rand(130, 190));
    imagearc($img, rand(0, $width), rand(0, $height), rand(40, 150), rand(20, 60), rand(0, 360), rand(0, 360), $arcColor);
}

// ── Render text using TrueType font for large readable characters ──
$ttfSize = 22; // Font size in points — large and readable
// Use bundled Open Sans font (works on any server, no system dependency)
$fontFile = __DIR__ . '/fonts/OpenSans.ttf';

// Calculate text bounding box for centering
$bbox = imagettfbbox($ttfSize, 0, $fontFile, $captchaText);
$textWidth = abs($bbox[4] - $bbox[0]);
$textHeight = abs($bbox[5] - $bbox[1]);
$startX = ($width - $textWidth) / 2;
$baseY = ($height + $textHeight) / 2;

// Render each character individually with slight random offsets and colors
$xCursor = (int)$startX;
for ($i = 0; $i < strlen($captchaText); $i++) {
    $char = $captchaText[$i];

    // Random dark color for each character
    $charColor = imagecolorallocate($img, rand(10, 100), rand(10, 100), rand(10, 100));

    // Slight random angle and vertical offset per character
    $angle = rand(-12, 12);
    $yOffset = (int)$baseY + rand(-4, 4);

    // Get single character width for spacing
    $charBbox = imagettfbbox($ttfSize, 0, $fontFile, $char);
    $charW = abs($charBbox[4] - $charBbox[0]);

    imagettftext($img, $ttfSize, $angle, $xCursor, $yOffset, $charColor, $fontFile, $char);

    $xCursor += $charW + rand(1, 4);
}

// ── More noise on top of the text ──
for ($i = 0; $i < 80; $i++) {
    $dotColor = imagecolorallocate($img, rand(100, 200), rand(100, 200), rand(100, 200));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $dotColor);
}

// ── Output as PNG ──
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
imagepng($img);
imagedestroy($img);
