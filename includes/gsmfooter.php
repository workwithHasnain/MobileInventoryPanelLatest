<div style="background-color: #151a2dba;">
    <div id="bottom" class="container py-3 bottom">
        <div class="row">
            <div class="col-12">
                <div id="newsletter_message_container"></div>
                <form id="newsletter_form" method="POST" action="" style="padding: 20px; border-radius: 4px; text-align: center;">
                    <p style="margin-bottom: 12px; color: white; font-weight: 500;">Subscribe to our newsletter</p>
                    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                        <input type="email" id="newsletter_email" name="newsletter_email" placeholder="Enter your email" required style="padding: 10px 12px; border: 1px solid white; border-radius: 4px; font-size: 14px; flex: 1; min-width: 200px; max-width: 300px; background-color: white;">
                        <style>
                            input::placeholder {
                                color: #1B2035;
                                opacity: 0.7;
                            }
                        </style>
                        <button type="submit" id="newsletter_btn" style="padding: 10px 24px; background-color: white; color: #1B2035; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; white-space: nowrap; font-weight: 500;">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row align-items-center">
            <div class="m-auto col-2 d-flex justify-content-center align-items-center" style="width: max-content;">
                <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="devices arena logo for footer" style=" width: 185px;">
            </div>
            <div class="col-10 nav-wrap m-auto text-center ">
                <div class="nav-container">
                    <a href="<?php echo $base; ?>" style="color: white;">Home</a>
                    <a href="<?php echo $base; ?>/reviews" style="color: white;">Reviews</a>
                    <a href="<?php echo $base; ?>/compare" style="color: white;">Compare</a>
                    <a href="<?php echo $base; ?>/phonefinder" style="color: white;">Phone Finder</a>
                    <a href="<?php echo $base; ?>/featured" style="color: white;">Featured</a>
                    <br>
                    <a href="https://youtube.com/@devicesarena" style="color: white;"> <i class="fa-brands fa-youtube fa-sm"></i></a>
                    <a href="https://www.instagram.com/devicesarenaofficial/" style="color: white;"> <i class="fa-brands fa-instagram fa-sm"></i></a>
                    <a href="https://www.tiktok.com/" style="color: white;"> <i class="fa-brands fa-tiktok fa-sm"></i></a>
                    <a href="https://www.facebook.com/profile.php?id=61585936163841" style="color: white;"> <i class="fa-brands fa-facebook-f fa-sm"></i></a>
                    <a href="https://www.twitter.com/" style="color: white;"> <i class="fa-brands fa-twitter fa-sm"></i></a>
                    <br>
                    <a href="#" style="color: white;">© 2000-2026 DevicesArena.com</a>
                </div>
            </div>
        </div>
    </div>
</div>