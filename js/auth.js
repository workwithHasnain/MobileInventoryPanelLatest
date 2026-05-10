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
      }
    });
    
    document.getElementById('profile-current-password').value = '';
    document.getElementById('profile-new-password').value = '';
    document.getElementById('delete-account-password').value = '';
    document.getElementById('profile-message').style.display = 'none';
    
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
}
