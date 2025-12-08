<h6 style="border-left: solid 5px grey; text-transform: uppercase; margin-top: 12px;"
    class="px-3 section-heading">Popular comparisons</h6>
<div class="sentizer bg-white mt-2 p-3 rounded " style="text-transform: uppercase; font-size: 13px; font-weight: 700;">
    <div class="row">
        <div class="col-12">
            <?php if (empty($topComparisons)): ?>
                <p class="mb-2" style=" text-transform: capitalize;">No Comparisons Yet</p>
            <?php else: ?>
                <?php foreach ($topComparisons as $index => $comparison): ?>
                    <!-- if $index is odd -->
                    <?php if ((($index + 1) % 2) != 0): ?>
                        <p class="mb-2 clickable-comparison" data-device1-id="<?php echo $comparison['device1_id'] ?? ''; ?>"
                            data-device2-id="<?php echo $comparison['device2_id'] ?? ''; ?>"
                            style="cursor: pointer; background-color: #ffe6f0; color: #090E21; text-transform: capitalize;"><?php $text = htmlspecialchars(($comparison['device1_name'] ?? $comparison['device1'] ?? 'Unknown') . ' vs. ' . ($comparison['device2_name'] ?? $comparison['device2'] ?? 'Unknown'));
                                                                                                                            echo strlen($text) > 50 ? substr($text, 0, 50) . '...' : $text; ?></p>
                                                                                                                            echo strlen($text) > 45 ? substr($text, 0, 45) . '...' : $text; ?></p>
                    <?php else: ?>
                        <!-- else if $index is even -->
                        <p class="mb-2 clickable-comparison" data-device1-id="<?php echo $comparison['device1_id'] ?? ''; ?>"
                            data-device2-id="<?php echo $comparison['device2_id'] ?? ''; ?>" style="cursor: pointer; text-transform: capitalize;"><?php $text = htmlspecialchars(($comparison['device1_name'] ?? $comparison['device1'] ?? 'Unknown') . ' vs. ' . ($comparison['device2_name'] ?? $comparison['device2'] ?? 'Unknown'));
                                                                                                                                                    echo strlen($text) > 50 ? substr($text, 0, 50) . '...' : $text; ?></p>
                                                                                                                                                    echo strlen($text) > 45 ? substr($text, 0, 45) . '...' : $text; ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>