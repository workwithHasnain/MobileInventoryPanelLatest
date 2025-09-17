// Add validation rules for phone form fields
document.addEventListener('DOMContentLoaded', function () {
    // Help text for fields
    const fieldHelp = {
        'name': 'Required. Enter complete model name (e.g., Galaxy S24 Ultra)',
        'brand': 'Required. Select existing brand or add new',
        'year': 'Required. Must be between 2000 and current year + 2',
        'price': 'Required. Enter price in USD (must be greater than 0)',
        'display_type': 'Select display technology type (e.g., AMOLED, IPS LCD)',
        'display_size': 'Enter screen size in inches (between 2" and 15")',
        'display_resolution': 'Format: width x height (e.g., 1080 x 2400)',
        'display_density': 'Pixels per inch (between 50 and 1000)',
        'ram': 'Format: size + GB (e.g., 8GB, 12GB)',
        'storage': 'Format: size + GB (e.g., 128GB, 256GB)',
        'battery_capacity': 'Enter capacity in mAh (between 1000 and 10000)',
        'weight': 'Enter weight in grams',
        'thickness': 'Enter thickness in millimeters (mm)',
        'height': 'Enter height in millimeters (mm)',
        'width': 'Enter width in millimeters (mm)',
        'color': 'Enter color(s) separated by commas',
        'chipset': 'Enter processor model name',
        'os': 'Select operating system',
        'main_camera_resolution': 'Format: MP (e.g., 48MP, 108MP)',
        'release_date': 'Select or enter release date'
    };

    // Add help text below each field
    for (let fieldId in fieldHelp) {
        const field = document.getElementById(fieldId);
        if (field) {
            // Create help text element
            const helpText = document.createElement('div');
            helpText.className = 'form-text text-muted small';
            helpText.innerHTML = fieldHelp[fieldId];

            // Add after the input field
            field.parentNode.insertBefore(helpText, field.nextSibling);

            // Add input validation event listeners
            field.addEventListener('input', function () {
                validateField(field);
            });
        }
    }

    // Validate individual field
    function validateField(field) {
        let isValid = true;
        const value = field.value.trim();

        switch (field.id) {
            case 'name':
                isValid = value.length > 0;
                break;
            case 'year':
                const currentYear = new Date().getFullYear();
                const year = parseInt(value);
                isValid = year >= 2000 && year <= currentYear + 2;
                break;
            case 'price':
                isValid = parseFloat(value) > 0;
                break;
            case 'display_size':
                const size = parseFloat(value);
                isValid = size >= 2 && size <= 15;
                break;
            case 'display_density':
                const density = parseInt(value);
                isValid = density >= 50 && density <= 1000;
                break;
            case 'battery_capacity':
                const capacity = parseInt(value);
                isValid = capacity >= 1000 && capacity <= 10000;
                break;
        }

        // Update field styling
        if (value) {
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
        } else {
            field.classList.remove('is-valid', 'is-invalid');
        }
    }

    // Add validation for the form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            let hasErrors = false;

            // Validate all required fields
            form.querySelectorAll('[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    hasErrors = true;
                }
                validateField(field);
            });

            if (hasErrors) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    }
});