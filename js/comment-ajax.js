/**
 * AJAX Comment Submission Handler with Reply Support + CAPTCHA
 * Uses global COMMENT_AJAX_BASE variable set by the page (via PHP $base)
 * No page reload — shows immediate success/error feedback
 */

/**
 * Refresh the CAPTCHA image by appending a timestamp to bust browser cache
 */
function refreshCaptcha() {
    var basePath = (typeof COMMENT_AJAX_BASE !== 'undefined') ? COMMENT_AJAX_BASE : '/';
    var img = document.getElementById('captcha-image');
    if (img) {
        img.src = basePath + 'captcha.php?t=' + Date.now();
    }
    var captchaInput = document.getElementById('captcha-input');
    if (captchaInput) {
        captchaInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Handle device comment form
    var deviceCommentForm = document.getElementById('device-comment-form');
    if (deviceCommentForm) {
        deviceCommentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitComment(this);
        });
    }

    // Handle post comment form
    var postCommentForm = document.getElementById('post-comment-form');
    if (postCommentForm) {
        postCommentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitComment(this);
        });
    }

    // ---- Reply button handling ----
    setupReplyButtons();

    // Cancel reply button
    var cancelBtn = document.getElementById('cancel-reply');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            cancelReply();
        });
    }
});

/**
 * Attach click handlers to all reply buttons
 */
function setupReplyButtons() {
    var replyButtons = document.querySelectorAll('.reply-btn');
    for (var i = 0; i < replyButtons.length; i++) {
        replyButtons[i].addEventListener('click', function () {
            var commentId = this.getAttribute('data-comment-id');
            var commentName = this.getAttribute('data-comment-name');
            startReply(commentId, commentName);
        });
    }
}

/**
 * Activate reply mode — sets parent_id, shows indicator, scrolls to form
 */
function startReply(commentId, commentName) {
    var parentIdField = document.getElementById('parent_id');
    var indicator = document.getElementById('reply-indicator');
    var replyToName = document.getElementById('reply-to-name');
    var formTitle = document.querySelector('.comment-form-title');

    if (parentIdField) parentIdField.value = commentId;
    if (replyToName) replyToName.textContent = commentName;
    if (indicator) indicator.classList.remove('d-none');
    if (formTitle) formTitle.textContent = 'Post a Reply';

    // Scroll the form into view
    var commentForm = document.querySelector('.comment-form');
    if (commentForm) {
        commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Focus the comment textarea
    var textarea = document.querySelector('.comment-form textarea[name="comment"]');
    if (textarea) {
        setTimeout(function () { textarea.focus(); }, 400);
    }
}

/**
 * Cancel reply mode — clears parent_id, hides indicator
 */
function cancelReply() {
    var parentIdField = document.getElementById('parent_id');
    var indicator = document.getElementById('reply-indicator');
    var formTitle = document.querySelector('.comment-form-title');

    if (parentIdField) parentIdField.value = '';
    if (indicator) indicator.classList.add('d-none');
    if (formTitle) formTitle.textContent = 'Share Your Opinion';
}

function submitComment(form) {
    var submitButton = form.querySelector('button[type="submit"]');
    var originalButtonText = submitButton.innerHTML;

    // Disable submit button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';

    // Clear any previous messages
    clearMessages(form);

    // Prepare form data
    var formData = new FormData(form);

    // Get base path from global variable (set by PHP inline script)
    var basePath = (typeof COMMENT_AJAX_BASE !== 'undefined') ? COMMENT_AJAX_BASE : '/';

    // Make AJAX request
    fetch(basePath + 'ajax_comment_handler.php', {
        method: 'POST',
        body: formData
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                // Check if this was a reply
                var wasReply = document.getElementById('parent_id') && document.getElementById('parent_id').value;
                var msg = wasReply
                    ? 'Your reply has been submitted and is awaiting approval.'
                    : data.message;
                showMessage(form, msg, 'success');
                form.reset();
                // Reset reply state
                cancelReply();
                // Refresh CAPTCHA for next submission
                refreshCaptcha();
            } else {
                showMessage(form, data.message || 'Failed to submit comment.', 'error');
                // Always refresh CAPTCHA after a failed attempt (it's single-use)
                refreshCaptcha();
            }
        })
        .catch(function (error) {
            console.error('Comment submission error:', error);
            showMessage(form, 'An error occurred while submitting your comment. Please try again.', 'error');
        })
        .finally(function () {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
}

function showMessage(form, message, type) {
    clearMessages(form);

    var messageDiv = document.createElement('div');
    messageDiv.className = 'comment-message alert ' + (type === 'success' ? 'alert-success' : 'alert-danger');
    messageDiv.style.cssText = 'margin-bottom: 15px; padding: 12px 16px; border-radius: 6px; font-size: 14px; animation: commentFadeIn 0.3s ease;';
    messageDiv.innerHTML = '<i class="fa ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' + message;

    // Insert at the top of the form
    form.insertBefore(messageDiv, form.firstChild);

    // Scroll message into view
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Auto-remove success messages after 6 seconds
    if (type === 'success') {
        setTimeout(function () {
            if (messageDiv.parentNode) {
                messageDiv.style.transition = 'opacity 0.3s';
                messageDiv.style.opacity = '0';
                setTimeout(function () { messageDiv.remove(); }, 300);
            }
        }, 6000);
    }
}

function clearMessages(form) {
    var existing = form.querySelectorAll('.comment-message');
    for (var i = 0; i < existing.length; i++) {
        existing[i].remove();
    }
}
