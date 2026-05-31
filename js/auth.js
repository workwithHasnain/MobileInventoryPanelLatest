// js/auth.js

// Prevent multiple initializations if included more than once
if (typeof window.authInitialized === 'undefined') {
  window.authInitialized = true;

  // ── Auth helpers ──
  function userAuthFetch(action, fd) {
    fd.append('action', action);
    const base = window.baseURL || '/';
    return fetch(base + 'user_auth_handler.php', {
      method: 'POST',
      body: fd
    }).then(r => r.json());
  }

  function showAuthMsg(id, msg, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.style.display = 'block';
  }

  document.addEventListener('DOMContentLoaded', () => {
    // ── Forms ──
    const loginForm = document.getElementById('publicLoginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Logging in...';
        userAuthFetch('login', new FormData(this)).then(data => {
          if (data.success) {
            showAuthMsg('login-message', data.message, 'success');
            setTimeout(() => location.reload(), 800);
          } else {
            showAuthMsg('login-message', data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
          }
        }).catch(() => {
          showAuthMsg('login-message', 'An error occurred.', 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
        });
      });
    }

    const signupForm = document.getElementById('publicSignupForm');
    if (signupForm) {
      signupForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('signupSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Creating account...';
        userAuthFetch('register', new FormData(this)).then(data => {
          if (data.success) {
            showAuthMsg('signup-message', data.message, 'success');
            setTimeout(() => location.reload(), 800);
          } else {
            showAuthMsg('signup-message', data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
          }
        }).catch(() => {
          showAuthMsg('signup-message', 'An error occurred.', 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
        });
      });
    }

    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) {
      profileForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('profileUpdateBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving...';
        userAuthFetch('update_profile', new FormData(this)).then(data => {
          showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
          if (data.success) setTimeout(() => location.reload(), 1000);
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
        }).catch(() => {
          showAuthMsg('profile-message', 'An error occurred.', 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
        });
      });
    }
  });

  // ── Global Handlers ──
  window.openProfileModal = function () {
    const modalEl = document.getElementById('profileModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    userAuthFetch('get_profile', new FormData()).then(data => {
      if (data.success && data.user) {
        document.getElementById('profile-name').value = data.user.name;
        document.getElementById('profile-email').value = data.user.email;
        
        const isGoogleLinked = (data.user.auth_provider === 'google');
        const hasSetPassword = (data.user.last_password_updated !== null);
        
        const badge = document.getElementById('google-linked-badge');
        const curPassGroup = document.getElementById('current-password-group');
        const curPassInput = document.getElementById('profile-current-password');
        const lastUpdated = document.getElementById('last-password-updated-text');
        
        const verifyBtnProf = document.getElementById('profileVerifyGoogleBtn');
        const verifyBtnDel = document.getElementById('deleteVerifyGoogleBtn');
        const delPassWrap = document.getElementById('delete-password-wrapper');
        
        if (badge) badge.style.display = isGoogleLinked ? 'block' : 'none';
        
        if (hasSetPassword) {
            if (curPassGroup) curPassGroup.style.display = 'block';
            if (curPassInput) { curPassInput.style.display = 'block'; curPassInput.required = false; }
            if (lastUpdated) {
                lastUpdated.style.display = 'block';
                lastUpdated.innerHTML = '<i class="fa fa-clock me-1"></i>Last updated: ' + data.user.last_password_updated;
            }
            if (delPassWrap) delPassWrap.style.display = 'block';
            if (verifyBtnProf) verifyBtnProf.style.display = isGoogleLinked ? 'block' : 'none';
            if (verifyBtnDel) verifyBtnDel.style.display = isGoogleLinked ? 'block' : 'none';
        } else {
            if (curPassGroup) curPassGroup.style.display = 'none';
            if (curPassInput) { curPassInput.style.display = 'none'; curPassInput.required = false; }
            if (lastUpdated) lastUpdated.style.display = 'none';
            if (delPassWrap) delPassWrap.style.display = 'none';
            if (verifyBtnProf) verifyBtnProf.style.display = 'none';
            if (verifyBtnDel) verifyBtnDel.style.display = 'none';
        }
      }
    });
    
    const curPassInput = document.getElementById('profile-current-password');
    if (curPassInput) curPassInput.value = '';
    const newPassInput = document.getElementById('profile-new-password');
    if (newPassInput) newPassInput.value = '';
    const delPassInput = document.getElementById('delete-account-password');
    if (delPassInput) delPassInput.value = '';
    const profMsg = document.getElementById('profile-message');
    if (profMsg) profMsg.style.display = 'none';
    
    modal.show();
  };

  window.deletePublicAccount = function () {
    if (!confirm('Permanently delete your account? This cannot be undone.')) return;
    const pwdEl = document.getElementById('delete-account-password');
    const pwd = pwdEl ? pwdEl.value.trim() : '';
    if (!pwd) {
      showAuthMsg('profile-message', 'Please enter your password.', 'warning');
      return;
    }
    const btn = document.getElementById('deleteAccountBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Deleting...';
    
    const fd = new FormData();
    fd.append('password', pwd);
    userAuthFetch('delete_account', fd).then(data => {
      showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
      if (data.success) setTimeout(() => location.reload(), 1000);
      else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
      }
    }).catch(() => {
      showAuthMsg('profile-message', 'An error occurred.', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
    });
  };

  window.publicUserLogout = function () {
    const base = window.baseURL || '/';
    fetch(base + 'notification_handler.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: 'action=reset'
    })
    .finally(() => {
      userAuthFetch('logout', new FormData()).then(() => location.reload());
    });
  };

  window.switchToSignup = function () {
    const loginEl = document.getElementById('loginModal');
    if (loginEl) {
      const loginModal = bootstrap.Modal.getInstance(loginEl);
      if (loginModal) loginModal.hide();
    }
    setTimeout(() => {
      const signupEl = document.getElementById('signupModal');
      if (signupEl) bootstrap.Modal.getOrCreateInstance(signupEl).show();
    }, 300);
  };

  window.switchToLogin = function () {
    const signupEl = document.getElementById('signupModal');
    if (signupEl) {
      const signupModal = bootstrap.Modal.getInstance(signupEl);
      if (signupModal) signupModal.hide();
    }
    setTimeout(() => {
      const loginEl = document.getElementById('loginModal');
      if (loginEl) bootstrap.Modal.getOrCreateInstance(loginEl).show();
    }, 300);
  };

  // ── Google Auth ──
  window.handleGoogleAuthClick = function (btnId, msgId, onSuccess) {
    const btn = document.getElementById(btnId);
    let originalHtml = '';
    if (btn) {
      originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Connecting...';
    }
    
    if (typeof google === 'undefined' || !google.accounts) {
        showAuthMsg(msgId, 'Google sign-in is not loaded.', 'danger');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        return;
    }

    const client = google.accounts.oauth2.initTokenClient({
      client_id: '139319462542-o9531la8q9fo2ul5m6amsf727or0n5n0.apps.googleusercontent.com',
      scope: 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
      callback: (tokenResponse) => {
        if (tokenResponse && tokenResponse.access_token) {
          fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
            headers: { Authorization: `Bearer ${tokenResponse.access_token}` }
          })
          .then(r => r.json())
          .then(profile => {
            if (!profile.email) throw new Error('No email found');
            const fd = new FormData();
            fd.append('email', profile.email);
            fd.append('name', profile.name || 'Google User');
            userAuthFetch('google_login', fd).then(data => {
              if (data.success) {
                if (onSuccess) {
                    onSuccess(data);
                } else {
                    showAuthMsg(msgId, data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                }
              } else {
                showAuthMsg(msgId, data.message, 'danger');
                if (btn) {
                  btn.disabled = false;
                  btn.innerHTML = originalHtml;
                }
              }
            }).catch(() => {
              showAuthMsg(msgId, 'An error occurred.', 'danger');
              if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
              }
            });
          })
          .catch(() => {
            showAuthMsg(msgId, 'Failed to get Google profile.', 'danger');
            if (btn) {
              btn.disabled = false;
              btn.innerHTML = originalHtml;
            }
          });
        } else {
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
          }
        }
      },
    });
    client.requestAccessToken();
  };

  document.addEventListener('DOMContentLoaded', () => {
    const glBtn = document.getElementById('googleLoginBtn');
    if (glBtn) {
      glBtn.addEventListener('click', () => {
        window.handleGoogleAuthClick('googleLoginBtn', 'login-message');
      });
    }
    const gsBtn = document.getElementById('googleSignupBtn');
    if (gsBtn) {
      gsBtn.addEventListener('click', () => {
        window.handleGoogleAuthClick('googleSignupBtn', 'signup-message');
      });
    }

    const pvgBtn = document.getElementById('profileVerifyGoogleBtn');
    if (pvgBtn) {
        pvgBtn.addEventListener('click', () => {
            window.handleGoogleAuthClick('profileVerifyGoogleBtn', 'profile-message', (data) => {
                showAuthMsg('profile-message', 'Verified via Google! You can now save changes without your current password.', 'success');
                const curPassGroup = document.getElementById('current-password-group');
                if (curPassGroup) curPassGroup.style.display = 'none';
                pvgBtn.style.display = 'none';
                pvgBtn.disabled = false;
                pvgBtn.innerHTML = '<img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:16px;margin-right:8px;"> Verify with Google instead';
            });
        });
    }

    const dvgBtn = document.getElementById('deleteVerifyGoogleBtn');
    if (dvgBtn) {
        dvgBtn.addEventListener('click', () => {
            window.handleGoogleAuthClick('deleteVerifyGoogleBtn', 'profile-message', (data) => {
                showAuthMsg('profile-message', 'Verified via Google! You can now delete your account without a password.', 'success');
                const delPassWrap = document.getElementById('delete-password-wrapper');
                if (delPassWrap) delPassWrap.style.display = 'none';
                dvgBtn.style.display = 'none';
                dvgBtn.disabled = false;
                dvgBtn.innerHTML = '<img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:14px;margin-right:6px;">Verify';
            });
        });
    }
  });
}
