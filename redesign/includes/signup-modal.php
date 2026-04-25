<div class="modal fade da-modal" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i>Create Account</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="signup-message" style="display:none;"></div>
          <form id="publicSignupForm" autocomplete="off">
            <div class="mb-3"><label class="form-label">Full Name</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" placeholder="John Doe" required minlength="2" maxlength="100"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6"></div>
            </div>
            <button type="submit" class="btn w-100 fw-semibold" id="signupSubmitBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-user-plus me-1"></i>Create Account</button>
          </form>
          <div class="text-center mt-3" style="font-size:13px;color:var(--text-muted);">Have account? <a href="#" onclick="switchToLogin();return false;" style="color:var(--accent);font-weight:600;">Login</a></div>
        </div>
      </div>
    </div>
  </div>