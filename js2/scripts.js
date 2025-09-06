// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', previewImage);
    }

    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Initialize any tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form validation - check at least one availability option is selected
    const phoneForm = document.querySelector('form');
    if (phoneForm) {
        phoneForm.addEventListener('submit', validateForm);
    }
    
    // Initialize enhanced phone selects with search functionality
    initPhoneSelects();
});

/**
 * Preview uploaded image before submission
 */
function previewImage(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Check if preview container exists
    let previewContainer = document.querySelector('.image-preview-container');
    
    // If no container exists, create one
    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview-container mt-2';
        e.target.parentNode.appendChild(previewContainer);
    }

    // Clear previous preview
    previewContainer.innerHTML = '';

    // Create image preview
    const img = document.createElement('img');
    img.className = 'image-preview img-thumbnail';
    img.file = file;
    previewContainer.appendChild(img);

    // Read the file to display the image
    const reader = new FileReader();
    reader.onload = (function(aImg) { 
        return function(e) { 
            aImg.src = e.target.result; 
        }; 
    })(img);
    reader.readAsDataURL(file);
}

/**
 * Validate form before submission
 */
function validateForm(e) {
    let isValid = true;
    
    // Check if at least one availability option is selected
    const availabilityInstore = document.getElementById('availability_instore');
    const availabilityOnline = document.getElementById('availability_online');
    const availabilityPreorder = document.getElementById('availability_preorder');
    
    if (availabilityInstore && availabilityOnline && availabilityPreorder) {
        if (!availabilityInstore.checked && !availabilityOnline.checked && !availabilityPreorder.checked) {
            isValid = false;
            const errorMsg = document.createElement('div');
            errorMsg.className = 'invalid-feedback d-block';
            errorMsg.textContent = 'At least one availability option must be selected';
            
            // Find the availability container
            const availabilityContainer = document.querySelector('label[for="availability_instore"]').closest('.mb-3');
            
            // Remove any existing error message
            const existingError = availabilityContainer.querySelector('.invalid-feedback');
            if (existingError) {
                existingError.remove();
            }
            
            // Add the error message
            availabilityContainer.appendChild(errorMsg);
        }
    }
    
    // Check if at least one network capability is selected
    const network3g = document.getElementById('3g');
    const network4g = document.getElementById('4g');
    const network5g = document.getElementById('5g');
    
    if (network3g && network4g && network5g) {
        if (!network3g.checked && !network4g.checked && !network5g.checked) {
            isValid = false;
            const errorMsg = document.createElement('div');
            errorMsg.className = 'invalid-feedback d-block';
            errorMsg.textContent = 'At least one network capability must be selected';
            
            // Find the network container
            const networkContainer = document.querySelector('label[for="3g"]').closest('.mb-3');
            
            // Remove any existing error message
            const existingError = networkContainer.querySelector('.invalid-feedback');
            if (existingError) {
                existingError.remove();
            }
            
            // Add the error message
            networkContainer.appendChild(errorMsg);
        }
    }
    
    if (!isValid) {
        e.preventDefault();
    }
}

/**
 * Initialize phone search functionality
 */
function initPhoneSelects() {
    // Check if we're on the compare phones page with new search functionality
    const phoneSearchInputs = document.querySelectorAll('.phone-search');
    
    if (phoneSearchInputs.length > 0 && typeof phoneData !== 'undefined') {
        phoneSearchInputs.forEach(function(searchInput) {
            const inputId = searchInput.id;
            const phoneFieldId = inputId.replace('_search', '');
            const phoneField = document.getElementById(phoneFieldId);
            const resultsContainer = document.getElementById(phoneFieldId + '_results');
            
            if (!resultsContainer || !phoneField) return;
            
            // Style the results container
            resultsContainer.className = 'search-results position-absolute w-100 border rounded bg-white shadow-sm d-none';
            resultsContainer.style.zIndex = '1000';
            resultsContainer.style.maxHeight = '250px';
            resultsContainer.style.overflowY = 'auto';
            
            // Add search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                if (searchTerm.length < 1) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.add('d-none');
                    return;
                }
                
                // Filter phones based on search term (search by phone name, not brand)
                const filteredPhones = phoneData.filter(phone => {
                    const phoneName = (phone.name || '').toLowerCase();
                    const brandName = (phone.brand || '').toLowerCase();
                    const searchString = (phoneName + ' ' + brandName).toLowerCase();
                    return searchString.includes(searchTerm);
                });
                
                // Generate results HTML
                resultsContainer.innerHTML = '';
                
                if (filteredPhones.length === 0) {
                    const noResults = document.createElement('div');
                    noResults.className = 'p-2 text-muted';
                    noResults.textContent = 'No phones found';
                    resultsContainer.appendChild(noResults);
                } else {
                    filteredPhones.forEach(phone => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'p-2 search-result d-flex align-items-center';
                        resultItem.style.cursor = 'pointer';
                        
                        // Add hover effect
                        resultItem.addEventListener('mouseover', function() {
                            this.classList.add('bg-light');
                        });
                        resultItem.addEventListener('mouseout', function() {
                            this.classList.remove('bg-light');
                        });
                        
                        // Display with image if available
                        let resultHTML = '';
                        if (phone.image) {
                            resultHTML += `<img src="${phone.image}" alt="${phone.name}" class="me-2" style="width: 40px; height: 40px; object-fit: contain;">`;
                        }
                        resultHTML += `<span><strong>${phone.name}</strong> - ${phone.brand} (${phone.year})</span>`;
                        resultItem.innerHTML = resultHTML;
                        
                        // Set click handler
                        resultItem.addEventListener('click', function() {
                            phoneField.value = phone.id;
                            searchInput.value = `${phone.name} - ${phone.brand} (${phone.year})`;
                            resultsContainer.classList.add('d-none');
                        });
                        
                        resultsContainer.appendChild(resultItem);
                    });
                }
                
                // Show results
                resultsContainer.classList.remove('d-none');
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('d-none');
                }
            });
            
            // Clear results when input is cleared
            searchInput.addEventListener('blur', function() {
                // If the input is empty, also clear the hidden field
                if (this.value.trim() === '') {
                    phoneField.value = -1;
                }
                
                // Don't hide results immediately to allow for clicks
                setTimeout(() => {
                    if (!resultsContainer.contains(document.activeElement)) {
                        resultsContainer.classList.add('d-none');
                    }
                }, 200);
            });
        });
    }
}
