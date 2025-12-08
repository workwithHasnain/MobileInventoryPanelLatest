<h6 style="border-left: 7px solid #EFEBE9 ; font-weight: 900; color: #090E21; text-transform: uppercase;"
                            class=" px-2 mt-2 d-inline mt-4">Top 10 by
                            Fans</h6>
                        <div class="center" style="margin-top: 12px;">
                            <table class="table table-sm custom-table">
                                <thead>
                                    <tr class="text-white" style="background-color: #14222D;">
                                        <th style="color: white;  font-size: 15px;  ">#</th>
                                        <th style="color: white;  font-size: 15px;">Device</th>
                                        <th style="color: white;  font-size: 15px;">Reviews</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topReviewedDevices)): ?>
                                        <tr>
                                            <th scope="row"></th>
                                            <td class="text-start">Not Enough Data Exists</td>
                                            <td class="text-end"></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topReviewedDevices as $index => $device):
                                            if (($index + 1) % 2 != 0): ?>
                                                <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td class="text-start"><?php $text = htmlspecialchars($device['brand_name'] . ' ' . $device['name']); echo strlen($text) > 35 ? substr($text, 0, 35) . '...' : $text; ?></td>
                                                    <td class="text-end"><?php echo $device['review_count']; ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="highlight-12 clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <th scope="row" class="text-white"><?php echo $index + 1; ?></th>
                                                    <td class="text-start"><?php $text = htmlspecialchars($device['brand_name'] . ' ' . $device['name']); echo strlen($text) > 35 ? substr($text, 0, 35) . '...' : $text; ?></td>
                                                    <td class="text-end"><?php echo $device['review_count']; ?></td>
                                                </tr>
                                    <?php
                                            endif;
                                        endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>