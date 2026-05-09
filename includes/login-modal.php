<div class="modal fade da-modal" id="loginModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-right-to-bracket me-2"></i>Login</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="login-message" style="display:none;"></div>
        <form id="publicLoginForm" autocomplete="off">
          <div class="mb-3"><label class="form-label">Email</label>
            <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div>
          </div>
          <div class="mb-3"><label class="form-label">Password</label>
            <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Password" required></div>
          </div>
          <button type="submit" class="btn w-100 fw-semibold" id="loginSubmitBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-right-to-bracket me-1"></i>Login</button>
        </form>
        <div class="text-center mt-3" style="font-size:13px;color:var(--text-muted);">No account? <a href="#" onclick="switchToSignup();return false;" style="color:var(--accent);font-weight:600;">Sign Up</a></div>
      </div>
    </div>
  </div>
</div>