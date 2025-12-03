<?php
// This script copies the phone form sections to tablet form, excluding keyboard and form_factor

// Read the current add_device.php file
$content = file_get_contents('add_device.php');

// Extract the phone form sections (after Network section, before the closing of phone form)
$phoneStart = strpos($content, '<!-- 4. SIM Section -->');
$phoneEnd = strpos($content, '<div class="d-flex justify-content-end mt-3">');

if ($phoneStart !== false && $phoneEnd !== false) {
    $phoneFormSections = substr($content, $phoneStart, $phoneEnd - $phoneStart);
    
    // Convert phone form to tablet form by:
    // 1. Adding "tablet" prefix to all IDs
    // 2. Excluding form_factor and keyboard fields
    // 3. Changing section headers to tablet equivalents
    
    $tabletSections = $phoneFormSections;
    
    // Replace IDs and headers
    $tabletSections = str_replace('id="sim', 'id="tabletSim', $tabletSections);
    $tabletSections = str_replace('id="body', 'id="tabletBody', $tabletSections);
    $tabletSections = str_replace('id="platform', 'id="tabletPlatform', $tabletSections);
    $tabletSections = str_replace('id="memory', 'id="tabletMemory', $tabletSections);
    $tabletSections = str_replace('id="display', 'id="tabletDisplay', $tabletSections);
    $tabletSections = str_replace('id="mainCamera', 'id="tabletMainCamera', $tabletSections);
    $tabletSections = str_replace('id="selfieCamera', 'id="tabletSelfieCamera', $tabletSections);
    $tabletSections = str_replace('id="audio', 'id="tabletAudio', $tabletSections);
    $tabletSections = str_replace('id="sensors', 'id="tabletSensors', $tabletSections);
    $tabletSections = str_replace('id="connectivity', 'id="tabletConnectivity', $tabletSections);
    $tabletSections = str_replace('id="battery', 'id="tabletBattery', $tabletSections);
    
    // Replace form field IDs
    $tabletSections = preg_replace('/id="([^"]*)"/', 'id="tablet_$1"', $tabletSections);
    $tabletSections = preg_replace('/for="([^"]*)"/', 'for="tablet_$1"', $tabletSections);
    $tabletSections = preg_replace('/data-bs-target="#([^"]*)"/', 'data-bs-target="#tablet$1"', $tabletSections);
    $tabletSections = preg_replace('/aria-labelledby="([^"]*)"/', 'aria-labelledby="tablet$1"', $tabletSections);
    
    // Remove form_factor and keyboard sections
    $tabletSections = preg_replace('/\s*<div class="col-md-6 mb-3">\s*<label for="[^"]*form_factor[^"]*".*?<\/div>\s*<\/div>/s', '', $tabletSections);
    $tabletSections = preg_replace('/\s*<div class="col-md-6 mb-3">\s*<label for="[^"]*keyboard[^"]*".*?<\/div>\s*<\/div>/s', '', $tabletSections);
    
    echo "Tablet form sections extracted and modified:\n";
    echo substr($tabletSections, 0, 500) . "...\n";
    echo "Length: " . strlen($tabletSections) . " characters\n";
    
    // Save to a temporary file for insertion
    file_put_contents('tablet_sections.txt', $tabletSections);
    echo "Tablet sections saved to tablet_sections.txt\n";
} else {
    echo "Could not find phone form sections\n";
}
?>