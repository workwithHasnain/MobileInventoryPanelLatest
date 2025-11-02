// Add validation rules for phone form fields
document.addEventListener('DOMContentLoaded', function () {
    // Add validation event listeners to fields
    const fieldsToValidate = ['name', 'brand', 'year', 'price'];
    
    fieldsToValidate.forEach(function(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            // Add input validation event listeners
            field.addEventListener('input', function () {
                validateField(field);
            });
        }
    });

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
