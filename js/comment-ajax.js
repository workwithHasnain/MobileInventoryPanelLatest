/**
 * AJAX Comment Submission Handler
 * Prevents form resubmission on page refresh
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle device comment form
    const deviceCommentForm = document.getElementById('device-comment-form');
    if (deviceCommentForm) {
        deviceCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitComment(this, 'device');
        });
    }

    // Handle post comment form
    const postCommentForm = document.getElementById('post-comment-form');
    if (postCommentForm) {
        postCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitComment(this, 'post');
        });
    }
});

function submitComment(form, type) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Disable submit button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';
    
    // Clear any previous messages
    clearMessages(form);
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Make AJAX request
    fetch('ajax_comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showMessage(form, data.message, 'success');
            
            // Clear form fields
            form.reset();
            
            // Optionally scroll to message
            const messageDiv = form.querySelector('.comment-message');
            if (messageDiv) {
                messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        } else {
            // Show error message
            showMessage(form, data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage(form, 'An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
}

function showMessage(form, message, type) {
    // Remove any existing message
    clearMessages(form);
    
    // Create message div
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} comment-message`;
    messageDiv.style.marginBottom = '15px';
    messageDiv.textContent = message;
    
    // Insert message at the top of the form
    form.insertBefore(messageDiv, form.firstChild);
    
    // Auto-remove success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

function clearMessages(form) {
    const existingMessages = form.querySelectorAll('.comment-message');
    existingMessages.forEach(msg => msg.remove());
}
