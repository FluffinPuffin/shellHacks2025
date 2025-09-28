/**
 * Budget App JavaScript utilities
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form enhancements
    initializeFormEnhancements();
    
    // Initialize auto-save functionality
    initializeAutoSave();
    
    // Initialize real-time calculations
    initializeRealTimeCalculations();
});

/**
 * Initialize form enhancements
 */
function initializeFormEnhancements() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            }
        });
    });
    
    // Add input validation feedback
    const inputs = document.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', validateInput);
        input.addEventListener('input', clearValidationError);
    });
}

/**
 * Validate individual input
 */
function validateInput(event) {
    const input = event.target;
    const value = input.value.trim();
    
    // Remove existing error styling
    input.classList.remove('error');
    
    // Check if required field is empty
    if (input.hasAttribute('required') && !value) {
        showInputError(input, 'This field is required');
        return false;
    }
    
    // Validate number inputs
    if (input.type === 'number') {
        const min = parseFloat(input.getAttribute('min'));
        const max = parseFloat(input.getAttribute('max'));
        const numValue = parseFloat(value);
        
        if (value && (isNaN(numValue) || (min !== null && numValue < min) || (max !== null && numValue > max))) {
            showInputError(input, `Please enter a value between ${min || 0} and ${max || 'unlimited'}`);
            return false;
        }
    }
    
    return true;
}

/**
 * Clear validation error
 */
function clearValidationError(event) {
    const input = event.target;
    input.classList.remove('error');
    const errorMsg = input.parentNode.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.remove();
    }
}

/**
 * Show input error
 */
function showInputError(input, message) {
    input.classList.add('error');
    
    // Remove existing error message
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

/**
 * Initialize auto-save functionality
 */
function initializeAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        let saveTimeout;
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    autoSaveForm(form);
                }, 2000); // Save after 2 seconds of inactivity
            });
        });
    });
}

/**
 * Auto-save form data
 */
function autoSaveForm(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Save to localStorage as backup
    localStorage.setItem('budget_app_autosave', JSON.stringify({
        timestamp: Date.now(),
        data: data
    }));
    
    // Show auto-save indicator
    showAutoSaveIndicator();
}

/**
 * Show auto-save indicator
 */
function showAutoSaveIndicator() {
    let indicator = document.querySelector('.autosave-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'autosave-indicator';
        indicator.innerHTML = '💾 Auto-saved';
        document.body.appendChild(indicator);
    }
    
    indicator.style.display = 'block';
    indicator.style.opacity = '1';
    
    setTimeout(() => {
        indicator.style.opacity = '0';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 300);
    }, 2000);
}

/**
 * Initialize real-time calculations
 */
function initializeRealTimeCalculations() {
    // Budget form calculations
    const budgetForm = document.querySelector('#budgetForm');
    if (budgetForm) {
        const calculationInputs = budgetForm.querySelectorAll('input[type="number"]');
        calculationInputs.forEach(input => {
            input.addEventListener('input', updateBudgetTotals);
        });
        updateBudgetTotals(); // Initial calculation
    }
    
    // Location comparison calculations
    const locationInputs = document.querySelectorAll('input[name*="rent"], input[name*="utilities"], input[name*="groceries"]');
    locationInputs.forEach(input => {
        input.addEventListener('input', updateLocationTotals);
    });
    updateLocationTotals(); // Initial calculation
}

/**
 * Update budget totals
 */
function updateBudgetTotals() {
    const form = document.querySelector('#budgetForm');
    if (!form) return;
    
    const rent = parseFloat(form.querySelector('input[name="rent"]')?.value || 0);
    const water = parseFloat(form.querySelector('input[name="water"]')?.value || 0);
    const phone = parseFloat(form.querySelector('input[name="phone"]')?.value || 0);
    const electricity = parseFloat(form.querySelector('input[name="electricity"]')?.value || 0);
    const otherUtilities = parseFloat(form.querySelector('input[name="other_utilities"]')?.value || 0);
    const groceries = parseFloat(form.querySelector('input[name="groceries"]')?.value || 0);
    const carCost = parseFloat(form.querySelector('input[name="car_cost"]')?.value || 0);
    const healthInsurance = parseFloat(form.querySelector('input[name="health_insurance"]')?.value || 0);
    const monthlyDebt = parseFloat(form.querySelector('input[name="monthly_debt"]')?.value || 0);
    
    const total = rent + water + phone + electricity + otherUtilities + groceries + carCost + healthInsurance + monthlyDebt;
    
    const totalElement = form.querySelector('.total-amount');
    if (totalElement) {
        totalElement.textContent = '$' + total.toFixed(2);
    }
}

/**
 * Update location totals
 */
function updateLocationTotals() {
    // Current location total
    const currentRent = parseFloat(document.querySelector('input[name="current_rent"]')?.value || 0);
    const currentUtilities = parseFloat(document.querySelector('input[name="current_utilities"]')?.value || 0);
    const currentGroceries = parseFloat(document.querySelector('input[name="current_groceries"]')?.value || 0);
    const currentTotal = currentRent + currentUtilities + currentGroceries;
    
    const currentTotalElement = document.querySelector('.location-form:first-child .total-amount');
    if (currentTotalElement) {
        currentTotalElement.textContent = '$' + currentTotal.toFixed(2);
    }
    
    // Destination total
    const destinationRent = parseFloat(document.querySelector('input[name="destination_rent"]')?.value || 0);
    const destinationUtilities = parseFloat(document.querySelector('input[name="destination_utilities"]')?.value || 0);
    const destinationGroceries = parseFloat(document.querySelector('input[name="destination_groceries"]')?.value || 0);
    const destinationTotal = destinationRent + destinationUtilities + destinationGroceries;
    
    const destinationTotalElement = document.querySelector('.location-form:last-child .total-amount');
    if (destinationTotalElement) {
        destinationTotalElement.textContent = '$' + destinationTotal.toFixed(2);
    }
}

/**
 * Utility function to format numbers as currency
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Utility function to show loading state
 */
function showLoading(element) {
    if (element) {
        element.disabled = true;
        element.innerHTML = '<span class="spinner"></span> Loading...';
    }
}

/**
 * Utility function to hide loading state
 */
function hideLoading(element, originalText) {
    if (element) {
        element.disabled = false;
        element.innerHTML = originalText;
    }
}
