<h4 class="section-heading">Latest Devices</h4>

    <?php if (empty($devices)): ?>

        <div class="text-center py-5">
            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Devices Available</h4>
            <p class="text-muted">Check back later for new devices!</p>
        </div>

    <?php else: ?>

        <!-- DESKTOP VIEW -->
        <div class="d-none d-lg-flex"
            style="overflow-y: auto; max-height:390px; width:320px; flex-wrap:wrap; overflow-x:hidden;">

            <?php foreach ($devices as $device): ?>
                <a href="device.php?id=<?php echo $device['id']; ?>" class="module-phones-link" style="    display: flex;
    align-items: center;
    justify-content: flex-start;" >
                    <img style="position:relative;left:0;" src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
                    <?php echo htmlspecialchars($device['name']); ?>
                </a>
            <?php endforeach; ?>

        </div>

        <!-- MOBILE SWIPER VIEW -->
        <div id="instores-container"
            class="d-block d-lg-none swiper-container material-card swiper-container-horizontal">

            <div class="scroller-phones swiper-wrapper">

                <?php
                // split into chunks of 2 items (because mobile shows 2 per slide)
                $mobileChunks = array_chunk($devices, 2);
                ?>

                <?php foreach ($mobileChunks as $chunk): ?>
                    <div class="swiper-slide">
                        <?php foreach ($chunk as $device): ?>
                            <div class="swiper-half-slide">
                                <a href="device.php?id=<?php echo $device['id']; ?>">
                                    <span>
                                        <img style="height:119px" src="<?php echo htmlspecialchars($device['image']); ?>"
                                             alt="<?php echo htmlspecialchars($device['name']); ?>">
                                    </span>
                                    <strong style="margin:0px"><?php echo htmlspecialchars($device['name']); ?></strong>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            </div>

            <div class="swiper-scrollbar"></div>
        </div>

    <?php endif; ?>
    