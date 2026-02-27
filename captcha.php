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
$scale  = 3; // Scale factor: render small with built-in font, then scale up

// ── Step 1: Render text on a small canvas using GD built-in font (no TTF needed) ──
$builtinFont = 5; // Largest built-in font: ~9px wide, 15px tall per char
$charW = imagefontwidth($builtinFont);
$charH = imagefontheight($builtinFont);
$textLen = strlen($captchaText);
$smallW = (int)ceil($width / $scale);
$smallH = (int)ceil($height / $scale);

$smallImg = imagecreatetruecolor($smallW, $smallH);

// Background - light random tint
$bgR = rand(230, 250);
$bgG = rand(230, 250);
$bgB = rand(230, 250);
$bgColor = imagecolorallocate($smallImg, $bgR, $bgG, $bgB);
imagefilledrectangle($smallImg, 0, 0, $smallW, $smallH, $bgColor);

// ── Noise (on small canvas): random dots ──
for ($i = 0; $i < 60; $i++) {
    $dotColor = imagecolorallocate($smallImg, rand(150, 220), rand(150, 220), rand(150, 220));
    imagesetpixel($smallImg, rand(0, $smallW), rand(0, $smallH), $dotColor);
}

// ── Noise: random lines ──
for ($i = 0; $i < 4; $i++) {
    $lineColor = imagecolorallocate($smallImg, rand(140, 200), rand(140, 200), rand(140, 200));
    imageline($smallImg, rand(0, $smallW), rand(0, $smallH), rand(0, $smallW), rand(0, $smallH), $lineColor);
}

// ── Render each character with random color and slight vertical jitter ──
$totalTextW = $textLen * ($charW + 1);
$startX = max(2, (int)(($smallW - $totalTextW) / 2));
$baseY  = (int)(($smallH - $charH) / 2);

for ($i = 0; $i < $textLen; $i++) {
    $char = $captchaText[$i];
    $charColor = imagecolorallocate($smallImg, rand(10, 100), rand(10, 100), rand(10, 100));
    $yJitter = rand(-2, 2);
    imagechar($smallImg, $builtinFont, $startX + $i * ($charW + 1), $baseY + $yJitter, $char, $charColor);
}

// ── More noise dots on top of text ──
for ($i = 0; $i < 30; $i++) {
    $dotColor = imagecolorallocate($smallImg, rand(100, 200), rand(100, 200), rand(100, 200));
    imagesetpixel($smallImg, rand(0, $smallW), rand(0, $smallH), $dotColor);
}

// ── Step 2: Scale up the small image to the final canvas ──
$img = imagecreatetruecolor($width, $height);
imagecopyresampled($img, $smallImg, 0, 0, 0, 0, $width, $height, $smallW, $smallH);
imagedestroy($smallImg);

// ── Extra noise on the scaled-up final image ──
for ($i = 0; $i < 5; $i++) {
    $arcColor = imagecolorallocate($img, rand(130, 200), rand(130, 200), rand(130, 200));
    imagearc($img, rand(0, $width), rand(0, $height), rand(40, 160), rand(20, 60), rand(0, 360), rand(0, 360), $arcColor);
}
for ($i = 0; $i < 100; $i++) {
    $dotColor = imagecolorallocate($img, rand(120, 210), rand(120, 210), rand(120, 210));
    imagesetpixel($img, rand(0, $width), rand(0, $height), $dotColor);
}

// ── Output as PNG ──
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
imagepng($img);
imagedestroy($img);
