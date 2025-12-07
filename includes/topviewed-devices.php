<h6 style="border-left: 7px solid #EFEBE9 ; font-weight: 900; color: #090E21; text-transform: uppercase;"
                            class=" px-2 mt-2 d-inline mt-4">Top 10
                            Daily Interest</h6>
                        <div class="center">
                            <table class="table table-sm custom-table">
                                <thead>
                                    <tr style="background-color: #4c7273; color: white;">
                                        <th style="color: white;">#</th>
                                        <th style="color: white;">Devices</th>
                                        <th style="color: white;">Daily Hits</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topViewedDevices)): ?>
                                        <tr>
                                            <th scope="row"></th>
                                            <td class="text-start">Not Enough Data Exists</td>
                                            <td class="text-end"></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topViewedDevices as $index => $device):
                                            if (($index + 1) % 2 != 0): ?>
                                                <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                                                    <td class="text-end"><?php echo $device['view_count']; ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr class="highlight clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <th scope="row" class="text-white"><?php echo $index + 1; ?></th>
                                                    <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                                                    <td class="text-end"><?php echo $device['view_count']; ?></td>
                                                </tr>
                                    <?php
                                            endif;
                                        endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>