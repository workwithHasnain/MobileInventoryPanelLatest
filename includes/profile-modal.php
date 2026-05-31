<div class="modal fade da-modal" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-user-pen me-2"></i>Your Profile</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="profile-message" style="display:none;"></div>

          <!-- Google Linked Badge -->
          <div id="google-linked-badge" class="mb-3 p-3 rounded" style="display:none;background:rgba(213,0,0,0.05);border:1px solid rgba(213,0,0,0.15);">
            <div class="d-flex align-items-center mb-1">
              <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:18px;margin-right:8px;">
              <span style="font-weight:600;color:var(--text-primary);">Google Account Linked</span>
            </div>
            <div id="google-linked-msg" style="font-size:12px;color:var(--text-muted);">
                You have logged in from Google and so you can set a password as well.
            </div>
          </div>
          <form id="profileUpdateForm" autocomplete="off">
            <div class="mb-3"><label class="form-label">Full Name</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" id="profile-name" required minlength="2" maxlength="100"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" id="profile-email" required></div>
            </div>
            <hr style="border-color:var(--border);">
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><i class="fa fa-info-circle me-1"></i>Leave password fields blank to keep current.</p>
            <div id="current-password-group" class="mb-3">
              <label class="form-label">Current Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-key"></i></span><input type="password" class="form-control" name="current_password" id="profile-current-password" placeholder="Required to change password"></div>
              <div id="last-password-updated-text" style="font-size:11px;color:var(--text-muted);margin-top:6px;display:none;"></div>
            </div>
            <div class="mb-3"><label class="form-label" id="new-password-label">New Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="new_password" id="profile-new-password" placeholder="Min 6 characters" minlength="6"></div>
            </div>
            <button type="button" class="btn btn-outline-secondary w-100 mb-2 mt-1" id="profileVerifyGoogleBtn" style="display:none;border-color:var(--border);"><img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:16px;margin-right:8px;"> Verify with Google instead</button>
            <button type="submit" class="btn w-100 fw-semibold" id="profileUpdateBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-save me-1"></i>Save Changes</button>
          </form>
          <div class="mt-4 pt-3" style="border-top:1px solid var(--border);">
            <p style="color:#f87171;font-size:12px;font-weight:600;margin-bottom:8px;"><i class="fa fa-triangle-exclamation me-1"></i>Danger Zone</p>
            <div class="d-flex gap-2 flex-wrap">
              <div id="delete-password-wrapper">
                <input type="password" class="form-control form-control-sm" id="delete-account-password" placeholder="Password to confirm" style="max-width:220px;background:var(--bg-card);border-color:var(--border);color:var(--text-primary);">
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="deleteVerifyGoogleBtn" style="display:none;border-color:var(--border);"><img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:14px;margin-right:6px;">Verify</button>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePublicAccount()" id="deleteAccountBtn"><i class="fa fa-trash me-1"></i>Delete Account</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>