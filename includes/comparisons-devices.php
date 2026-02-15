<?php
// Ensure $base is defined (should be set by parent file via config.php)
if (!isset($base)) {
    require_once __DIR__ . '/../config.php';
}
?>
<h6 style="border-left: solid 5px grey; text-transform: uppercase; margin-top: 12px;"
    class="px-3 section-heading">Popular comparisons</h6>
<div class="sentizer bg-white mt-2 p-3 rounded " style="text-transform: uppercase; font-size: 13px; font-weight: 700;">
    <div class="row">
        <div class="col-12" style="padding: 0px;">
            <?php if (empty($topComparisons)): ?>
                <p class="mb-2" style=" text-transform: capitalize;">No Comparisons Yet</p>
            <?php else: ?>
                <?php foreach ($topComparisons as $index => $comparison): ?>
                    <?php
                    // Build clean URL for compare page using slugs
                    $device1_slug = $comparison['device1_slug'] ?? $comparison['device1_id'] ?? '';
                    $device2_slug = $comparison['device2_slug'] ?? $comparison['device2_id'] ?? '';
                    $compare_url = $base . 'compare/' . urlencode($device1_slug) . 'VS' . urlencode($device2_slug);
                    $comparison_text = htmlspecialchars(($comparison['device1_name'] ?? 'Unknown') . ' vs. ' . ($comparison['device2_name'] ?? 'Unknown'));
                    ?>
                    <!-- if $index is odd -->
                    <?php if ((($index + 1) % 2) != 0): ?>
                        <a href="<?php echo $compare_url; ?>" style="text-decoration: none; color: inherit;">
                            <p class="mb-2 px-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; background-color: #ffe6f0; color: #090E21; text-transform: capitalize;"><?php echo $comparison_text; ?></p>
                        </a>
                    <?php else: ?>
                        <!-- else if $index is even -->
                        <a href="<?php echo $compare_url; ?>" style="text-decoration: none; color: inherit;">
                            <p class="mb-2 px-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; text-transform: capitalize;"><?php echo $comparison_text; ?></p>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>