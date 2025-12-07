<h4 class="section-heading ">Latest Devices</h4>
<div class="d-none d-lg-flex" style="overflow: hidden; overflow-y: auto; max-height: 390px; width: 320px; flex-wrap: wrap; flex-direction: column;">
    <?php if (empty($devices)): ?>
        <div class="text-center py-5">
            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Devices Available</h4>
            <p class="text-muted">Check back later for new devices!</p>
        </div>
    <?php else: $chunks = array_chunk($devices, 3); ?>
        <?php foreach ($chunks as $row): ?>
            <div style="display: flex; justify-content: flex-start; width: 100%;">
                <?php foreach ($row as $i => $device): ?>
                    <a href="#" data-device-id="<?php echo $device['id']; ?>" class="module-phones-link" style="float: none;" onclick="goToDevice(<?php echo $device['id']; ?>); return false;">
                        <?php if (isset($device['image']) && !empty($device['image'])): ?>
                            <img src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
                        <?php else: ?>
                            <img src="" alt="">
                        <?php endif; ?>
                        <br>
                        <?php echo htmlspecialchars($device['name'] ?? ''); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div id="instores-container" class="d-block d-lg-none swiper-container material-card swiper-container-horizontal">
    <div class="scroller-phones swiper-wrapper">
        <?php if (empty($latestDevices)): ?>
            <div class="swiper-slide swiper-slide-active">
                <div class="text-center py-5">
                    <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No Devices Available</h6>
                </div>
            </div>
        <?php else: ?>
            <?php $chunks = array_chunk($latestDevices, 2); ?>
            <?php foreach ($chunks as $slideIndex => $row): ?>
                <div class="swiper-slide <?php echo $slideIndex === 0 ? 'swiper-slide-active' : ($slideIndex === 1 ? 'swiper-slide-next' : ''); ?>">
                    <?php foreach ($row as $device): ?>
                        <div class="swiper-half-slide">
                            <a href="#" onclick="goToDevice(<?php echo $device['id']; ?>); return false;">
                                <span>
                                    <img src="<?php echo htmlspecialchars($device['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($device['name'] ?? ''); ?>">
                                </span>
                                <strong><?php echo htmlspecialchars($device['name'] ?? ''); ?></strong>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($row) < 2): ?>
                        <div class="swiper-half-slide"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<div class="swiper-scrollbar" style="opacity: 0;">
    <div class="swiper-scrollbar-drag" style="width: 1051.88px;"></div>
</div>