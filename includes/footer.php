<footer class="da-footer-new" aria-label="Site Footer">
    <div class="da-footer-container">

      <!-- Top Row: Logo & Social -->
      <div class="da-footer-top-row">
        <a class="da-logo" href="<?php echo $base; ?>">
          <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena" />
        </a>
        <div class="da-social-icons-top">
          <a href="https://www.facebook.com/profile.php?id=61585936163841" target="_blank" title="Facebook" class="fb"><i class="fab fa-facebook-f"></i></a>
          <a href="https://youtube.com/@devicesarena" target="_blank" title="YouTube" class="yt"><i class="fab fa-youtube"></i></a>
          <a href="https://twitter.com/" target="_blank" title="X" class="tt"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://www.instagram.com/devicesarenaofficial" target="_blank" title="Instagram" class="ig"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <!-- Middle Row: Company/Licensing & Content/Help -->
      <div class="da-footer-mid-row">
        <!-- Left Column: Company & Licensing -->
        <div class="da-footer-col-group">
          <div class="da-footer-section">
            <h4>COMPANY</h4>
            <ul class="da-footer-links">
              <li><a href="<?php echo $base; ?>about-us">About Us</a></li>
              <li><a href="#">Team</a></li>
              <li><a href="<?php echo $base; ?>contact-us">Contact Us</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Ethics statement</a></li>
              <li><a href="#">How we rate</a></li>
              <li><a href="#">AI at DevicesArena</a></li>
            </ul>
          </div>
          <div class="da-footer-section">
            <h4>LICENSING</h4>
            <ul class="da-footer-links">
              <li><a href="#">Reprint & Permissions</a></li>
              <li><a href="#">Database Licensing</a></li>
              <li><a href="<?php echo $base; ?>advertise-with-us">Advertise with us</a></li>
            </ul>
          </div>
        </div>

        <!-- Right Column: Content & Help -->
        <div class="da-footer-col-group">
          <div class="da-footer-section">
            <h4>CONTENT</h4>
            <ul class="da-footer-links inline-list">
              <li><a href="<?php echo $base; ?>home">Home</a></li>
              <li><a href="<?php echo $base; ?>news">News</a></li>
              <li><a href="#">Manufacturers</a></li>
              <li><a href="#">Carriers</a></li>
              <li><a href="<?php echo $base; ?>reviews">Reviews</a></li>
              <li><a href="<?php echo $base; ?>sitemap">Sitemap</a></li>
              <li><a href="#">News Archive</a></li>
              <li><a href="#">Reviews Archive</a></li>
            </ul>
          </div>
          <div class="da-footer-section">
            <h4>HELP</h4>
            <ul class="da-footer-links inline-list">
              <li><a href="#">Terms of Use</a></li>
              <li><a href="<?php echo $base; ?>privacy-policy">Privacy Policy</a></li>
              <li><a href="#">Web Notifications</a></li>
              <li><a href="#">Cookies</a></li>
            </ul>
          </div>
        </div>
      </div>

      <hr class="da-footer-hr">

      <!-- Bottom Row: Guides -->
      <div class="da-footer-guides">
        <h4>GUIDES</h4>
        <div class="da-guides-grid">
          <!-- Col 1 -->
          <ul>
            <li><a href="#">Best Phones</a></li>
            <li><a href="#">Best Samsung Tablets</a></li>
            <li><a href="#">Best Pixel Phones</a></li>
            <li><a href="#">Best Foldable Phones</a></li>
            <li><a href="#">Best Camera Phones</a></li>
            <li><a href="#">Best Nokia Phones</a></li>
            <li><a href="#">Best AirPods</a></li>
          </ul>
          <!-- Col 2 -->
          <ul>
            <li><a href="#">Best Tablets</a></li>
            <li><a href="#">Best Apple Watch</a></li>
            <li><a href="#">Best Motorola Phones</a></li>
            <li><a href="#">Best Small Phones</a></li>
            <li><a href="#">Best Gaming Phones</a></li>
            <li><a href="#">Best Smartwatches</a></li>
          </ul>
          <!-- Col 3 -->
          <ul>
            <li><a href="#">Best iPads</a></li>
            <li><a href="#">Best Android Phones</a></li>
            <li><a href="#">Best Sony Phones</a></li>
            <li><a href="#">Best Flip Phones</a></li>
            <li><a href="#">Best Budget Phones</a></li>
            <li><a href="#">Best Android Smartwatches</a></li>
          </ul>
          <!-- Col 4 -->
          <ul>
            <li><a href="#">Best Budget Tablets</a></li>
            <li><a href="#">Best iPhone</a></li>
            <li><a href="#">Best OnePlus Phones</a></li>
            <li><a href="#">Best Mid-Range Phones</a></li>
            <li><a href="#">Best Asus Phones</a></li>
            <li><a href="#">Best Budget Smartwatches</a></li>
          </ul>
          <!-- Col 5 -->
          <ul>
            <li><a href="#">Best Android Tablets</a></li>
            <li><a href="#">Best Samsung Phones</a></li>
            <li><a href="#">Best Xiaomi Phones</a></li>
            <li><a href="#">Phones with best battery</a></li>
            <li><a href="#">Fastest Charging Phones</a></li>
            <li><a href="#">Best Galaxy Watch</a></li>
          </ul>
        </div>
      </div>

      <!-- ── Newsletter divider ── -->
      <hr class="da-footer-hr">

      <!-- ── Newsletter Strip ── -->
      <div class="da-footer-newsletter-slim">
        <input type="email" id="ft-newsletter-email" class="da-fn-input" placeholder="Your email address" autocomplete="off" autocorrect="off" spellcheck="false">
        <button id="ft-newsletter-btn" class="da-fn-btn"><i class="fa fa-paper-plane"></i> Subscribe</button>
        <div id="ft-newsletter-msg" class="da-fn-msg"></div>
        <p class="da-fn-desc">
          Get the latest mobile reviews, breaking news, and deals delivered to your inbox.
          No spam. Unsubscribe anytime.
        </p>
        <p class="da-fn-legal">
          &copy; <?php echo date('Y'); ?> DevicesArena. All rights reserved.
        </p>
      </div>
    </div>
  </footer>

  <script>
    (function () {
      var btn   = document.getElementById('ft-newsletter-btn');
      var email = document.getElementById('ft-newsletter-email');
      var msg   = document.getElementById('ft-newsletter-msg');
      if (!btn || !email || !msg) return;

      btn.addEventListener('click', function () {
        var val = email.value.trim();
        if (!val) {
          msg.textContent = 'Please enter your email address.';
          msg.className = 'da-fn-msg error';
          return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Subscribing...';

        var base = (window.baseURL || '/');
        fetch(base + 'handle_newsletter.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'newsletter_email=' + encodeURIComponent(val)
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            msg.textContent = data.message;
            msg.className = 'da-fn-msg ' + (data.success ? 'success' : 'error');
            if (data.success) { email.value = ''; }
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Subscribe';
          })
          .catch(function () {
            msg.textContent = 'Something went wrong. Please try again.';
            msg.className = 'da-fn-msg error';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Subscribe';
          });
      });

      email.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') btn.click();
      });
    })();
  </script>

  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <!-- Authentication Logic -->
  <script src="<?php echo $base; ?>js/auth.js"></script>